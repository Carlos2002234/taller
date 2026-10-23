<?php
/**
 * Gestión de pedidos desde el admin: lista, detalle y cambios de estado.
 *
 * REGLAS (decisiones del dueño):
 * - JAMÁS se aprueba un pago sin comprobante: solo desde 'comprobante_enviado' y con el
 *   comprobante pendiente de revisión, y solo el dueño, confirmando que verificó el ingreso.
 * - Rechazar comprobante: el cliente ve el motivo, el pedido vuelve a 'pendiente_pago' con 24 h más.
 * - Rechazar o cancelar un pedido devuelve el stock. Cancelar (incluso pagado) solo el dueño.
 * - El empleado puede ver pedidos y comprobantes, y marcar "en preparación" y "entregado".
 */

declare(strict_types=1);

require_once RAIZ . '/app/modelos/pedidos.php';
require_once RAIZ . '/app/modelos/comprobantes.php';

const HORAS_TRAS_RECHAZO = 24;
const PEDIDOS_POR_PAGINA = 20;

const ESTADOS_PEDIDO = [
    'pendiente_pago'      => 'Pendiente de pago',
    'comprobante_enviado' => 'Comprobante por revisar',
    'pagado'              => 'Pagado',
    'en_preparacion'      => 'En preparación',
    'entregado'           => 'Entregado',
    'cancelado'           => 'Cancelado',
    'rechazado'           => 'Rechazado',
];

// Pestañas de la lista: clave => [texto, estados que incluye]
const PESTANAS_PEDIDOS = [
    'por-revisar'  => ['Por revisar', ['comprobante_enviado']],
    'pendientes'   => ['Pendientes de pago', ['pendiente_pago']],
    'pagados'      => ['Pagados', ['pagado']],
    'preparacion'  => ['En preparación', ['en_preparacion']],
    'entregados'   => ['Entregados', ['entregado']],
    'cerrados'     => ['Cancelados / rechazados', ['cancelado', 'rechazado']],
    'todos'        => ['Todos', []],
];

/**
 * Lista con pestaña, búsqueda (número, nombre o teléfono) y paginación.
 */
function pedidos_admin_listar(string $pestana, string $q, int $pagina): array
{
    $where = ['1 = 1'];
    $params = [];

    $estados = PESTANAS_PEDIDOS[$pestana][1] ?? [];
    if ($estados) {
        $where[] = 'estado IN (' . implode(',', array_fill(0, count($estados), '?')) . ')';
        array_push($params, ...$estados);
    }
    if ($q !== '') {
        $like = '%' . addcslashes($q, '%_\\') . '%';
        $digitos = preg_replace('/\D/', '', $q);
        $where[] = '(numero_pedido LIKE ? OR nombre_cliente LIKE ?' . ($digitos !== '' ? ' OR REPLACE(telefono, \'-\', \'\') LIKE ?' : '') . ')';
        array_push($params, $like, $like);
        if ($digitos !== '') {
            $params[] = '%' . $digitos . '%';
        }
    }

    $sqlWhere = 'FROM orders WHERE ' . implode(' AND ', $where);
    $stmt = db()->prepare('SELECT COUNT(*) ' . $sqlWhere);
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();

    // Los que esperan revisión, del más antiguo al más nuevo; el resto, más recientes primero
    $orden = $pestana === 'por-revisar' ? 'actualizado_en ASC' : 'creado_en DESC';
    $stmt = db()->prepare(
        'SELECT id, numero_pedido, nombre_cliente, telefono, tipo_entrega, total, estado, creado_en, expira_en '
        . $sqlWhere . ' ORDER BY ' . $orden . ', id DESC LIMIT ? OFFSET ?'
    );
    foreach ($params as $i => $valor) {
        $stmt->bindValue($i + 1, $valor);
    }
    $stmt->bindValue(count($params) + 1, PEDIDOS_POR_PAGINA, PDO::PARAM_INT);
    $stmt->bindValue(count($params) + 2, ($pagina - 1) * PEDIDOS_POR_PAGINA, PDO::PARAM_INT);
    $stmt->execute();

    return ['pedidos' => $stmt->fetchAll(), 'total' => $total];
}

/**
 * Cantidad de pedidos por estado (para las pestañas y el aviso del menú).
 */
function pedidos_conteos(): array
{
    return db()->query('SELECT estado, COUNT(*) FROM orders GROUP BY estado')->fetchAll(PDO::FETCH_KEY_PAIR);
}

function pedido_comprobantes(int $pedidoId): array
{
    $stmt = db()->prepare(
        'SELECT p.*, u.nombre AS revisor FROM payment_proofs p LEFT JOIN users u ON u.id = p.revisado_por
          WHERE p.order_id = ? ORDER BY p.id DESC'
    );
    $stmt->execute([$pedidoId]);
    return $stmt->fetchAll();
}

function pedido_historial_admin(int $pedidoId): array
{
    $stmt = db()->prepare(
        'SELECT l.*, u.nombre AS usuario FROM order_status_log l LEFT JOIN users u ON u.id = l.usuario_id
          WHERE l.order_id = ? ORDER BY l.fecha DESC, l.id DESC'
    );
    $stmt->execute([$pedidoId]);
    return $stmt->fetchAll();
}

function comprobante_por_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT p.*, o.numero_pedido FROM payment_proofs p JOIN orders o ON o.id = p.order_id WHERE p.id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Ejecuta un cambio de estado de forma segura: bloquea el pedido, revisa que el estado
 * actual permita el cambio, aplica los pasos extra y registra el historial.
 * Devuelve null si salió bien, o un mensaje de error.
 */
function transicion_pedido(int $pedidoId, array $desde, string $hacia, array $usuario, ?string $nota, ?callable $extra = null): ?string
{
    $db = db();
    $db->beginTransaction();
    try {
        $stmt = $db->prepare('SELECT * FROM orders WHERE id = ? FOR UPDATE');
        $stmt->execute([$pedidoId]);
        $pedido = $stmt->fetch();

        if (!$pedido || !in_array($pedido['estado'], $desde, true)) {
            $db->rollBack();
            return 'No se puede hacer esto con un pedido en estado "' . (ESTADOS_PEDIDO[$pedido['estado'] ?? ''] ?? '—')
                . '". Si alguien más lo cambió, revisa la información actualizada.';
        }

        if ($extra) {
            $error = $extra($db, $pedido);
            if (is_string($error)) {
                $db->rollBack();
                return $error;
            }
        }

        $db->prepare('UPDATE orders SET estado = ? WHERE id = ?')->execute([$hacia, $pedidoId]);
        registrar_estado($db, $pedidoId, $pedido['estado'], $hacia, 'admin', $nota, (int) $usuario['id']);
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        error_log("Error al cambiar el pedido {$pedidoId} a {$hacia}: " . $e->getMessage());
        return 'No se pudo guardar el cambio. Intenta de nuevo.';
    }
    return null;
}

/**
 * Aprobar pago: SOLO el dueño, SOLO con comprobante pendiente y confirmando que verificó el ingreso.
 */
function aprobar_pago(int $pedidoId, array $usuario, bool $verificoIngreso): ?string
{
    if ($usuario['rol'] !== 'duenio') {
        return 'Solo el dueño puede aprobar pagos.';
    }
    if (!$verificoIngreso) {
        return 'Marca la casilla confirmando que verificaste el ingreso del dinero en tu cuenta.';
    }
    if ((pedido_por_id($pedidoId)['estado'] ?? null) === 'pendiente_pago') {
        return 'El cliente todavía no subió un comprobante. Nunca se aprueba un pago sin comprobante.';
    }

    return transicion_pedido($pedidoId, ['comprobante_enviado'], 'pagado', $usuario, 'Pago verificado y aprobado',
        function (PDO $db, array $pedido) use ($usuario): ?string {
            $stmt = $db->prepare("SELECT id FROM payment_proofs WHERE order_id = ? AND estado_revision = 'pendiente' ORDER BY id DESC LIMIT 1 FOR UPDATE");
            $stmt->execute([$pedido['id']]);
            $comprobante = $stmt->fetchColumn();
            if (!$comprobante) {
                return 'No hay un comprobante pendiente de revisión. Nunca se aprueba un pago sin comprobante.';
            }
            $db->prepare("UPDATE payment_proofs SET estado_revision = 'aprobado', revisado_por = ?, revisado_en = NOW() WHERE id = ?")
                ->execute([$usuario['id'], $comprobante]);
            return null;
        });
}

/**
 * Rechazar comprobante: el cliente ve el motivo y puede subir otro (24 h más para pagar).
 */
function rechazar_comprobante(int $pedidoId, array $usuario, string $motivo): ?string
{
    if ($usuario['rol'] !== 'duenio') {
        return 'Solo el dueño puede rechazar comprobantes.';
    }

    return transicion_pedido($pedidoId, ['comprobante_enviado'], 'pendiente_pago', $usuario, 'Comprobante rechazado: ' . $motivo,
        function (PDO $db, array $pedido) use ($usuario, $motivo): ?string {
            $db->prepare(
                "UPDATE payment_proofs SET estado_revision = 'rechazado', revisado_por = ?, revisado_en = NOW(), nota_revision = ?
                  WHERE order_id = ? AND estado_revision = 'pendiente'"
            )->execute([$usuario['id'], $motivo, $pedido['id']]);
            $db->prepare('UPDATE orders SET expira_en = NOW() + INTERVAL ? HOUR WHERE id = ?')
                ->execute([HORAS_TRAS_RECHAZO, $pedido['id']]);
            return null;
        });
}

/**
 * Rechazar pedido (final): se cierra, se devuelve el stock y el cliente ve el motivo.
 */
function rechazar_pedido(int $pedidoId, array $usuario, string $motivo): ?string
{
    if ($usuario['rol'] !== 'duenio') {
        return 'Solo el dueño puede rechazar pedidos.';
    }

    return transicion_pedido($pedidoId, ['pendiente_pago', 'comprobante_enviado'], 'rechazado', $usuario, $motivo,
        function (PDO $db, array $pedido) use ($usuario, $motivo): ?string {
            $db->prepare(
                "UPDATE payment_proofs SET estado_revision = 'rechazado', revisado_por = ?, revisado_en = NOW(), nota_revision = ?
                  WHERE order_id = ? AND estado_revision = 'pendiente'"
            )->execute([$usuario['id'], $motivo, $pedido['id']]);
            devolver_stock($db, (int) $pedido['id']);
            return null;
        });
}

/**
 * Cancelar pedido (solo el dueño), en cualquier estado antes de entregarse. Devuelve el stock.
 */
function cancelar_pedido(int $pedidoId, array $usuario, string $motivo): ?string
{
    if ($usuario['rol'] !== 'duenio') {
        return 'Solo el dueño puede cancelar pedidos.';
    }

    return transicion_pedido($pedidoId, ['pendiente_pago', 'comprobante_enviado', 'pagado', 'en_preparacion'], 'cancelado', $usuario, $motivo,
        function (PDO $db, array $pedido): ?string {
            devolver_stock($db, (int) $pedido['id']);
            return null;
        });
}

function marcar_en_preparacion(int $pedidoId, array $usuario): ?string
{
    return transicion_pedido($pedidoId, ['pagado'], 'en_preparacion', $usuario, null);
}

function marcar_entregado(int $pedidoId, array $usuario): ?string
{
    return transicion_pedido($pedidoId, ['en_preparacion'], 'entregado', $usuario, null);
}

function guardar_notas_internas(int $pedidoId, string $notas): void
{
    db()->prepare('UPDATE orders SET notas_internas = ? WHERE id = ?')->execute([$notas !== '' ? $notas : null, $pedidoId]);
}

/**
 * Ventas de un mes: pedidos cuyo pago se aprobó en ese mes y que no se cancelaron después.
 */
function ventas_del_mes(string $inicioMes): array
{
    $stmt = db()->prepare(
        "SELECT COALESCE(SUM(o.total), 0) AS total, COUNT(*) AS pedidos
           FROM orders o
           JOIN order_status_log l ON l.order_id = o.id AND l.estado_nuevo = 'pagado'
          WHERE o.estado IN ('pagado', 'en_preparacion', 'entregado')
            AND l.fecha >= ? AND l.fecha < (? + INTERVAL 1 MONTH)"
    );
    $stmt->execute([$inicioMes, $inicioMes]);
    return $stmt->fetch();
}

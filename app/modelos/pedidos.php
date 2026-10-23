<?php
/**
 * Pedidos: creación (con reserva de stock), número único y vencimiento.
 * REGLA: ningún pedido pasa a 'pagado' desde aquí. Eso solo lo hace el dueño en el admin.
 */

declare(strict_types=1);

require_once RAIZ . '/app/servicios/notificaciones.php';

const PEDIDOS_PENDIENTES_POR_TELEFONO = 3;
const PEDIDOS_POR_HORA_POR_IP = 5;
const MAX_UNIDADES_POR_PRODUCTO = 99;
const MAX_PRODUCTOS_POR_PEDIDO = 30;

/**
 * Convierte el carrito recibido del navegador en [product_id => cantidad] válido.
 * Solo se aceptan IDs y cantidades; cualquier otro dato se ignora.
 */
function normalizar_carrito(mixed $carrito): array
{
    $limpio = [];
    if (!is_array($carrito)) {
        return $limpio;
    }

    foreach ($carrito as $item) {
        $id = filter_var($item['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $cantidad = filter_var($item['cantidad'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id && $cantidad) {
            $limpio[$id] = min(($limpio[$id] ?? 0) + $cantidad, MAX_UNIDADES_POR_PRODUCTO);
        }
    }

    return array_slice($limpio, 0, MAX_PRODUCTOS_POR_PEDIDO, true);
}

/**
 * Revisa los límites contra pedidos falsos. Devuelve un mensaje de error o null.
 */
function limite_pedidos_superado(string $telefono, string $ip): ?string
{
    $stmt = db()->prepare("SELECT COUNT(*) FROM orders WHERE telefono = ? AND estado = 'pendiente_pago'");
    $stmt->execute([$telefono]);
    if ((int) $stmt->fetchColumn() >= PEDIDOS_PENDIENTES_POR_TELEFONO) {
        return 'Ya tienes ' . PEDIDOS_PENDIENTES_POR_TELEFONO . ' pedidos pendientes de pago con este teléfono. '
            . 'Paga o espera a que venzan antes de hacer otro, o escríbenos por WhatsApp.';
    }

    $stmt = db()->prepare('SELECT COUNT(*) FROM orders WHERE ip = ? AND creado_en > (NOW() - INTERVAL 1 HOUR)');
    $stmt->execute([$ip]);
    if ((int) $stmt->fetchColumn() >= PEDIDOS_POR_HORA_POR_IP) {
        return 'Se hicieron demasiados pedidos desde esta conexión. Intenta de nuevo en una hora o escríbenos por WhatsApp.';
    }

    return null;
}

/**
 * Siguiente número de pedido del año (ej. RGA-2026-0001).
 * Debe llamarse DENTRO de una transacción: el bloqueo de la fila evita números repetidos.
 */
function generar_numero_pedido(PDO $db): string
{
    $anio = (int) date('Y');

    $db->prepare('INSERT IGNORE INTO order_sequences (anio, ultimo) VALUES (?, 0)')->execute([$anio]);
    $stmt = $db->prepare('SELECT ultimo FROM order_sequences WHERE anio = ? FOR UPDATE');
    $stmt->execute([$anio]);
    $siguiente = (int) $stmt->fetchColumn() + 1;

    // Si el contador quedó atrasado (ej. un respaldo restaurado a medias), se salta los números ya usados
    $prefijo = preg_replace('/[^A-Z0-9]/', '', strtoupper(ajuste('prefijo_pedido', 'RGA'))) ?: 'RGA';
    $existe = $db->prepare('SELECT 1 FROM orders WHERE numero_pedido = ?');
    for ($intentos = 0; $intentos < 10000; $intentos++, $siguiente++) {
        $numero = sprintf('%s-%d-%04d', $prefijo, $anio, $siguiente);
        $existe->execute([$numero]);
        if (!$existe->fetchColumn()) {
            break;
        }
    }

    $db->prepare('UPDATE order_sequences SET ultimo = ? WHERE anio = ?')->execute([$siguiente, $anio]);
    return $numero;
}

/**
 * Crea el pedido en una sola transacción: valida stock y precios REALES,
 * genera el número, guarda productos, reserva stock y registra el historial.
 *
 * $datos: nombre_cliente, telefono, email, tipo_entrega, zona_envio_id, direccion, notas, utm_*, ip
 * $carrito: [product_id => cantidad] (de normalizar_carrito)
 *
 * Devuelve ['pedido' => array] o ['errores' => string[]]
 */
function crear_pedido(array $datos, array $carrito): array
{
    if (!$carrito) {
        return ['errores' => ['Tu carrito está vacío.']];
    }

    $db = db();
    $db->beginTransaction();

    try {
        // 1. Productos con bloqueo de fila: nadie más puede tocar su stock hasta terminar
        $ids = array_keys($carrito);
        $stmt = $db->prepare(
            'SELECT p.id, p.nombre, p.precio, p.stock
               FROM products p JOIN categories c ON c.id = p.category_id
              WHERE p.activo = 1 AND c.activa = 1 AND p.id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')
              FOR UPDATE'
        );
        $stmt->execute($ids);
        $productos = array_column($stmt->fetchAll(), null, 'id');

        $errores = [];
        foreach ($carrito as $id => $cantidad) {
            $p = $productos[$id] ?? null;
            if (!$p) {
                $errores[] = 'Uno de los productos de tu carrito ya no está disponible.';
            } elseif ((int) $p['stock'] <= 0) {
                $errores[] = "«{$p['nombre']}» se agotó.";
            } elseif ((int) $p['stock'] < $cantidad) {
                $errores[] = "Solo quedan {$p['stock']} unidades de «{$p['nombre']}».";
            }
        }

        // 2. Zona de envío (si aplica)
        $zona = null;
        if ($datos['tipo_entrega'] === 'envio') {
            $stmt = $db->prepare('SELECT id, nombre, tarifa FROM shipping_zones WHERE id = ? AND activa = 1');
            $stmt->execute([$datos['zona_envio_id']]);
            $zona = $stmt->fetch() ?: null;
            if (!$zona) {
                $errores[] = 'Elige una zona de envío válida.';
            }
        }

        if ($errores) {
            $db->rollBack();
            return ['errores' => array_values(array_unique($errores))];
        }

        // 3. Totales con precios de la base de datos (colones enteros)
        $subtotal = 0;
        foreach ($carrito as $id => $cantidad) {
            $subtotal += round((float) $productos[$id]['precio']) * $cantidad;
        }
        $costoEnvio = $zona ? round((float) $zona['tarifa']) : 0;
        $total = $subtotal + $costoEnvio;

        // 4. Pedido
        $numero = generar_numero_pedido($db);
        $horas = max(1, (int) ajuste('horas_expiracion_pedido', '48'));

        $stmt = $db->prepare(
            'INSERT INTO orders (numero_pedido, nombre_cliente, telefono, email, tipo_entrega, zona_envio_id,
                                 zona_envio_nombre, direccion, notas, subtotal, costo_envio, total, estado,
                                 expira_en, utm_source, utm_medium, utm_campaign, ip)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'pendiente_pago\', NOW() + INTERVAL ? HOUR, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $numero,
            $datos['nombre_cliente'],
            $datos['telefono'],
            $datos['email'] ?: null,
            $datos['tipo_entrega'],
            $zona['id'] ?? null,
            $zona['nombre'] ?? null,
            $zona ? $datos['direccion'] : null,
            $datos['notas'] ?: null,
            $subtotal,
            $costoEnvio,
            $total,
            $horas,
            $datos['utm_source'] ?: null,
            $datos['utm_medium'] ?: null,
            $datos['utm_campaign'] ?: null,
            $datos['ip'],
        ]);
        $pedidoId = (int) $db->lastInsertId();

        // 5. Productos del pedido (copia de nombre y precio) y reserva de stock
        $insertarItem = $db->prepare(
            'INSERT INTO order_items (order_id, product_id, nombre_snapshot, precio_snapshot, cantidad) VALUES (?, ?, ?, ?, ?)'
        );
        $descontar = $db->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');
        foreach ($carrito as $id => $cantidad) {
            $insertarItem->execute([$pedidoId, $id, $productos[$id]['nombre'], $productos[$id]['precio'], $cantidad]);
            $descontar->execute([$cantidad, $id]);
        }

        // 6. Historial
        registrar_estado($db, $pedidoId, null, 'pendiente_pago', 'cliente', 'Pedido creado desde la web');

        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        error_log('Error al crear pedido: ' . $e->getMessage());
        return ['errores' => ['No pudimos crear tu pedido por un problema técnico. Intenta de nuevo en un momento.']];
    }

    $pedido = pedido_por_id($pedidoId);

    // Fase 2: un fallo al notificar nunca debe afectar el pedido ya creado
    try {
        notificarPedido($pedido);
    } catch (Throwable $e) {
        error_log('Error en notificarPedido: ' . $e->getMessage());
    }

    return ['pedido' => $pedido];
}

function registrar_estado(PDO $db, int $pedidoId, ?string $anterior, string $nuevo, string $origen, ?string $nota = null, ?int $usuarioId = null): void
{
    $db->prepare(
        'INSERT INTO order_status_log (order_id, estado_anterior, estado_nuevo, usuario_id, origen, nota) VALUES (?, ?, ?, ?, ?, ?)'
    )->execute([$pedidoId, $anterior, $nuevo, $usuarioId, $origen, $nota]);
}

function pedido_por_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Busca un pedido por número + teléfono (los dos deben coincidir).
 */
function pedido_por_numero_y_telefono(string $numero, string $telefono): ?array
{
    $stmt = db()->prepare('SELECT * FROM orders WHERE numero_pedido = ? AND telefono = ?');
    $stmt->execute([$numero, $telefono]);
    return $stmt->fetch() ?: null;
}

function pedido_por_numero(string $numero): ?array
{
    $stmt = db()->prepare('SELECT * FROM orders WHERE numero_pedido = ?');
    $stmt->execute([$numero]);
    return $stmt->fetch() ?: null;
}

/**
 * Historial de estados (del más antiguo al más reciente).
 */
function pedido_historial(int $pedidoId): array
{
    $stmt = db()->prepare(
        'SELECT estado_anterior, estado_nuevo, origen, nota, fecha FROM order_status_log WHERE order_id = ? ORDER BY fecha, id'
    );
    $stmt->execute([$pedidoId]);
    return $stmt->fetchAll();
}

/**
 * Pedidos que el visitante puede ver en esta sesión (los que creó o consultó con número + teléfono).
 */
function autorizar_pedido(int $pedidoId): void
{
    iniciar_sesion();
    $autorizados = $_SESSION['pedidos_autorizados'] ?? [];
    $autorizados[] = $pedidoId;
    $_SESSION['pedidos_autorizados'] = array_slice(array_values(array_unique($autorizados)), -10);
}

function pedido_autorizado(int $pedidoId): bool
{
    iniciar_sesion();
    return in_array($pedidoId, $_SESSION['pedidos_autorizados'] ?? [], true);
}

function pedido_items(int $pedidoId): array
{
    $stmt = db()->prepare(
        'SELECT product_id, nombre_snapshot, precio_snapshot, cantidad FROM order_items WHERE order_id = ? ORDER BY id'
    );
    $stmt->execute([$pedidoId]);
    return $stmt->fetchAll();
}

/**
 * Devuelve al inventario el stock reservado por un pedido.
 * Debe llamarse DENTRO de una transacción.
 */
function devolver_stock(PDO $db, int $pedidoId): void
{
    $db->prepare(
        'UPDATE products p JOIN order_items i ON i.product_id = p.id
            SET p.stock = p.stock + i.cantidad
          WHERE i.order_id = ?'
    )->execute([$pedidoId]);
}

/**
 * Cancela los pedidos que siguen en 'pendiente_pago' después del plazo y libera su stock.
 * Los pedidos con comprobante enviado NO vencen: esperan la revisión del dueño.
 * Devuelve cuántos pedidos se cancelaron.
 */
function liberar_pedidos_vencidos(): int
{
    $db = db();
    $ids = $db->query(
        "SELECT id FROM orders WHERE estado = 'pendiente_pago' AND expira_en < NOW() ORDER BY id LIMIT 200"
    )->fetchAll(PDO::FETCH_COLUMN);

    $cancelados = 0;
    foreach ($ids as $id) {
        $db->beginTransaction();
        try {
            // Volver a revisar con bloqueo: el cliente pudo subir el comprobante justo ahora
            $stmt = $db->prepare('SELECT estado FROM orders WHERE id = ? FOR UPDATE');
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() !== 'pendiente_pago') {
                $db->rollBack();
                continue;
            }

            devolver_stock($db, (int) $id);
            $db->prepare("UPDATE orders SET estado = 'cancelado' WHERE id = ?")->execute([$id]);
            registrar_estado($db, (int) $id, 'pendiente_pago', 'cancelado', 'sistema',
                'No se recibió el pago dentro del plazo');
            $db->commit();
            $cancelados++;
        } catch (Throwable $e) {
            $db->rollBack();
            error_log("Error al liberar el pedido {$id}: " . $e->getMessage());
        }
    }

    return $cancelados;
}

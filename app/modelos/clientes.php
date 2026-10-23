<?php
/**
 * Clientes del CRM: se arman solos con las consultas y los pedidos, unidos por teléfono (decisión del dueño).
 * - "Cliente": tiene al menos un pedido pagado (pagado, en preparación o entregado).
 * - "Posible cliente": dejó una consulta o hizo pedidos que no pagó.
 * Solo las notas internas se guardan aparte (tabla clientes_notas).
 */

declare(strict_types=1);

// Estados de pedido que cuentan como compra (un cancelado o rechazado no es compra)
const ESTADOS_COMPRA = ['pagado', 'en_preparacion', 'entregado'];

const TIPOS_CLIENTE = [
    'todos'     => 'Todos',
    'clientes'  => 'Clientes',
    'posibles'  => 'Posibles clientes',
];

/**
 * Consulta base: una fila por cada consulta y cada pedido, con el teléfono como llave.
 */
function sql_actividad_clientes(): string
{
    $compra = "'" . implode("','", ESTADOS_COMPRA) . "'";
    return "SELECT telefono, nombre, email, creado_en AS fecha, 1 AS es_consulta, 0 AS es_pedido, 0 AS es_compra, 0 AS monto
              FROM consultas
            UNION ALL
            SELECT telefono, nombre_cliente, email, creado_en, 0, 1,
                   estado IN ({$compra}), CASE WHEN estado IN ({$compra}) THEN total ELSE 0 END
              FROM orders";
}

/**
 * Lista de clientes (más reciente primero). $tipo: 'todos' | 'clientes' | 'posibles'; $q busca por nombre o teléfono.
 */
function listar_clientes(string $tipo = 'todos', string $q = '', int $limite = 300): array
{
    // Nombre y correo: los del registro más reciente que los tenga
    $sql = "SELECT a.telefono,
                   SUBSTRING_INDEX(GROUP_CONCAT(a.nombre ORDER BY a.fecha DESC SEPARATOR '\\n'), '\\n', 1) AS nombre,
                   SUBSTRING_INDEX(GROUP_CONCAT(NULLIF(a.email, '') ORDER BY a.fecha DESC SEPARATOR '\\n'), '\\n', 1) AS email,
                   SUM(a.es_consulta) AS consultas,
                   SUM(a.es_pedido) AS pedidos,
                   SUM(a.es_compra) AS compras,
                   SUM(a.monto) AS total_comprado,
                   MIN(a.fecha) AS primera_vez,
                   MAX(a.fecha) AS ultima_vez
              FROM (" . sql_actividad_clientes() . ") a
             GROUP BY a.telefono";

    $condiciones = [];
    $parametros = [];
    if ($tipo === 'clientes') {
        $condiciones[] = 'compras > 0';
    } elseif ($tipo === 'posibles') {
        $condiciones[] = 'compras = 0';
    }
    if ($q !== '') {
        $digitos = preg_replace('/\D/', '', $q);
        $condiciones[] = '(nombre LIKE ?' . ($digitos !== '' ? " OR REPLACE(telefono, '-', '') LIKE ?" : '') . ')';
        $parametros[] = '%' . addcslashes($q, '%_\\') . '%';
        if ($digitos !== '') {
            $parametros[] = '%' . $digitos . '%';
        }
    }
    if ($condiciones) {
        $sql .= ' HAVING ' . implode(' AND ', $condiciones);
    }

    $stmt = db()->prepare($sql . ' ORDER BY ultima_vez DESC LIMIT ' . $limite);
    $stmt->execute($parametros);
    return $stmt->fetchAll();
}

/**
 * Cantidad de personas por tipo: ['todos' => 10, 'clientes' => 3, 'posibles' => 7]
 */
function contar_clientes(): array
{
    $fila = db()->query(
        'SELECT COUNT(*) AS todos, COALESCE(SUM(compras > 0), 0) AS clientes, COALESCE(SUM(compras = 0), 0) AS posibles
           FROM (SELECT telefono, SUM(es_compra) AS compras FROM (' . sql_actividad_clientes() . ') a GROUP BY telefono) c'
    )->fetch();
    return array_map('intval', $fila);
}

/**
 * Ficha de un cliente: resumen, sus consultas, sus pedidos y las notas. null si no existe.
 */
function cliente_por_telefono(string $telefono): ?array
{
    $stmt = db()->prepare('SELECT id, mensaje, estado, utm_source, creado_en FROM consultas WHERE telefono = ? ORDER BY creado_en DESC');
    $stmt->execute([$telefono]);
    $consultas = $stmt->fetchAll();

    $stmt = db()->prepare('SELECT numero_pedido, total, estado, creado_en FROM orders WHERE telefono = ? ORDER BY creado_en DESC');
    $stmt->execute([$telefono]);
    $pedidos = $stmt->fetchAll();

    if (!$consultas && !$pedidos) {
        return null;
    }

    $resumen = null;
    foreach (listar_clientes('todos', $telefono) as $fila) {
        if ($fila['telefono'] === $telefono) {
            $resumen = $fila;
            break;
        }
    }

    $stmt = db()->prepare(
        'SELECT n.notas, n.actualizado_en, u.nombre AS actualizado_nombre
           FROM clientes_notas n LEFT JOIN users u ON u.id = n.actualizado_por
          WHERE n.telefono = ?'
    );
    $stmt->execute([$telefono]);

    return ['resumen' => $resumen, 'consultas' => $consultas, 'pedidos' => $pedidos, 'notas' => $stmt->fetch() ?: null];
}

/**
 * Guarda (o borra, si queda vacía) la nota interna del cliente.
 */
function guardar_notas_cliente(string $telefono, string $notas, int $usuarioId): void
{
    if ($notas === '') {
        db()->prepare('DELETE FROM clientes_notas WHERE telefono = ?')->execute([$telefono]);
        return;
    }
    db()->prepare(
        'INSERT INTO clientes_notas (telefono, notas, actualizado_por) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE notas = VALUES(notas), actualizado_por = VALUES(actualizado_por)'
    )->execute([$telefono, $notas, $usuarioId]);
}

/**
 * Dirección de la ficha de un cliente a partir de su teléfono ("8888-8888").
 */
function url_cliente(string $telefono): string
{
    return url('admin/clientes/' . $telefono);
}

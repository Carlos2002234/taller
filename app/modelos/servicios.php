<?php
/**
 * Servicios del taller (sin precios; se cotizan por WhatsApp).
 */

declare(strict_types=1);

// Íconos disponibles para los servicios: clave => nombre para el admin
const ICONOS_SERVICIO = [
    'aceite'    => 'Aceite',
    'lavado'    => 'Lavado / brillo',
    'escaner'   => 'Escáner / computadora',
    'revision'  => 'Revisión / lista',
    'engranaje' => 'Mecánica / engranaje',
    'electrico' => 'Eléctrico',
    'llave'     => 'Llave',
    'camion'    => 'Vehículo',
    'escudo'    => 'Garantía / escudo',
];

/**
 * Servicios visibles en la tienda. $soloInicio = solo los marcados para la página de inicio.
 */
function servicios_activos(bool $soloInicio = false): array
{
    return db()->query(
        'SELECT id, nombre, descripcion, icono FROM services WHERE activo = 1'
        . ($soloInicio ? ' AND en_inicio = 1' : '') . ' ORDER BY orden, nombre'
    )->fetchAll();
}

function servicios_admin_listar(): array
{
    return db()->query('SELECT * FROM services ORDER BY orden, nombre')->fetchAll();
}

function servicio_por_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM services WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function servicio_guardar(?int $id, string $nombre, string $descripcion, string $icono, int $orden, bool $activo, bool $enInicio): int
{
    $valores = [$nombre, $descripcion !== '' ? $descripcion : null, $icono, $orden, $activo ? 1 : 0, $enInicio ? 1 : 0];
    if ($id === null) {
        db()->prepare('INSERT INTO services (nombre, descripcion, icono, orden, activo, en_inicio) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute($valores);
        return (int) db()->lastInsertId();
    }
    db()->prepare('UPDATE services SET nombre = ?, descripcion = ?, icono = ?, orden = ?, activo = ?, en_inicio = ? WHERE id = ?')
        ->execute([...$valores, $id]);
    return $id;
}

function servicio_borrar(int $id): void
{
    db()->prepare('DELETE FROM services WHERE id = ?')->execute([$id]);
}

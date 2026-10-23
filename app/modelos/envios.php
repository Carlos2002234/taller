<?php
/**
 * Zonas de envío (GAM, mensajero propio). Tarifas editables desde el admin.
 */

declare(strict_types=1);

function zonas_envio_activas(): array
{
    return db()->query(
        'SELECT id, nombre, descripcion, tarifa FROM shipping_zones WHERE activa = 1 ORDER BY orden, nombre'
    )->fetchAll();
}

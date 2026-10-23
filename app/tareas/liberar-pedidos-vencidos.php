<?php
/**
 * Cancela los pedidos sin pagar que superaron el plazo y devuelve su stock.
 * Programar en Hostinger (hPanel → Avanzado → Cron Jobs) para que corra cada hora:
 *
 *   php /home/USUARIO/taller/app/tareas/liberar-pedidos-vencidos.php
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/config/config.php';
require RAIZ . '/app/modelos/pedidos.php';

$cancelados = liberar_pedidos_vencidos();
echo date('Y-m-d H:i:s') . " — pedidos vencidos cancelados: {$cancelados}\n";

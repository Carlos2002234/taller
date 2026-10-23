<?php
/**
 * Entrega el archivo de un comprobante SOLO a usuarios del panel con sesión.
 * Los comprobantes viven fuera de la carpeta pública y no tienen dirección directa.
 */

require_once RAIZ . '/app/modelos/pedidos-admin.php';

requerir_admin();

$id = ctype_digit($parametros['id']) ? (int) $parametros['id'] : no_encontrado();
$comprobante = comprobante_por_id($id) ?? no_encontrado();

// La ruta guardada debe quedar dentro de la carpeta de comprobantes (nunca "..")
$carpeta = realpath(config('ruta.comprobantes'));
$archivo = realpath($carpeta . '/' . $comprobante['ruta_archivo']);
if (!$carpeta || !$archivo || !str_starts_with($archivo, $carpeta . DIRECTORY_SEPARATOR) || !is_file($archivo)) {
    error_log("Comprobante {$id} sin archivo: " . $comprobante['ruta_archivo']);
    no_encontrado();
}

$tipos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];
$mime = isset($tipos[$comprobante['mime']]) ? $comprobante['mime'] : 'application/octet-stream';
$nombre = 'comprobante-' . strtolower($comprobante['numero_pedido']) . '.' . ($tipos[$mime] ?? 'bin');

// La política de seguridad de las páginas puede impedir que el navegador muestre el PDF
header_remove('Content-Security-Policy');
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($archivo));
header('Content-Disposition: inline; filename="' . $nombre . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($archivo);

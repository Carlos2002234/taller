<?php
/**
 * API de solo lectura para el carrito: devuelve precio y stock REALES de los productos.
 * El navegador solo guarda IDs y cantidades; nunca se confía en precios del navegador.
 *
 * GET /api/carrito?ids=1,2,3
 */

require_once RAIZ . '/app/modelos/productos.php';

$ids = array_slice(explode(',', is_string($_GET['ids'] ?? null) ? $_GET['ids'] : ''), 0, 50);

try {
    $productos = array_map(fn (array $p): array => [
        'id'              => (int) $p['id'],
        'nombre'          => $p['nombre'],
        'marca'           => $p['marca'],
        'precio'          => (float) $p['precio'],
        'precio_anterior' => $p['precio_anterior'] !== null ? (float) $p['precio_anterior'] : null,
        'stock'           => max(0, (int) $p['stock']),
        'imagen'          => $p['imagen'] ? url('uploads/' . $p['imagen']) : null,
        'url'             => url('producto/' . $p['slug']),
    ], productos_por_ids($ids));

    echo json_encode(['productos' => $productos], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    error_log('API carrito: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo cargar el carrito.']);
}

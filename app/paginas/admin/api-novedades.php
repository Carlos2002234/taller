<?php
/**
 * El panel pregunta cada 30 s si llegaron consultas nuevas (para el aviso con sonido y notificación).
 * No renueva la sesión: dejar el panel abierto no evita el cierre por inactividad.
 *
 * GET /admin/api/novedades → {"nuevas": 2, "ultima": {"id": 7, "nombre": "…", "telefono": "…", "url": "…"}}
 */

require_once RAIZ . '/app/modelos/consultas.php';

header('X-Robots-Tag: noindex, nofollow');

if (!usuario_sin_renovar()) {
    http_response_code(401);
    echo json_encode(['error' => 'sin_sesion']);
    return;
}

$ultima = db()->query('SELECT id, nombre, telefono FROM consultas ORDER BY id DESC LIMIT 1')->fetch();

echo json_encode([
    'nuevas' => contar_consultas_nuevas(),
    'ultima' => $ultima ? [
        'id'       => (int) $ultima['id'],
        'nombre'   => $ultima['nombre'],
        'telefono' => $ultima['telefono'],
        'url'      => url('admin/consultas/' . $ultima['id']),
    ] : null,
], JSON_UNESCAPED_UNICODE);

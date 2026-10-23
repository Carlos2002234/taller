<?php
/**
 * sitemap.xml para Google: páginas principales, categorías con productos y cada producto visible.
 * Se genera solo; al agregar o editar productos se actualiza sin hacer nada.
 */

require_once RAIZ . '/app/modelos/categorias.php';

$urls = [
    ['', null, '1.0'],
    ['tienda', null, '0.9'],
    ['servicios', null, '0.7'],
    ['contacto', null, '0.6'],
    ['terminos', null, '0.2'],
    ['privacidad', null, '0.2'],
];

foreach (categorias_activas() as $categoria) {
    if ((int) $categoria['total_productos'] > 0) {
        $urls[] = ['tienda?categoria=' . rawurlencode($categoria['slug']), null, '0.7'];
    }
}

$productos = db()->query(
    'SELECT p.slug, p.actualizado_en FROM products p JOIN categories c ON c.id = p.category_id
      WHERE p.activo = 1 AND c.activa = 1 ORDER BY p.actualizado_en DESC'
)->fetchAll();
foreach ($productos as $producto) {
    $urls[] = ['producto/' . $producto['slug'], substr($producto['actualizado_en'], 0, 10), '0.8'];
}

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');

echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', "\n";
foreach ($urls as [$ruta, $modificado, $prioridad]) {
    echo '  <url><loc>', htmlspecialchars(url($ruta), ENT_XML1), '</loc>';
    if ($modificado) {
        echo '<lastmod>', $modificado, '</lastmod>';
    }
    echo '<priority>', $prioridad, '</priority></url>', "\n";
}
echo '</urlset>', "\n";

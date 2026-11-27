<?php
/**
 * sitemap.xml para Google: páginas principales, categorías con productos y cada producto visible
 * (con sus fotos, para que aparezcan en Google Imágenes).
 * Se genera solo; al agregar o editar productos se actualiza sin hacer nada.
 * lastmod: solo donde la fecha es real (productos; tienda y categorías = el producto editado más reciente).
 */

require_once RAIZ . '/app/modelos/categorias.php';

$productos = db()->query(
    'SELECT p.id, p.slug, p.actualizado_en, c.slug AS categoria_slug FROM products p JOIN categories c ON c.id = p.category_id
      WHERE p.activo = 1 AND c.activa = 1 ORDER BY p.actualizado_en DESC'
)->fetchAll();

// Fotos de los productos visibles (máx. 5 por producto, en el mismo orden que la galería)
$fotos = [];
if ($productos) {
    $ids = array_map(fn ($p) => (int) $p['id'], $productos);
    $marcas = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("SELECT product_id, ruta FROM product_images WHERE product_id IN ($marcas) ORDER BY product_id, orden, id");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $fila) {
        if (count($fotos[$fila['product_id']] ?? []) < 5) {
            $fotos[$fila['product_id']][] = url_foto($fila['ruta']);
        }
    }
}

// Fecha más reciente por categoría y de toda la tienda
$fechaTienda = null;
$fechaCategoria = [];
foreach ($productos as $producto) {
    $fecha = substr($producto['actualizado_en'], 0, 10);
    $fechaTienda = max($fechaTienda ?? $fecha, $fecha);
    $fechaCategoria[$producto['categoria_slug']] = max($fechaCategoria[$producto['categoria_slug']] ?? $fecha, $fecha);
}

// [ruta, lastmod, prioridad, fotos]
$urls = [
    ['', null, '1.0', []],
    ['tienda', $fechaTienda, '0.9', []],
    ['servicios', null, '0.8', []],
    ['ubicacion', null, '0.7', []],
    ['contacto', null, '0.6', []],
    ['preguntas-frecuentes', null, '0.5', []],
    ['terminos', null, '0.2', []],
    ['privacidad', null, '0.2', []],
];

foreach (categorias_activas() as $categoria) {
    if ((int) $categoria['total_productos'] > 0) {
        $urls[] = ['tienda?categoria=' . rawurlencode($categoria['slug']), $fechaCategoria[$categoria['slug']] ?? null, '0.7', []];
    }
}

foreach ($productos as $producto) {
    $urls[] = ['producto/' . $producto['slug'], substr($producto['actualizado_en'], 0, 10), '0.8', $fotos[$producto['id']] ?? []];
}

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');

echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">', "\n";
foreach ($urls as [$ruta, $modificado, $prioridad, $imagenes]) {
    echo '  <url><loc>', htmlspecialchars(url($ruta), ENT_XML1), '</loc>';
    if ($modificado) {
        echo '<lastmod>', $modificado, '</lastmod>';
    }
    echo '<priority>', $prioridad, '</priority>';
    foreach ($imagenes as $imagen) {
        echo '<image:image><image:loc>', htmlspecialchars($imagen, ENT_XML1), '</image:loc></image:image>';
    }
    echo '</url>', "\n";
}
echo '</urlset>', "\n";

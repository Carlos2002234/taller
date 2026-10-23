<?php
/**
 * robots.txt: Google puede leer la tienda, pero no el panel, el carrito ni los pedidos.
 */

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: public, max-age=86400');

echo "User-agent: *\n";
foreach (['/admin', '/carrito', '/checkout', '/pedido-confirmado', '/mi-pedido', '/api/'] as $ruta) {
    echo "Disallow: {$ruta}\n";
}
echo "\nSitemap: " . url('sitemap.xml') . "\n";

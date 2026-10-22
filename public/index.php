<?php
/**
 * Punto de entrada del sitio.
 * TEMPORAL (Módulo 1): solo verifica que la configuración y la base de datos funcionen.
 * En el Módulo 2 se reemplaza por el enrutador de páginas.
 */

declare(strict_types=1);

require dirname(__DIR__) . '/app/config/config.php';

/**
 * Prueba escribir un archivo real (is_writable() falla en carpetas de OneDrive en Windows).
 */
function carpeta_escribible(string $carpeta): bool
{
    $prueba = $carpeta . '/.prueba-' . bin2hex(random_bytes(4));
    if (@file_put_contents($prueba, 'ok') === false) {
        return false;
    }
    unlink($prueba);
    return true;
}

$revisiones = [];

$revisiones['PHP ' . PHP_VERSION] = version_compare(PHP_VERSION, '8.2.0', '>=');
$revisiones['Extensión PDO MySQL'] = extension_loaded('pdo_mysql');
$revisiones['Carpeta de fotos escribible'] = carpeta_escribible(config('ruta.uploads') . '/productos');
$revisiones['Carpeta de comprobantes escribible'] = carpeta_escribible(config('ruta.comprobantes'));

try {
    $tablas = (int) db()->query(
        'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()'
    )->fetchColumn();
    $revisiones["Base de datos conectada ({$tablas} tablas)"] = $tablas > 0;
    $negocio = ajuste('negocio_nombre', 'Tienda');
} catch (Throwable $e) {
    $revisiones['Base de datos conectada'] = false;
    $negocio = 'Tienda';
}

$todoBien = !in_array(false, $revisiones, true);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= e($negocio) ?> — Instalación</title>
    <style>
        body { margin: 0; font-family: system-ui, sans-serif; background: #0B0B0B; color: #fff; }
        main { max-width: 32rem; margin: 3rem auto; padding: 0 1rem; }
        h1 { font-size: 1.5rem; } h1 span { color: #D11A21; }
        li { list-style: none; padding: .6rem .8rem; margin: .4rem 0; background: #1A1A1A; border-radius: .4rem; }
        .ok::before { content: "✔ "; color: #3ecf6e; }
        .error::before { content: "✘ "; color: #D11A21; }
        ul { padding: 0; }
    </style>
</head>
<body>
<main>
    <h1><?= e($negocio) ?> <span>· revisión de instalación</span></h1>
    <ul>
        <?php foreach ($revisiones as $nombre => $ok): ?>
            <li class="<?= $ok ? 'ok' : 'error' ?>"><?= e($nombre) ?></li>
        <?php endforeach; ?>
    </ul>
    <p><?= $todoBien
        ? 'Todo listo. La base del proyecto funciona correctamente.'
        : 'Hay puntos por corregir. Revisa docs/instalacion-local.md.' ?></p>
</main>
</body>
</html>

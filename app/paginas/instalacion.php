<?php
/**
 * Revisión de instalación (solo en desarrollo): PHP, extensiones, carpetas y base de datos.
 */

$meta['titulo'] = 'Revisión de instalación';
$meta['noindex'] = true;

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

$revisiones = [
    'PHP ' . PHP_VERSION                 => version_compare(PHP_VERSION, '8.2.0', '>='),
    'Extensión PDO MySQL'                => extension_loaded('pdo_mysql'),
    'Extensión GD con WebP (fotos)'      => function_exists('imagewebp'),
    'Extensión fileinfo (comprobantes)'  => class_exists('finfo'),
    'Subidas de al menos 5 MB (upload_max_filesize y post_max_size)' =>
        ini_parse_quantity(ini_get('upload_max_filesize')) >= 5 * 1024 * 1024
        && ini_parse_quantity(ini_get('post_max_size')) >= 6 * 1024 * 1024,
    'Carpeta de fotos escribible'        => carpeta_escribible(config('ruta.uploads') . '/productos'),
    'Carpeta de comprobantes escribible' => carpeta_escribible(config('ruta.comprobantes')),
];

try {
    $tablas = (int) db()->query(
        'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()'
    )->fetchColumn();
    $revisiones["Base de datos conectada ({$tablas} tablas)"] = $tablas > 0;
} catch (Throwable $e) {
    $revisiones['Base de datos conectada'] = false;
}

$todoBien = !in_array(false, $revisiones, true);
?>
<section class="contenedor max-w-2xl py-12">
    <?php componente('titulo-seccion', ['nivel' => 'h1', 'etiqueta' => 'Solo en desarrollo', 'titulo' => 'Revisión de instalación']); ?>
    <ul class="space-y-2">
        <?php foreach ($revisiones as $nombre => $ok): ?>
            <li class="tarjeta flex items-center gap-3 px-4 py-3 text-sm">
                <span class="<?= $ok ? 'text-emerald-400' : 'text-rivera-rojo' ?>"><?= icono($ok ? 'exito' : 'alerta', 'h-5 w-5') ?></span>
                <?= e($nombre) ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <div class="mt-6">
        <?php componente('aviso', $todoBien
            ? ['tipo' => 'exito', 'mensaje' => 'Todo listo. La base del proyecto funciona correctamente.']
            : ['tipo' => 'error', 'mensaje' => 'Hay puntos por corregir. Revisa docs/instalacion-local.md.']); ?>
    </div>
</section>

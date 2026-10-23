<?php
/**
 * Estructura HTML común a todas las páginas públicas.
 * Recibe de mostrar_pagina(): $meta (array) y $contenido (HTML de la página).
 */

$negocio = ajuste('negocio_nombre', 'Rivera Grupo Automotriz');
$tituloCompleto = $meta['titulo'] ? $meta['titulo'] . ' | ' . $negocio : $negocio;
$urlActual = url(ruta_actual());
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($tituloCompleto) ?></title>
    <meta name="description" content="<?= e($meta['descripcion']) ?>">
    <?php if ($meta['noindex']): ?>
        <meta name="robots" content="noindex, nofollow">
    <?php else: ?>
        <link rel="canonical" href="<?= e($urlActual) ?>">
    <?php endif; ?>
    <meta name="theme-color" content="#0B0B0B">

    <!-- Vista previa al compartir en redes (Open Graph) -->
    <meta property="og:type" content="website">
    <meta property="og:locale" content="es_CR">
    <meta property="og:site_name" content="<?= e($negocio) ?>">
    <meta property="og:title" content="<?= e($tituloCompleto) ?>">
    <meta property="og:description" content="<?= e($meta['descripcion']) ?>">
    <meta property="og:url" content="<?= e($urlActual) ?>">
    <meta property="og:image" content="<?= e(url('img/og-imagen.jpg')) ?>">

    <link rel="icon" type="image/png" sizes="32x32" href="<?= e(asset('img/favicon-32.png')) ?>">
    <link rel="apple-touch-icon" href="<?= e(asset('img/apple-touch-icon.png')) ?>">
    <link rel="preload" href="<?= e(url('fonts/montserrat-latin-800-normal.woff2')) ?>" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <script src="<?= e(asset('js/app.js')) ?>" defer></script>
</head>
<body class="flex min-h-screen flex-col bg-rivera-negro font-sans text-white antialiased">
    <a href="#contenido" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded focus:bg-white focus:px-4 focus:py-2 focus:text-rivera-negro">
        Saltar al contenido
    </a>

    <?php require RAIZ . '/app/vistas/parciales/header.php'; ?>

    <main id="contenido" class="flex-1 <?= $meta['fondo_claro'] ? 'bg-rivera-gris text-rivera-negro' : '' ?>">
        <?= $contenido ?>
    </main>

    <?php require RAIZ . '/app/vistas/parciales/footer.php'; ?>
    <?php require RAIZ . '/app/vistas/parciales/nav-movil.php'; ?>
    <?php require RAIZ . '/app/vistas/parciales/boton-whatsapp.php'; ?>
</body>
</html>

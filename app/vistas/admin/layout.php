<?php
/**
 * Diseño del panel de administración: menú lateral (computadora) o desplegable (celular).
 * Recibe de mostrar_pagina(): $meta y $contenido.
 */

$usuario = usuario_actual();
$negocio = ajuste('negocio_nombre', 'Rivera Grupo Automotriz');
$actual = ruta_actual();

$menu = [];
$porRevisar = 0;
if ($usuario) {
    $porRevisar = (int) db()->query("SELECT COUNT(*) FROM orders WHERE estado = 'comprobante_enviado'")->fetchColumn();
    $menu[] = ['admin', 'Inicio', 'inicio'];
    $menu[] = ['admin/pedidos', 'Pedidos', 'pedido'];
    if (es_duenio($usuario)) {
        $menu[] = ['admin/reportes', 'Reportes', 'grafico'];
        $menu[] = ['admin/productos', 'Productos', 'tienda'];
        $menu[] = ['admin/categorias', 'Categorías', 'menu'];
        $menu[] = ['admin/servicios', 'Servicios', 'engranaje'];
        $menu[] = ['admin/configuracion', 'Configuración', 'llave'];
        $menu[] = ['admin/usuarios', 'Usuarios', 'chat'];
    }
    $menu[] = ['admin/cuenta', 'Mi cuenta', 'escudo'];
}
$activo = fn (string $ruta): bool => $ruta === 'admin' ? $actual === 'admin' : str_starts_with($actual, $ruta);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e(($meta['titulo'] ? $meta['titulo'] . ' | ' : '') . 'Panel · ' . $negocio) ?></title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= e(asset('img/favicon-32.png')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <script src="<?= e(asset('js/app.js')) ?>" defer></script>
    <script src="<?= e(asset('js/admin.js')) ?>" defer></script>
</head>
<body class="fondo-claro min-h-screen bg-rivera-gris font-sans text-rivera-negro antialiased lg:flex">

<?php if ($usuario): ?>
    <!-- Menú -->
    <header class="sticky top-0 z-40 bg-rivera-negro text-white lg:static lg:flex lg:min-h-screen lg:w-60 lg:shrink-0 lg:flex-col">
        <div class="flex h-14 items-center gap-2.5 px-4 lg:h-16">
            <img src="<?= e(asset('img/logo.webp')) ?>" alt="" width="32" height="32" class="h-8 w-8">
            <span class="leading-none">
                <img src="<?= e(asset('img/rivera-texto.webp')) ?>" alt="Rivera" width="139" height="15" class="mb-1 block h-[15px] w-auto">
                <span class="block text-[9px] font-semibold uppercase tracking-[0.2em] text-neutral-400">Panel</span>
            </span>
            <?php if ($porRevisar > 0): ?>
                <a href="<?= e(url('admin/pedidos?ver=por-revisar')) ?>" class="ml-auto rounded-full bg-rivera-rojo px-2.5 py-1 text-xs font-bold lg:hidden">
                    <?= $porRevisar ?> por revisar
                </a>
            <?php endif; ?>
            <button type="button" class="<?= $porRevisar > 0 ? '' : 'ml-auto' ?> rounded-md p-2 text-neutral-200 lg:hidden"
                    data-menu-boton aria-controls="menu-movil" aria-expanded="false" aria-label="Abrir menú">
                <span data-menu-icono-abrir><?= icono('menu', 'h-6 w-6') ?></span>
                <span data-menu-icono-cerrar class="hidden"><?= icono('cerrar', 'h-6 w-6') ?></span>
            </button>
        </div>

        <div id="menu-movil" class="hidden border-t border-white/10 lg:flex lg:flex-1 lg:flex-col lg:border-0">
            <nav class="grid gap-1 p-3" aria-label="Panel">
                <?php foreach ($menu as [$ruta, $texto, $icono]): ?>
                    <a href="<?= e(url($ruta)) ?>"
                       class="flex items-center gap-3 rounded-md px-3 py-2.5 text-sm font-medium <?= $activo($ruta) ? 'bg-rivera-rojo text-white' : 'text-neutral-300 hover:bg-white/5 hover:text-white' ?>"
                       <?= $activo($ruta) ? 'aria-current="page"' : '' ?>>
                        <?= icono($icono, 'h-5 w-5') ?> <?= e($texto) ?>
                        <?php if ($ruta === 'admin/pedidos' && $porRevisar > 0): ?>
                            <span class="ml-auto rounded-full px-2 text-xs font-bold <?= $activo($ruta) ? 'bg-white text-rivera-rojo' : 'bg-rivera-rojo text-white' ?>"
                                  title="Comprobantes por revisar"><?= $porRevisar ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="mt-auto border-t border-white/10 p-3">
                <p class="px-3 text-xs text-neutral-400"><?= e($usuario['nombre']) ?> · <?= e(ROLES[$usuario['rol']] ?? '') ?></p>
                <a href="<?= e(url()) ?>" target="_blank" rel="noopener"
                   class="mt-2 flex items-center gap-3 rounded-md px-3 py-2 text-sm text-neutral-300 hover:bg-white/5 hover:text-white">
                    <?= icono('flecha', 'h-5 w-5') ?> Ver tienda
                </a>
                <form method="post" action="<?= e(url('admin/salir')) ?>">
                    <?= csrf_campo() ?>
                    <button type="submit" class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-left text-sm text-neutral-300 hover:bg-white/5 hover:text-white">
                        <?= icono('cerrar', 'h-5 w-5') ?> Cerrar sesión
                    </button>
                </form>
            </div>
        </div>
    </header>
<?php endif; ?>

<main class="min-w-0 flex-1">
    <div class="<?= $usuario ? 'mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8' : '' ?>">
        <?php if ($usuario): ?>
            <?php $avisos = tomar_avisos(); ?>
            <?php if ($avisos): ?>
                <div class="mb-5 space-y-2">
                    <?php foreach ($avisos as $aviso) { componente('aviso', $aviso); } ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        <?= $contenido ?>
    </div>
</main>
</body>
</html>

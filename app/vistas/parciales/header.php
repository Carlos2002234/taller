<?php
/**
 * Encabezado: logo, menú, buscador y carrito. En celular, menú desplegable.
 */

$menu = [
    ''          => 'Inicio',
    'tienda'    => 'Tienda',
    'servicios' => 'Servicios',
    'contacto'  => 'Contacto',
    'mi-pedido' => 'Mi pedido',
];
?>
<header class="sticky top-0 z-40 border-b border-white/10 bg-rivera-negro/95 backdrop-blur">
    <div class="contenedor flex h-16 items-center gap-6">
        <!-- Logo -->
        <a href="<?= e(url()) ?>" class="flex shrink-0 items-center gap-2.5" aria-label="<?= e(ajuste('negocio_nombre', 'Rivera Grupo Automotriz')) ?> — Inicio">
            <img src="<?= e(asset('img/logo.webp')) ?>" alt="" width="40" height="40" class="h-10 w-10">
            <span class="leading-none">
                <span class="block font-display text-xl font-extrabold italic tracking-wide"><span class="text-rivera-rojo">R</span>IVERA</span>
                <span class="mt-0.5 block text-[9px] font-semibold uppercase tracking-[0.28em] text-neutral-400">Grupo Automotriz</span>
            </span>
        </a>

        <!-- Menú de escritorio -->
        <nav class="hidden flex-1 items-center justify-center gap-7 lg:flex" aria-label="Principal">
            <?php foreach ($menu as $ruta => $texto): ?>
                <a href="<?= e(url($ruta)) ?>"
                   class="enlace-menu <?= ruta_actual() === $ruta ? 'enlace-menu-activo' : '' ?>"
                   <?= ruta_actual() === $ruta ? 'aria-current="page"' : '' ?>><?= e($texto) ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="ml-auto flex items-center gap-2 lg:ml-0">
            <!-- Buscador (escritorio) -->
            <form action="<?= e(url('tienda')) ?>" method="get" role="search" class="relative hidden md:block">
                <label for="buscar-escritorio" class="sr-only">Buscar productos</label>
                <input id="buscar-escritorio" type="search" name="q" placeholder="Buscar productos…"
                       class="campo w-56 py-2 pl-9 xl:w-64">
                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-neutral-500"><?= icono('buscar', 'h-4 w-4') ?></span>
            </form>

            <!-- Carrito -->
            <a href="<?= e(url('carrito')) ?>" class="relative rounded-md p-2 text-neutral-200 hover:text-white" aria-label="Ver carrito">
                <?= icono('carrito', 'h-6 w-6') ?>
                <span data-carrito-contador
                      class="absolute -right-0.5 -top-0.5 hidden h-5 min-w-5 items-center justify-center rounded-full bg-rivera-rojo px-1 text-[11px] font-semibold text-white">0</span>
            </a>

            <!-- Botón de menú (celular) -->
            <button type="button" class="rounded-md p-2 text-neutral-200 hover:text-white lg:hidden"
                    data-menu-boton aria-controls="menu-movil" aria-expanded="false" aria-label="Abrir menú">
                <span data-menu-icono-abrir><?= icono('menu', 'h-6 w-6') ?></span>
                <span data-menu-icono-cerrar class="hidden"><?= icono('cerrar', 'h-6 w-6') ?></span>
            </button>
        </div>
    </div>

    <!-- Menú desplegable (celular) -->
    <div id="menu-movil" class="hidden border-t border-white/10 lg:hidden">
        <div class="contenedor space-y-4 py-4">
            <form action="<?= e(url('tienda')) ?>" method="get" role="search" class="relative md:hidden">
                <label for="buscar-movil" class="sr-only">Buscar productos</label>
                <input id="buscar-movil" type="search" name="q" placeholder="Buscar productos…" class="campo pl-9">
                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-neutral-500"><?= icono('buscar', 'h-4 w-4') ?></span>
            </form>
            <nav class="grid gap-1" aria-label="Principal (celular)">
                <?php foreach ($menu as $ruta => $texto): ?>
                    <a href="<?= e(url($ruta)) ?>"
                       class="rounded-md px-3 py-2.5 text-base font-medium <?= ruta_actual() === $ruta ? 'bg-white/5 text-white' : 'text-neutral-300 hover:bg-white/5' ?>"
                       <?= ruta_actual() === $ruta ? 'aria-current="page"' : '' ?>><?= e($texto) ?></a>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>
</header>

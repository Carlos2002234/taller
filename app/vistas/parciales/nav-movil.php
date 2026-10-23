<?php
/**
 * Barra de navegación fija en la parte de abajo (solo celular).
 */

$accesos = [
    ''          => ['Inicio', 'inicio'],
    'tienda'    => ['Tienda', 'tienda'],
    'carrito'   => ['Carrito', 'carrito'],
    'mi-pedido' => ['Mi pedido', 'pedido'],
];
?>
<nav class="fixed inset-x-0 bottom-0 z-40 border-t border-white/10 bg-rivera-negro/95 backdrop-blur lg:hidden" aria-label="Accesos rápidos">
    <ul class="grid h-16 grid-cols-4">
        <?php foreach ($accesos as $ruta => [$texto, $icono]): ?>
            <?php $activo = ruta_actual() === $ruta; ?>
            <li>
                <a href="<?= e(url($ruta)) ?>"
                   class="relative flex h-full flex-col items-center justify-center gap-1 text-[11px] font-medium <?= $activo ? 'text-rivera-rojo' : 'text-neutral-400' ?>"
                   <?= $activo ? 'aria-current="page"' : '' ?>>
                    <?= icono($icono, 'h-6 w-6') ?>
                    <?= e($texto) ?>
                    <?php if ($ruta === 'carrito'): ?>
                        <span data-carrito-contador
                              class="absolute right-[calc(50%-1.25rem)] top-1.5 hidden h-4 min-w-4 items-center justify-center rounded-full bg-rivera-rojo px-1 text-[10px] font-semibold text-white">0</span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>

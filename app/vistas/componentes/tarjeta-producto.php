<?php
/**
 * Tarjeta de producto.
 * Datos: $producto (nombre, slug, marca, precio, precio_anterior, stock, destacado, ?imagen)
 *        ?$tono ('oscuro' | 'claro')
 */

$tono ??= 'oscuro';
$oferta = $producto['precio_anterior'] !== null && (float) $producto['precio_anterior'] > (float) $producto['precio'];
$agotado = (int) $producto['stock'] <= 0;
$enlace = url('producto/' . $producto['slug']);
$descuento = $oferta ? (int) round(100 - $producto['precio'] * 100 / $producto['precio_anterior']) : 0;
?>
<article class="<?= $tono === 'claro' ? 'tarjeta-clara' : 'tarjeta' ?> group flex flex-col">
    <a href="<?= e($enlace) ?>" class="relative block aspect-square overflow-hidden <?= $tono === 'claro' ? 'bg-white' : 'bg-neutral-900' ?>">
        <?php if (!empty($producto['imagen'])): ?>
            <img src="<?= e(url_foto($producto['imagen'], true)) ?>" alt="<?= e($producto['nombre']) ?>"
                 loading="lazy" class="h-full w-full object-contain p-4 transition-transform duration-300 group-hover:scale-105">
        <?php else: ?>
            <span class="flex h-full w-full items-center justify-center <?= $tono === 'claro' ? 'text-neutral-300' : 'text-neutral-700' ?>">
                <?= icono('llave', 'h-14 w-14') ?>
            </span>
        <?php endif; ?>

        <span class="absolute left-2 top-2 flex flex-col items-start gap-1">
            <?php if ($agotado): ?>
                <span class="insignia-agotado">Agotado</span>
            <?php elseif ($oferta): ?>
                <span class="insignia-oferta">Oferta -<?= $descuento ?>%</span>
            <?php elseif ($producto['destacado']): ?>
                <span class="insignia-destacado">Destacado</span>
            <?php endif; ?>
        </span>
    </a>

    <div class="flex flex-1 flex-col p-3 sm:p-4">
        <?php if (!empty($producto['marca'])): ?>
            <p class="text-[11px] font-semibold uppercase tracking-wider <?= $tono === 'claro' ? 'text-neutral-500' : 'text-neutral-400' ?>"><?= e($producto['marca']) ?></p>
        <?php endif; ?>
        <h3 class="mt-0.5 line-clamp-2 font-sans text-sm font-semibold">
            <a href="<?= e($enlace) ?>" class="hover:text-rivera-rojo"><?= e($producto['nombre']) ?></a>
        </h3>

        <div class="mt-2 flex flex-wrap items-baseline gap-x-2">
            <span class="font-display text-lg font-bold"><?= colones($producto['precio']) ?></span>
            <?php if ($oferta): ?>
                <span class="text-xs text-neutral-500 line-through"><?= colones($producto['precio_anterior']) ?></span>
            <?php endif; ?>
        </div>

        <div class="mt-auto pt-3">
            <button type="button" class="btn-primario btn-pequeno w-full sm:py-2.5"
                    data-agregar-carrito="<?= (int) ($producto['id'] ?? 0) ?>" data-stock="<?= (int) $producto['stock'] ?>"
                    <?= $agotado ? 'disabled' : '' ?>>
                <?= icono('carrito', 'h-4 w-4') ?>
                <?php if ($agotado): ?>
                    Agotado
                <?php else: ?>
                    <span class="sm:hidden">Agregar</span><span class="hidden sm:inline">Agregar al carrito</span>
                <?php endif; ?>
            </button>
        </div>
    </div>
</article>

<?php
/**
 * Tarjeta de categoría con foto.
 * Datos: $categoria (nombre, slug, ?imagen)
 */
?>
<a href="<?= e(url('tienda?categoria=' . rawurlencode($categoria['slug']))) ?>" class="tarjeta group block">
    <span class="block aspect-[4/3] overflow-hidden bg-neutral-900">
        <?php if (!empty($categoria['imagen'])): ?>
            <img src="<?= e(url_foto($categoria['imagen'], true)) ?>" alt="" loading="lazy"
                 class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105">
        <?php else: ?>
            <span class="flex h-full w-full items-center justify-center text-neutral-700">
                <?= icono('tienda', 'h-12 w-12') ?>
            </span>
        <?php endif; ?>
    </span>
    <span class="block p-3">
        <span class="block text-sm font-semibold"><?= e($categoria['nombre']) ?></span>
        <span class="mt-1 inline-flex items-center gap-0.5 text-xs font-medium text-rivera-rojo-claro">
            Ver productos <?= icono('flecha', 'h-3.5 w-3.5') ?>
        </span>
    </span>
</a>

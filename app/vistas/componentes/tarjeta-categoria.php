<?php
/**
 * Tarjeta de categoría con foto (si no tiene, un recuadro con texto, sin íconos).
 * Datos: $categoria (nombre, slug, ?imagen)
 */
?>
<a href="<?= e(url('tienda?categoria=' . rawurlencode($categoria['slug']))) ?>" class="tarjeta tarjeta-interactiva group flex flex-col rounded">
    <span class="relative block aspect-[4/3] overflow-hidden bg-[#1B1B1B]">
        <?php if (!empty($categoria['imagen'])): ?>
            <img src="<?= e(url_foto($categoria['imagen'], true)) ?>" alt="" width="400" height="400" loading="lazy" decoding="async"
                 class="h-full w-full object-cover transition-transform duration-lenta ease-suave motion-safe:group-hover:scale-105">
        <?php else: ?>
            <span class="flex h-full w-full items-center justify-center text-[11px] uppercase tracking-[0.14em] text-neutral-500">Foto pendiente</span>
        <?php endif; ?>
    </span>
    <span class="flex items-center justify-between gap-2 px-4 py-3.5">
        <span class="text-[0.95rem] font-semibold"><?= e($categoria['nombre']) ?></span>
        <span class="shrink-0 text-rivera-rojo-claro transition-transform motion-safe:group-hover:translate-x-1"><?= icono('flecha', 'h-4 w-4') ?></span>
    </span>
</a>

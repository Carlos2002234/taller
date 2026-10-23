<?php
/**
 * Galería de fotos del producto (hasta 5). En celular se desliza con el dedo;
 * las miniaturas llevan a cada foto.
 * Datos: $imagenes (rutas relativas a /uploads), $nombre
 */
?>
<div data-galeria>
    <div class="tarjeta-clara relative">
        <?php if ($imagenes): ?>
            <div class="flex snap-x snap-mandatory overflow-x-auto [scrollbar-width:none]" data-galeria-pista>
                <?php foreach ($imagenes as $i => $ruta): ?>
                    <img src="<?= e(url_foto($ruta)) ?>"
                         alt="<?= e($nombre) ?><?= count($imagenes) > 1 ? ' — foto ' . ($i + 1) : '' ?>"
                         class="aspect-square w-full shrink-0 snap-center object-contain p-6"
                         <?= $i === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="flex aspect-square items-center justify-center text-neutral-300">
                <?= icono('llave', 'h-24 w-24') ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if (count($imagenes) > 1): ?>
        <div class="mt-3 grid grid-cols-5 gap-2">
            <?php foreach ($imagenes as $i => $ruta): ?>
                <button type="button" data-galeria-ir="<?= $i ?>" aria-label="Ver foto <?= $i + 1 ?>"
                        class="tarjeta-clara aspect-square border-2 <?= $i === 0 ? 'border-rivera-rojo' : 'border-transparent' ?>">
                    <img src="<?= e(url_foto($ruta, true)) ?>" alt="" loading="lazy" class="h-full w-full object-contain p-1">
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

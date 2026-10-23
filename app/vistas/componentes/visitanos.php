<?php
/**
 * Bloque "Visítanos": foto de la recepción, dirección, horario, comodidades de la sala de espera
 * y botones de contacto.
 */
?>
<section class="bg-black/40 py-20 sm:py-24" aria-labelledby="visitanos">
    <div class="contenedor grid items-center gap-8 lg:grid-cols-2">
        <picture class="aparecer group block overflow-hidden rounded-2xl shadow-2xl shadow-black/60 ring-1 ring-white/10">
            <source type="image/webp" srcset="<?= e(url('img/fotos/rivera-23-800.webp')) ?> 800w, <?= e(url('img/fotos/rivera-23-1600.webp')) ?> 1600w" sizes="(min-width: 1024px) 50vw, 100vw">
            <img src="<?= e(url('img/fotos/rivera-23-1600.webp')) ?>" alt="Recepción de <?= e(ajuste('negocio_nombre', 'Rivera Grupo Automotriz')) ?>" loading="lazy"
                 class="aspect-[16/10] w-full object-cover transition-transform duration-700 ease-out group-hover:scale-105">
        </picture>
        <div class="aparecer">
            <p class="etiqueta-seccion">Te esperamos</p>
            <h2 id="visitanos" class="titulo-seccion">Visítanos en <span class="text-rivera-rojo">el taller</span></h2>
            <ul class="mt-6 space-y-4 text-neutral-300">
                <li class="flex gap-3"><span class="mt-0.5 shrink-0 text-rivera-rojo"><?= icono('ubicacion', 'h-5 w-5') ?></span><?= e(ajuste('direccion')) ?></li>
                <li class="flex gap-3"><span class="mt-0.5 shrink-0 text-rivera-rojo"><?= icono('reloj', 'h-5 w-5') ?></span><?= e(ajuste('horario')) ?></li>
                <li class="flex gap-3"><span class="mt-0.5 shrink-0 text-rivera-rojo"><?= icono('telefono', 'h-5 w-5') ?></span><?= e(ajuste('telefono')) ?></li>
            </ul>
            <!-- Comodidades de la sala de espera -->
            <div class="mt-7 rounded-xl border border-white/10 bg-white/5 p-5">
                <p class="font-display text-base font-bold italic text-white">Sala de espera cómoda</p>
                <p class="mt-1 text-sm text-neutral-400">Mientras trabajamos en tu auto, contamos con:</p>
                <ul class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 text-sm text-neutral-200">
                    <li class="flex items-center gap-2.5"><span class="shrink-0 text-rivera-rojo"><?= icono('wifi', 'h-5 w-5') ?></span>Wi-Fi</li>
                    <li class="flex items-center gap-2.5"><span class="shrink-0 text-rivera-rojo"><?= icono('aire', 'h-5 w-5') ?></span>Aire acondicionado</li>
                    <li class="flex items-center gap-2.5"><span class="shrink-0 text-rivera-rojo"><?= icono('cafe', 'h-5 w-5') ?></span>Café</li>
                    <li class="flex items-center gap-2.5"><span class="shrink-0 text-rivera-rojo"><?= icono('carrito', 'h-5 w-5') ?></span>Tienda de accesorios</li>
                </ul>
            </div>
            <div class="mt-7 flex flex-wrap gap-3">
                <a href="<?= e(mapa_url()) ?>" target="_blank" rel="noopener" class="btn-primario"><?= icono('ubicacion', 'h-4 w-4') ?> Cómo llegar</a>
                <?php if (ajuste('waze_url') !== ''): ?>
                    <a href="<?= e(ajuste('waze_url')) ?>" target="_blank" rel="noopener" class="btn-secundario"><?= icono('ubicacion', 'h-4 w-4') ?> Waze</a>
                <?php endif; ?>
                <a href="<?= e(whatsapp_url('Hola, les escribo desde la página web.')) ?>" target="_blank" rel="noopener" class="btn-secundario"><?= icono('whatsapp', 'h-4 w-4') ?> WhatsApp</a>
            </div>
        </div>
    </div>
</section>

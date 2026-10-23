<?php
/**
 * Bloque "Visítanos": foto de la recepción, dirección, horario y botones de contacto.
 */
?>
<section class="bg-black/40 py-14" aria-labelledby="visitanos">
    <div class="contenedor grid items-center gap-8 lg:grid-cols-2">
        <picture class="block overflow-hidden rounded-lg">
            <source type="image/webp" srcset="<?= e(url('img/fotos/rivera-23-800.webp')) ?> 800w, <?= e(url('img/fotos/rivera-23-1600.webp')) ?> 1600w" sizes="(min-width: 1024px) 50vw, 100vw">
            <img src="<?= e(url('img/fotos/rivera-23-1600.webp')) ?>" alt="Recepción de <?= e(ajuste('negocio_nombre', 'Rivera Grupo Automotriz')) ?>" loading="lazy"
                 class="aspect-[16/10] w-full object-cover">
        </picture>
        <div>
            <p class="etiqueta-seccion">Te esperamos</p>
            <h2 id="visitanos" class="titulo-seccion">Visítanos en <span class="text-rivera-rojo">el taller</span></h2>
            <ul class="mt-6 space-y-4 text-neutral-300">
                <li class="flex gap-3"><span class="mt-0.5 shrink-0 text-rivera-rojo"><?= icono('ubicacion', 'h-5 w-5') ?></span><?= e(ajuste('direccion')) ?></li>
                <li class="flex gap-3"><span class="mt-0.5 shrink-0 text-rivera-rojo"><?= icono('reloj', 'h-5 w-5') ?></span><?= e(ajuste('horario')) ?></li>
                <li class="flex gap-3"><span class="mt-0.5 shrink-0 text-rivera-rojo"><?= icono('telefono', 'h-5 w-5') ?></span><?= e(ajuste('telefono')) ?></li>
            </ul>
            <div class="mt-7 flex flex-wrap gap-3">
                <a href="<?= e(mapa_url()) ?>" target="_blank" rel="noopener" class="btn-primario"><?= icono('ubicacion', 'h-4 w-4') ?> Cómo llegar</a>
                <a href="<?= e(whatsapp_url('Hola, les escribo desde la página web.')) ?>" target="_blank" rel="noopener" class="btn-secundario"><?= icono('whatsapp', 'h-4 w-4') ?> WhatsApp</a>
            </div>
        </div>
    </div>
</section>

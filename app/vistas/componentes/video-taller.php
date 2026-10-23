<?php
/**
 * Bloque "Así trabajamos": video vertical del taller (sin audio) junto a un texto corto.
 * Para que la página no se ponga lenta, el video NO se descarga al abrir la página
 * (preload="none"): app.js lo reproduce solo cuando la sección entra en pantalla y lo pausa al salir.
 * Con "reducir movimiento" o "ahorro de datos" no arranca solo; queda la portada con el botón ▶.
 */
$tiktok = ajuste('tiktok_url');
?>
<section class="contenedor pt-20 sm:pt-24" aria-labelledby="asi-trabajamos">
    <div class="grid items-center gap-10 lg:grid-cols-2">
        <div class="aparecer">
            <p class="etiqueta-seccion">Conoce el taller</p>
            <h2 id="asi-trabajamos" class="titulo-seccion">Así trabajamos <span class="text-rivera-rojo">en Rivera</span></h2>
            <p class="mt-5 max-w-xl text-neutral-300">
                Cambios de aceite, frenos, llantas, lavado y una tienda de accesorios, todo en un mismo lugar.
                Trabajamos con cuidado y te explicamos lo que necesita tu auto.
            </p>
            <div class="mt-7 flex flex-wrap gap-3">
                <a href="<?= e(whatsapp_url('Hola, quiero cotizar un servicio para mi auto.')) ?>" target="_blank" rel="noopener" class="btn-primario"><?= icono('whatsapp', 'h-4 w-4') ?> Cotizar por WhatsApp</a>
                <?php if ($tiktok !== ''): ?>
                    <a href="<?= e($tiktok) ?>" target="_blank" rel="noopener" class="btn-secundario"><?= icono('tiktok', 'h-4 w-4') ?> Más videos en TikTok</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="aparecer relative mx-auto w-full max-w-[260px] sm:max-w-[300px]" data-video>
            <video class="aspect-[9/16] w-full rounded-[2rem] bg-rivera-carbon object-cover shadow-2xl shadow-black/60 ring-1 ring-white/10"
                   width="540" height="960" muted loop playsinline preload="none" disablepictureinpicture
                   poster="<?= e(asset('video/taller-portada.webp')) ?>" data-video-auto
                   aria-label="Video del taller: cambios de aceite, frenos, llantas, lavado, tienda de accesorios y sala de espera">
                <source src="<?= e(asset('video/taller.mp4')) ?>" type="video/mp4">
            </video>
            <button type="button" class="absolute bottom-4 right-4 hidden rounded-full bg-black/60 p-3 text-white ring-1 ring-white/20 backdrop-blur transition hover:bg-rivera-rojo"
                    aria-label="Reproducir video" data-video-boton>
                <span data-video-icono-reproducir><?= icono('reproducir', 'h-5 w-5') ?></span>
                <span class="hidden" data-video-icono-pausa><?= icono('pausa', 'h-5 w-5') ?></span>
            </button>
        </div>
    </div>
</section>

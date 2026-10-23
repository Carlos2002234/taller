<?php
/**
 * Página provisional para secciones que se construyen en módulos siguientes.
 */

$meta['titulo'] = 'Muy pronto';
$meta['noindex'] = true;
?>
<section class="contenedor flex min-h-[50vh] flex-col items-center justify-center py-16 text-center">
    <img src="<?= e(asset('img/logo.webp')) ?>" alt="" width="64" height="64" class="h-16 w-16">
    <h1 class="mt-6 text-3xl">Muy <span class="text-rivera-rojo">pronto</span></h1>
    <p class="mt-3 max-w-sm text-neutral-400">Estamos preparando esta sección. Mientras tanto, con gusto te atendemos por WhatsApp.</p>
    <div class="mt-8 flex flex-wrap justify-center gap-3">
        <a href="<?= e(whatsapp_url('Hola, les escribo desde la página web.')) ?>" class="btn-primario" target="_blank" rel="noopener">
            <?= icono('whatsapp', 'h-4 w-4') ?> Escribir por WhatsApp
        </a>
        <a href="<?= e(url()) ?>" class="btn-secundario">Volver al inicio</a>
    </div>
</section>

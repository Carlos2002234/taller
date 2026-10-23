<?php
/**
 * Página no encontrada.
 */

$meta['titulo'] = 'Página no encontrada';
$meta['noindex'] = true;
?>
<section class="contenedor flex min-h-[50vh] flex-col items-center justify-center py-16 text-center">
    <p class="font-display text-7xl font-extrabold italic text-rivera-rojo">404</p>
    <h1 class="mt-4 text-2xl">Esta página no existe</h1>
    <p class="mt-3 max-w-sm text-neutral-400">Puede que el enlace esté mal escrito o que la página se haya movido.</p>
    <div class="mt-8 flex flex-wrap justify-center gap-3">
        <a href="<?= e(url()) ?>" class="btn-primario">Ir al inicio</a>
        <a href="<?= e(url('tienda')) ?>" class="btn-secundario">Ver tienda</a>
    </div>
</section>

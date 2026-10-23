<?php
/**
 * Inicio — PROVISIONAL (Módulo 2): banner principal y beneficios.
 * La página completa (servicios, productos destacados, ubicación) se arma en el Módulo 8.
 */

$meta['descripcion'] = 'Rivera Grupo Automotriz: taller mecánico y tienda de accesorios y repuestos en La Puebla. Paga por SINPE Móvil o transferencia.';
?>
<section class="relative isolate overflow-hidden">
    <picture>
        <source type="image/webp"
                srcset="<?= e(url('img/fotos/rivera-07-960.webp')) ?> 960w, <?= e(url('img/fotos/rivera-07-1920.webp')) ?> 1920w"
                sizes="100vw">
        <img src="<?= e(url('img/fotos/rivera-07-1920.webp')) ?>" alt=""
             class="absolute inset-0 -z-10 h-full w-full object-cover object-[70%_center]" fetchpriority="high">
    </picture>
    <div class="absolute inset-0 -z-10 bg-gradient-to-r from-rivera-negro via-rivera-negro/80 to-rivera-negro/10"></div>

    <div class="contenedor flex min-h-[28rem] flex-col justify-center py-16 sm:min-h-[34rem]">
        <p class="etiqueta-seccion"><?= e(ajuste('banner_etiqueta', 'Accesorios y repuestos para tu auto')) ?></p>
        <h1 class="max-w-xl font-display text-4xl font-extrabold leading-[1.05] sm:text-5xl lg:text-6xl">
            <?= e(ajuste('banner_titulo', 'Equipa tu camino')) ?>
            <span class="block text-rivera-rojo"><?= e(ajuste('banner_titulo_rojo', 'con lo mejor')) ?></span>
        </h1>
        <p class="mt-5 max-w-md text-base text-neutral-300"><?= e(ajuste('banner_texto')) ?></p>
        <div class="mt-8 flex flex-wrap gap-3">
            <a href="<?= e(url('tienda')) ?>" class="btn-primario">Ver tienda <?= icono('flecha', 'h-4 w-4') ?></a>
            <a href="<?= e(whatsapp_url('Hola, quiero cotizar un servicio para mi auto.')) ?>" class="btn-secundario" target="_blank" rel="noopener">
                <?= icono('whatsapp', 'h-4 w-4') ?> Cotizar servicio
            </a>
        </div>
    </div>
</section>

<?php componente('beneficios'); ?>

<section class="contenedor py-12">
    <?php componente('aviso', ['tipo' => 'info', 'mensaje' => 'Estamos preparando nuestra tienda en línea. Mientras tanto, escríbenos por WhatsApp.']); ?>
</section>

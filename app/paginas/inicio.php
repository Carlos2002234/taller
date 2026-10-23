<?php
/**
 * Inicio: portada con la fachada del taller, beneficios, categorías, destacados, servicios,
 * cómo comprar y ubicación. Todo el contenido sale de la base de datos (editable desde el admin).
 */

require_once RAIZ . '/app/modelos/productos.php';
require_once RAIZ . '/app/modelos/categorias.php';
require_once RAIZ . '/app/modelos/servicios.php';

$meta['descripcion'] = ajuste('negocio_nombre', 'Rivera Grupo Automotriz')
    . ': taller mecánico y tienda de accesorios y repuestos. Paga por SINPE Móvil o transferencia y retira en el taller o recibe en la GAM.';
$meta['portada'] = true;
$meta['datos'][] = datos_negocio();

$categorias = array_slice(array_filter(categorias_activas(), fn ($c) => (int) $c['total_productos'] > 0), 0, 6);
$destacados = productos_destacados(8);
$servicios = servicios_activos(true);
?>
<!-- Portada: fachada completa del taller -->
<section class="relative isolate overflow-hidden">
    <picture>
        <source type="image/webp"
                srcset="<?= e(url('img/fotos/rivera-14-960.webp')) ?> 960w, <?= e(url('img/fotos/rivera-14-1920.webp')) ?> 1920w"
                sizes="100vw">
        <img src="<?= e(url('img/fotos/rivera-14-1920.webp')) ?>"
             alt="Fachada de <?= e(ajuste('negocio_nombre', 'Rivera Grupo Automotriz')) ?>"
             class="portada-foto absolute inset-0 -z-20 h-full w-full object-cover object-[62%_75%] sm:object-[center_70%]" fetchpriority="high">
    </picture>
    <!-- Degradados: oscurecen el cielo detrás del texto y funden la foto con la página -->
    <div class="absolute inset-0 -z-10 bg-gradient-to-r from-rivera-negro/95 via-rivera-negro/75 to-rivera-negro/25"></div>
    <div class="absolute inset-0 -z-10 bg-gradient-to-t from-rivera-negro via-transparent to-rivera-negro/60"></div>

    <div class="contenedor flex min-h-[88svh] flex-col justify-center pb-20 pt-28 sm:min-h-[90vh]">
        <p class="etiqueta-seccion portada-entrar portada-entrar-1"><?= e(ajuste('banner_etiqueta', 'Accesorios y repuestos para tu auto')) ?></p>
        <h1 class="portada-entrar portada-entrar-2 max-w-2xl font-display text-4xl font-extrabold leading-[1.05] tracking-tight sm:text-6xl lg:text-7xl">
            <?= e(ajuste('banner_titulo', 'Equipa tu camino')) ?>
            <span class="block text-rivera-rojo"><?= e(ajuste('banner_titulo_rojo', 'con lo mejor')) ?></span>
        </h1>
        <p class="portada-entrar portada-entrar-3 mt-6 max-w-md text-base leading-relaxed text-neutral-200 sm:text-lg"><?= e(ajuste('banner_texto')) ?></p>
        <div class="portada-entrar portada-entrar-4 mt-9 flex flex-wrap gap-3">
            <a href="<?= e(url('tienda')) ?>" class="btn-primario px-7 py-3.5 text-base">Ver tienda <?= icono('flecha', 'h-4 w-4') ?></a>
            <a href="<?= e(url('servicios')) ?>" class="btn-secundario px-7 py-3.5 text-base"><?= icono('llave', 'h-4 w-4') ?> Servicios del taller</a>
        </div>
    </div>

    <a href="#beneficios" class="indicador-bajar absolute bottom-6 left-1/2 hidden -translate-x-1/2 text-neutral-300 hover:text-white sm:block" aria-label="Ver más">
        <span class="block rotate-90"><?= icono('flecha', 'h-7 w-7') ?></span>
    </a>
</section>

<div id="beneficios">
    <?php componente('beneficios'); ?>
</div>

<?php if ($categorias): ?>
    <section class="contenedor pt-20 sm:pt-24">
        <div class="aparecer">
            <?php componente('titulo-seccion', ['etiqueta' => 'Categorías', 'titulo' => 'Explora por categoría', 'enlace' => url('tienda'), 'enlace_texto' => 'Ver tienda']); ?>
        </div>
        <div class="escalonado grid grid-cols-2 gap-3 sm:gap-4 <?= count($categorias) <= 4 ? 'sm:grid-cols-4' : 'sm:grid-cols-3 lg:grid-cols-6' ?>">
            <?php foreach ($categorias as $categoria) { componente('tarjeta-categoria', ['categoria' => $categoria]); } ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($destacados): ?>
    <section class="contenedor pt-20 sm:pt-24">
        <div class="aparecer">
            <?php componente('titulo-seccion', ['etiqueta' => 'Lo más buscado', 'titulo' => 'Productos destacados', 'enlace' => url('tienda'), 'enlace_texto' => 'Ver todos']); ?>
        </div>
        <div class="escalonado grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
            <?php foreach ($destacados as $producto) { componente('tarjeta-producto', ['producto' => $producto]); } ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($servicios): ?>
    <section class="contenedor pt-20 sm:pt-24">
        <div class="aparecer">
            <?php componente('titulo-seccion', ['etiqueta' => 'Taller mecánico', 'titulo' => 'Cuidamos tu auto', 'titulo_rojo' => 'como si fuera nuestro', 'enlace' => url('servicios'), 'enlace_texto' => 'Ver servicios']); ?>
        </div>
        <div class="escalonado grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($servicios as $servicio) { componente('tarjeta-servicio', ['servicio' => $servicio]); } ?>
        </div>
    </section>
<?php endif; ?>

<?php componente('video-taller'); ?>

<?php componente('testimonios'); ?>

<?php componente('cta-consulta'); ?>

<?php componente('como-comprar'); ?>

<?php componente('visitanos'); ?>

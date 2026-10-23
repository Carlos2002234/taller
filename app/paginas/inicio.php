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
<!-- Portada: la fachada del taller va limpia, sin texto encima; el texto va debajo, sobre el fondo negro
     y solo roza la parte de la foto que ya se funde con la página
     (decisión del dueño: "minimalista y elegante, que la letra no tape la imagen") -->
<section class="relative isolate">
    <div class="relative h-[62svh] min-h-[22rem] overflow-hidden sm:h-[74vh]">
        <picture>
            <source type="image/webp"
                    srcset="<?= e(url('img/fotos/rivera-14-960.webp')) ?> 960w, <?= e(url('img/fotos/rivera-14-1920.webp')) ?> 1920w"
                    sizes="100vw">
            <img src="<?= e(url('img/fotos/rivera-14-1920.webp')) ?>"
                 alt="Fachada de <?= e(ajuste('negocio_nombre', 'Rivera Grupo Automotriz')) ?>"
                 class="portada-foto absolute inset-0 h-full w-full object-cover object-[22%_72%] sm:object-[center_65%]" fetchpriority="high">
        </picture>
        <!-- Sombra arriba para leer el menú y fundido abajo hacia el negro de la página -->
        <div class="absolute inset-x-0 top-0 h-28 bg-gradient-to-b from-rivera-negro/70 to-transparent"></div>
        <div class="absolute inset-x-0 bottom-0 h-1/3 bg-gradient-to-t from-rivera-negro to-transparent"></div>
    </div>

    <div class="contenedor relative -mt-16 pb-14 sm:-mt-20 sm:pb-20">
        <p class="portada-entrar portada-entrar-1 text-[11px] font-semibold uppercase tracking-[0.25em] text-neutral-400"><?= e(ajuste('banner_etiqueta', 'Accesorios y repuestos para tu auto')) ?></p>
        <h1 class="portada-entrar portada-entrar-2 mt-3 font-display text-3xl font-bold leading-tight sm:text-4xl lg:text-5xl">
            <?= e(ajuste('banner_titulo', 'Equipa tu camino')) ?> <span class="text-rivera-rojo"><?= e(ajuste('banner_titulo_rojo', 'con lo mejor')) ?></span>
        </h1>
        <?php if (ajuste('banner_texto') !== ''): ?>
            <p class="portada-entrar portada-entrar-3 mt-3 max-w-md text-sm leading-relaxed text-neutral-400"><?= e(ajuste('banner_texto')) ?></p>
        <?php endif; ?>
        <div class="portada-entrar portada-entrar-4 mt-6 flex flex-wrap items-center gap-x-6 gap-y-3">
            <a href="<?= e(url('tienda')) ?>" class="btn-primario">Ver tienda <?= icono('flecha', 'h-4 w-4') ?></a>
            <a href="<?= e(url('servicios')) ?>" class="inline-flex items-center gap-1.5 text-sm font-semibold text-white/90 underline-offset-4 hover:text-white hover:underline">
                Servicios del taller <?= icono('flecha', 'h-4 w-4') ?>
            </a>
        </div>
    </div>
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

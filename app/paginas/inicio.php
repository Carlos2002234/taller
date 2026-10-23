<?php
/**
 * Inicio: banner, beneficios, categorías, destacados, servicios, cómo comprar y ubicación.
 * Todo el contenido sale de la base de datos (editable desde el admin).
 */

require_once RAIZ . '/app/modelos/productos.php';
require_once RAIZ . '/app/modelos/categorias.php';
require_once RAIZ . '/app/modelos/servicios.php';

$meta['descripcion'] = ajuste('negocio_nombre', 'Rivera Grupo Automotriz')
    . ': taller mecánico y tienda de accesorios y repuestos. Paga por SINPE Móvil o transferencia y retira en el taller o recibe en la GAM.';

$categorias = array_slice(array_filter(categorias_activas(), fn ($c) => (int) $c['total_productos'] > 0), 0, 6);
$destacados = productos_destacados(8);
$servicios = servicios_activos(true);
?>
<!-- Banner principal -->
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
            <a href="<?= e(url('servicios')) ?>" class="btn-secundario"><?= icono('llave', 'h-4 w-4') ?> Servicios del taller</a>
        </div>
    </div>
</section>

<?php componente('beneficios'); ?>

<?php if ($categorias): ?>
    <section class="contenedor pt-14">
        <?php componente('titulo-seccion', ['etiqueta' => 'Categorías', 'titulo' => 'Explora por categoría', 'enlace' => url('tienda'), 'enlace_texto' => 'Ver tienda']); ?>
        <div class="grid grid-cols-2 gap-3 <?= count($categorias) <= 4 ? 'sm:grid-cols-4' : 'sm:grid-cols-3 lg:grid-cols-6' ?>">
            <?php foreach ($categorias as $categoria) { componente('tarjeta-categoria', ['categoria' => $categoria]); } ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($destacados): ?>
    <section class="contenedor pt-14">
        <?php componente('titulo-seccion', ['etiqueta' => 'Lo más buscado', 'titulo' => 'Productos destacados', 'enlace' => url('tienda'), 'enlace_texto' => 'Ver todos']); ?>
        <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
            <?php foreach ($destacados as $producto) { componente('tarjeta-producto', ['producto' => $producto]); } ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($servicios): ?>
    <section class="contenedor pt-14">
        <?php componente('titulo-seccion', ['etiqueta' => 'Taller mecánico', 'titulo' => 'Cuidamos tu auto', 'titulo_rojo' => 'como si fuera nuestro', 'enlace' => url('servicios'), 'enlace_texto' => 'Ver servicios']); ?>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($servicios as $servicio) { componente('tarjeta-servicio', ['servicio' => $servicio]); } ?>
        </div>
    </section>
<?php endif; ?>

<?php componente('como-comprar'); ?>

<?php componente('visitanos'); ?>

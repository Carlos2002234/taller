<?php
/**
 * Inicio: fachada del taller completa (sin nada encima), texto y botones debajo, beneficios,
 * categorías, destacados, marcas, taller y testimonios. Todo sale de la base de datos (editable desde el admin).
 *
 * Decisión del dueño (2026-11-08): la foto del inicio se ve COMPLETA, sin texto ni sombras encima;
 * encabezado en barra negra sólida con el logo completo del manual de marca.
 */

require_once RAIZ . '/app/modelos/productos.php';
require_once RAIZ . '/app/modelos/categorias.php';
require_once RAIZ . '/app/modelos/servicios.php';

$meta['descripcion'] = ajuste('negocio_nombre', 'Rivera Grupo Automotriz')
    . ': taller mecánico y tienda de accesorios y repuestos. Paga por SINPE Móvil o transferencia y retira en el taller o recibe en la GAM.';
$meta['datos'][] = datos_negocio();

$categorias = array_slice(array_filter(categorias_activas(), fn ($c) => (int) $c['total_productos'] > 0), 0, 6);
$destacados = productos_destacados(8);
$servicios = servicios_activos(true);

// Marcas de los productos que se usan y venden (texto, sin logos)
$marcas = ['Havoline', 'Castrol', 'Chevron', 'Delo', 'Vonixx', 'Malco'];
?>
<!-- Portada: la fachada completa, sin texto encima -->
<section aria-label="Portada">
    <picture class="block overflow-hidden bg-rivera-carbon">
        <source type="image/webp"
                srcset="<?= e(url('img/fotos/rivera-14-960.webp')) ?> 960w, <?= e(url('img/fotos/rivera-14-1920.webp')) ?> 1920w"
                sizes="100vw">
        <img src="<?= e(url('img/fotos/rivera-14-1920.webp')) ?>" width="1920" height="1080"
             alt="Fachada de <?= e(ajuste('negocio_nombre', 'Rivera Grupo Automotriz')) ?> vista de frente"
             class="foto-entrar block aspect-video h-auto w-full object-cover" fetchpriority="high">
    </picture>
    <div class="h-[3px] bg-rivera-rojo"></div>

    <div class="contenedor grid gap-8 py-12 sm:py-16 lg:grid-cols-[minmax(0,7fr)_minmax(0,5fr)] lg:items-end lg:gap-16">
        <div>
            <p class="etiqueta-seccion portada-entrar portada-entrar-1"><?= e(ajuste('banner_etiqueta', 'Accesorios y repuestos para tu auto')) ?></p>
            <h1 class="portada-entrar portada-entrar-2 mt-3 font-display text-4xl font-extrabold leading-[0.98] tracking-tight sm:text-5xl xl:text-6xl">
                <?= e(ajuste('banner_titulo', 'Equipa tu camino')) ?><br><span class="text-rivera-rojo"><?= e(ajuste('banner_titulo_rojo', 'con lo mejor')) ?></span>
            </h1>
        </div>
        <div class="portada-entrar portada-entrar-3">
            <?php if (ajuste('banner_texto') !== ''): ?>
                <p class="max-w-md leading-relaxed text-neutral-300"><?= e(ajuste('banner_texto')) ?></p>
            <?php endif; ?>
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="<?= e(url('tienda')) ?>" class="btn-primario">Ver la tienda <?= icono('flecha', 'h-4 w-4') ?></a>
                <a href="<?= e(url('servicios')) ?>" class="btn-secundario">Servicios del taller</a>
            </div>
        </div>
    </div>
</section>

<div id="beneficios" class="border-t border-white/10">
    <?php componente('beneficios'); ?>
</div>

<?php if ($categorias): ?>
    <section class="contenedor pt-16 sm:pt-24">
        <div class="aparecer">
            <?php componente('titulo-seccion', ['etiqueta' => 'Categorías', 'titulo' => 'Explora por categoría', 'enlace' => url('tienda'), 'enlace_texto' => 'Ver todas las categorías']); ?>
        </div>
        <div class="escalonado grid grid-cols-2 gap-3 sm:gap-4 <?= count($categorias) <= 4 ? 'sm:grid-cols-4' : 'sm:grid-cols-3 lg:grid-cols-6' ?>">
            <?php foreach ($categorias as $categoria) { componente('tarjeta-categoria', ['categoria' => $categoria]); } ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($destacados): ?>
    <section class="contenedor pt-16 sm:pt-24">
        <div class="aparecer">
            <?php componente('titulo-seccion', ['etiqueta' => 'Lo más buscado', 'titulo' => 'Productos destacados', 'enlace' => url('tienda'), 'enlace_texto' => 'Ver todos los productos']); ?>
        </div>
        <div class="escalonado grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
            <?php foreach ($destacados as $producto) { componente('tarjeta-producto', ['producto' => $producto]); } ?>
        </div>
    </section>
<?php endif; ?>

<!-- Marcas -->
<section aria-label="Marcas con las que trabajamos" class="fondo-claro mt-16 bg-white text-rivera-negro sm:mt-24">
    <div class="contenedor flex flex-wrap items-center justify-between gap-x-12 gap-y-5 py-10">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-neutral-600">Marcas con las que trabajamos</p>
        <ul class="flex flex-wrap gap-x-12 gap-y-3 font-display text-xl font-extrabold uppercase italic tracking-wide text-neutral-800 sm:text-2xl">
            <?php foreach ($marcas as $marca): ?><li><?= e($marca) ?></li><?php endforeach; ?>
        </ul>
    </div>
</section>

<?php if ($servicios): ?>
    <!-- Taller: foto + lista numerada -->
    <section class="contenedor grid items-stretch gap-10 pt-16 sm:pt-24 lg:grid-cols-2 lg:gap-14" aria-labelledby="taller">
        <figure class="aparecer relative min-h-[18rem] overflow-hidden rounded bg-rivera-carbon sm:min-h-[26rem]">
            <img src="<?= e(url('img/fotos/rivera-08-1600.webp')) ?>"
                 srcset="<?= e(url('img/fotos/rivera-08-800.webp')) ?> 800w, <?= e(url('img/fotos/rivera-08-1600.webp')) ?> 1600w"
                 sizes="(min-width: 1024px) 50vw, 100vw" loading="lazy"
                 alt="Zona de lavado del taller con un sedán gris" class="absolute inset-0 h-full w-full object-cover">
        </figure>
        <div class="aparecer flex flex-col justify-center">
            <p class="etiqueta-seccion">Taller de servicio</p>
            <h2 id="taller" class="mt-3 text-3xl font-extrabold leading-[1.02] tracking-tight sm:text-4xl xl:text-5xl">Cuidamos tu auto<br><span class="text-rivera-rojo">como si fuera nuestro</span></h2>
            <ol class="mt-7 border-t border-white/10">
                <?php foreach ($servicios as $i => $servicio): ?>
                    <li class="flex min-h-[3.25rem] items-center gap-4 border-b border-white/10">
                        <span class="w-7 font-display text-sm font-bold italic text-rivera-rojo-claro" aria-hidden="true"><?= sprintf('%02d', $i + 1) ?></span>
                        <span class="text-[0.95rem] font-medium"><?= e($servicio['nombre']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ol>
            <div class="mt-7 flex flex-wrap gap-3">
                <a href="<?= e(whatsapp_url('Hola, quiero cotizar un servicio para mi auto.')) ?>" target="_blank" rel="noopener" class="btn-primario">Cotizar por WhatsApp</a>
                <a href="<?= e(url('servicios')) ?>" class="btn-secundario">Ver servicios</a>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php componente('testimonios'); ?>

<?php componente('video-taller'); ?>

<?php componente('cta-consulta'); ?>

<div class="pb-20 sm:pb-24"></div>

<?php
/**
 * Inicio: fachada del taller completa (sin nada encima) y debajo el bloque racing (título grande, botones,
 * beneficios y cinta de categorías sobre fondo oscuro liso),
 * categorías, destacados, marcas, taller, testimonios y video del taller (las preguntas frecuentes tienen su pestaña: /preguntas-frecuentes). Todo sale de la base de datos (editable desde el admin).
 *
 * Decisiones del dueño: la foto del inicio se ve COMPLETA, sin texto ni sombras encima (2026-11-08, ratificado
 * 2026-11-21: "salía la imagen principal del taller"); el bloque de debajo, estilo racing (2026-11-21).
 * Encabezado en barra negra sólida con el logo completo del manual de marca.
 */

require_once RAIZ . '/app/modelos/productos.php';
require_once RAIZ . '/app/modelos/categorias.php';
require_once RAIZ . '/app/modelos/servicios.php';

// SEO local: lo que la gente busca en Google ("taller mecánico Heredia") + el nombre del negocio
$meta['titulo_completo'] = 'Taller mecánico y accesorios en Heredia | ' . ajuste('negocio_nombre', 'Rivera Grupo Automotriz');
$meta['descripcion'] = 'Taller mecánico en La Puebla, Heredia: cambio de aceite, diagnóstico, frenos y lavado. '
    . 'Accesorios y repuestos con envío en la GAM. WhatsApp ' . ajuste('telefono') . '.';
$meta['datos'][] = datos_negocio();
$meta['datos'][] = sitio_web_schema();

// Foto de portada = imagen más grande al cargar (LCP): se precarga con los mismos tamaños que el <img>
$portadaSrcset = asset('img/fotos/rivera-14-800.webp') . ' 800w, ' . asset('img/fotos/rivera-14-960.webp') . ' 960w, '
    . asset('img/fotos/rivera-14-1600.webp') . ' 1600w, ' . asset('img/fotos/rivera-14-1920.webp') . ' 1920w';
$meta['precargar'] = ['src' => asset('img/fotos/rivera-14-1920.webp'), 'srcset' => $portadaSrcset, 'sizes' => '100vw'];

$categorias = array_slice(array_filter(categorias_activas(), fn ($c) => (int) $c['total_productos'] > 0), 0, 6);
$destacados = productos_destacados(8);
$servicios = servicios_activos(true);

// Portada: el título editable ("Equipa tu camino") se parte para dejar la última palabra solo en contorno
$titulo = trim(ajuste('banner_titulo', 'Equipa tu camino'));
$corte = strrpos($titulo, ' ');
$titulo_inicio = $corte === false ? '' : substr($titulo, 0, $corte);
$titulo_contorno = $corte === false ? $titulo : substr($titulo, $corte + 1);

// Cinta de la portada: categorías reales con productos (si hay pocas, una lista base)
$cinta = array_column(array_filter(categorias_activas(), fn ($c) => (int) $c['total_productos'] > 0), 'nombre');
if (count($cinta) < 3) {
    $cinta = ['Aceites y filtros', 'Luces LED', 'Alfombras', 'Accesorios', 'Repuestos', 'Lavado'];
}

// Marcas de los productos que se usan y venden (texto, sin logos)
$marcas = ['Havoline', 'Castrol', 'Chevron', 'Delo', 'Vonixx', 'Malco'];
?>
<!-- Portada: la fachada completa, sin texto encima -->
<section aria-labelledby="titulo-portada">
    <picture class="block overflow-hidden bg-rivera-carbon">
        <img src="<?= e($meta['precargar']['src']) ?>" srcset="<?= e($portadaSrcset) ?>" sizes="100vw" width="1920" height="1080"
             alt="Fachada de <?= e(ajuste('negocio_nombre', 'Rivera Grupo Automotriz')) ?> vista de frente"
             class="foto-entrar block aspect-video h-auto w-full object-cover" fetchpriority="high">
    </picture>

    <!-- Debajo de la foto: bloque racing sobre fondo oscuro liso (franjas, título, botones, beneficios y cinta).
         Estilos en "Portada del inicio, estilo racing" (resources/css/app.css). -->
    <div class="portada-racing">
        <div class="contenedor pt-12 sm:pt-16 lg:pt-20">
            <!-- Etiqueta + título; las franjas van detrás, a la derecha, desde la línea roja hasta el título -->
            <div class="relative">
            <div class="portada-franja portada-franja-1" aria-hidden="true"></div>
            <div class="portada-franja portada-franja-2" aria-hidden="true"></div>
            <div class="racing-entra flex flex-wrap items-center gap-x-3.5 gap-y-2">
                <span class="text-xs font-semibold uppercase tracking-[0.22em] text-neutral-300"><?= e(ajuste('banner_etiqueta', 'Accesorios y repuestos para tu auto')) ?></span>
            </div>

            <h1 id="titulo-portada" class="titulo-racing mt-7">
                <?php if ($titulo_inicio !== ''): ?><span class="derrape block"><?= e($titulo_inicio) ?></span><?php endif; ?>
                <span class="derrape derrape-2 texto-contorno block"><?= e($titulo_contorno) ?></span>
                <span class="derrape derrape-2 flex items-center gap-6 text-rivera-rojo"><?= e(ajuste('banner_titulo_rojo', 'con lo mejor')) ?><span class="lineas-velocidad hidden sm:block" aria-hidden="true"></span></span>
            </h1>
            </div>

            <div class="mt-10 flex flex-wrap items-end justify-between gap-x-12 gap-y-7">
                <?php if (ajuste('banner_texto') !== ''): ?>
                    <p class="racing-entra racing-entra-1 max-w-[420px] text-lg leading-relaxed text-neutral-300"><?= e(ajuste('banner_texto')) ?></p>
                <?php endif; ?>
                <div class="racing-entra racing-entra-2 flex flex-wrap gap-3.5 pl-2">
                    <a href="<?= e(url('tienda')) ?>" class="btn-sesgo btn-sesgo-rojo"><span>Ver la tienda
                        <svg class="flechita h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span></a>
                    <a href="<?= e(url('servicios')) ?>" class="btn-sesgo btn-sesgo-linea"><span>Servicios del taller</span></a>
                </div>
            </div>

            <div class="mt-16 lg:mt-[72px]">
                <?php componente('beneficios', ['animar' => true]); ?>
            </div>
        </div>

        <!-- Cinta con las categorías (decorativa: las mismas categorías están en la sección siguiente y en la tienda) -->
        <div class="cinta-racing mb-10 mt-2 overflow-hidden bg-rivera-rojo py-4" aria-hidden="true">
            <div class="cinta-racing-pista font-display text-2xl font-black uppercase italic tracking-[0.04em] text-white">
                <?php for ($copia = 0; $copia < 2; $copia++): ?>
                    <span class="flex shrink-0 items-center gap-10 pr-10">
                        <?php foreach ($cinta as $nombre): ?>
                            <span class="whitespace-nowrap"><?= e($nombre) ?></span><span class="text-rivera-negro">///</span>
                        <?php endforeach; ?>
                    </span>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</section>

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
        <div class="aparecer desde-izq">
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
        <ul class="escalonado flex flex-wrap gap-x-12 gap-y-3 font-display text-xl font-extrabold uppercase italic tracking-wide text-neutral-800 sm:text-2xl">
            <?php foreach ($marcas as $marca): ?><li><?= e($marca) ?></li><?php endforeach; ?>
        </ul>
    </div>
</section>

<?php if ($servicios): ?>
    <!-- Taller: foto + lista numerada -->
    <section class="contenedor grid items-stretch gap-10 pt-16 sm:pt-24 lg:grid-cols-2 lg:gap-14" aria-labelledby="taller">
        <figure class="revelar relative min-h-[18rem] overflow-hidden rounded bg-rivera-carbon sm:min-h-[26rem]">
            <img src="<?= e(asset('img/fotos/rivera-08-1600.webp')) ?>"
                 srcset="<?= e(asset('img/fotos/rivera-08-800.webp')) ?> 800w, <?= e(asset('img/fotos/rivera-08-1600.webp')) ?> 1600w"
                 sizes="(min-width: 1024px) 50vw, 100vw" loading="lazy" decoding="async" width="1600" height="900"
                 alt="Zona de lavado del taller con un sedán gris" class="absolute inset-0 h-full w-full object-cover">
        </figure>
        <div class="aparecer desde-der flex flex-col justify-center">
            <p class="etiqueta-seccion">Taller de servicio · La Puebla, Heredia</p>
            <h2 id="taller" class="mt-3 text-3xl font-extrabold leading-[1.02] tracking-tight sm:text-4xl xl:text-5xl">Cuidamos tu auto<br><span class="text-rivera-rojo">como si fuera nuestro</span></h2>
            <ol class="escalonado mt-7 border-t border-white/10">
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

<?php componente('acceso-ubicacion'); ?>

<div class="pb-20 sm:pb-24"></div>

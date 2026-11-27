<?php
/**
 * Bloque "Lo que dicen nuestros clientes": reseñas reales de Google (pedido del dueño 2026-11-27;
 * reemplaza las capturas de WhatsApp).
 *
 * - Datos: app/datos/resenas-google.php (ahí se agregan reseñas nuevas; este HTML no se toca).
 * - Bloque de confianza: "5.0" que cuenta desde 0.0, 5 estrellas que se llenan, "N reseñas en Google".
 * - Carrusel: 3 tarjetas en escritorio, 2 en tablet, 1 en celular (con un pedazo de la siguiente).
 *   Lógica en public/js/app.js ([data-resenas]); estilos en resources/css/app.css (bloque "Reseñas de Google").
 * - Mejora progresiva: sin JavaScript (o mientras carga) todo se ve completo y el carrusel se desliza
 *   con el dedo o la barra de desplazamiento nativa; los controles quedan reservados (sin salto de diseño).
 * - El logo de Google es un SVG en línea (nada se carga de Google).
 */

require_once RAIZ . '/app/modelos/resenas.php';

$datos = resenas_google();
$resenas = $datos['resenas'];
$total_lista = count($resenas);
$calificacion = calificacion_texto($datos['calificacion']);
$segundos = 6;

// "G" multicolor oficial de Google
$logo_google = static fn (string $clases): string =>
    '<svg class="' . e($clases) . '" viewBox="0 0 48 48" aria-hidden="true" focusable="false">'
    . '<path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>'
    . '<path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>'
    . '<path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>'
    . '<path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>'
    . '</svg>';

// Estrellas (las llenas en amarillo de Google; las vacías en gris)
$estrellas = static function (int $llenas, string $clases, string $extra = '') : string {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $html .= '<svg class="' . e($clases . ' ' . $extra . ($i <= $llenas ? ' text-[#FBBC04]' : ' text-neutral-600')) . '" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">'
            . '<path d="M10 1.5l2.6 5.5 6 .8-4.4 4.1 1.1 5.9L10 14.9l-5.3 2.9 1.1-5.9L1.4 7.8l6-.8z"/></svg>';
    }
    return $html;
};

$flecha = '<svg class="flechita h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';
?>
<section class="resenas contenedor pt-20 sm:pt-28" aria-labelledby="testimonios" data-resenas data-segundos="<?= $segundos ?>">
    <!-- Encabezado: mismo estilo que el resto de secciones -->
    <div class="resenas-entra border-b border-white/10 pb-9" data-resenas-parte>
        <p class="etiqueta-seccion">Testimonios</p>
        <h2 id="testimonios" class="mt-3 text-4xl font-extrabold leading-none tracking-tight sm:text-5xl">Lo que dicen<br><span class="text-rivera-rojo">nuestros clientes</span></h2>
    </div>

    <div class="resenas-rejilla mt-10 grid gap-6 sm:mt-12 xl:gap-8">
        <!-- Bloque de confianza: calificación de Google -->
        <div class="resenas-confianza resenas-entra relative flex flex-wrap items-center gap-x-8 gap-y-6 overflow-hidden rounded-xl border border-rivera-borde bg-rivera-carbon p-6 sm:p-8 xl:flex-col xl:items-start xl:justify-between" data-resenas-parte data-resenas-confianza>
            <div class="flex flex-wrap items-end gap-x-6 gap-y-3">
                <p class="flex flex-col">
                    <span class="flex items-center gap-2 text-sm font-semibold text-neutral-300">
                        <?= $logo_google('h-5 w-5') ?> Calificación en Google
                    </span>
                    <span class="resenas-numero mt-1 font-display text-[5.5rem] font-black italic leading-[0.9] tracking-tight text-white sm:text-[6.5rem]">
                        <span class="sr-only"><?= e($calificacion) ?> de 5 estrellas</span><span aria-hidden="true" data-resenas-numero data-valor="<?= e($calificacion) ?>"><?= e($calificacion) ?></span>
                    </span>
                </p>
                <div class="pb-3">
                    <span class="resenas-estrellas flex gap-1" aria-hidden="true"><?= $estrellas((int) round($datos['calificacion']), 'h-6 w-6', 'resenas-estrella') ?></span>
                    <p class="mt-2 text-base text-neutral-300"><?= e($datos['total']) ?> <?= $datos['total'] === 1 ? 'reseña' : 'reseñas' ?> en Google</p>
                </div>
            </div>
            <a href="<?= e($datos['url']) ?>" target="_blank" rel="noopener" class="btn-secundario btn-brillo min-h-12 text-[0.95rem]">
                Ver reseñas en Google <?= $flecha ?>
            </a>
        </div>

        <!-- Carrusel de reseñas -->
        <div class="resenas-carrusel min-w-0" data-resenas-carrusel>
            <div class="resenas-ventana" tabindex="0" role="region" aria-roledescription="carrusel"
                 aria-label="Reseñas de Google (flechas ← → para cambiar)" data-resenas-ventana>
                <div class="resenas-pista" data-resenas-pista>
                    <?php foreach ($resenas as $i => $r): ?>
                        <figure class="resena relative flex flex-col rounded-xl border border-rivera-borde bg-rivera-carbon p-6 sm:p-7"
                                role="group" aria-roledescription="reseña" aria-label="<?= $i + 1 ?> de <?= $total_lista ?>" data-resena>
                            <span class="resena-comillas" aria-hidden="true">“</span>
                            <figcaption class="relative flex items-center gap-3.5">
                                <span class="resena-avatar flex h-12 w-12 shrink-0 items-center justify-center rounded-full font-display text-xl font-extrabold italic text-white" aria-hidden="true"><?= e($r['inicial']) ?></span>
                                <span class="flex min-w-0 flex-col gap-1 pr-8">
                                    <span class="text-base font-semibold leading-snug text-white [overflow-wrap:anywhere]"><?= e($r['nombre']) ?></span>
                                    <span class="flex gap-0.5"><?= $estrellas($r['estrellas'], 'h-4 w-4') ?></span>
                                    <span class="sr-only"><?= $r['estrellas'] ?> de 5 estrellas</span>
                                </span>
                            </figcaption>
                            <blockquote class="relative mt-5 flex-1">
                                <p class="text-base leading-relaxed text-neutral-200"><?= e($r['texto']) ?></p>
                            </blockquote>
                            <p class="relative mt-6 flex items-center gap-2 border-t border-white/10 pt-4 text-[0.95rem] text-neutral-300">
                                <?= $logo_google('h-4 w-4') ?> Reseña de Google
                            </p>
                        </figure>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Controles (el JavaScript los activa; mientras tanto ocupan su lugar sin verse) -->
            <div class="resenas-controles mt-4 flex flex-wrap items-center justify-between gap-x-4 gap-y-1 sm:mt-6 sm:flex-nowrap">
                <button type="button" class="min-h-11 text-[0.95rem] font-semibold text-neutral-200 underline decoration-neutral-500 underline-offset-4 transition-colors hover:text-white hover:decoration-white"
                        aria-label="Pausar: las reseñas cambian solas cada <?= $segundos ?> segundos" data-resenas-pausa>Pausar</button>
                <div class="order-first flex min-w-0 basis-full justify-center sm:order-none sm:basis-auto sm:flex-1" data-resenas-puntos>
                    <?php foreach ($resenas as $i => $r): ?>
                        <button type="button" class="resenas-punto group flex h-11 w-9 items-center justify-center" aria-label="Ir a la reseña <?= $i + 1 ?> de <?= $total_lista ?>"
                                aria-pressed="<?= $i === 0 ? 'true' : 'false' ?>" data-resenas-punto>
                            <span class="block h-[3px] w-6 overflow-hidden rounded-full bg-neutral-600 transition-colors group-hover:bg-neutral-400" aria-hidden="true">
                                <span class="resenas-barra block h-full bg-rivera-rojo" data-resenas-barra></span>
                            </span>
                        </button>
                    <?php endforeach; ?>
                </div>
                <span class="flex gap-2">
                    <button type="button" class="resenas-flecha" aria-label="Reseña anterior" data-resenas-anterior>
                        <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
                    </button>
                    <button type="button" class="resenas-flecha" aria-label="Reseña siguiente" data-resenas-siguiente>
                        <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
                    </button>
                </span>
            </div>
        </div>
    </div>

    <!-- Llamada final: dejar reseña en Google (principal) o escribir por WhatsApp (secundario) -->
    <div class="cta-franjas resenas-entra mt-10 flex flex-col items-start justify-between gap-6 rounded-2xl border border-white/10 bg-gradient-to-br from-rivera-carbon to-black p-6 sm:p-8 lg:flex-row lg:items-center" data-resenas-parte>
        <div>
            <p class="font-display text-2xl font-extrabold italic leading-tight sm:text-[1.6rem]">¿Te atendimos? <span class="text-rivera-rojo">Cuéntalo en Google</span></p>
            <p class="mt-2 max-w-xl text-base text-neutral-300">Tu reseña ayuda a otros conductores a elegir taller.</p>
        </div>
        <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row">
            <a href="<?= e($datos['url']) ?>" target="_blank" rel="noopener" class="btn-primario btn-brillo min-h-12 text-[0.95rem]">
                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-white"><?= $logo_google('h-4 w-4') ?></span>
                Déjanos tu reseña en Google <?= $flecha ?>
            </a>
            <a href="<?= e(whatsapp_url('Hola, les quiero contar cómo me fue en el taller.')) ?>" target="_blank" rel="noopener" class="btn-secundario btn-brillo min-h-12 text-[0.95rem]">
                <?= icono('whatsapp', 'h-4 w-4') ?> Escríbenos por WhatsApp
            </a>
        </div>
    </div>
</section>

<?php
/**
 * Bloque "Visítanos" (Inicio y Servicios): ubicación y horario con estilo racing.
 * - Izquierda: tarjeta con la foto de la entrada del taller (sin texto encima), dirección y botones
 *   "Cómo llegar" (Google Maps) y "Abrir en Waze".
 * - Derecha: horario por día con hoy resaltado e indicador "Abierto ahora" (componente horario-semana),
 *   teléfono/WhatsApp y comodidades de la sala de espera.
 * No se incrusta un mapa (decisión del dueño: riesgo de ubicación equivocada); el botón usa las coordenadas exactas.
 * Todos los datos salen de Configuración (direccion, telefono, waze_url, mapa_url, horario_semana).
 */
$telefono = preg_replace('/\D/', '', ajuste('telefono'));
?>
<section class="visitanos relative overflow-hidden bg-black/40 py-20 sm:py-24" aria-labelledby="visitanos">
    <div class="contenedor">
        <div class="aparecer mb-10">
            <p class="etiqueta-seccion">Te esperamos</p>
            <h2 id="visitanos" class="mt-3 text-4xl font-extrabold leading-[1.02] tracking-tight sm:text-5xl">Visítanos en<br><span class="text-rivera-rojo">el taller</span></h2>
        </div>

        <div class="grid gap-5 lg:grid-cols-12">
            <!-- Ubicación -->
            <div class="aparecer desde-izq tarjeta flex flex-col lg:col-span-7">
                <div class="revelar tarjeta-foto relative overflow-hidden">
                    <picture>
                        <source type="image/webp" srcset="<?= e(url('img/fotos/rivera-18-800.webp')) ?> 800w, <?= e(url('img/fotos/rivera-18-1600.webp')) ?> 1600w" sizes="(min-width: 1024px) 58vw, 100vw">
                        <img src="<?= e(url('img/fotos/rivera-18-1600.webp')) ?>" alt="Entrada de <?= e(ajuste('negocio_nombre', 'Rivera Grupo Automotriz')) ?> en La Puebla" loading="lazy"
                             class="aspect-[16/9] w-full object-cover">
                    </picture>
                    <span class="visitanos-franja" aria-hidden="true"></span>
                </div>
                <div class="flex flex-1 flex-col justify-between gap-6 p-6 sm:p-8">
                    <div class="flex gap-3">
                        <span class="mt-1 shrink-0 text-rivera-rojo"><?= icono('ubicacion', 'h-6 w-6') ?></span>
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-neutral-400">Dirección</p>
                            <p class="mt-1.5 text-lg font-medium leading-snug text-white"><?= e(ajuste('direccion')) ?></p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <a href="<?= e(mapa_url()) ?>" target="_blank" rel="noopener" class="btn-primario"><?= icono('ubicacion', 'h-4 w-4') ?> Cómo llegar</a>
                        <?php if (ajuste('waze_url') !== ''): ?>
                            <a href="<?= e(ajuste('waze_url')) ?>" target="_blank" rel="noopener" class="btn-secundario"><?= icono('ubicacion', 'h-4 w-4') ?> Abrir en Waze</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Horario y contacto -->
            <div class="aparecer desde-der tarjeta flex flex-col gap-7 p-6 sm:p-8 lg:col-span-5">
                <?php componente('horario-semana'); ?>

                <div class="border-t border-white/10 pt-6">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-neutral-400">Teléfono y WhatsApp</p>
                    <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
                        <a href="tel:+506<?= e($telefono) ?>" class="font-display text-2xl font-bold italic text-white hover:text-rivera-rojo-claro"><?= e(ajuste('telefono')) ?></a>
                        <a href="<?= e(whatsapp_url('Hola, les escribo desde la página web.')) ?>" target="_blank" rel="noopener" class="btn-secundario btn-pequeno"><?= icono('whatsapp', 'h-4 w-4') ?> WhatsApp</a>
                    </div>
                </div>

                <!-- Comodidades de la sala de espera -->
                <div class="border-t border-white/10 pt-6">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-neutral-400">Sala de espera cómoda</p>
                    <ul class="mt-3 grid grid-cols-2 gap-x-4 gap-y-3 text-sm text-neutral-200">
                        <li class="flex items-center gap-2.5"><span class="shrink-0 text-rivera-rojo"><?= icono('wifi', 'h-5 w-5') ?></span>Wi-Fi</li>
                        <li class="flex items-center gap-2.5"><span class="shrink-0 text-rivera-rojo"><?= icono('aire', 'h-5 w-5') ?></span>Aire acondicionado</li>
                        <li class="flex items-center gap-2.5"><span class="shrink-0 text-rivera-rojo"><?= icono('cafe', 'h-5 w-5') ?></span>Café</li>
                        <li class="flex items-center gap-2.5"><span class="shrink-0 text-rivera-rojo"><?= icono('carrito', 'h-5 w-5') ?></span>Tienda de accesorios</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
/**
 * Fila de la lista numerada de servicios (página Servicios, fondo claro).
 * Datos: $servicio (nombre, descripcion), $numero (1, 2, 3...)
 */
$mensaje = 'Hola, quiero cotizar el servicio de ' . mb_strtolower($servicio['nombre']) . ' para mi auto.';
?>
<li class="flex flex-wrap items-center gap-x-10 gap-y-3 border-b border-neutral-200 py-7 sm:py-8">
    <span class="w-12 shrink-0 font-display text-xl font-bold italic text-rivera-rojo-oscuro sm:w-16" aria-hidden="true"><?= sprintf('%02d', $numero) ?></span>
    <h3 class="min-w-0 flex-[1_1_15rem] text-2xl leading-tight lg:text-[1.75rem]"><?= e($servicio['nombre']) ?></h3>
    <p class="min-w-0 flex-[2_1_20rem] leading-relaxed text-neutral-600"><?= e($servicio['descripcion'] ?: 'Escríbenos y te damos el precio para tu vehículo.') ?></p>
    <a href="<?= e(whatsapp_url($mensaje)) ?>" target="_blank" rel="noopener"
       class="btn-claro shrink-0" aria-label="Cotizar <?= e(mb_strtolower($servicio['nombre'])) ?> por WhatsApp">
        Cotizar <?= icono('flecha', 'h-4 w-4') ?>
    </a>
</li>

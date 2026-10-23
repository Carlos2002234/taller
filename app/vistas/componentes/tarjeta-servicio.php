<?php
/**
 * Tarjeta de un servicio del taller con botón para cotizar por WhatsApp.
 * Datos: $servicio (nombre, descripcion, icono)
 */
?>
<article class="tarjeta group flex flex-col p-5 transition-colors hover:border-rivera-rojo/60">
    <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-rivera-rojo/10 text-rivera-rojo">
        <?= icono($servicio['icono'], 'h-7 w-7') ?>
    </span>
    <h3 class="mt-4 font-display text-lg font-bold"><?= e($servicio['nombre']) ?></h3>
    <?php if ($servicio['descripcion']): ?>
        <p class="mt-1.5 text-sm text-neutral-400"><?= e($servicio['descripcion']) ?></p>
    <?php endif; ?>
    <a href="<?= e(whatsapp_url('Hola, quiero cotizar el servicio de ' . mb_strtolower($servicio['nombre']) . ' para mi auto.')) ?>"
       target="_blank" rel="noopener"
       class="mt-auto inline-flex items-center gap-1.5 pt-4 text-sm font-semibold text-white hover:text-rivera-rojo">
        <?= icono('whatsapp', 'h-4 w-4') ?> Cotizar por WhatsApp
    </a>
</article>

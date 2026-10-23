<?php
/**
 * Franja de beneficios bajo el banner principal.
 */

$beneficios = [
    ['celular', 'Pago con SINPE Móvil', 'o transferencia bancaria'],
    ['camion',  'Envíos en la GAM',     'Entrega en ' . ajuste('envio_plazo', '48 horas')],
    ['escudo',  'Productos originales', 'Marcas de confianza'],
    ['chat',    'Asesoría por WhatsApp', 'Te ayudamos a elegir'],
];
?>
<section class="border-y border-white/10 bg-black/40" aria-label="Beneficios">
    <ul class="contenedor grid grid-cols-2 gap-x-4 gap-y-6 py-6 lg:grid-cols-4 lg:divide-x lg:divide-white/10">
        <?php foreach ($beneficios as [$icono, $titulo, $texto]): ?>
            <li class="flex items-center gap-3 lg:justify-center lg:px-4">
                <span class="shrink-0 text-rivera-rojo"><?= icono($icono, 'h-8 w-8') ?></span>
                <span>
                    <span class="block text-sm font-semibold"><?= e($titulo) ?></span>
                    <span class="block text-xs text-neutral-400"><?= e($texto) ?></span>
                </span>
            </li>
        <?php endforeach; ?>
    </ul>
</section>

<?php
/**
 * Franja de beneficios bajo la portada: solo texto con numeración roja (sin íconos).
 */

$beneficios = [
    ['Pago con SINPE Móvil',  'o transferencia bancaria'],
    ['Envíos en la GAM',      'Entrega en ' . ajuste('envio_plazo', '48 horas')],
    ['Productos originales',  'Marcas de confianza'],
    ['Asesoría por WhatsApp', 'Te ayudamos a elegir'],
];
?>
<section class="border-b border-white/10 bg-[#111111]" aria-label="Beneficios">
    <ol class="escalonado contenedor grid grid-cols-1 divide-y divide-white/10 sm:grid-cols-2 sm:divide-y-0 lg:grid-cols-4 lg:divide-x">
        <?php foreach ($beneficios as $i => [$titulo, $texto]): ?>
            <li class="flex gap-4 py-5 sm:py-7 lg:px-6 lg:first:pl-0 lg:last:pr-0">
                <span class="font-display text-sm font-extrabold italic text-rivera-rojo-claro" aria-hidden="true"><?= sprintf('%02d', $i + 1) ?></span>
                <span>
                    <span class="block text-[0.95rem] font-semibold"><?= e($titulo) ?></span>
                    <span class="mt-0.5 block text-sm text-neutral-400"><?= e($texto) ?></span>
                </span>
            </li>
        <?php endforeach; ?>
    </ol>
</section>

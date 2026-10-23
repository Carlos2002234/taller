<?php
/**
 * Explicación corta de cómo se compra (pagos por SINPE/transferencia con verificación manual).
 */

$pasos = [
    ['carrito', 'Elige tus productos', 'Agrégalos al carrito y confirma tu pedido.'],
    ['celular', 'Paga por SINPE o transferencia', 'Te damos el monto exacto y tu número de pedido.'],
    ['pedido', 'Sube tu comprobante', 'Desde "Mi pedido", en el celular o la computadora.'],
    ['camion', 'Retira o recibe', 'Verificamos el pago y lo retiras en el taller o te lo enviamos en la GAM.'],
];
?>
<section class="contenedor py-20 sm:py-24">
    <div class="aparecer">
        <?php componente('titulo-seccion', ['etiqueta' => 'Así de fácil', 'titulo' => 'Cómo comprar', 'titulo_rojo' => 'en 4 pasos']); ?>
    </div>
    <ol class="escalonado grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <?php foreach ($pasos as $i => [$icono, $titulo, $texto]): ?>
            <li class="tarjeta relative overflow-hidden p-6 before:absolute before:inset-x-0 before:top-0 before:h-0.5 before:bg-gradient-to-r before:from-rivera-rojo before:to-transparent before:content-['']">
                <span class="absolute right-4 top-3 font-display text-4xl font-extrabold text-white/5"><?= $i + 1 ?></span>
                <span class="text-rivera-rojo"><?= icono($icono, 'h-8 w-8') ?></span>
                <h3 class="mt-3 font-sans text-base font-semibold"><?= e($titulo) ?></h3>
                <p class="mt-1 text-sm text-neutral-400"><?= e($texto) ?></p>
            </li>
        <?php endforeach; ?>
    </ol>
</section>

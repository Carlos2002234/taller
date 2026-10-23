<?php
/**
 * Línea de tiempo del estado del pedido.
 * Datos: $estado (estado actual del pedido)
 */

$pasos = [
    'pendiente_pago'      => 'Pendiente de pago',
    'comprobante_enviado' => 'Comprobante enviado',
    'pagado'              => 'Pago verificado',
    'en_preparacion'      => 'En preparación',
    'entregado'           => 'Entregado',
];
$posicion = array_search($estado, array_keys($pasos), true);
?>
<?php if ($posicion === false): ?>
    <div class="tarjeta-clara flex items-center gap-3 p-4">
        <span class="text-rivera-rojo"><?= icono('alerta', 'h-6 w-6') ?></span>
        <p class="font-semibold"><?= $estado === 'cancelado' ? 'Pedido cancelado' : 'Pedido rechazado' ?></p>
    </div>
<?php else: ?>
    <ol class="tarjeta-clara grid grid-cols-5 gap-1 p-4 sm:p-5" aria-label="Estado del pedido">
        <?php foreach (array_values($pasos) as $i => $texto): ?>
            <?php $hecho = $i <= $posicion; ?>
            <li class="relative flex flex-col items-center text-center" <?= $i === $posicion ? 'aria-current="step"' : '' ?>>
                <?php if ($i > 0): ?>
                    <span class="absolute right-1/2 top-3.5 -z-0 h-0.5 w-full <?= $hecho ? 'bg-rivera-rojo' : 'bg-neutral-200' ?>" aria-hidden="true"></span>
                <?php endif; ?>
                <span class="relative z-10 flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold
                             <?= $hecho ? 'bg-rivera-rojo text-white' : 'border-2 border-neutral-200 bg-white text-neutral-400' ?>
                             <?= $i === $posicion ? 'ring-4 ring-rivera-rojo/20' : '' ?>">
                    <?= $hecho && $i < $posicion ? '✓' : $i + 1 ?>
                </span>
                <span class="mt-2 text-[11px] leading-tight sm:text-xs <?= $i === $posicion ? 'font-semibold text-rivera-negro' : 'text-neutral-500' ?>">
                    <?= e($texto) ?>
                </span>
            </li>
        <?php endforeach; ?>
    </ol>
<?php endif; ?>

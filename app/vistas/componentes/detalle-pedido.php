<?php
/**
 * Detalle del pedido: productos, totales, entrega y datos del cliente.
 * Datos: $pedido, $items
 */
?>
<div class="tarjeta-clara p-5 text-sm sm:p-6">
    <ul class="space-y-2">
        <?php foreach ($items as $item): ?>
            <li class="flex justify-between gap-4">
                <span><?= e($item['nombre_snapshot']) ?> <span class="text-neutral-500">× <?= (int) $item['cantidad'] ?></span></span>
                <span class="shrink-0 font-semibold"><?= colones($item['precio_snapshot'] * $item['cantidad']) ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
    <dl class="mt-4 space-y-1.5 border-t border-neutral-200 pt-4">
        <div class="flex justify-between"><dt class="text-neutral-600">Subtotal</dt><dd><?= colones($pedido['subtotal']) ?></dd></div>
        <div class="flex justify-between">
            <dt class="text-neutral-600">Envío<?= $pedido['zona_envio_nombre'] ? ' (' . e($pedido['zona_envio_nombre']) . ')' : '' ?></dt>
            <dd><?= (float) $pedido['costo_envio'] > 0 ? colones($pedido['costo_envio']) : 'Gratis' ?></dd>
        </div>
        <div class="flex justify-between text-base font-bold"><dt>Total</dt><dd><?= colones($pedido['total']) ?></dd></div>
    </dl>
    <div class="mt-4 space-y-1 border-t border-neutral-200 pt-4 text-neutral-700">
        <p><strong>Entrega:</strong>
            <?= $pedido['tipo_entrega'] === 'envio'
                ? 'Envío a domicilio — ' . e($pedido['direccion'])
                : 'Retiro en el taller — ' . e(ajuste('direccion')) ?></p>
        <p><strong>A nombre de:</strong> <?= e($pedido['nombre_cliente']) ?> · <?= e($pedido['telefono']) ?></p>
        <?php if ($pedido['notas']): ?>
            <p><strong>Notas:</strong> <?= e($pedido['notas']) ?></p>
        <?php endif; ?>
    </div>
</div>

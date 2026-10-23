<?php
/**
 * Instrucciones de pago (SINPE Móvil / transferencia) con el monto exacto.
 * Los datos de pago salen de settings (editables en el admin); los vacíos o "PENDIENTE" no se muestran.
 * Datos: $pedido (numero_pedido, total, expira_en)
 */

$configurado = fn (string $clave): ?string =>
    ($v = trim(ajuste($clave))) !== '' && strtoupper($v) !== 'PENDIENTE' ? $v : null;

$sinpe = [
    'Número'  => $configurado('sinpe_numero'),
    'A nombre de' => $configurado('sinpe_titular'),
];
$banco = [
    'Banco'   => $configurado('banco_nombre'),
    'Cuenta IBAN' => $configurado('banco_iban'),
    'A nombre de' => $configurado('banco_titular'),
    'Cédula'  => $configurado('banco_cedula'),
];
$hayBanco = (bool) $configurado('banco_iban');
$vence = date_create($pedido['expira_en']);
?>
<div class="space-y-4">
    <div class="tarjeta-clara p-5 sm:p-6">
        <p class="text-sm text-neutral-600">Monto exacto a pagar</p>
        <div class="mt-1 flex items-center gap-3">
            <p class="font-display text-3xl font-extrabold"><?= colones($pedido['total']) ?></p>
            <button type="button" class="btn-claro btn-pequeno" data-copiar="<?= (int) round((float) $pedido['total']) ?>">Copiar</button>
        </div>
        <p class="mt-3 text-sm text-neutral-700">
            En el detalle o descripción del pago escribe tu número de pedido:
            <strong class="whitespace-nowrap"><?= e($pedido['numero_pedido']) ?></strong>
        </p>
    </div>

    <div class="grid gap-4 <?= $hayBanco ? 'sm:grid-cols-2' : '' ?>">
        <div class="tarjeta-clara p-5">
            <h3 class="flex items-center gap-2 font-display text-base font-bold">
                <span class="text-rivera-rojo"><?= icono('celular', 'h-5 w-5') ?></span> SINPE Móvil
            </h3>
            <dl class="mt-3 space-y-2 text-sm">
                <?php foreach (array_filter($sinpe) as $etiqueta => $valor): ?>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-neutral-600"><?= e($etiqueta) ?></dt>
                        <dd class="flex items-center gap-2 font-semibold">
                            <?= e($valor) ?>
                            <?php if ($etiqueta === 'Número'): ?>
                                <button type="button" class="text-xs font-semibold text-rivera-rojo hover:underline" data-copiar="<?= e(preg_replace('/\D/', '', $valor)) ?>">Copiar</button>
                            <?php endif; ?>
                        </dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        </div>

        <?php if ($hayBanco): ?>
            <div class="tarjeta-clara p-5">
                <h3 class="flex items-center gap-2 font-display text-base font-bold">
                    <span class="text-rivera-rojo"><?= icono('escudo', 'h-5 w-5') ?></span> Transferencia bancaria
                </h3>
                <dl class="mt-3 space-y-2 text-sm">
                    <?php foreach (array_filter($banco) as $etiqueta => $valor): ?>
                        <div>
                            <dt class="text-neutral-600"><?= e($etiqueta) ?></dt>
                            <dd class="flex items-center gap-2 break-all font-semibold">
                                <?= e($valor) ?>
                                <?php if ($etiqueta === 'Cuenta IBAN'): ?>
                                    <button type="button" class="text-xs font-semibold text-rivera-rojo hover:underline" data-copiar="<?= e(preg_replace('/\s/', '', $valor)) ?>">Copiar</button>
                                <?php endif; ?>
                            </dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            </div>
        <?php endif; ?>
    </div>

    <ol class="tarjeta-clara space-y-3 p-5 text-sm text-neutral-700 sm:p-6">
        <li class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-rivera-rojo text-xs font-bold text-white">1</span>
            <span>Haz el pago por el monto exacto y escribe <strong class="whitespace-nowrap"><?= e($pedido['numero_pedido']) ?></strong> en el detalle.</span></li>
        <li class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-rivera-rojo text-xs font-bold text-white">2</span>
            <span>Guarda la captura o el comprobante del pago.</span></li>
        <li class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-rivera-rojo text-xs font-bold text-white">3</span>
            <span>Súbelo en <a href="<?= e(url('mi-pedido')) ?>" class="font-semibold text-rivera-rojo hover:underline">Mi pedido</a> con tu número de pedido y tu teléfono.</span></li>
        <li class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-rivera-rojo text-xs font-bold text-white">4</span>
            <span>Verificamos el ingreso en nuestra cuenta y preparamos tu pedido.</span></li>
    </ol>

    <?php if ($vence): ?>
        <p class="text-center text-xs text-neutral-500">
            Tienes hasta el <strong><?= e($vence->format('d/m/Y')) ?> a las <?= e($vence->format('g:i a')) ?></strong> para pagar.
            Si no recibimos el pago, el pedido se cancela automáticamente.
        </p>
    <?php endif; ?>
</div>

<?php
/**
 * Confirmación del pedido recién creado: número, monto exacto e instrucciones de pago.
 * Solo la ve quien acaba de hacer el pedido (ID guardado en su sesión);
 * después puede consultarlo en "Mi pedido" con número + teléfono.
 */

require_once RAIZ . '/app/modelos/pedidos.php';

iniciar_sesion();
$pedido = isset($_SESSION['pedido_confirmado']) ? pedido_por_id((int) $_SESSION['pedido_confirmado']) : null;

$meta['titulo'] = $pedido ? 'Pedido ' . $pedido['numero_pedido'] : 'Pedido';
$meta['noindex'] = true;
$meta['fondo_claro'] = true;

if (!$pedido): ?>
    <div class="contenedor py-16 text-center">
        <h1 class="text-2xl">No encontramos un pedido reciente</h1>
        <p class="mt-3 text-neutral-600">Si ya hiciste tu pedido, puedes consultarlo con tu número de pedido y tu teléfono.</p>
        <a href="<?= e(url('mi-pedido')) ?>" class="btn-primario mt-6">Consultar mi pedido</a>
    </div>
<?php return; endif;

$items = pedido_items((int) $pedido['id']);
?>
<div class="contenedor max-w-3xl py-8 sm:py-10" data-vaciar-carrito>
    <div class="text-center">
        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600"><?= icono('exito', 'h-8 w-8') ?></span>
        <h1 class="mt-4 font-display text-2xl font-extrabold sm:text-3xl">¡Recibimos tu pedido!</h1>
        <p class="mt-2 text-neutral-600">Tu número de pedido es</p>
        <p class="mt-1 font-display text-3xl font-extrabold tracking-wide text-rivera-rojo sm:text-4xl"><?= e($pedido['numero_pedido']) ?></p>
        <p class="mt-3 text-sm text-neutral-600">Guárdalo: lo necesitas para pagar y para consultar tu pedido.</p>
    </div>

    <div class="mt-6">
        <?php componente('aviso', ['tipo' => 'info', 'mensaje' => 'Tu pedido está pendiente de pago. Lo preparamos cuando verifiquemos el pago en nuestra cuenta.']); ?>
    </div>

    <h2 class="mt-8 text-xl">Cómo pagar</h2>
    <div class="mt-4">
        <?php componente('instrucciones-pago', ['pedido' => $pedido]); ?>
    </div>

    <h2 class="mt-10 text-xl">Detalle del pedido</h2>
    <div class="tarjeta-clara mt-4 p-5 text-sm sm:p-6">
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
        <div class="mt-4 border-t border-neutral-200 pt-4 text-neutral-700">
            <p><strong>Entrega:</strong>
                <?= $pedido['tipo_entrega'] === 'envio'
                    ? 'Envío a domicilio — ' . e($pedido['direccion'])
                    : 'Retiro en el taller — ' . e(ajuste('direccion')) ?></p>
            <p class="mt-1"><strong>A nombre de:</strong> <?= e($pedido['nombre_cliente']) ?> · <?= e($pedido['telefono']) ?></p>
        </div>
    </div>

    <p class="mt-8 text-center text-sm text-neutral-600">
        ¿Dudas? <a href="<?= e(whatsapp_url('Hola, tengo una consulta sobre mi pedido ' . $pedido['numero_pedido'])) ?>" target="_blank" rel="noopener" class="font-semibold text-rivera-rojo hover:underline">Escríbenos por WhatsApp</a>
    </p>
</div>

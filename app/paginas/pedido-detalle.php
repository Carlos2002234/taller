<?php
/**
 * Detalle de un pedido para el cliente: estado, pago y subida del comprobante.
 * Solo accesible si el visitante creó el pedido o lo consultó con número + teléfono (sesión).
 * Recibe $parametros['numero'] (en minúsculas en la URL).
 */

require_once RAIZ . '/app/modelos/pedidos.php';
require_once RAIZ . '/app/modelos/comprobantes.php';

$pedido = pedido_por_numero(strtoupper($parametros['numero']));
if (!$pedido || !pedido_autorizado((int) $pedido['id'])) {
    // Sin autorización no se revela si el pedido existe: se pide número + teléfono
    redirigir('mi-pedido?numero=' . rawurlencode(strtoupper($parametros['numero'])));
}

$urlPedido = 'mi-pedido/' . strtolower($pedido['numero_pedido']);
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Si el archivo supera el límite del servidor (post_max_size), PHP descarta todo el formulario
    if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $error = 'El archivo pesa más de 5 MB. Sube una captura de pantalla o un PDF más liviano.';
    } else {
        verificar_csrf();
        // El pedido pudo vencer: primero se procesan los vencidos
        liberar_pedidos_vencidos();
        $error = guardar_comprobante(pedido_por_id((int) $pedido['id']), $_FILES['comprobante'] ?? []);
        if ($error === null) {
            iniciar_sesion();
            $_SESSION['aviso'] = 'Recibimos tu comprobante. Lo revisaremos y te confirmaremos el pago.';
            redirigir($urlPedido);
        }
    }
    http_response_code(422);
    $pedido = pedido_por_id((int) $pedido['id']);
}

iniciar_sesion();
$aviso = $_SESSION['aviso'] ?? null;
unset($_SESSION['aviso']);

$items = pedido_items((int) $pedido['id']);
$comprobante = comprobante_ultimo((int) $pedido['id']);
$puedeSubir = comprobante_permitido($pedido);
$historial = pedido_historial((int) $pedido['id']);
$ultimoCambio = end($historial) ?: null;
$fecha = fn (string $f): string => date_create($f)->format('d/m/Y g:i a');

$meta['titulo'] = 'Pedido ' . $pedido['numero_pedido'];
$meta['noindex'] = true;
$meta['fondo_claro'] = true;
?>
<div class="contenedor max-w-3xl py-8 sm:py-10">
    <a href="<?= e(url('mi-pedido')) ?>" class="inline-flex items-center gap-1 text-sm font-medium text-neutral-600 hover:text-rivera-negro">
        <span class="rotate-180"><?= icono('flecha', 'h-4 w-4') ?></span> Consultar otro pedido
    </a>
    <div class="mt-3 flex flex-wrap items-end justify-between gap-2">
        <div>
            <p class="text-sm text-neutral-600">Pedido</p>
            <h1 class="font-display text-3xl font-extrabold tracking-wide"><?= e($pedido['numero_pedido']) ?></h1>
        </div>
        <p class="text-sm text-neutral-500">Hecho el <?= e($fecha($pedido['creado_en'])) ?></p>
    </div>

    <div class="mt-6">
        <?php componente('estado-pedido', ['estado' => $pedido['estado']]); ?>
    </div>

    <div class="mt-4 space-y-3">
        <?php if ($aviso): ?>
            <?php componente('aviso', ['tipo' => 'exito', 'mensaje' => $aviso]); ?>
        <?php endif; ?>
        <?php if ($error): ?>
            <?php componente('aviso', ['tipo' => 'error', 'mensaje' => $error]); ?>
        <?php endif; ?>

        <?php switch ($pedido['estado']):
            case 'pendiente_pago': ?>
                <?php if ($comprobante && $comprobante['estado_revision'] === 'rechazado'): ?>
                    <?php componente('aviso', ['tipo' => 'error', 'mensaje' => 'Tu comprobante fue rechazado'
                        . ($comprobante['nota_revision'] ? '. Motivo: ' . rtrim($comprobante['nota_revision'], '. ') . '.' : '.')
                        . ' Puedes subir uno nuevo.']); ?>
                <?php endif; ?>
                <?php break;
            case 'comprobante_enviado': ?>
                <?php if (!$aviso) componente('aviso',['tipo' => 'info', 'mensaje' => 'Recibimos tu comprobante el ' . $fecha($comprobante['subido_en'] ?? $pedido['actualizado_en'])
                    . '. Estamos verificando el pago en nuestra cuenta; te confirmaremos pronto.']); ?>
                <?php break;
            case 'pagado': ?>
                <?php componente('aviso', ['tipo' => 'exito', 'mensaje' => 'Pago verificado. Pronto empezaremos a preparar tu pedido.']); ?>
                <?php break;
            case 'en_preparacion': ?>
                <?php componente('aviso', ['tipo' => 'exito', 'mensaje' => $pedido['tipo_entrega'] === 'envio'
                    ? 'Estamos preparando tu pedido para el envío.'
                    : 'Estamos preparando tu pedido. Te avisaremos cuando esté listo para retirar.']); ?>
                <?php break;
            case 'entregado': ?>
                <?php componente('aviso', ['tipo' => 'exito', 'mensaje' => '¡Pedido entregado! Gracias por comprar en ' . ajuste('negocio_nombre', 'Rivera Grupo Automotriz') . '.']); ?>
                <?php break;
            default: ?>
                <?php componente('aviso', ['tipo' => 'error', 'mensaje' => ($pedido['estado'] === 'cancelado' ? 'Este pedido fue cancelado.' : 'Este pedido fue rechazado.')
                    . ($ultimoCambio && $ultimoCambio['nota'] ? ' Motivo: ' . $ultimoCambio['nota'] : '')]); ?>
        <?php endswitch; ?>
    </div>

    <?php if ($puedeSubir): ?>
        <section class="mt-8">
            <h2 class="text-xl">Sube tu comprobante</h2>
            <form method="post" action="<?= e(url($urlPedido)) ?>" enctype="multipart/form-data" class="tarjeta-clara mt-4 p-5 sm:p-6" data-subir-comprobante>
                <?= csrf_campo() ?>
                <label for="comprobante" class="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-neutral-300 px-4 py-8 text-center transition-colors hover:border-rivera-rojo hover:bg-red-50/30">
                    <span class="text-rivera-rojo"><?= icono('celular', 'h-10 w-10') ?></span>
                    <span class="mt-3 text-sm font-semibold" data-nombre-archivo>Toca aquí para elegir la captura o el PDF</span>
                    <span class="mt-1 text-xs text-neutral-500">JPG, PNG o PDF · máximo 5 MB</span>
                    <input id="comprobante" name="comprobante" type="file" accept="image/jpeg,image/png,application/pdf,.jpg,.jpeg,.png,.pdf" required class="sr-only">
                </label>
                <button type="submit" class="btn-primario mt-4 w-full text-base">Enviar comprobante</button>
                <p class="mt-3 text-center text-xs text-neutral-500">Solo puedes subir un comprobante. Revísalo antes de enviarlo.</p>
            </form>
        </section>

        <h2 class="mt-10 text-xl">Cómo pagar</h2>
        <div class="mt-4">
            <?php componente('instrucciones-pago', ['pedido' => $pedido]); ?>
        </div>
    <?php endif; ?>

    <h2 class="mt-10 text-xl">Detalle del pedido</h2>
    <div class="mt-4">
        <?php componente('detalle-pedido', ['pedido' => $pedido, 'items' => $items]); ?>
    </div>

    <p class="mt-8 text-center text-sm text-neutral-600">
        ¿Dudas? <a href="<?= e(whatsapp_url('Hola, tengo una consulta sobre mi pedido ' . $pedido['numero_pedido'])) ?>" target="_blank" rel="noopener" class="font-semibold text-rivera-rojo hover:underline">Escríbenos por WhatsApp</a>
    </p>
</div>

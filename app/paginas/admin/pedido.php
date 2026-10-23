<?php
/**
 * Detalle de un pedido en el admin: comprobante, aprobación de pago y cambios de estado.
 * JAMÁS se aprueba un pago sin comprobante (ver aprobar_pago()).
 */

require_once RAIZ . '/app/modelos/pedidos-admin.php';

$usuario = requerir_admin();
$meta['layout'] = 'admin/layout';

$pedido = pedido_por_numero(strtoupper($parametros['numero'])) ?? no_encontrado();
$id = (int) $pedido['id'];
$urlPedido = 'admin/pedidos/' . strtolower($pedido['numero_pedido']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $accion = is_string($_POST['accion'] ?? null) ? $_POST['accion'] : '';
    $motivo = is_string($_POST['motivo'] ?? null) && mb_check_encoding($_POST['motivo'], 'UTF-8')
        ? mb_substr(trim(preg_replace('/\s+/', ' ', $_POST['motivo'])), 0, 200) : '';
    $pedirMotivo = in_array($accion, ['rechazar_comprobante', 'rechazar_pedido', 'cancelar'], true);

    if ($pedirMotivo && mb_strlen($motivo) < 5) {
        avisar('error', 'Escribe el motivo (el cliente lo verá).');
        redirigir($urlPedido);
    }

    $estadoAntes = $pedido['estado'];
    $error = match ($accion) {
        'aprobar'              => aprobar_pago($id, $usuario, isset($_POST['verifique'])),
        'rechazar_comprobante' => rechazar_comprobante($id, $usuario, $motivo),
        'rechazar_pedido'      => rechazar_pedido($id, $usuario, $motivo),
        'cancelar'             => cancelar_pedido($id, $usuario, $motivo),
        'preparacion'          => marcar_en_preparacion($id, $usuario),
        'entregado'            => marcar_entregado($id, $usuario),
        'notas'                => (function () use ($id) {
            $notas = is_string($_POST['notas_internas'] ?? null) && mb_check_encoding($_POST['notas_internas'], 'UTF-8')
                ? mb_substr(trim($_POST['notas_internas']), 0, 2000) : '';
            guardar_notas_internas($id, $notas);
            return null;
        })(),
        default                => 'Acción no válida.',
    };

    if ($error) {
        avisar('error', $error);
    } else {
        avisar('exito', match ($accion) {
            'aprobar'              => 'Pago aprobado. El pedido está listo para preparar.',
            'rechazar_comprobante' => 'Comprobante rechazado. El cliente verá el motivo y tiene ' . HORAS_TRAS_RECHAZO . ' h para subir otro.',
            'rechazar_pedido'      => 'Pedido rechazado y stock devuelto al inventario.',
            'cancelar'             => 'Pedido cancelado y stock devuelto al inventario.',
            'preparacion'          => 'Pedido marcado en preparación.',
            'entregado'            => 'Pedido marcado como entregado.',
            'notas'                => 'Notas internas guardadas.',
        });
        if ($accion === 'cancelar' && in_array($estadoAntes, ['pagado', 'en_preparacion'], true)) {
            avisar('info', 'Este pedido ya estaba pagado: recuerda devolverle el dinero al cliente.');
        }
    }
    redirigir($urlPedido);
}

$items = pedido_items($id);
$comprobantes = pedido_comprobantes($id);
$comprobante = $comprobantes[0] ?? null;
$historial = pedido_historial_admin($id);
$esDuenio = es_duenio($usuario);
$estado = $pedido['estado'];
$fecha = fn (?string $f): string => $f ? date_create($f)->format('d/m/Y g:i a') : '—';
$meta['titulo'] = 'Pedido ' . $pedido['numero_pedido'];

$mensajesWhatsapp = [
    'pendiente_pago'      => "Hola {$pedido['nombre_cliente']}, te escribimos de " . ajuste('negocio_nombre') . " sobre tu pedido {$pedido['numero_pedido']}.",
    'comprobante_enviado' => "Hola {$pedido['nombre_cliente']}, recibimos tu comprobante del pedido {$pedido['numero_pedido']}. Lo estamos verificando.",
    'pagado'              => "Hola {$pedido['nombre_cliente']}, verificamos tu pago del pedido {$pedido['numero_pedido']}. ¡Gracias! Te avisaremos cuando esté listo.",
    'en_preparacion'      => "Hola {$pedido['nombre_cliente']}, tu pedido {$pedido['numero_pedido']} está en preparación.",
    'entregado'           => "Hola {$pedido['nombre_cliente']}, gracias por tu compra (pedido {$pedido['numero_pedido']}).",
];
$colores = [
    'pendiente_pago' => 'bg-amber-100 text-amber-900', 'comprobante_enviado' => 'bg-rivera-rojo text-white',
    'pagado' => 'bg-emerald-100 text-emerald-900', 'en_preparacion' => 'bg-sky-100 text-sky-900',
    'entregado' => 'bg-neutral-800 text-white', 'cancelado' => 'bg-neutral-200 text-neutral-600', 'rechazado' => 'bg-neutral-200 text-neutral-600',
];
$revision = ['pendiente' => 'Pendiente de revisión', 'aprobado' => 'Aprobado', 'rechazado' => 'Rechazado'];
?>
<a href="<?= e(url('admin/pedidos')) ?>" class="inline-flex items-center gap-1 text-sm font-medium text-neutral-600 hover:text-rivera-negro">
    <span class="rotate-180"><?= icono('flecha', 'h-4 w-4') ?></span> Pedidos
</a>
<div class="mt-2 flex flex-wrap items-center gap-3">
    <h1 class="font-display text-2xl font-extrabold tracking-wide sm:text-3xl"><?= e($pedido['numero_pedido']) ?></h1>
    <span class="insignia <?= $colores[$estado] ?> text-xs"><?= e(ESTADOS_PEDIDO[$estado]) ?></span>
</div>
<p class="mt-1 text-sm text-neutral-600">
    Hecho el <?= e($fecha($pedido['creado_en'])) ?>
    <?php if ($estado === 'pendiente_pago'): ?> · vence el <?= e($fecha($pedido['expira_en'])) ?><?php endif; ?>
</p>

<div class="mt-6 grid gap-5 lg:grid-cols-[1fr_22rem]">
    <div class="space-y-5">
        <!-- Comprobante -->
        <section class="tarjeta-clara p-5 sm:p-6">
            <h2 class="text-lg">Comprobante de pago</h2>
            <?php if (!$comprobante): ?>
                <p class="mt-3 text-sm text-neutral-600">El cliente todavía no ha subido un comprobante. <strong>No se puede aprobar el pago sin comprobante.</strong></p>
            <?php else: ?>
                <?php $urlArchivo = url('admin/comprobantes/' . $comprobante['id']); ?>
                <div class="mt-4 overflow-hidden rounded-lg border border-neutral-200 bg-neutral-50">
                    <?php if ($comprobante['mime'] === 'application/pdf'): ?>
                        <a href="<?= e($urlArchivo) ?>" target="_blank" rel="noopener" class="flex items-center justify-center gap-3 p-10 text-sm font-semibold text-rivera-rojo hover:underline">
                            <?= icono('pedido', 'h-8 w-8') ?> Abrir comprobante (PDF)
                        </a>
                    <?php else: ?>
                        <a href="<?= e($urlArchivo) ?>" target="_blank" rel="noopener" title="Abrir en tamaño completo">
                            <img src="<?= e($urlArchivo) ?>" alt="Comprobante del pedido <?= e($pedido['numero_pedido']) ?>" class="mx-auto max-h-[32rem] w-auto object-contain">
                        </a>
                    <?php endif; ?>
                </div>
                <dl class="mt-3 grid gap-x-4 gap-y-1 text-sm sm:grid-cols-2">
                    <div><dt class="inline text-neutral-500">Subido:</dt> <dd class="inline"><?= e($fecha($comprobante['subido_en'])) ?></dd></div>
                    <div><dt class="inline text-neutral-500">Estado:</dt> <dd class="inline font-semibold"><?= e($revision[$comprobante['estado_revision']]) ?></dd></div>
                    <?php if ($comprobante['revisor']): ?>
                        <div class="sm:col-span-2"><dt class="inline text-neutral-500">Revisado por:</dt> <dd class="inline"><?= e($comprobante['revisor']) ?> · <?= e($fecha($comprobante['revisado_en'])) ?></dd></div>
                    <?php endif; ?>
                    <?php if ($comprobante['nota_revision']): ?>
                        <div class="sm:col-span-2"><dt class="inline text-neutral-500">Motivo del rechazo:</dt> <dd class="inline"><?= e($comprobante['nota_revision']) ?></dd></div>
                    <?php endif; ?>
                </dl>
                <?php if (count($comprobantes) > 1): ?>
                    <details class="mt-3 text-sm">
                        <summary class="cursor-pointer font-medium text-neutral-700">Comprobantes anteriores (<?= count($comprobantes) - 1 ?>)</summary>
                        <ul class="mt-2 space-y-1">
                            <?php foreach (array_slice($comprobantes, 1) as $anterior): ?>
                                <li><a href="<?= e(url('admin/comprobantes/' . $anterior['id'])) ?>" target="_blank" rel="noopener" class="text-rivera-rojo hover:underline"><?= e($fecha($anterior['subido_en'])) ?></a>
                                    — <?= e($revision[$anterior['estado_revision']]) ?><?= $anterior['nota_revision'] ? ': ' . e($anterior['nota_revision']) : '' ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </details>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <!-- Acciones -->
        <?php if (!in_array($estado, ['entregado', 'cancelado', 'rechazado'], true)): ?>
            <section class="tarjeta-clara space-y-4 p-5 sm:p-6">
                <h2 class="text-lg">Acciones</h2>

                <?php if ($estado === 'comprobante_enviado'): ?>
                    <?php if ($esDuenio): ?>
                        <form method="post" action="<?= e(url($urlPedido)) ?>" class="rounded-lg border-2 border-emerald-500 bg-emerald-50/50 p-4">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="accion" value="aprobar">
                            <label class="flex cursor-pointer items-start gap-3 text-sm">
                                <input type="checkbox" name="verifique" required class="mt-0.5 h-5 w-5 shrink-0 accent-emerald-600">
                                <span>Verifiqué en mi cuenta que <strong>entró <?= colones($pedido['total']) ?></strong> (pedido <?= e($pedido['numero_pedido']) ?>). Un comprobante puede ser falso: no basta con la imagen.</span>
                            </label>
                            <button type="submit" class="btn mt-4 w-full bg-emerald-600 text-white hover:bg-emerald-700">Aprobar pago</button>
                        </form>

                        <details class="rounded-lg border border-neutral-200 p-4">
                            <summary class="cursor-pointer text-sm font-semibold">Rechazar comprobante (el cliente puede subir otro)</summary>
                            <form method="post" action="<?= e(url($urlPedido)) ?>" class="mt-3 space-y-3">
                                <?= csrf_campo() ?>
                                <input type="hidden" name="accion" value="rechazar_comprobante">
                                <label for="motivo-comprobante" class="etiqueta-campo">Motivo (lo verá el cliente)</label>
                                <textarea id="motivo-comprobante" name="motivo" rows="2" maxlength="200" required minlength="5" class="campo-claro"
                                          placeholder="Ej. El monto recibido fue ₡5.000 y el pedido es por <?= e(colones($pedido['total'])) ?>."></textarea>
                                <button type="submit" class="btn-claro btn-pequeno bg-white">Rechazar comprobante</button>
                            </form>
                        </details>
                    <?php else: ?>
                        <?php componente('aviso', ['tipo' => 'info', 'mensaje' => 'Solo el dueño puede aprobar o rechazar el pago.']); ?>
                    <?php endif; ?>
                <?php elseif ($estado === 'pendiente_pago'): ?>
                    <p class="text-sm text-neutral-600">Esperando que el cliente pague y suba el comprobante. No se puede aprobar sin comprobante.</p>
                <?php elseif ($estado === 'pagado'): ?>
                    <form method="post" action="<?= e(url($urlPedido)) ?>">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="accion" value="preparacion">
                        <button type="submit" class="btn-primario w-full">Marcar "En preparación"</button>
                    </form>
                <?php elseif ($estado === 'en_preparacion'): ?>
                    <form method="post" action="<?= e(url($urlPedido)) ?>" data-confirmar="¿Confirmas que el pedido se entregó al cliente?">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="accion" value="entregado">
                        <button type="submit" class="btn-primario w-full">Marcar "Entregado"</button>
                    </form>
                <?php endif; ?>

                <?php if ($esDuenio && in_array($estado, ['pendiente_pago', 'comprobante_enviado'], true)): ?>
                    <details class="rounded-lg border border-red-200 p-4">
                        <summary class="cursor-pointer text-sm font-semibold text-rivera-rojo">Rechazar pedido (final, devuelve stock)</summary>
                        <form method="post" action="<?= e(url($urlPedido)) ?>" class="mt-3 space-y-3" data-confirmar="¿Rechazar el pedido? No se puede deshacer.">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="accion" value="rechazar_pedido">
                            <label for="motivo-pedido" class="etiqueta-campo">Motivo (lo verá el cliente)</label>
                            <textarea id="motivo-pedido" name="motivo" rows="2" maxlength="200" required minlength="5" class="campo-claro"></textarea>
                            <button type="submit" class="btn btn-pequeno border border-rivera-rojo text-rivera-rojo hover:bg-rivera-rojo hover:text-white">Rechazar pedido</button>
                        </form>
                    </details>
                <?php endif; ?>

                <?php if ($esDuenio): ?>
                    <details class="rounded-lg border border-red-200 p-4">
                        <summary class="cursor-pointer text-sm font-semibold text-rivera-rojo">Cancelar pedido</summary>
                        <form method="post" action="<?= e(url($urlPedido)) ?>" class="mt-3 space-y-3"
                              data-confirmar="¿Cancelar el pedido?<?= in_array($estado, ['pagado', 'en_preparacion'], true) ? ' Ya está pagado: tendrás que devolverle el dinero al cliente.' : '' ?>">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="accion" value="cancelar">
                            <?php if (in_array($estado, ['pagado', 'en_preparacion'], true)): ?>
                                <?php componente('aviso', ['tipo' => 'error', 'mensaje' => 'Este pedido ya está pagado. Si lo cancelas, debes devolverle el dinero al cliente (el sistema no hace devoluciones).']); ?>
                            <?php endif; ?>
                            <label for="motivo-cancelar" class="etiqueta-campo">Motivo (lo verá el cliente)</label>
                            <textarea id="motivo-cancelar" name="motivo" rows="2" maxlength="200" required minlength="5" class="campo-claro"></textarea>
                            <button type="submit" class="btn btn-pequeno border border-rivera-rojo text-rivera-rojo hover:bg-rivera-rojo hover:text-white">Cancelar pedido</button>
                        </form>
                    </details>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <!-- Detalle -->
        <section>
            <h2 class="mb-3 text-lg">Productos</h2>
            <?php componente('detalle-pedido', ['pedido' => $pedido, 'items' => $items]); ?>
        </section>
    </div>

    <aside class="space-y-5">
        <!-- Cliente -->
        <section class="tarjeta-clara p-5">
            <h2 class="text-base">Cliente</h2>
            <p class="mt-3 font-semibold"><?= e($pedido['nombre_cliente']) ?></p>
            <p class="text-sm text-neutral-600"><?= e($pedido['telefono']) ?><?= $pedido['email'] ? ' · ' . e($pedido['email']) : '' ?></p>
            <div class="mt-3 grid grid-cols-2 gap-2">
                <a href="<?= e(whatsapp_cliente_url($pedido['telefono'], $mensajesWhatsapp[$estado] ?? "Hola {$pedido['nombre_cliente']}, sobre tu pedido {$pedido['numero_pedido']}:")) ?>"
                   target="_blank" rel="noopener" class="btn btn-pequeno bg-green-700 text-white hover:bg-green-800">
                    <?= icono('whatsapp', 'h-4 w-4') ?> WhatsApp
                </a>
                <a href="tel:+506<?= e(preg_replace('/\D/', '', $pedido['telefono'])) ?>" class="btn-claro btn-pequeno bg-white"><?= icono('telefono', 'h-4 w-4') ?> Llamar</a>
            </div>
            <?php if ($pedido['utm_source']): ?>
                <p class="mt-3 text-xs text-neutral-500">Llegó desde: <?= e(implode(' / ', array_filter([$pedido['utm_source'], $pedido['utm_medium'], $pedido['utm_campaign']]))) ?></p>
            <?php endif; ?>
        </section>

        <!-- Notas internas -->
        <form method="post" action="<?= e(url($urlPedido)) ?>" class="tarjeta-clara space-y-3 p-5">
            <?= csrf_campo() ?>
            <input type="hidden" name="accion" value="notas">
            <label for="notas_internas" class="text-base font-display font-bold">Notas internas</label>
            <p class="text-xs text-neutral-500">Solo las ve el equipo, nunca el cliente.</p>
            <textarea id="notas_internas" name="notas_internas" rows="3" maxlength="2000" class="campo-claro"><?= e($pedido['notas_internas'] ?? '') ?></textarea>
            <button type="submit" class="btn-claro btn-pequeno bg-white">Guardar notas</button>
        </form>

        <!-- Historial -->
        <section class="tarjeta-clara p-5">
            <h2 class="text-base">Historial</h2>
            <ol class="mt-3 space-y-3 border-l-2 border-neutral-200 pl-4 text-sm">
                <?php foreach ($historial as $h): ?>
                    <li>
                        <p class="font-semibold"><?= e(ESTADOS_PEDIDO[$h['estado_nuevo']] ?? $h['estado_nuevo']) ?></p>
                        <p class="text-xs text-neutral-500">
                            <?= e($fecha($h['fecha'])) ?> ·
                            <?= e($h['usuario'] ?? ($h['origen'] === 'cliente' ? 'Cliente' : 'Sistema')) ?>
                        </p>
                        <?php if ($h['nota']): ?><p class="mt-0.5 text-neutral-700"><?= e($h['nota']) ?></p><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        </section>
    </aside>
</div>

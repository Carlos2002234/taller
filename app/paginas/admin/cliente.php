<?php
/**
 * Ficha de un cliente: datos, cuánto ha comprado, sus consultas y pedidos, y notas internas.
 * Recibe $parametros['telefono'] ("8888-8888"). Dueño y empleado la ven y escriben notas.
 */

require_once RAIZ . '/app/modelos/clientes.php';
require_once RAIZ . '/app/modelos/consultas.php';
require_once RAIZ . '/app/modelos/pedidos-admin.php';

$usuario = requerir_admin();
$meta['layout'] = 'admin/layout';

$telefono = normalizar_telefono($parametros['telefono'] ?? '') ?? no_encontrado();
$ficha = cliente_por_telefono($telefono) ?? no_encontrado();
$c = $ficha['resumen'] ?? no_encontrado();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $notas = is_string($_POST['notas'] ?? null) && mb_check_encoding($_POST['notas'], 'UTF-8')
        ? mb_substr(trim($_POST['notas']), 0, 3000) : '';
    guardar_notas_cliente($telefono, $notas, (int) $usuario['id']);
    avisar('exito', 'Notas guardadas.');
    redirigir('admin/clientes/' . $telefono);
}

$meta['titulo'] = $c['nombre'];
$esCliente = (int) $c['compras'] > 0;
$primerNombre = explode(' ', $c['nombre'])[0];
$coloresPedido = [
    'pendiente_pago' => 'bg-amber-100 text-amber-900', 'comprobante_enviado' => 'bg-rivera-rojo text-white',
    'pagado' => 'bg-emerald-100 text-emerald-900', 'en_preparacion' => 'bg-sky-100 text-sky-900',
    'entregado' => 'bg-neutral-800 text-white', 'cancelado' => 'bg-neutral-200 text-neutral-600', 'rechazado' => 'bg-neutral-200 text-neutral-600',
];
?>
<a href="<?= e(url('admin/clientes')) ?>" class="inline-flex items-center gap-1 text-sm font-medium text-neutral-600 hover:text-rivera-negro">
    <span class="rotate-180"><?= icono('flecha', 'h-4 w-4') ?></span> Volver a clientes
</a>
<div class="mt-3 flex flex-wrap items-center gap-3">
    <h1 class="font-display text-2xl font-extrabold sm:text-3xl"><?= e($c['nombre']) ?></h1>
    <span class="insignia <?= $esCliente ? 'bg-emerald-100 text-emerald-900' : 'bg-amber-100 text-amber-900' ?>"><?= $esCliente ? 'Cliente' : 'Posible cliente' ?></span>
</div>
<p class="mt-1 text-sm text-neutral-600">
    Primer contacto: <?= e(date_create($c['primera_vez'])->format('d/m/Y')) ?> · Último: <?= e(date_create($c['ultima_vez'])->format('d/m/Y')) ?>
</p>

<!-- Resumen -->
<div class="mt-6 grid grid-cols-3 gap-3">
    <?php foreach ([
        ['Total comprado', $esCliente ? colones($c['total_comprado']) : '—'],
        ['Pedidos pagados', (int) $c['compras'] . ' de ' . (int) $c['pedidos']],
        ['Consultas', (string) (int) $c['consultas']],
    ] as [$texto, $valor]): ?>
        <div class="tarjeta-clara p-4">
            <p class="text-xs text-neutral-600 sm:text-sm"><?= e($texto) ?></p>
            <p class="mt-1 font-display text-lg font-extrabold sm:text-2xl"><?= e($valor) ?></p>
        </div>
    <?php endforeach; ?>
</div>

<div class="mt-5 grid gap-5 lg:grid-cols-3">
    <div class="space-y-5 lg:col-span-2">
        <!-- Notas -->
        <form method="post" action="<?= e(url('admin/clientes/' . $telefono)) ?>" class="tarjeta-clara space-y-3 p-5 sm:p-6">
            <?= csrf_campo() ?>
            <label for="notas" class="block text-base font-display font-bold italic">Notas del cliente</label>
            <p class="-mt-1 text-xs text-neutral-500">Solo las ve el equipo. Ej.: qué carro tiene, cómo prefiere que lo contacten, qué le interesa.</p>
            <textarea id="notas" name="notas" rows="5" maxlength="3000" class="campo-claro"
                      placeholder="Ej. Hyundai Tucson 2019. Prefiere WhatsApp. Le interesa el mantenimiento de 40.000 km."><?= e($ficha['notas']['notas'] ?? '') ?></textarea>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <button type="submit" class="btn-primario">Guardar notas</button>
                <?php if ($ficha['notas']): ?>
                    <p class="text-xs text-neutral-500">Último cambio: <?= e($ficha['notas']['actualizado_nombre'] ?? '—') ?> · <?= e(date_create($ficha['notas']['actualizado_en'])->format('d/m/Y g:i a')) ?></p>
                <?php endif; ?>
            </div>
        </form>

        <!-- Pedidos -->
        <section class="tarjeta-clara p-5 sm:p-6">
            <h2 class="text-base">Pedidos</h2>
            <?php if (!$ficha['pedidos']): ?>
                <p class="mt-2 text-sm text-neutral-600">Todavía no ha hecho pedidos en la tienda.</p>
            <?php else: ?>
                <ul class="mt-3 divide-y divide-neutral-100">
                    <?php foreach ($ficha['pedidos'] as $p): ?>
                        <li>
                            <a href="<?= e(url('admin/pedidos/' . strtolower($p['numero_pedido']))) ?>" class="flex flex-wrap items-center gap-x-3 gap-y-1 py-2.5 hover:text-rivera-rojo">
                                <span class="font-display text-sm font-bold"><?= e($p['numero_pedido']) ?></span>
                                <span class="insignia <?= $coloresPedido[$p['estado']] ?? 'bg-neutral-200' ?>"><?= e(ESTADOS_PEDIDO[$p['estado']] ?? $p['estado']) ?></span>
                                <span class="ml-auto text-sm font-semibold"><?= colones($p['total']) ?></span>
                                <span class="w-full text-xs text-neutral-500 sm:w-auto"><?= e(date_create($p['creado_en'])->format('d/m/Y')) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <!-- Consultas -->
        <section class="tarjeta-clara p-5 sm:p-6">
            <h2 class="text-base">Consultas</h2>
            <?php if (!$ficha['consultas']): ?>
                <p class="mt-2 text-sm text-neutral-600">No ha dejado consultas en la página.</p>
            <?php else: ?>
                <ul class="mt-3 divide-y divide-neutral-100">
                    <?php foreach ($ficha['consultas'] as $q): ?>
                        <li>
                            <a href="<?= e(url('admin/consultas/' . $q['id'])) ?>" class="block py-2.5 hover:text-rivera-rojo">
                                <span class="flex flex-wrap items-center gap-2 text-xs text-neutral-500">
                                    <?= e(date_create($q['creado_en'])->format('d/m/Y')) ?> · <?= e(CONSULTA_ESTADOS[$q['estado']]) ?>
                                </span>
                                <span class="mt-0.5 block truncate text-sm"><?= $q['mensaje'] ? '“' . e($q['mensaje']) . '”' : 'Pidió que lo contacten (sin mensaje).' ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>

    <aside>
        <section class="tarjeta-clara p-5">
            <h2 class="text-base">Contactar</h2>
            <p class="mt-3 text-sm text-neutral-600"><?= e($telefono) ?></p>
            <?php if ($c['email']): ?>
                <p class="break-all text-sm text-neutral-600"><?= e($c['email']) ?></p>
            <?php endif; ?>
            <div class="mt-3 grid grid-cols-2 gap-2">
                <a href="<?= e(whatsapp_cliente_url($telefono, 'Hola ' . $primerNombre . ', le saluda ' . ajuste('negocio_nombre', 'Rivera Grupo Automotriz') . '.')) ?>"
                   target="_blank" rel="noopener" class="btn btn-pequeno bg-green-700 text-white hover:bg-green-800">
                    <?= icono('whatsapp', 'h-4 w-4') ?> WhatsApp
                </a>
                <a href="tel:+506<?= e(preg_replace('/\D/', '', $telefono)) ?>" class="btn-claro btn-pequeno bg-white"><?= icono('telefono', 'h-4 w-4') ?> Llamar</a>
                <?php if ($c['email']): ?>
                    <a href="mailto:<?= e($c['email']) ?>" class="btn-claro btn-pequeno col-span-2 bg-white">Escribir correo</a>
                <?php endif; ?>
            </div>
        </section>
    </aside>
</div>

<?php
/**
 * Inicio del panel: pedidos que necesitan atención, ventas del mes (dueño) y últimos pedidos.
 * Una venta cuenta en el mes en que se APROBÓ el pago (y si después se canceló, deja de contar).
 */

require_once RAIZ . '/app/modelos/pedidos-admin.php';

$usuario = requerir_admin();
$meta['titulo'] = 'Inicio';
$meta['layout'] = 'admin/layout';

$conteos = pedidos_conteos();
$atencion = [
    ['Comprobantes por revisar', (int) ($conteos['comprobante_enviado'] ?? 0), 'pedido', 'por-revisar', true],
    ['Pagados por preparar', (int) ($conteos['pagado'] ?? 0), 'camion', 'pagados', false],
    ['En preparación', (int) ($conteos['en_preparacion'] ?? 0), 'llave', 'preparacion', false],
    ['Pendientes de pago', (int) ($conteos['pendiente_pago'] ?? 0), 'reloj', 'pendientes', false],
];

$esDuenio = es_duenio($usuario);
if ($esDuenio) {
    $mesActual = date('Y-m-01');
    $mesAnterior = date('Y-m-01', strtotime('first day of last month'));
    $ventas = ventas_del_mes($mesActual);
    $ventasAnterior = ventas_del_mes($mesAnterior);
    $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'setiembre', 'octubre', 'noviembre', 'diciembre'];
    $nombreMes = $meses[(int) date('n') - 1];
    $stockBajo = (int) db()->query('SELECT COUNT(*) FROM products WHERE activo = 1 AND stock <= 3')->fetchColumn();
}
$ultimos = pedidos_admin_listar('todos', '', 1)['pedidos'];
$ultimos = array_slice($ultimos, 0, 5);
?>
<h1 class="font-display text-2xl font-extrabold sm:text-3xl">Hola, <?= e(explode(' ', $usuario['nombre'])[0]) ?></h1>
<p class="mt-1 text-sm text-neutral-600">Resumen de tu tienda.</p>

<?php if ($esDuenio): ?>
    <div class="mt-6 grid gap-3 sm:grid-cols-2">
        <div class="rounded-lg bg-rivera-negro p-5 text-white">
            <p class="text-sm text-neutral-400">Ventas de <?= e($nombreMes) ?></p>
            <p class="mt-1 font-display text-3xl font-extrabold"><?= colones($ventas['total']) ?></p>
            <p class="mt-1 text-sm text-neutral-300"><?= (int) $ventas['pedidos'] ?> pedido(s) pagado(s)
                <?php if ((float) $ventasAnterior['total'] > 0): ?>
                    · mes anterior <?= colones($ventasAnterior['total']) ?>
                <?php endif; ?>
            </p>
        </div>
        <div class="tarjeta-clara p-5">
            <p class="text-sm text-neutral-600">Promedio por pedido (<?= e($nombreMes) ?>)</p>
            <p class="mt-1 font-display text-3xl font-extrabold">
                <?= (int) $ventas['pedidos'] > 0 ? colones((float) $ventas['total'] / (int) $ventas['pedidos']) : '—' ?>
            </p>
            <?php if ($stockBajo > 0): ?>
                <a href="<?= e(url('admin/productos?estado=stock-bajo')) ?>" class="mt-1 inline-block text-sm font-medium text-rivera-rojo hover:underline">
                    <?= $stockBajo ?> producto(s) con stock bajo →
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<h2 class="mt-8 text-lg">Necesitan atención</h2>
<div class="mt-3 grid grid-cols-2 gap-3 lg:grid-cols-4">
    <?php foreach ($atencion as [$texto, $numero, $icono, $pestana, $destacar]): ?>
        <a href="<?= e(url('admin/pedidos?ver=' . $pestana)) ?>"
           class="tarjeta-clara flex flex-col gap-3 p-4 transition-colors hover:border-rivera-rojo sm:p-5 <?= $destacar && $numero > 0 ? 'border-rivera-rojo' : '' ?>">
            <span class="flex h-10 w-10 items-center justify-center rounded-full <?= $destacar && $numero > 0 ? 'bg-rivera-rojo text-white' : 'bg-neutral-100 text-neutral-600' ?>">
                <?= icono($icono, 'h-5 w-5') ?>
            </span>
            <span>
                <span class="block font-display text-2xl font-extrabold"><?= $numero ?></span>
                <span class="block text-sm text-neutral-600"><?= e($texto) ?></span>
            </span>
        </a>
    <?php endforeach; ?>
</div>

<div class="mt-8 flex items-center justify-between">
    <h2 class="text-lg">Últimos pedidos</h2>
    <a href="<?= e(url('admin/pedidos?ver=todos')) ?>" class="text-sm font-medium text-rivera-rojo hover:underline">Ver todos →</a>
</div>
<?php if ($ultimos): ?>
    <ul class="tarjeta-clara mt-3 divide-y divide-neutral-200">
        <?php foreach ($ultimos as $p): ?>
            <li>
                <a href="<?= e(url('admin/pedidos/' . strtolower($p['numero_pedido']))) ?>" class="flex items-center gap-3 px-4 py-3 hover:bg-neutral-50">
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold"><?= e($p['numero_pedido']) ?> · <?= e($p['nombre_cliente']) ?></span>
                        <span class="block text-xs text-neutral-500"><?= e(ESTADOS_PEDIDO[$p['estado']]) ?> · <?= e(date_create($p['creado_en'])->format('d/m g:i a')) ?></span>
                    </span>
                    <span class="font-display text-sm font-bold"><?= colones($p['total']) ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <div class="tarjeta-clara mt-3 px-6 py-10 text-center text-sm text-neutral-600">Todavía no hay pedidos.</div>
<?php endif; ?>

<?php if ($esDuenio): ?>
    <div class="mt-6 flex flex-wrap gap-3">
        <a href="<?= e(url('admin/productos/nuevo')) ?>" class="btn-primario">+ Nuevo producto</a>
        <a href="<?= e(url('admin/configuracion')) ?>" class="btn-claro bg-white">Configuración</a>
    </div>
<?php endif; ?>

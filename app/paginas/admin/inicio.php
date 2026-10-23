<?php
/**
 * Inicio del panel: conteos básicos y accesos rápidos.
 * El dashboard completo (ventas del mes, pedidos) llega en el Módulo 7.
 */

$usuario = requerir_admin();
$meta['titulo'] = 'Inicio';
$meta['layout'] = 'admin/layout';

$contar = fn (string $sql): int => (int) db()->query($sql)->fetchColumn();
$tarjetas = [
    ['Comprobantes por revisar', $contar("SELECT COUNT(*) FROM orders WHERE estado = 'comprobante_enviado'"), 'pedido', true],
    ['Pedidos pendientes de pago', $contar("SELECT COUNT(*) FROM orders WHERE estado = 'pendiente_pago'"), 'reloj', false],
    ['Pedidos por preparar', $contar("SELECT COUNT(*) FROM orders WHERE estado IN ('pagado','en_preparacion')"), 'camion', false],
];
if (es_duenio($usuario)) {
    $tarjetas[] = ['Productos visibles', $contar('SELECT COUNT(*) FROM products WHERE activo = 1'), 'tienda', false];
    $tarjetas[] = ['Con stock bajo (≤ 3)', $contar('SELECT COUNT(*) FROM products WHERE activo = 1 AND stock <= 3'), 'alerta', false];
}
?>
<h1 class="font-display text-2xl font-extrabold sm:text-3xl">Hola, <?= e(explode(' ', $usuario['nombre'])[0]) ?></h1>
<p class="mt-1 text-sm text-neutral-600">Resumen de tu tienda.</p>

<div class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-3">
    <?php foreach ($tarjetas as [$texto, $numero, $icono, $destacar]): ?>
        <div class="tarjeta-clara flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:gap-4 sm:p-5 <?= $destacar && $numero > 0 ? 'border-rivera-rojo' : '' ?>">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full sm:h-11 sm:w-11 <?= $destacar && $numero > 0 ? 'bg-rivera-rojo text-white' : 'bg-neutral-100 text-neutral-600' ?>">
                <?= icono($icono, 'h-5 w-5') ?>
            </span>
            <span>
                <span class="block font-display text-2xl font-extrabold"><?= $numero ?></span>
                <span class="block text-sm text-neutral-600"><?= e($texto) ?></span>
            </span>
        </div>
    <?php endforeach; ?>
</div>

<div class="mt-6">
    <?php componente('aviso', ['tipo' => 'info', 'mensaje' => 'La gestión de pedidos y la aprobación de pagos llegan en el próximo módulo.']); ?>
</div>

<?php if (es_duenio($usuario)): ?>
    <div class="mt-6 flex flex-wrap gap-3">
        <a href="<?= e(url('admin/productos/nuevo')) ?>" class="btn-primario">+ Nuevo producto</a>
        <a href="<?= e(url('admin/productos?estado=stock-bajo')) ?>" class="btn-claro bg-white">Ver stock bajo</a>
    </div>
<?php endif; ?>

<?php
/**
 * Lista de pedidos del admin con pestañas por estado, búsqueda y paginación.
 */

require_once RAIZ . '/app/modelos/pedidos-admin.php';

requerir_admin();
$meta['titulo'] = 'Pedidos';
$meta['layout'] = 'admin/layout';

$conteos = pedidos_conteos();
$pestana = is_string($_GET['ver'] ?? null) && isset(PESTANAS_PEDIDOS[$_GET['ver']]) ? $_GET['ver'] : null;
// Por defecto se abre "Por revisar" si hay comprobantes esperando; si no, "Todos"
$pestana ??= ($conteos['comprobante_enviado'] ?? 0) > 0 ? 'por-revisar' : 'todos';
$q = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 60) : '';
$pagina = max(1, ctype_digit((string) ($_GET['pagina'] ?? '')) ? (int) $_GET['pagina'] : 1);

$resultado = pedidos_admin_listar($pestana, $q, $pagina);
$totalPaginas = max(1, (int) ceil($resultado['total'] / PEDIDOS_POR_PAGINA));

$colores = [
    'pendiente_pago'      => 'bg-amber-100 text-amber-900',
    'comprobante_enviado' => 'bg-rivera-rojo text-white',
    'pagado'              => 'bg-emerald-100 text-emerald-900',
    'en_preparacion'      => 'bg-sky-100 text-sky-900',
    'entregado'           => 'bg-neutral-800 text-white',
    'cancelado'           => 'bg-neutral-200 text-neutral-600',
    'rechazado'           => 'bg-neutral-200 text-neutral-600',
];
$enlace = fn (array $extra) => url('admin/pedidos?' . http_build_query(array_filter(['ver' => $pestana, 'q' => $q]) + $extra));
?>
<h1 class="font-display text-2xl font-extrabold sm:text-3xl">Pedidos</h1>

<!-- Pestañas -->
<nav class="-mx-4 mt-5 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:px-0" aria-label="Estados">
    <?php foreach (PESTANAS_PEDIDOS as $clave => [$texto, $estados]): ?>
        <?php
        $cantidad = $estados ? array_sum(array_map(fn ($e) => (int) ($conteos[$e] ?? 0), $estados)) : array_sum(array_map('intval', $conteos));
        $activa = $clave === $pestana;
        ?>
        <a href="<?= e(url('admin/pedidos?' . http_build_query(['ver' => $clave] + ($q !== '' ? ['q' => $q] : [])))) ?>"
           class="flex shrink-0 items-center gap-2 rounded-full border px-3.5 py-1.5 text-sm font-medium
                  <?= $activa ? 'border-rivera-negro bg-rivera-negro text-white' : 'border-neutral-300 bg-white hover:border-rivera-negro' ?>"
           <?= $activa ? 'aria-current="page"' : '' ?>>
            <?= e($texto) ?>
            <span class="rounded-full px-1.5 text-xs <?= $clave === 'por-revisar' && $cantidad > 0 ? 'bg-rivera-rojo text-white' : ($activa ? 'bg-white/20' : 'bg-neutral-100') ?>"><?= $cantidad ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<form method="get" action="<?= e(url('admin/pedidos')) ?>" class="mt-4 flex gap-2">
    <input type="hidden" name="ver" value="<?= e($pestana) ?>">
    <label for="q" class="sr-only">Buscar</label>
    <input id="q" name="q" type="search" class="campo-claro" placeholder="Buscar por número, nombre o teléfono…" value="<?= e($q) ?>">
    <button type="submit" class="btn-claro bg-white">Buscar</button>
</form>

<?php if ($resultado['pedidos']): ?>
    <ul class="mt-4 space-y-2">
        <?php foreach ($resultado['pedidos'] as $p): ?>
            <li>
                <a href="<?= e(url('admin/pedidos/' . strtolower($p['numero_pedido']))) ?>"
                   class="tarjeta-clara flex flex-wrap items-center gap-x-4 gap-y-1 p-4 transition-colors hover:border-rivera-rojo">
                    <span class="min-w-0 flex-1">
                        <span class="flex flex-wrap items-center gap-2">
                            <span class="font-display text-sm font-bold"><?= e($p['numero_pedido']) ?></span>
                            <span class="insignia <?= $colores[$p['estado']] ?>"><?= e(ESTADOS_PEDIDO[$p['estado']]) ?></span>
                        </span>
                        <span class="mt-0.5 block truncate text-sm text-neutral-700">
                            <?= e($p['nombre_cliente']) ?> · <?= e($p['telefono']) ?> · <?= $p['tipo_entrega'] === 'envio' ? 'Envío' : 'Retiro' ?>
                        </span>
                    </span>
                    <span class="text-right">
                        <span class="block font-display font-bold"><?= colones($p['total']) ?></span>
                        <span class="block text-xs text-neutral-500"><?= e(date_create($p['creado_en'])->format('d/m/Y g:i a')) ?></span>
                    </span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php if ($totalPaginas > 1): ?>
        <nav class="mt-6 flex items-center justify-center gap-3 text-sm" aria-label="Páginas">
            <?php if ($pagina > 1): ?><a href="<?= e($enlace(['pagina' => $pagina - 1])) ?>" class="btn-claro btn-pequeno bg-white">← Anterior</a><?php endif; ?>
            <span class="text-neutral-600">Página <?= $pagina ?> de <?= $totalPaginas ?></span>
            <?php if ($pagina < $totalPaginas): ?><a href="<?= e($enlace(['pagina' => $pagina + 1])) ?>" class="btn-claro btn-pequeno bg-white">Siguiente →</a><?php endif; ?>
        </nav>
    <?php endif; ?>
<?php else: ?>
    <div class="tarjeta-clara mt-4 px-6 py-12 text-center text-sm text-neutral-600">
        <?= $q !== '' ? 'No hay pedidos que coincidan con la búsqueda.' : 'No hay pedidos en esta sección.' ?>
    </div>
<?php endif; ?>

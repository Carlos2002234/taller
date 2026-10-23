<?php
/**
 * Lista de productos del admin (incluye ocultos), con buscador y filtros.
 */

require_once RAIZ . '/app/modelos/productos-admin.php';
require_once RAIZ . '/app/modelos/categorias-admin.php';

requerir_admin('duenio');
$meta['titulo'] = 'Productos';
$meta['layout'] = 'admin/layout';

$q = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 100) : '';
$categoriaId = ctype_digit((string) ($_GET['categoria'] ?? '')) ? (int) $_GET['categoria'] : null;
$estados = ['' => 'Todos', 'visibles' => 'Visibles', 'ocultos' => 'Ocultos', 'stock-bajo' => 'Stock bajo (≤ ' . STOCK_BAJO . ')'];
$estado = is_string($_GET['estado'] ?? null) && array_key_exists($_GET['estado'], $estados) ? $_GET['estado'] : '';

$productos = productos_admin_listar($q, $categoriaId, $estado);
$categorias = categorias_admin_listar();
?>
<div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="font-display text-2xl font-extrabold sm:text-3xl">Productos</h1>
    <a href="<?= e(url('admin/productos/nuevo')) ?>" class="btn-primario">+ Nuevo producto</a>
</div>

<form method="get" action="<?= e(url('admin/productos')) ?>" class="tarjeta-clara mt-5 grid gap-3 p-4 sm:grid-cols-[1fr_auto_auto_auto]">
    <label for="q" class="sr-only">Buscar</label>
    <input id="q" name="q" type="search" class="campo-claro" placeholder="Buscar por nombre o marca…" value="<?= e($q) ?>">
    <label for="categoria" class="sr-only">Categoría</label>
    <select id="categoria" name="categoria" class="campo-claro">
        <option value="">Todas las categorías</option>
        <?php foreach ($categorias as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= $categoriaId === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option>
        <?php endforeach; ?>
    </select>
    <label for="estado" class="sr-only">Estado</label>
    <select id="estado" name="estado" class="campo-claro">
        <?php foreach ($estados as $clave => $texto): ?>
            <option value="<?= e($clave) ?>" <?= $estado === $clave ? 'selected' : '' ?>><?= e($texto) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn-claro bg-white">Filtrar</button>
</form>

<p class="mt-4 text-sm text-neutral-600"><?= count($productos) ?> producto(s)</p>

<?php if ($productos): ?>
    <ul class="mt-3 space-y-2">
        <?php foreach ($productos as $p): ?>
            <li>
                <a href="<?= e(url('admin/productos/' . $p['id'])) ?>" class="tarjeta-clara flex items-center gap-3 p-3 transition-colors hover:border-rivera-rojo sm:gap-4">
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-md bg-neutral-50 text-neutral-300">
                        <?php if ($p['imagen']): ?>
                            <img src="<?= e(url_foto($p['imagen'], true)) ?>" alt="" class="h-full w-full object-contain" loading="lazy">
                        <?php else: ?>
                            <?= icono('llave', 'h-6 w-6') ?>
                        <?php endif; ?>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-semibold"><?= e($p['nombre']) ?></span>
                        <span class="block truncate text-xs text-neutral-500">
                            <?= e($p['categoria']) ?><?= $p['marca'] ? ' · ' . e($p['marca']) : '' ?>
                        </span>
                        <span class="mt-1 flex flex-wrap gap-1">
                            <?php if (!$p['activo']): ?><span class="insignia bg-neutral-200 text-neutral-700">Oculto</span><?php endif; ?>
                            <?php if ($p['destacado']): ?><span class="insignia bg-rivera-negro text-white">Destacado</span><?php endif; ?>
                            <?php if ($p['precio_anterior'] !== null && (float) $p['precio_anterior'] > (float) $p['precio']): ?><span class="insignia-oferta">Oferta</span><?php endif; ?>
                        </span>
                    </span>
                    <span class="shrink-0 text-right">
                        <span class="block font-display text-sm font-bold sm:text-base"><?= colones($p['precio']) ?></span>
                        <span class="block text-xs <?= (int) $p['stock'] <= STOCK_BAJO ? 'font-semibold text-rivera-rojo' : 'text-neutral-500' ?>">
                            Stock: <?= (int) $p['stock'] ?>
                        </span>
                    </span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <div class="tarjeta-clara mt-3 px-6 py-12 text-center text-sm text-neutral-600">No hay productos con esos filtros.</div>
<?php endif; ?>

<?php
/**
 * Base de clientes del CRM: clientes (ya compraron) y posibles clientes (consultas o pedidos sin pagar).
 * Se arma sola con las consultas y los pedidos (ver app/modelos/clientes.php). Dueño y empleado la ven.
 */

require_once RAIZ . '/app/modelos/clientes.php';

requerir_admin();
$meta['titulo'] = 'Clientes';
$meta['layout'] = 'admin/layout';

$conteos = contar_clientes();
$tipo = is_string($_GET['ver'] ?? null) && isset(TIPOS_CLIENTE[$_GET['ver']]) ? $_GET['ver'] : 'todos';
$q = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 60) : '';
$clientes = listar_clientes($tipo, $q);
?>
<h1 class="font-display text-2xl font-extrabold sm:text-3xl">Clientes</h1>
<p class="mt-1 text-sm text-neutral-600">
    Se arma sola con las consultas y los pedidos (por número de teléfono). <strong>Cliente</strong> = ya compró;
    <strong>posible cliente</strong> = dejó una consulta o hizo un pedido que no pagó.
</p>

<!-- Pestañas -->
<nav class="-mx-4 mt-5 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:px-0" aria-label="Tipo">
    <?php foreach (TIPOS_CLIENTE as $clave => $texto): ?>
        <?php $activa = $clave === $tipo; ?>
        <a href="<?= e(url('admin/clientes?' . http_build_query(['ver' => $clave] + ($q !== '' ? ['q' => $q] : [])))) ?>"
           class="flex shrink-0 items-center gap-2 rounded-full border px-3.5 py-1.5 text-sm font-medium
                  <?= $activa ? 'border-rivera-negro bg-rivera-negro text-white' : 'border-neutral-300 bg-white hover:border-rivera-negro' ?>"
           <?= $activa ? 'aria-current="page"' : '' ?>>
            <?= e($texto) ?>
            <span class="rounded-full px-1.5 text-xs <?= $activa ? 'bg-white/20' : 'bg-neutral-100' ?>"><?= $conteos[$clave] ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<form method="get" action="<?= e(url('admin/clientes')) ?>" class="mt-4 flex gap-2">
    <input type="hidden" name="ver" value="<?= e($tipo) ?>">
    <label for="q" class="sr-only">Buscar</label>
    <input id="q" name="q" type="search" class="campo-claro" placeholder="Buscar por nombre o teléfono…" value="<?= e($q) ?>">
    <button type="submit" class="btn-claro bg-white">Buscar</button>
</form>

<?php if ($clientes): ?>
    <ul class="mt-4 space-y-2">
        <?php foreach ($clientes as $c): ?>
            <?php $esCliente = (int) $c['compras'] > 0; ?>
            <li>
                <a href="<?= e(url_cliente($c['telefono'])) ?>"
                   class="tarjeta-clara flex flex-wrap items-center gap-x-4 gap-y-1 p-4 transition-colors hover:border-rivera-rojo">
                    <span class="min-w-0 flex-1">
                        <span class="flex flex-wrap items-center gap-2">
                            <span class="font-semibold"><?= e($c['nombre']) ?></span>
                            <span class="insignia <?= $esCliente ? 'bg-emerald-100 text-emerald-900' : 'bg-amber-100 text-amber-900' ?>">
                                <?= $esCliente ? 'Cliente' : 'Posible cliente' ?>
                            </span>
                        </span>
                        <span class="mt-0.5 block truncate text-sm text-neutral-700">
                            <?= e($c['telefono']) ?><?= $c['email'] ? ' · ' . e($c['email']) : '' ?>
                        </span>
                        <span class="mt-0.5 block text-xs text-neutral-500">
                            <?= (int) $c['consultas'] ?> consulta<?= (int) $c['consultas'] === 1 ? '' : 's' ?>
                            · <?= (int) $c['pedidos'] ?> pedido<?= (int) $c['pedidos'] === 1 ? '' : 's' ?>
                            <?= $esCliente ? '(' . (int) $c['compras'] . ' pagado' . ((int) $c['compras'] === 1 ? '' : 's') . ')' : '' ?>
                        </span>
                    </span>
                    <span class="text-right">
                        <span class="block font-display font-bold"><?= $esCliente ? colones($c['total_comprado']) : '—' ?></span>
                        <span class="block text-xs text-neutral-500">Último contacto: <?= e(date_create($c['ultima_vez'])->format('d/m/Y')) ?></span>
                    </span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <div class="tarjeta-clara mt-4 px-6 py-12 text-center text-sm text-neutral-600">
        <?= $q !== '' ? 'No hay clientes que coincidan con la búsqueda.' : 'Todavía no hay clientes aquí. Aparecen solos cuando alguien deja una consulta o hace un pedido.' ?>
    </div>
<?php endif; ?>

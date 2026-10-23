<?php
/**
 * Lista de categorías del admin.
 */

require_once RAIZ . '/app/modelos/categorias-admin.php';

requerir_admin('duenio');
$meta['titulo'] = 'Categorías';
$meta['layout'] = 'admin/layout';

$categorias = categorias_admin_listar();
?>
<div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="font-display text-2xl font-extrabold sm:text-3xl">Categorías</h1>
    <a href="<?= e(url('admin/categorias/nueva')) ?>" class="btn-primario">+ Nueva categoría</a>
</div>

<ul class="mt-5 space-y-2">
    <?php foreach ($categorias as $c): ?>
        <li>
            <a href="<?= e(url('admin/categorias/' . $c['id'])) ?>" class="tarjeta-clara flex items-center gap-3 p-3 transition-colors hover:border-rivera-rojo sm:gap-4">
                <span class="flex h-12 w-16 shrink-0 items-center justify-center overflow-hidden rounded-md bg-neutral-100 text-neutral-400">
                    <?php if ($c['imagen']): ?>
                        <img src="<?= e(url_foto($c['imagen'], true)) ?>" alt="" class="h-full w-full object-cover" loading="lazy">
                    <?php else: ?>
                        <?= icono('tienda', 'h-5 w-5') ?>
                    <?php endif; ?>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-semibold"><?= e($c['nombre']) ?></span>
                    <span class="block text-xs text-neutral-500"><?= (int) $c['total_productos'] ?> producto(s) · orden <?= (int) $c['orden'] ?></span>
                </span>
                <?php if (!$c['activa']): ?><span class="insignia bg-neutral-200 text-neutral-700">Oculta</span><?php endif; ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

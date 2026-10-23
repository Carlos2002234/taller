<?php
/**
 * Usuarios del panel (solo el dueño).
 */

requerir_admin('duenio');
$meta['titulo'] = 'Usuarios';
$meta['layout'] = 'admin/layout';

$usuarios = usuarios_listar();
?>
<div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="font-display text-2xl font-extrabold sm:text-3xl">Usuarios</h1>
    <a href="<?= e(url('admin/usuarios/nuevo')) ?>" class="btn-primario">+ Nuevo usuario</a>
</div>
<p class="mt-2 text-sm text-neutral-600">Los empleados solo pueden ver y atender pedidos. Solo el dueño aprueba pagos y maneja productos.</p>

<ul class="mt-5 space-y-2">
    <?php foreach ($usuarios as $u): ?>
        <li>
            <a href="<?= e(url('admin/usuarios/' . $u['id'])) ?>" class="tarjeta-clara flex items-center gap-3 p-4 transition-colors hover:border-rivera-rojo">
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-semibold"><?= e($u['nombre']) ?></span>
                    <span class="block truncate text-xs text-neutral-500"><?= e($u['email']) ?></span>
                </span>
                <span class="insignia <?= $u['rol'] === 'duenio' ? 'bg-rivera-negro text-white' : 'bg-neutral-200 text-neutral-700' ?>"><?= e(ROLES[$u['rol']]) ?></span>
                <?php if (!$u['activo']): ?><span class="insignia bg-red-100 text-red-800">Desactivado</span><?php endif; ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

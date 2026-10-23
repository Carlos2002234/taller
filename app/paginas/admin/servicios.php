<?php
/**
 * Lista de servicios del taller (solo el dueño).
 */

require_once RAIZ . '/app/modelos/servicios.php';

requerir_admin('duenio');
$meta['titulo'] = 'Servicios';
$meta['layout'] = 'admin/layout';

$servicios = servicios_admin_listar();
?>
<div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="font-display text-2xl font-extrabold sm:text-3xl">Servicios del taller</h1>
    <a href="<?= e(url('admin/servicios/nuevo')) ?>" class="btn-primario">+ Nuevo servicio</a>
</div>
<p class="mt-2 text-sm text-neutral-600">Se muestran sin precio; el cliente los cotiza por WhatsApp.</p>

<ul class="mt-5 space-y-2">
    <?php foreach ($servicios as $s): ?>
        <li>
            <a href="<?= e(url('admin/servicios/' . $s['id'])) ?>" class="tarjeta-clara flex items-center gap-3 p-4 transition-colors hover:border-rivera-rojo">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-rivera-rojo/10 text-rivera-rojo"><?= icono($s['icono'], 'h-6 w-6') ?></span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-semibold"><?= e($s['nombre']) ?></span>
                    <span class="block truncate text-xs text-neutral-500"><?= e($s['descripcion'] ?? '') ?></span>
                </span>
                <span class="flex shrink-0 flex-col items-end gap-1">
                    <?php if (!$s['activo']): ?><span class="insignia bg-neutral-200 text-neutral-700">Oculto</span><?php endif; ?>
                    <?php if ($s['activo'] && $s['en_inicio']): ?><span class="insignia bg-rivera-negro text-white">En inicio</span><?php endif; ?>
                </span>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

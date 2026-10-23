<?php
/**
 * Un usuario del panel intentó entrar a una sección que su rol no permite.
 */

$meta['titulo'] = 'Sin permiso';
$meta['layout'] = 'admin/layout';
?>
<div class="tarjeta-clara px-6 py-14 text-center">
    <span class="mx-auto block w-fit text-rivera-rojo"><?= icono('escudo', 'h-12 w-12') ?></span>
    <h1 class="mt-4 text-2xl">No tienes permiso para esta sección</h1>
    <p class="mt-2 text-sm text-neutral-600">Si necesitas acceso, pídeselo al dueño.</p>
    <a href="<?= e(url('admin')) ?>" class="btn-primario mt-6">Ir al inicio del panel</a>
</div>

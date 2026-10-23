<?php
/**
 * Título de sección al estilo del prototipo.
 * Datos: $titulo, ?$etiqueta (texto rojo pequeño), ?$titulo_rojo (segunda línea roja),
 *        ?$enlace + ?$enlace_texto (link "Ver todos" a la derecha), ?$nivel ('h1' | 'h2')
 */

$nivel ??= 'h2';
$etiqueta ??= null;
$titulo_rojo ??= null;
$enlace ??= null;
$enlace_texto ??= 'Ver todos';
?>
<div class="mb-6 flex items-end justify-between gap-4">
    <div>
        <?php if ($etiqueta): ?>
            <p class="etiqueta-seccion"><?= e($etiqueta) ?></p>
        <?php endif; ?>
        <<?= $nivel ?> class="titulo-seccion">
            <?= e($titulo) ?>
            <?php if ($titulo_rojo): ?>
                <span class="block text-rivera-rojo"><?= e($titulo_rojo) ?></span>
            <?php endif; ?>
        </<?= $nivel ?>>
    </div>
    <?php if ($enlace): ?>
        <a href="<?= e($enlace) ?>" class="inline-flex shrink-0 items-center gap-1 text-sm font-medium opacity-80 hover:opacity-100">
            <?= e($enlace_texto) ?> <?= icono('flecha', 'h-4 w-4') ?>
        </a>
    <?php endif; ?>
</div>

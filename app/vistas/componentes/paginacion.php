<?php
/**
 * Paginación del catálogo.
 * Datos: $pagina, $totalPaginas, $consulta (filtros actuales para conservarlos)
 */

if ($totalPaginas <= 1) {
    return;
}

$enlace = fn (int $n): string => url('tienda?' . http_build_query($consulta + ['pagina' => $n]));
$desde = max(1, $pagina - 2);
$hasta = min($totalPaginas, $pagina + 2);
$claseNumero = 'flex h-10 min-w-10 items-center justify-center rounded-md px-3 text-sm font-medium';
?>
<nav class="mt-8 flex items-center justify-center gap-1" aria-label="Páginas">
    <?php if ($pagina > 1): ?>
        <a href="<?= e($enlace($pagina - 1)) ?>" class="<?= $claseNumero ?> hover:bg-white" aria-label="Página anterior">
            <span class="rotate-180"><?= icono('flecha', 'h-4 w-4') ?></span>
        </a>
    <?php endif; ?>

    <?php for ($n = $desde; $n <= $hasta; $n++): ?>
        <?php if ($n === $pagina): ?>
            <span class="<?= $claseNumero ?> bg-rivera-rojo text-white" aria-current="page"><?= $n ?></span>
        <?php else: ?>
            <a href="<?= e($enlace($n)) ?>" class="<?= $claseNumero ?> hover:bg-white"><?= $n ?></a>
        <?php endif; ?>
    <?php endfor; ?>

    <?php if ($pagina < $totalPaginas): ?>
        <a href="<?= e($enlace($pagina + 1)) ?>" class="<?= $claseNumero ?> hover:bg-white" aria-label="Página siguiente">
            <?= icono('flecha', 'h-4 w-4') ?>
        </a>
    <?php endif; ?>
</nav>

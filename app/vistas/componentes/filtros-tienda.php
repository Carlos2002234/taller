<?php
/**
 * Formulario de filtros del catálogo (se usa dos veces: escritorio y celular).
 * Datos: $categorias, ?$categoria, $marcas, $marcasElegidas, ?$min, ?$max, $q, $orden,
 *        $prefijo (para que los id de los campos no se repitan)
 */
?>
<form action="<?= e(url('tienda')) ?>" method="get" class="space-y-6 text-sm">
    <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= e($q) ?>"><?php endif; ?>
    <?php if ($orden !== 'destacados'): ?><input type="hidden" name="orden" value="<?= e($orden) ?>"><?php endif; ?>

    <fieldset>
        <legend class="mb-3 font-display text-sm font-bold uppercase tracking-wide">Categoría</legend>
        <div class="space-y-2">
            <label class="flex cursor-pointer items-center gap-2.5">
                <input type="radio" name="categoria" value="" class="h-4 w-4 accent-rivera-rojo" <?= $categoria ? '' : 'checked' ?>>
                Todas
            </label>
            <?php foreach ($categorias as $c): ?>
                <label class="flex cursor-pointer items-center gap-2.5">
                    <input type="radio" name="categoria" value="<?= e($c['slug']) ?>" class="h-4 w-4 accent-rivera-rojo"
                        <?= ($categoria['id'] ?? null) === $c['id'] ? 'checked' : '' ?>>
                    <span class="flex-1"><?= e($c['nombre']) ?></span>
                    <span class="text-xs text-neutral-400"><?= (int) $c['total_productos'] ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>

    <?php if ($marcas): ?>
        <fieldset>
            <legend class="mb-3 font-display text-sm font-bold uppercase tracking-wide">Marca</legend>
            <div class="space-y-2">
                <?php foreach ($marcas as $marca): ?>
                    <label class="flex cursor-pointer items-center gap-2.5">
                        <input type="checkbox" name="marca[]" value="<?= e($marca) ?>" class="h-4 w-4 rounded accent-rivera-rojo"
                            <?= in_array($marca, $marcasElegidas, true) ? 'checked' : '' ?>>
                        <?= e($marca) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>
    <?php endif; ?>

    <fieldset>
        <legend class="mb-3 font-display text-sm font-bold uppercase tracking-wide">Precio (₡)</legend>
        <div class="flex items-center gap-2">
            <label for="<?= e($prefijo) ?>-min" class="sr-only">Precio mínimo</label>
            <input id="<?= e($prefijo) ?>-min" type="number" name="min" min="0" step="500" inputmode="numeric"
                   value="<?= e($min ?? '') ?>" placeholder="Mín." class="campo-claro py-2">
            <span class="text-neutral-400">–</span>
            <label for="<?= e($prefijo) ?>-max" class="sr-only">Precio máximo</label>
            <input id="<?= e($prefijo) ?>-max" type="number" name="max" min="0" step="500" inputmode="numeric"
                   value="<?= e($max ?? '') ?>" placeholder="Máx." class="campo-claro py-2">
        </div>
    </fieldset>

    <div class="flex gap-2">
        <button type="submit" class="btn-primario flex-1">Aplicar</button>
        <a href="<?= e(url('tienda')) ?>" class="btn-claro">Limpiar</a>
    </div>
</form>

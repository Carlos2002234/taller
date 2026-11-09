<?php
/**
 * Formulario de filtros del catálogo (se usa dos veces: escritorio y celular).
 * Diseño 2026-11-08: tarjeta blanca con secciones separadas por líneas, títulos pequeños en mayúscula.
 * Datos: $categorias, ?$categoria, $marcas, $marcasElegidas, ?$min, ?$max, $q, $orden,
 *        $prefijo (para que los id de los campos no se repitan)
 */
?>
<form action="<?= e(url('tienda')) ?>" method="get" class="text-sm">
    <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= e($q) ?>"><?php endif; ?>
    <?php if ($orden !== 'destacados'): ?><input type="hidden" name="orden" value="<?= e($orden) ?>"><?php endif; ?>

    <div class="flex items-center justify-between border-b border-neutral-200 px-5 py-4">
        <h2 class="text-xl font-extrabold">Filtrar</h2>
        <a href="<?= e(url('tienda')) ?>" class="inline-flex min-h-9 items-center text-[0.82rem] font-semibold text-rivera-rojo-oscuro hover:underline">Limpiar</a>
    </div>

    <fieldset class="border-b border-neutral-200 px-5 py-4">
        <legend class="mb-2.5 text-[11px] font-bold uppercase tracking-[0.14em] text-neutral-600">Categoría</legend>
        <div>
            <label class="flex min-h-9 cursor-pointer items-center gap-2.5">
                <input type="radio" name="categoria" value="" class="h-4 w-4 accent-rivera-rojo" <?= $categoria ? '' : 'checked' ?>>
                Todas
            </label>
            <?php foreach ($categorias as $c): ?>
                <?php if ((int) $c['total_productos'] === 0 && ($categoria['id'] ?? null) !== $c['id']) { continue; } ?>
                <label class="flex min-h-9 cursor-pointer items-center gap-2.5">
                    <input type="radio" name="categoria" value="<?= e($c['slug']) ?>" class="h-4 w-4 accent-rivera-rojo"
                        <?= ($categoria['id'] ?? null) === $c['id'] ? 'checked' : '' ?>>
                    <span class="flex-1"><?= e($c['nombre']) ?></span>
                    <span class="text-xs text-neutral-500"><?= (int) $c['total_productos'] ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>

    <?php if ($marcas): ?>
        <fieldset class="border-b border-neutral-200 px-5 py-4">
            <legend class="mb-2.5 text-[11px] font-bold uppercase tracking-[0.14em] text-neutral-600">Marca</legend>
            <div>
                <?php foreach ($marcas as $marca): ?>
                    <label class="flex min-h-9 cursor-pointer items-center gap-2.5">
                        <input type="checkbox" name="marca[]" value="<?= e($marca) ?>" class="h-4 w-4 rounded accent-rivera-rojo"
                            <?= in_array($marca, $marcasElegidas, true) ? 'checked' : '' ?>>
                        <?= e($marca) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>
    <?php endif; ?>

    <fieldset class="px-5 pb-5 pt-4">
        <legend class="mb-2.5 text-[11px] font-bold uppercase tracking-[0.14em] text-neutral-600">Precio (₡)</legend>
        <div class="grid grid-cols-2 gap-2.5">
            <label for="<?= e($prefijo) ?>-min" class="flex flex-col gap-1.5 text-xs text-neutral-600">Desde
                <input id="<?= e($prefijo) ?>-min" type="number" name="min" min="0" step="500" inputmode="numeric"
                       value="<?= e($min ?? '') ?>" placeholder="0" class="campo-claro py-2">
            </label>
            <label for="<?= e($prefijo) ?>-max" class="flex flex-col gap-1.5 text-xs text-neutral-600">Hasta
                <input id="<?= e($prefijo) ?>-max" type="number" name="max" min="0" step="500" inputmode="numeric"
                       value="<?= e($max ?? '') ?>" placeholder="Sin límite" class="campo-claro py-2">
            </label>
        </div>
        <button type="submit" class="btn-primario mt-4 w-full">Aplicar filtros</button>
    </fieldset>
</form>

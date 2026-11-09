<?php
/**
 * Tienda: catálogo con filtros (categoría, marca, precio), búsqueda, orden y paginación.
 * Todos los filtros llegan por GET y se validan antes de consultar.
 */

require_once RAIZ . '/app/modelos/productos.php';
require_once RAIZ . '/app/modelos/categorias.php';

$porPagina = 12;

// --- Leer y validar filtros -------------------------------------------------
$texto = fn (string $clave): string => is_string($_GET[$clave] ?? null) ? trim($_GET[$clave]) : '';
$entero = fn (string $clave): ?int => ctype_digit($texto($clave)) ? (int) $texto($clave) : null;

$q = mb_substr($texto('q'), 0, 100);
$categoria = $texto('categoria') !== '' ? categoria_por_slug($texto('categoria')) : null;
$marcasElegidas = array_slice(array_values(array_filter(
    is_array($_GET['marca'] ?? null) ? $_GET['marca'] : [],
    fn ($m) => is_string($m) && $m !== '' && mb_strlen($m) <= 80
)), 0, 20);
$min = $entero('min');
$max = $entero('max');
if ($min !== null && $max !== null && $min > $max) {
    [$min, $max] = [$max, $min];
}
$orden = array_key_exists($texto('orden'), PRODUCTO_ORDENES) ? $texto('orden') : 'destacados';
$pagina = max(1, $entero('pagina') ?? 1);

// --- Consultar ---------------------------------------------------------------
$resultado = productos_catalogo([
    'categoria_id' => $categoria['id'] ?? null,
    'marcas'       => $marcasElegidas,
    'min'          => $min,
    'max'          => $max,
    'q'            => $q,
    'orden'        => $orden,
], $pagina, $porPagina);

$totalPaginas = max(1, (int) ceil($resultado['total'] / $porPagina));
$categorias = categorias_activas();
$marcas = productos_marcas($categoria['id'] ?? null);

// Filtros actuales (para mantenerlos en enlaces y formularios)
$consulta = array_filter([
    'q'         => $q,
    'categoria' => $categoria['slug'] ?? null,
    'marca'     => $marcasElegidas,
    'min'       => $min,
    'max'       => $max,
    'orden'     => $orden !== 'destacados' ? $orden : null,
], fn ($v) => $v !== null && $v !== '' && $v !== []);

$filtrosActivos = count($marcasElegidas) + ($categoria ? 1 : 0) + ($min !== null ? 1 : 0) + ($max !== null ? 1 : 0);

// --- Título y metadatos ------------------------------------------------------
$titulo = $categoria['nombre'] ?? ($q !== '' ? 'Resultados de búsqueda' : 'Tienda');
$meta['titulo'] = $titulo;
$meta['descripcion'] = $categoria
    ? $categoria['nombre'] . ' para tu auto en Rivera Grupo Automotriz. Paga por SINPE Móvil o transferencia.'
    : 'Accesorios y repuestos para tu auto. Paga por SINPE Móvil o transferencia y retira en el taller o recibe en la GAM.';
$meta['fondo_claro'] = true;
// Las combinaciones de filtros no deben indexarse como páginas distintas
$meta['noindex'] = $q !== '' || $marcasElegidas || $min !== null || $max !== null || $pagina > 1;
?>
<section class="bg-rivera-negro text-white">
    <div class="contenedor flex flex-wrap items-stretch gap-x-12">
    <div class="flex-[1_1_28rem] py-9 sm:py-11">
        <nav class="mb-3 text-xs text-neutral-400" aria-label="Ruta">
            <a href="<?= e(url()) ?>" class="hover:text-white">Inicio</a>
            <span class="mx-1.5">/</span>
            <?php if ($categoria): ?>
                <a href="<?= e(url('tienda')) ?>" class="hover:text-white">Tienda</a>
                <span class="mx-1.5">/</span>
                <span class="text-neutral-200"><?= e($categoria['nombre']) ?></span>
            <?php else: ?>
                <span class="text-neutral-200">Tienda</span>
            <?php endif; ?>
        </nav>
        <h1 class="mt-1 font-display text-5xl font-extrabold leading-[0.98] tracking-tight sm:text-6xl"><?= e($titulo) ?></h1>
        <?php if ($q !== ''): ?>
            <p class="mt-3 text-neutral-300">Buscaste: <span class="font-semibold text-white">“<?= e($q) ?>”</span></p>
        <?php else: ?>
            <p class="mt-3 max-w-md leading-relaxed text-neutral-400">Accesorios y repuestos para tu auto. Pagas por SINPE Móvil o transferencia y retiras en el taller o recibes en la GAM.</p>
        <?php endif; ?>
    </div>
    <!-- Foto del taller al lado (sin texto encima) -->
    <figure class="relative hidden min-h-[13rem] flex-[0_1_28rem] overflow-hidden md:block">
        <img src="<?= e(url('img/fotos/rivera-25-800.webp')) ?>" alt="Aceites Havoline y Castrol en exhibición en el taller"
             class="absolute inset-0 h-full w-full object-cover" fetchpriority="high">
    </figure>
    </div>
    <div class="h-[3px] bg-rivera-rojo"></div>
</section>

<!-- Accesos rápidos por categoría (solo las que tienen productos) -->
<nav aria-label="Categorías" class="border-b border-neutral-200 bg-white">
    <div class="contenedor flex flex-wrap gap-2 py-3.5">
        <?php
        $chips = [['', 'Todas', null]];
        foreach ($categorias as $c) {
            if ((int) $c['total_productos'] > 0) { $chips[] = [$c['slug'], $c['nombre'], (int) $c['total_productos']]; }
        }
        foreach ($chips as [$slug, $nombre, $total]):
            $activa = ($categoria['slug'] ?? '') === $slug;
            $enlace = url('tienda' . ($slug !== '' ? '?categoria=' . rawurlencode($slug) : ''));
        ?>
            <a href="<?= e($enlace) ?>" <?= $activa ? 'aria-current="page"' : '' ?>
               class="inline-flex min-h-10 items-center gap-1.5 rounded-full border px-4 text-[0.82rem] font-semibold transition-colors <?= $activa ? 'border-rivera-negro bg-rivera-negro text-white' : 'border-neutral-300 bg-white text-rivera-negro hover:border-rivera-negro' ?>">
                <?= e($nombre) ?><?php if ($total !== null): ?> <span class="font-medium opacity-60"><?= $total ?></span><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
</nav>

<div class="contenedor grid gap-7 pb-16 pt-8 lg:grid-cols-[16.5rem_1fr]">
    <!-- Filtros de escritorio -->
    <aside class="hidden self-start rounded border border-neutral-200 bg-white lg:block" aria-label="Filtros">
        <?php componente('filtros-tienda', compact('categorias', 'categoria', 'marcas', 'marcasElegidas', 'min', 'max', 'q', 'orden') + ['prefijo' => 'esc']); ?>
    </aside>

    <div class="min-w-0">
        <!-- Barra: filtros en celular, cantidad y orden -->
        <div class="mb-5 flex flex-wrap items-center gap-3">
            <details class="w-full lg:hidden">
                <summary class="btn-claro w-full cursor-pointer list-none bg-white">
                    Filtrar<?= $filtrosActivos ? ' (' . $filtrosActivos . ')' : '' ?>
                </summary>
                <div class="tarjeta-clara mt-2">
                    <?php componente('filtros-tienda', compact('categorias', 'categoria', 'marcas', 'marcasElegidas', 'min', 'max', 'q', 'orden') + ['prefijo' => 'mov']); ?>
                </div>
            </details>

            <p class="text-sm text-neutral-600">
                <strong class="text-rivera-negro"><?= $resultado['total'] === 1 ? '1 producto' : $resultado['total'] . ' productos' ?></strong><?= $categoria ? ' en ' . e($categoria['nombre']) : '' ?>
            </p>

            <form action="<?= e(url('tienda')) ?>" method="get" class="ml-auto flex items-center gap-2">
                <?php foreach (array_diff_key($consulta, ['orden' => 1]) as $clave => $valor): ?>
                    <?php foreach ((array) $valor as $v): ?>
                        <input type="hidden" name="<?= e($clave) . (is_array($valor) ? '[]' : '') ?>" value="<?= e($v) ?>">
                    <?php endforeach; ?>
                <?php endforeach; ?>
                <label for="orden" class="text-sm text-neutral-600">Ordenar por</label>
                <select id="orden" name="orden" class="campo-claro w-auto py-2" data-auto-enviar>
                    <?php foreach (PRODUCTO_ORDENES as $clave => [$etiqueta]): ?>
                        <option value="<?= e($clave) ?>" <?= $orden === $clave ? 'selected' : '' ?>><?= e($etiqueta) ?></option>
                    <?php endforeach; ?>
                </select>
                <noscript><button class="btn-claro btn-pequeno">Aplicar</button></noscript>
            </form>
        </div>

        <?php if ($resultado['productos']): ?>
            <div class="escalonado grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4">
                <?php foreach ($resultado['productos'] as $producto): ?>
                    <?php componente('tarjeta-producto', ['producto' => $producto, 'tono' => 'claro']); ?>
                <?php endforeach; ?>
            </div>

            <?php componente('paginacion', ['pagina' => $pagina, 'totalPaginas' => $totalPaginas, 'consulta' => $consulta]); ?>
        <?php else: ?>
            <div class="tarjeta-clara rounded px-6 py-14 text-center">
                <h2 class="text-2xl font-extrabold">No encontramos productos</h2>
                <p class="mt-2 text-sm text-neutral-600">Prueba con otra categoría o marca, o escríbenos: lo buscamos por ti.</p>
                <div class="mt-6 flex flex-wrap justify-center gap-3">
                    <a href="<?= e(url('tienda')) ?>" class="btn-claro">Ver todos los productos</a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Ayuda para elegir -->
        <div class="mt-7 flex flex-wrap items-center justify-between gap-x-8 gap-y-4 rounded bg-rivera-negro px-6 py-5 text-white sm:px-7">
            <div>
                <p class="font-display text-xl font-extrabold italic">¿No encuentras lo que buscas?</p>
                <p class="mt-1 text-sm text-neutral-300">Escríbenos con la marca, modelo y año de tu auto y te ayudamos a elegir.</p>
            </div>
            <a href="<?= e(whatsapp_url('Hola, busco un producto' . ($q !== '' ? ': ' . $q : '') . ' para mi auto. ¿Lo tienen?')) ?>"
               class="btn-primario" target="_blank" rel="noopener">Preguntar por WhatsApp</a>
        </div>
    </div>
</div>

<?php
/**
 * Visitas a la tienda (solo el dueño): cuántas personas entran, qué ven, de dónde llegan y desde qué aparato.
 * Los datos son anónimos (ver app/modelos/visitas.php).
 */

require_once RAIZ . '/app/modelos/visitas.php';
require_once RAIZ . '/app/modelos/reportes.php';

requerir_admin('duenio');
$meta['titulo'] = 'Visitas';
$meta['layout'] = 'admin/layout';

// Mes elegido (últimos 12 meses), igual que en Reportes
$meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'setiembre', 'octubre', 'noviembre', 'diciembre'];
$opcionesMes = [];
for ($i = 0; $i < 12; $i++) {
    $t = strtotime("first day of -{$i} month");
    $opcionesMes[date('Y-m', $t)] = ucfirst($meses[(int) date('n', $t) - 1]) . ' ' . date('Y', $t);
}
$mes = is_string($_GET['mes'] ?? null) && isset($opcionesMes[$_GET['mes']]) ? $_GET['mes'] : array_key_first($opcionesMes);
$inicioMes = $mes . '-01';

$resumen = visitas_resumen($inicioMes);
$dias = visitas_por_dia($inicioMes);
$origenes = visitas_por_origen($inicioMes);
$paginas = visitas_paginas_top($inicioMes);
$productos = visitas_productos_top($inicioMes);
$pedidos = pedidos_creados_mes($inicioMes);

$maxDia = max([0, ...array_column($dias, 'visitantes')]);
$maxOrigen = max([0, ...array_map('intval', array_column($origenes, 'entradas'))]);
// Tope de 100 %: puede haber más pedidos que visitantes contados (ej. pedidos hechos con sesión en el panel)
$porcentaje = fn (int $parte, int $total): string => $total > 0 ? min(100, round($parte / $total * 100)) . ' %' : '—';
$diaCorto = fn (string $fecha): string => (int) substr($fecha, 8, 2) . ' ' . substr($meses[(int) substr($fecha, 5, 2) - 1], 0, 3);
?>
<div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="font-display text-2xl font-extrabold sm:text-3xl">Visitas</h1>
    <form method="get" action="<?= e(url('admin/visitas')) ?>" class="flex items-center gap-2">
        <label for="mes" class="text-sm text-neutral-600">Mes</label>
        <select id="mes" name="mes" class="campo-claro w-auto py-2" data-auto-enviar>
            <?php foreach ($opcionesMes as $clave => $texto): ?>
                <option value="<?= e($clave) ?>" <?= $clave === $mes ? 'selected' : '' ?>><?= e($texto) ?></option>
            <?php endforeach; ?>
        </select>
        <noscript><button class="btn-claro btn-pequeno bg-white">Ver</button></noscript>
    </form>
</div>

<!-- Resumen del mes -->
<div class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
    <?php foreach ([
        ['Visitantes', number_format($resumen['visitantes'], 0, ',', '.'), 'Personas distintas por día, sumadas.'],
        ['Páginas vistas', number_format($resumen['paginas'], 0, ',', '.'), $resumen['visitantes'] ? round($resumen['paginas'] / $resumen['visitantes'], 1) . ' por visitante' : ''],
        ['Desde celular', $porcentaje($resumen['celular'], $resumen['visitantes']), 'Del total de visitantes.'],
        ['Hicieron un pedido', $porcentaje($pedidos, $resumen['visitantes']), $pedidos . ' pedido' . ($pedidos === 1 ? '' : 's') . ' creado' . ($pedidos === 1 ? '' : 's') . ' en el mes.'],
    ] as [$texto, $valor, $nota]): ?>
        <div class="tarjeta-clara p-4 sm:p-5">
            <p class="text-sm text-neutral-600"><?= e($texto) ?></p>
            <p class="mt-1 font-display text-2xl font-extrabold"><?= e($valor) ?></p>
            <?php if ($nota !== ''): ?><p class="mt-0.5 text-xs text-neutral-500"><?= e($nota) ?></p><?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($resumen['paginas'] === 0): ?>
    <div class="tarjeta-clara mt-6 px-6 py-12 text-center text-sm text-neutral-600">
        Todavía no hay visitas registradas en este mes. Se empiezan a contar cuando la página está publicada y la visita alguien que no tiene sesión en el panel.
    </div>
<?php else: ?>

<!-- Visitantes por día -->
<section class="tarjeta-clara mt-5 p-5 sm:p-6" aria-labelledby="titulo-dias">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <h2 id="titulo-dias" class="text-lg">Visitantes por día</h2>
        <p class="text-xs text-neutral-500">Día con más visitantes: <?= (int) $maxDia ?></p>
    </div>
    <div class="relative mt-5 h-44 border-b border-neutral-300" aria-hidden="true">
        <!-- Línea de referencia del máximo -->
        <div class="absolute inset-x-0 top-0 border-t border-dashed border-neutral-200"></div>
        <div class="flex h-full items-end gap-[2px]">
            <?php foreach ($dias as $fecha => $d): ?>
                <div class="group relative flex h-full flex-1 items-end">
                    <div class="w-full rounded-t-[4px] bg-dato transition-opacity group-hover:opacity-80 <?= clase_alto_barra($d['visitantes'], $maxDia) ?>"></div>
                    <!-- Dato al pasar el mouse -->
                    <div class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 hidden -translate-x-1/2 whitespace-nowrap rounded-md bg-rivera-negro px-2.5 py-1.5 text-xs text-white shadow-lg group-hover:block">
                        <span class="font-semibold"><?= e($diaCorto($fecha)) ?></span><br>
                        <?= (int) $d['visitantes'] ?> visitante<?= $d['visitantes'] === 1 ? '' : 's' ?> · <?= (int) $d['paginas'] ?> página<?= $d['paginas'] === 1 ? '' : 's' ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <!-- Eje: algunos días como referencia -->
    <div class="mt-1.5 flex gap-[2px] text-[10px] text-neutral-500" aria-hidden="true">
        <?php foreach (array_keys($dias) as $fecha): ?>
            <?php $n = (int) substr($fecha, 8, 2); ?>
            <span class="flex-1 text-center"><?= $n === 1 || $n % 5 === 0 ? $n : '' ?></span>
        <?php endforeach; ?>
    </div>
    <details class="mt-4 text-sm">
        <summary class="cursor-pointer font-medium text-neutral-700">Ver como tabla</summary>
        <div class="mt-3 max-h-72 overflow-y-auto">
            <table class="w-full text-left text-sm">
                <thead class="sticky top-0 bg-white text-xs uppercase tracking-wide text-neutral-600">
                    <tr><th scope="col" class="py-1.5">Día</th><th scope="col" class="py-1.5 text-right">Visitantes</th><th scope="col" class="py-1.5 text-right">Páginas vistas</th></tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    <?php foreach ($dias as $fecha => $d): ?>
                        <tr><td class="py-1.5"><?= e($diaCorto($fecha)) ?></td><td class="py-1.5 text-right tabular-nums"><?= (int) $d['visitantes'] ?></td><td class="py-1.5 text-right tabular-nums"><?= (int) $d['paginas'] ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </details>
</section>

<div class="mt-5 grid gap-5 lg:grid-cols-2">
    <!-- De dónde llegan -->
    <section class="tarjeta-clara p-5 sm:p-6" aria-labelledby="titulo-origenes">
        <h2 id="titulo-origenes" class="text-lg">¿De dónde llegan?</h2>
        <p class="mt-1 text-sm text-neutral-600">Veces que alguien entró a la página desde cada lugar.</p>
        <?php if (!$origenes): ?>
            <p class="mt-6 text-sm text-neutral-600">Sin datos todavía.</p>
        <?php else: ?>
            <ul class="mt-5 space-y-4">
                <?php foreach ($origenes as $o): ?>
                    <?php $nombre = $o['origen'] === 'otro' ? 'Otros sitios' : nombre_origen($o['origen']); ?>
                    <li title="<?= e($nombre . ': ' . (int) $o['entradas']) ?>">
                        <div class="flex items-baseline justify-between gap-3 text-sm">
                            <span class="font-semibold"><?= e($nombre) ?></span>
                            <span class="text-neutral-700"><span class="font-semibold text-rivera-negro"><?= (int) $o['entradas'] ?></span>
                                · <?= $porcentaje((int) $o['entradas'], (int) $resumen['entradas']) ?></span>
                        </div>
                        <div class="mt-1.5 h-3" aria-hidden="true">
                            <div class="h-3 rounded-r bg-dato <?= clase_ancho_barra((float) $o['entradas'], (float) $maxOrigen) ?>"></div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <p class="mt-4 text-xs text-neutral-500">"Directo" = escribió la dirección, usó un favorito o abrió un enlace compartido por WhatsApp.</p>
    </section>

    <!-- Páginas y productos más vistos -->
    <section class="tarjeta-clara p-5 sm:p-6" aria-labelledby="titulo-paginas">
        <h2 id="titulo-paginas" class="text-lg">Lo más visto</h2>
        <div class="mt-4 grid gap-6 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wide text-neutral-600">Páginas</h3>
                <ol class="mt-2 divide-y divide-neutral-100 text-sm">
                    <?php foreach ($paginas as $p): ?>
                        <li class="flex justify-between gap-3 py-1.5">
                            <span class="min-w-0 truncate"><?= e(nombre_pagina_visita($p['ruta'])) ?></span>
                            <span class="shrink-0 font-semibold tabular-nums"><?= (int) $p['vistas'] ?></span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wide text-neutral-600">Productos</h3>
                <?php if (!$productos): ?>
                    <p class="mt-2 text-sm text-neutral-600">Nadie ha abierto un producto este mes.</p>
                <?php else: ?>
                    <ol class="mt-2 divide-y divide-neutral-100 text-sm">
                        <?php foreach ($productos as $p): ?>
                            <li class="flex justify-between gap-3 py-1.5">
                                <span class="min-w-0 truncate"><?= e($p['nombre'] ?? '/' . $p['ruta'] . ' (ya no existe)') ?></span>
                                <span class="shrink-0 font-semibold tabular-nums"><?= (int) $p['vistas'] ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>
<?php endif; ?>

<p class="mt-4 text-xs text-neutral-500">
    Las visitas son anónimas: no se guarda la IP ni datos personales, y no se usan cookies. No se cuentan robots (Google, Facebook…)
    ni tus visitas mientras tienes sesión en el panel. Se guardan unos 13 meses.
</p>

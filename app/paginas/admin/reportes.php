<?php
/**
 * Reportes (solo el dueño): ¿qué anuncio vende?, productos más vendidos y generador de enlaces para anuncios.
 */

require_once RAIZ . '/app/modelos/reportes.php';
require_once RAIZ . '/app/modelos/categorias.php';

requerir_admin('duenio');
$meta['titulo'] = 'Reportes';
$meta['layout'] = 'admin/layout';

// Mes elegido (últimos 12 meses)
$meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'setiembre', 'octubre', 'noviembre', 'diciembre'];
$opcionesMes = [];
for ($i = 0; $i < 12; $i++) {
    $t = strtotime("first day of -{$i} month");
    $opcionesMes[date('Y-m', $t)] = ucfirst($meses[(int) date('n', $t) - 1]) . ' ' . date('Y', $t);
}
$mes = is_string($_GET['mes'] ?? null) && isset($opcionesMes[$_GET['mes']]) ? $_GET['mes'] : array_key_first($opcionesMes);
$inicioMes = $mes . '-01';

$filas = reporte_origenes($inicioMes);
$origenes = reporte_por_origen($filas);
$productos = productos_mas_vendidos($inicioMes);

$totalVendido = array_sum(array_column($origenes, 'total'));
$totalPagados = array_sum(array_column($origenes, 'pagados'));
$totalCreados = array_sum(array_column($origenes, 'creados'));
$maxOrigen = max([0, ...array_column($origenes, 'total')]);
$maxUnidades = max([0, ...array_map('intval', array_column($productos, 'unidades'))]);

// --- Generador de enlaces para anuncios ---
$destinos = ['' => 'Inicio', 'tienda' => 'Tienda (todos los productos)', 'servicios' => 'Servicios del taller'];
foreach (categorias_activas() as $c) {
    $destinos['tienda?categoria=' . $c['slug']] = 'Categoría: ' . $c['nombre'];
}
$fuentes = ['facebook' => 'Facebook', 'instagram' => 'Instagram', 'google' => 'Google', 'tiktok' => 'TikTok', 'whatsapp' => 'WhatsApp'];
$medios = ['anuncio' => 'Anuncio pagado', 'publicacion' => 'Publicación normal', 'perfil' => 'Enlace del perfil / biografía', 'estado' => 'Estado de WhatsApp'];

$g = fn (string $clave, array $permitidos, string $porDefecto) =>
    is_string($_GET[$clave] ?? null) && array_key_exists($_GET[$clave], $permitidos) ? $_GET[$clave] : $porDefecto;
$destino = $g('destino', $destinos, 'tienda');
$fuente = $g('fuente', $fuentes, 'facebook');
$medio = $g('medio', $medios, 'anuncio');
$campana = is_string($_GET['campana'] ?? null) ? slug(mb_substr($_GET['campana'], 0, 60)) : '';
$enlaceAnuncio = null;
if (isset($_GET['generar'])) {
    $enlaceAnuncio = url($destino) . (str_contains($destino, '?') ? '&' : '?')
        . http_build_query(array_filter(['utm_source' => $fuente, 'utm_medium' => $medio, 'utm_campaign' => $campana]));
}
?>
<div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="font-display text-2xl font-extrabold sm:text-3xl">Reportes</h1>
    <form method="get" action="<?= e(url('admin/reportes')) ?>" class="flex items-center gap-2">
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
        ['Vendido', colones($totalVendido)],
        ['Pedidos pagados', (string) $totalPagados],
        ['Promedio por pedido', $totalPagados ? colones($totalVendido / $totalPagados) : '—'],
        ['Pedidos que se pagaron', $totalCreados ? round($totalPagados / $totalCreados * 100) . ' %' : '—'],
    ] as [$texto, $valor]): ?>
        <div class="tarjeta-clara p-4 sm:p-5">
            <p class="text-sm text-neutral-600"><?= e($texto) ?></p>
            <p class="mt-1 font-display text-2xl font-extrabold"><?= e($valor) ?></p>
        </div>
    <?php endforeach; ?>
</div>
<p class="mt-2 text-xs text-neutral-600">
    Una venta cuenta en el mes en que aprobaste el pago. "Pedidos que se pagaron" compara los pagados con los creados en el mes.
</p>

<div class="mt-8 grid gap-5 lg:grid-cols-2">
    <!-- ¿Qué anuncio vende? -->
    <section class="tarjeta-clara p-5 sm:p-6" aria-labelledby="titulo-origenes">
        <h2 id="titulo-origenes" class="text-lg">¿De dónde vienen las ventas?</h2>
        <p class="mt-1 text-sm text-neutral-600">Monto vendido por origen del cliente.</p>
        <?php if (!$origenes): ?>
            <p class="mt-6 text-sm text-neutral-600">Todavía no hay pedidos en este mes.</p>
        <?php else: ?>
            <ul class="mt-5 space-y-4">
                <?php foreach ($origenes as $o): ?>
                    <?php $nombre = nombre_origen($o['origen']); ?>
                    <li title="<?= e($nombre . ': ' . colones($o['total']) . ' en ' . $o['pagados'] . ' pedido(s) pagado(s) de ' . $o['creados'] . ' creado(s)') ?>">
                        <div class="flex items-baseline justify-between gap-3 text-sm">
                            <span class="font-semibold"><?= e($nombre) ?></span>
                            <span class="text-right text-neutral-700">
                                <span class="font-semibold text-rivera-negro"><?= colones($o['total']) ?></span>
                                · <?= (int) $o['pagados'] ?> pagado<?= $o['pagados'] === 1 ? '' : 's' ?> de <?= (int) $o['creados'] ?> pedido<?= $o['creados'] === 1 ? '' : 's' ?>
                            </span>
                        </div>
                        <div class="mt-1.5 h-3" aria-hidden="true">
                            <div class="h-3 rounded-r bg-dato <?= clase_ancho_barra($o['total'], $maxOrigen) ?>"></div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <!-- Productos más vendidos -->
    <section class="tarjeta-clara p-5 sm:p-6" aria-labelledby="titulo-productos">
        <h2 id="titulo-productos" class="text-lg">Productos más vendidos</h2>
        <p class="mt-1 text-sm text-neutral-600">Unidades vendidas en pedidos pagados.</p>
        <?php if (!$productos): ?>
            <p class="mt-6 text-sm text-neutral-600">Todavía no hay ventas pagadas en este mes.</p>
        <?php else: ?>
            <ol class="mt-5 space-y-4">
                <?php foreach ($productos as $p): ?>
                    <li title="<?= e($p['nombre'] . ': ' . (int) $p['unidades'] . ' unidad(es), ' . colones($p['total'])) ?>">
                        <div class="flex items-baseline justify-between gap-3 text-sm">
                            <span class="min-w-0 truncate font-semibold"><?= e($p['nombre']) ?></span>
                            <span class="shrink-0 text-neutral-700"><span class="font-semibold text-rivera-negro"><?= (int) $p['unidades'] ?> u.</span> · <?= colones($p['total']) ?></span>
                        </div>
                        <div class="mt-1.5 h-3" aria-hidden="true">
                            <div class="h-3 rounded-r bg-dato <?= clase_ancho_barra((float) $p['unidades'], (float) $maxUnidades) ?>"></div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </section>
</div>

<!-- Detalle por campaña (tabla) -->
<?php if ($filas): ?>
    <section class="tarjeta-clara mt-5 overflow-hidden" aria-labelledby="titulo-campanas">
        <h2 id="titulo-campanas" class="px-5 pt-5 text-lg sm:px-6">Detalle por campaña</h2>
        <div class="mt-3 overflow-x-auto">
            <table class="w-full min-w-[32rem] text-left text-sm">
                <thead class="border-y border-neutral-200 bg-neutral-50 text-xs uppercase tracking-wide text-neutral-600">
                    <tr>
                        <th scope="col" class="px-5 py-2.5 font-semibold sm:px-6">Origen</th>
                        <th scope="col" class="px-3 py-2.5 font-semibold">Campaña</th>
                        <th scope="col" class="px-3 py-2.5 text-right font-semibold">Creados</th>
                        <th scope="col" class="px-3 py-2.5 text-right font-semibold">Pagados</th>
                        <th scope="col" class="px-5 py-2.5 text-right font-semibold sm:px-6">Vendido</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    <?php foreach ($filas as $f): ?>
                        <tr>
                            <td class="px-5 py-2.5 sm:px-6"><?= e(nombre_origen($f['origen'])) ?></td>
                            <td class="px-3 py-2.5 text-neutral-700"><?= $f['campana'] !== '' ? e($f['campana']) : '—' ?></td>
                            <td class="px-3 py-2.5 text-right tabular-nums"><?= (int) $f['creados'] ?></td>
                            <td class="px-3 py-2.5 text-right tabular-nums"><?= (int) $f['pagados'] ?></td>
                            <td class="px-5 py-2.5 text-right font-semibold tabular-nums sm:px-6"><?= colones($f['total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<!-- Generador de enlaces para anuncios -->
<section id="enlaces" class="tarjeta-clara mt-5 p-5 sm:p-6" aria-labelledby="titulo-enlaces">
    <h2 id="titulo-enlaces" class="text-lg">Enlace para tus anuncios</h2>
    <p class="mt-1 text-sm text-neutral-600">
        Usa este enlace en cada anuncio o publicación: así la tienda sabe de dónde vino cada venta y aparece en este reporte.
        Usa un nombre de campaña distinto para cada anuncio (por ejemplo <em>aceite-octubre</em>).
    </p>
    <form method="get" action="<?= e(url('admin/reportes')) ?>#enlaces" class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <input type="hidden" name="mes" value="<?= e($mes) ?>">
        <input type="hidden" name="generar" value="1">
        <div>
            <label for="destino" class="etiqueta-campo">Página</label>
            <select id="destino" name="destino" class="campo-claro">
                <?php foreach ($destinos as $clave => $texto): ?>
                    <option value="<?= e($clave) ?>" <?= $clave === $destino ? 'selected' : '' ?>><?= e($texto) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="fuente" class="etiqueta-campo">Dónde se publica</label>
            <select id="fuente" name="fuente" class="campo-claro">
                <?php foreach ($fuentes as $clave => $texto): ?>
                    <option value="<?= e($clave) ?>" <?= $clave === $fuente ? 'selected' : '' ?>><?= e($texto) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="medio" class="etiqueta-campo">Tipo</label>
            <select id="medio" name="medio" class="campo-claro">
                <?php foreach ($medios as $clave => $texto): ?>
                    <option value="<?= e($clave) ?>" <?= $clave === $medio ? 'selected' : '' ?>><?= e($texto) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="campana" class="etiqueta-campo">Nombre de la campaña</label>
            <input id="campana" name="campana" class="campo-claro" maxlength="60" placeholder="aceite-octubre" value="<?= e($campana) ?>">
        </div>
        <div class="sm:col-span-2 lg:col-span-4">
            <button type="submit" class="btn-primario">Crear enlace</button>
        </div>
    </form>

    <?php if ($enlaceAnuncio): ?>
        <div class="mt-4 rounded-lg border border-neutral-200 bg-neutral-50 p-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-neutral-600">Tu enlace</p>
            <div class="mt-1 flex flex-wrap items-center gap-3">
                <code class="min-w-0 flex-1 break-all text-sm"><?= e($enlaceAnuncio) ?></code>
                <button type="button" class="btn-claro btn-pequeno bg-white" data-copiar="<?= e($enlaceAnuncio) ?>">Copiar</button>
            </div>
        </div>
    <?php endif; ?>
    <p class="mt-3 text-xs text-neutral-600">
        Si alguna vez publicas sin este enlace, la tienda igual intenta reconocer si el cliente llegó desde Facebook, Instagram o Google.
    </p>
</section>

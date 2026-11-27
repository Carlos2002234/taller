<?php
/**
 * Monitoreo (solo el dueño): estado real de la web, el servidor (la Mac), los respaldos,
 * UptimeRobot, la actividad de la tienda y Clarity / Search Console. Ver app/modelos/monitoreo.php.
 * Se recarga sola cada 60 s (public/js/admin.js, data-recargar-cada).
 */

require_once RAIZ . '/app/modelos/monitoreo.php';
require_once RAIZ . '/app/modelos/reportes.php';   // clase_ancho_barra()

requerir_admin('duenio');
$meta['titulo'] = 'Monitoreo';
$meta['layout'] = 'admin/layout';
header('Cache-Control: no-store');

$forzar = isset($_GET['actualizar']);
$web = monitoreo_web($forzar);
$servidor = monitoreo_servidor();
$respaldos = monitoreo_respaldos();
$uptime = monitoreo_uptimerobot($forzar);
$tienda = monitoreo_tienda();
$clarity = monitoreo_clarity();

$general = monitoreo_resumen([
    'la web pública'    => $web['respuesta']['estado'],
    'el certificado HTTPS' => $web['certificado']['estado'],
    'el dominio'        => $web['dominio']['estado'],
    'el servidor'       => $servidor['estado'] === 'sin_datos' ? 'ok' : $servidor['estado'],
    'los respaldos'     => $respaldos['estado'] === 'sin_datos' ? 'aviso' : $respaldos['estado'],
    'UptimeRobot'       => $uptime['estado'] === 'sin_datos' ? 'ok' : $uptime['estado'],
]);
$revisado = time();

$estiloInsignia = [
    'ok'        => ['bg-emerald-50 text-emerald-800 ring-emerald-200', 'bg-emerald-600'],
    'aviso'     => ['bg-amber-50 text-amber-900 ring-amber-200', 'bg-amber-500'],
    'falla'     => ['bg-red-50 text-red-800 ring-red-200', 'bg-red-600'],
    'sin_datos' => ['bg-neutral-100 text-neutral-700 ring-neutral-200', 'bg-neutral-400'],
];
$insignia = function (string $estado, ?string $texto = null) use ($estiloInsignia): string {
    [$caja, $punto] = $estiloInsignia[$estado] ?? $estiloInsignia['sin_datos'];
    return '<span class="inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded px-2 py-0.5 text-xs font-semibold ring-1 ring-inset ' . $caja . '">'
        . '<span class="h-2 w-2 rounded-sm ' . $punto . '" aria-hidden="true"></span>' . e($texto ?? MONITOREO_ESTADOS[$estado] ?? 'Sin datos') . '</span>';
};
$ms = fn (?int $v): string => $v === null ? '—' : number_format($v, 0, ',', '.') . ' ms';
$porc = fn (?float $v): string => $v === null ? '—' : rtrim(rtrim(number_format($v, 2, ',', '.'), '0'), ',') . ' %';
$num = fn (?int $v): string => $v === null ? '—' : number_format($v, 0, ',', '.');

/**
 * Mini gráfico de línea (SVG, sin estilos inline: la CSP no los permite). $valores: lista de int|null.
 * Los huecos (null) cortan la línea. $marcas: índices con fallas (se marcan abajo en rojo).
 */
$grafico = function (array $valores, array $marcas, string $etiqueta): string {
    $ancho = 480; $alto = 72; $n = count($valores);
    $max = max([1, ...array_filter($valores, 'is_int')]);
    $paso = $n > 1 ? $ancho / ($n - 1) : $ancho;
    $tramos = []; $actual = [];
    foreach ($valores as $i => $v) {
        if ($v === null) { if ($actual) { $tramos[] = $actual; $actual = []; } continue; }
        $actual[] = round($i * $paso, 1) . ',' . round($alto - 4 - ($v / $max) * ($alto - 12), 1);
    }
    if ($actual) { $tramos[] = $actual; }
    $svg = '<svg viewBox="0 0 ' . $ancho . ' ' . ($alto + 6) . '" preserveAspectRatio="none" class="h-20 w-full" role="img" aria-label="' . e($etiqueta) . '">';
    $svg .= '<line x1="0" y1="' . $alto . '" x2="' . $ancho . '" y2="' . $alto . '" class="stroke-neutral-200" stroke-width="1" vector-effect="non-scaling-stroke"/>';
    foreach ($tramos as $puntos) {
        $svg .= count($puntos) === 1
            ? '<circle cx="' . explode(',', $puntos[0])[0] . '" cy="' . explode(',', $puntos[0])[1] . '" r="2" class="fill-dato"/>'
            : '<polyline points="' . implode(' ', $puntos) . '" fill="none" class="stroke-dato" stroke-width="2" stroke-linejoin="round" vector-effect="non-scaling-stroke"/>';
    }
    foreach ($marcas as $i) {
        $svg .= '<rect x="' . max(0, round($i * $paso - 3, 1)) . '" y="' . ($alto + 1) . '" width="6" height="5" class="fill-red-600"/>';
    }
    return $svg . '</svg>';
};
$diaBarra = fn (float $u): string => $u >= 99.95 ? 'bg-emerald-600' : ($u >= 99 ? 'bg-amber-500' : 'bg-red-600');
?>
<div data-recargar-cada="60">

<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="font-display text-2xl font-extrabold sm:text-3xl">Monitoreo</h1>
        <p class="mt-1 text-sm text-neutral-600">
            Revisado a las <time datetime="<?= e(date('c', $revisado)) ?>"><?= e(date('g:i:s a', $revisado)) ?></time> · se actualiza sola cada 60 s
        </p>
    </div>
    <a href="<?= e(url('admin/monitoreo?actualizar=1')) ?>" class="btn-claro btn-pequeno bg-white">Actualizar ahora</a>
</div>

<!-- 1. Estado general -->
<section class="tarjeta-clara mt-5 p-5 sm:p-6" aria-labelledby="titulo-general">
    <div class="flex flex-wrap items-center gap-3">
        <?= $insignia($general['estado']) ?>
        <h2 id="titulo-general" class="text-xl not-italic sm:text-2xl"><?= e($general['titulo']) ?></h2>
    </div>
    <?php if ($general['detalle']): ?><p class="mt-2 text-sm text-neutral-700"><?= e($general['detalle']) ?></p><?php endif; ?>
    <dl class="mt-4 grid grid-cols-2 gap-x-6 gap-y-2 border-t border-neutral-100 pt-4 text-sm sm:grid-cols-3 lg:grid-cols-6">
        <?php foreach ([
            ['Web pública', $web['respuesta']['estado']],
            ['HTTPS', $web['certificado']['estado']],
            ['Dominio', $web['dominio']['estado']],
            ['Servidor', $servidor['estado']],
            ['Respaldos', $respaldos['estado']],
            ['UptimeRobot', $uptime['estado']],
        ] as [$nombre, $estado]): ?>
            <div class="flex items-center justify-between gap-2 sm:block">
                <dt class="text-neutral-600"><?= e($nombre) ?></dt>
                <dd class="sm:mt-1"><?= $insignia($estado) ?></dd>
            </div>
        <?php endforeach; ?>
    </dl>
</section>

<!-- 2. La web pública -->
<section class="mt-8" aria-labelledby="titulo-web">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <h2 id="titulo-web" class="text-lg">La web pública</h2>
        <p class="break-all text-sm text-neutral-600"><?= e($web['url']) ?></p>
    </div>
    <div class="mt-3 grid gap-3 md:grid-cols-3">
        <?php $r = $web['respuesta']; ?>
        <div class="tarjeta-clara p-4 sm:p-5">
            <div class="flex items-center justify-between gap-2"><h3 class="text-sm font-semibold not-italic text-neutral-700">Respuesta</h3><?= $insignia($r['estado']) ?></div>
            <p class="mt-2 font-display text-3xl font-extrabold not-italic tabular-nums"><?= e($ms($r['ms'] ?? null)) ?></p>
            <p class="mt-1 text-sm text-neutral-600"><?= e($r['resumen']) ?><?= !empty($r['codigo']) ? ' · código ' . (int) $r['codigo'] : '' ?></p>
            <p class="mt-1 text-xs text-neutral-500">Revisado <?= e(monitoreo_hace((int) $r['revisado'])) ?></p>
        </div>
        <?php $c = $web['certificado']; ?>
        <div class="tarjeta-clara p-4 sm:p-5">
            <div class="flex items-center justify-between gap-2"><h3 class="text-sm font-semibold not-italic text-neutral-700">Certificado HTTPS</h3><?= $insignia($c['estado']) ?></div>
            <?php if (isset($c['vence'])): ?>
                <p class="mt-2 text-lg font-semibold">Vence el <?= e(monitoreo_fecha((int) $c['vence'])) ?></p>
                <p class="mt-1 text-sm text-neutral-600"><?= e($c['resumen']) ?><?= $c['emisor'] ? ' · emitido por ' . e($c['emisor']) : '' ?></p>
                <p class="mt-1 text-xs text-neutral-500"><?= e($c['nota'] ?? '') ?></p>
            <?php else: ?>
                <p class="mt-2 text-sm text-neutral-700"><?= e($c['resumen']) ?></p>
            <?php endif; ?>
        </div>
        <?php $d = $web['dominio']; ?>
        <div class="tarjeta-clara p-4 sm:p-5">
            <div class="flex items-center justify-between gap-2"><h3 class="text-sm font-semibold not-italic text-neutral-700">Dominio</h3><?= $insignia($d['estado']) ?></div>
            <?php if (isset($d['vence'])): ?>
                <p class="mt-2 text-lg font-semibold">Vence el <?= e(monitoreo_fecha((int) $d['vence'])) ?></p>
                <p class="mt-1 text-sm text-neutral-600"><?= e($d['dominio']) ?> · <?= e($d['resumen']) ?></p>
                <p class="mt-1 text-xs text-neutral-500">Registrador: <?= e($d['registrador'] ?: 'no indicado') ?>. Renovar antes de esa fecha (en Cloudflare: Domain Registration → Auto-renew).</p>
            <?php else: ?>
                <p class="mt-2 text-sm text-neutral-700"><?= e($d['resumen']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <?php $h = $web['historial']; ?>
    <div class="tarjeta-clara mt-3 p-4 sm:p-5">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h3 class="text-sm font-semibold not-italic text-neutral-700">Tiempo de respuesta, últimas 24 h</h3>
            <?php if ($h['mediciones']): ?>
                <p class="text-xs text-neutral-600">
                    Disponible <strong class="text-rivera-negro"><?= e($porc($h['disponible'])) ?></strong>
                    · mediana <strong class="text-rivera-negro"><?= e($ms($h['mediana'])) ?></strong>
                    · 95 % por debajo de <strong class="text-rivera-negro"><?= e($ms($h['p95'])) ?></strong>
                    · <?= (int) $h['mediciones'] ?> mediciones<?= $h['fallas'] ? ', ' . (int) $h['fallas'] . ' con error' : '' ?>
                </p>
            <?php endif; ?>
        </div>
        <?php if ($h['mediciones'] >= 2): ?>
            <?php
            $valores = array_column($h['serie'], 'ms');
            $marcas = array_keys(array_filter(array_column($h['serie'], 'fallas')));
            $maxSerie = max([0, ...array_filter($valores, 'is_int')]);
            ?>
            <div class="mt-3 flex gap-2">
                <div class="flex flex-col justify-between pb-2 text-right text-[10px] tabular-nums text-neutral-500" aria-hidden="true">
                    <span><?= e($ms($maxSerie)) ?></span><span>0</span>
                </div>
                <div class="min-w-0 flex-1">
                    <?= $grafico($valores, $marcas, 'Tiempo de respuesta promedio cada 30 minutos; máximo ' . $ms($maxSerie)) ?>
                    <div class="mt-1 flex justify-between text-[10px] text-neutral-500" aria-hidden="true">
                        <span>hace 24 h</span><span>hace 12 h</span><span>ahora</span>
                    </div>
                </div>
            </div>
            <p class="mt-2 text-xs text-neutral-500">Promedio cada 30 min, medido desde esta Mac cada minuto. <span class="font-medium text-red-700">Marcas rojas</span> = tramos con alguna respuesta con error.</p>
        <?php else: ?>
            <p class="mt-2 text-sm text-neutral-600">
                <strong>Sin datos todavía.</strong> El historial lo arma la tarea «Estado del servidor» (una medición por minuto).
                <?= $servidor['tarea']['generado'] ? 'Aparecerá en unos minutos.' : 'Instálala con los pasos de la guía de producción.' ?>
            </p>
        <?php endif; ?>
    </div>
</section>

<!-- 3. Servidor (la Mac) -->
<section class="mt-8" aria-labelledby="titulo-servidor">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <h2 id="titulo-servidor" class="text-lg">Servidor (la Mac)</h2>
        <?php if (!$servidor['tarea']['generado']): ?>
            <p class="text-xs text-neutral-600">Datos de launchd: sin datos (la tarea «Estado del servidor» no está instalada).</p>
        <?php elseif (!$servidor['tarea']['al_dia']): ?>
            <p class="text-xs font-medium text-red-700">Datos de launchd de <?= e(monitoreo_hace((int) $servidor['tarea']['generado'])) ?>: la tarea de estado no corre.</p>
        <?php endif; ?>
    </div>
    <div class="mt-3 grid gap-3 lg:grid-cols-[3fr_2fr]">
        <div class="tarjeta-clara">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">Servicios de la Mac</caption>
                <thead class="border-b border-neutral-200 bg-neutral-50 text-xs uppercase tracking-wide text-neutral-600">
                    <tr><th scope="col" class="px-4 py-2.5">Servicio</th><th scope="col" class="px-4 py-2.5">Estado</th></tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    <?php foreach ($servidor['servicios'] as $s): ?>
                        <tr>
                            <th scope="row" class="px-4 py-3 align-top font-medium">
                                <?= e($s['nombre']) ?>
                                <span class="mt-0.5 block text-xs font-normal text-neutral-600"><?= e($s['detalle']) ?></span>
                            </th>
                            <td class="px-4 py-3 align-top"><?= $insignia($s['estado']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <dl class="tarjeta-clara divide-y divide-neutral-100 text-sm">
            <?php $t = $servidor['tunel']; ?>
            <div class="px-4 py-3">
                <dt class="text-neutral-600">Túnel de Cloudflare</dt>
                <dd class="mt-0.5 font-medium"><?= e($t['resumen']) ?></dd>
                <?php if (!empty($t['centros'])): ?>
                    <dd class="mt-0.5 text-xs text-neutral-600">
                        Centros: <?= e(implode(', ', $t['centros'])) ?>
                        <?= $t['peticiones'] !== null ? ' · ' . e($num($t['peticiones'])) . ' visitas atendidas desde que arrancó' : '' ?>
                        <?= $t['errores'] ? ' · ' . e($num($t['errores'])) . ' con error' : '' ?>
                        <?= $t['version'] ? ' · cloudflared ' . e($t['version']) : '' ?>
                    </dd>
                <?php endif; ?>
            </div>
            <div class="px-4 py-3">
                <dt class="text-neutral-600">Mac encendida</dt>
                <dd class="mt-0.5 font-medium">
                    <?= $servidor['arranque'] ? 'Hace ' . e(monitoreo_duracion(time() - (int) $servidor['arranque'])) . ' (desde el ' . e(monitoreo_fecha((int) $servidor['arranque'])) . ')' : 'Sin datos' ?>
                </dd>
                <?php if ($servidor['macos']): ?><dd class="mt-0.5 text-xs text-neutral-600">macOS <?= e($servidor['macos']) ?></dd><?php endif; ?>
            </div>
            <div class="px-4 py-3">
                <dt class="flex items-center justify-between gap-2 text-neutral-600">Reposo y cortes de luz <?= $insignia($servidor['reposo']['estado']) ?></dt>
                <dd class="mt-0.5"><?= e($servidor['reposo']['texto']) ?></dd>
            </div>
            <?php $disco = $servidor['disco']; ?>
            <div class="px-4 py-3">
                <dt class="flex items-center justify-between gap-2 text-neutral-600">Disco <?= $insignia($disco['estado']) ?></dt>
                <?php if (isset($disco['libre'])): ?>
                    <dd class="mt-0.5 font-medium"><?= e(monitoreo_bytes($disco['libre'])) ?> libres de <?= e(monitoreo_bytes($disco['total'])) ?></dd>
                    <dd class="mt-2 h-2 overflow-hidden rounded-sm bg-neutral-100" aria-hidden="true">
                        <div class="h-full bg-dato <?= clase_ancho_barra(100 - $disco['porcentaje'], 100) ?>"></div>
                    </dd>
                    <dd class="mt-1 text-xs text-neutral-600"><?= 100 - (int) $disco['porcentaje'] ?> % usado</dd>
                <?php else: ?>
                    <dd class="mt-0.5">Sin datos</dd>
                <?php endif; ?>
            </div>
            <div class="px-4 py-3">
                <dt class="text-neutral-600">Versiones</dt>
                <dd class="mt-0.5">PHP <?= e($servidor['php']) ?> (<?= e($servidor['php_sapi']) ?>)</dd>
                <dd class="mt-0.5">
                    <?php if ($servidor['bd']['estado'] === 'ok'): ?>
                        MariaDB <?= e(preg_replace('/-MariaDB.*$/', '', (string) $servidor['bd']['version'])) ?> · encendida hace <?= e(monitoreo_duracion((int) $servidor['bd']['encendida'])) ?>
                    <?php else: ?>
                        <span class="font-medium text-red-700">La base de datos no responde</span>
                    <?php endif; ?>
                </dd>
            </div>
        </dl>
    </div>
</section>

<!-- 4. Respaldos -->
<section class="mt-8" aria-labelledby="titulo-respaldos">
    <h2 id="titulo-respaldos" class="text-lg">Respaldos</h2>
    <div class="tarjeta-clara mt-3 p-4 sm:p-5">
        <div class="flex flex-wrap items-center gap-3">
            <?= $insignia($respaldos['estado']) ?>
            <p class="font-medium"><?= e($respaldos['resumen']) ?></p>
        </div>
        <?php if (isset($respaldos['ultimo'])): ?>
            <?php $u = $respaldos['ultimo']; ?>
            <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                <div><dt class="text-neutral-600">Último respaldo</dt><dd class="mt-0.5 font-medium"><?= e(monitoreo_fecha_hora($u['momento'])) ?></dd><dd class="text-xs text-neutral-600"><?= e(monitoreo_hace($u['momento'])) ?></dd></div>
                <div><dt class="text-neutral-600">Tamaño</dt><dd class="mt-0.5 font-medium"><?= e(monitoreo_bytes($u['tamano'])) ?></dd><dd class="text-xs text-neutral-600"><?= $u['completo'] ? 'Base, fotos, comprobantes y sumas de control' : 'Faltan archivos: ' . e(implode(', ', array_diff(['base.sql.gz', 'uploads.tar.gz', 'comprobantes.tar.gz', 'SHA256SUMS'], array_keys($u['archivos'])))) ?></dd></div>
                <div><dt class="text-neutral-600">Guardados</dt><dd class="mt-0.5 font-medium"><?= (int) $respaldos['cantidad'] ?> respaldo<?= $respaldos['cantidad'] === 1 ? '' : 's' ?></dd><dd class="text-xs text-neutral-600">Desde el <?= e(monitoreo_fecha((int) $respaldos['mas_viejo'])) ?> · se conservan 14 días</dd></div>
                <div><dt class="text-neutral-600">Espacio total</dt><dd class="mt-0.5 font-medium"><?= e(monitoreo_bytes($respaldos['total'])) ?></dd><dd class="break-all text-xs text-neutral-600"><?= e($respaldos['carpeta']) ?></dd></div>
            </dl>
            <?php if ($respaldos['registro']): ?>
                <p class="mt-4 rounded bg-neutral-50 px-3 py-2 font-mono text-xs text-neutral-700">Último registro: <?= e($respaldos['registro']) ?></p>
            <?php endif; ?>
        <?php endif; ?>
        <p class="mt-4 text-xs text-neutral-600">
            Recuerda: los respaldos están en el mismo disco que la tienda. Si el disco de la Mac falla se pierden los dos;
            copia de vez en cuando la carpeta de respaldos a un disco externo o a la nube.
        </p>
    </div>
</section>

<!-- 5. UptimeRobot -->
<section class="mt-8" aria-labelledby="titulo-uptime">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <h2 id="titulo-uptime" class="text-lg">UptimeRobot (vigilancia desde afuera)</h2>
        <?php if (!empty($uptime['revisado']) && empty($uptime['sin_clave'])): ?><p class="text-xs text-neutral-600">Consultado <?= e(monitoreo_hace((int) $uptime['revisado'])) ?></p><?php endif; ?>
    </div>
    <?php if (!empty($uptime['sin_clave'])): ?>
        <div class="tarjeta-clara mt-3 p-4 sm:p-5">
            <div class="flex flex-wrap items-center gap-3"><?= $insignia('sin_datos') ?><p class="font-medium">Falta conectar UptimeRobot</p></div>
            <p class="mt-2 text-sm text-neutral-700">UptimeRobot revisa la web desde internet cada 5 minutos y avisa por correo si se cae (incluso si la Mac está apagada). Para ver aquí su estado:</p>
            <ol class="mt-3 list-decimal space-y-1.5 pl-5 text-sm text-neutral-700">
                <li>Entra a <a class="font-semibold text-rivera-rojo underline underline-offset-2" href="https://dashboard.uptimerobot.com/" target="_blank" rel="noopener">dashboard.uptimerobot.com</a> y crea un monitor HTTP(s) para https://riveraautomotriz.com (si aún no existe).</li>
                <li>Ve a <strong>Integrations &amp; API</strong> → <strong>API</strong> → <strong>Read-only API key</strong> → crear y copiar (empieza con «ur»).</li>
                <li>Pégala en <a class="font-semibold text-rivera-rojo underline underline-offset-2" href="<?= e(url('admin/configuracion#monitoreo')) ?>">Configuración → Monitoreo</a>. Usa SOLO la de lectura: con ella nadie puede cambiar ni borrar monitores.</li>
            </ol>
        </div>
    <?php elseif (empty($uptime['monitores'])): ?>
        <div class="tarjeta-clara mt-3 p-4 sm:p-5">
            <div class="flex flex-wrap items-center gap-3"><?= $insignia($uptime['estado']) ?><p class="text-sm"><?= e($uptime['resumen']) ?></p></div>
        </div>
    <?php else: ?>
        <div class="mt-3 grid gap-3">
            <?php foreach ($uptime['monitores'] as $m): ?>
                <article class="tarjeta-clara p-4 sm:p-5">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="min-w-0">
                            <h3 class="truncate text-base not-italic"><?= e($m['nombre']) ?></h3>
                            <p class="truncate text-xs text-neutral-600"><?= e($m['url']) ?><?= $m['intervalo'] ? ' · cada ' . intdiv($m['intervalo'], 60) . ' min' : '' ?></p>
                        </div>
                        <?= $insignia($m['estado'], $m['texto']) ?>
                    </div>
                    <dl class="mt-4 grid grid-cols-3 gap-3 text-sm sm:grid-cols-4">
                        <div><dt class="text-neutral-600">24 h</dt><dd class="mt-0.5 font-semibold tabular-nums"><?= e($porc($m['dia'])) ?></dd></div>
                        <div><dt class="text-neutral-600">7 días</dt><dd class="mt-0.5 font-semibold tabular-nums"><?= e($porc($m['semana'])) ?></dd></div>
                        <div><dt class="text-neutral-600">30 días</dt><dd class="mt-0.5 font-semibold tabular-nums"><?= e($porc($m['mes'])) ?></dd></div>
                        <div class="hidden sm:block"><dt class="text-neutral-600">Respuesta media</dt><dd class="mt-0.5 font-semibold tabular-nums"><?= e($ms($m['promedio'])) ?></dd></div>
                    </dl>
                    <?php if ($m['dias']): ?>
                        <div class="mt-4">
                            <p class="text-xs text-neutral-600">Disponibilidad por día, últimos 30 días</p>
                            <div class="mt-1.5 flex h-8 gap-[2px]" role="img" aria-label="Disponibilidad diaria: <?= e(implode(', ', array_map(fn ($u) => $porc($u), $m['dias']))) ?>">
                                <?php foreach ($m['dias'] as $i => $u): ?>
                                    <span class="flex-1 rounded-[2px] <?= $diaBarra($u) ?>" title="<?= e(monitoreo_fecha(strtotime('-' . (29 - $i) . ' day', strtotime('today'))) . ': ' . $porc($u)) ?>"></span>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-1 flex justify-between text-[10px] text-neutral-500" aria-hidden="true"><span>hace 30 días</span><span>hoy</span></div>
                            <p class="mt-1 text-[11px] text-neutral-500">Verde: 100 % · ámbar: 99 % o más · rojo: menos de 99 %.</p>
                        </div>
                    <?php endif; ?>
                    <?php if (count($m['tiempos']) >= 2): ?>
                        <div class="mt-4">
                            <p class="text-xs text-neutral-600">Tiempo de respuesta medido por UptimeRobot, últimas 24 h (máx. <?= e($ms(max(array_column($m['tiempos'], 'ms')))) ?>)</p>
                            <?= $grafico(array_column($m['tiempos'], 'ms'), [], 'Tiempo de respuesta según UptimeRobot') ?>
                        </div>
                    <?php endif; ?>
                    <div class="mt-4">
                        <p class="text-xs text-neutral-600">Últimas caídas</p>
                        <?php if ($m['incidentes']): ?>
                            <ul class="mt-1 divide-y divide-neutral-100 text-sm">
                                <?php foreach ($m['incidentes'] as $inc): ?>
                                    <li class="flex flex-wrap justify-between gap-x-4 py-1.5">
                                        <span><?= e(monitoreo_fecha_hora($inc['momento'])) ?><?= $inc['motivo'] ? ' · ' . e($inc['motivo']) : '' ?></span>
                                        <span class="tabular-nums text-neutral-600">duró <?= e(monitoreo_duracion($inc['duracion'])) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="mt-1 text-sm">Ninguna registrada.</p>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if (empty($uptime['sin_clave']) && $uptime['estado'] === 'falla' && empty($uptime['monitores'])): ?>
        <p class="mt-2 text-xs text-neutral-600">Revisa la clave en <a class="font-semibold text-rivera-rojo underline underline-offset-2" href="<?= e(url('admin/configuracion#monitoreo')) ?>">Configuración → Monitoreo</a>.</p>
    <?php endif; ?>
</section>

<!-- 6. Actividad de la tienda -->
<section class="mt-8" aria-labelledby="titulo-tienda">
    <h2 id="titulo-tienda" class="text-lg">Actividad de la tienda</h2>
    <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        <?php foreach ([
            ['Pedidos hoy', $tienda['pedidos_hoy'], 'admin/pedidos', false],
            ['Pedidos, 7 días', $tienda['pedidos_7'], 'admin/pedidos', false],
            ['Comprobantes por revisar', $tienda['por_revisar'], 'admin/pedidos?ver=por-revisar', true],
            ['Esperando pago', $tienda['por_pagar'], 'admin/pedidos', false],
            ['Consultas nuevas', $tienda['consultas'], 'admin/consultas?ver=nueva', true],
            ['Productos sin stock', $tienda['sin_stock'], 'admin/productos', true],
        ] as [$texto, $valor, $ruta, $atencion]): ?>
            <a href="<?= e(url($ruta)) ?>" class="tarjeta-clara block p-4 hover:border-neutral-400">
                <p class="text-sm text-neutral-600"><?= e($texto) ?></p>
                <p class="mt-1 font-display text-2xl font-extrabold not-italic tabular-nums <?= $atencion && $valor ? 'text-rivera-rojo' : '' ?>"><?= $valor === null ? 'Sin datos' : e($num($valor)) ?></p>
            </a>
        <?php endforeach; ?>
    </div>
    <?php if ($tienda['productos'] !== null): ?><p class="mt-2 text-xs text-neutral-600"><?= e($num($tienda['productos'])) ?> productos visibles en la tienda.</p><?php endif; ?>
</section>

<!-- 7. Clarity y Search Console -->
<section class="mt-8" aria-labelledby="titulo-analitica">
    <h2 id="titulo-analitica" class="text-lg">Microsoft Clarity y Google Search Console</h2>
    <div class="mt-3 grid gap-3 md:grid-cols-2">
        <div class="tarjeta-clara p-4 sm:p-5">
            <div class="flex items-center justify-between gap-2">
                <h3 class="text-base not-italic">Microsoft Clarity</h3>
                <?= $clarity['instalado'] ? $insignia('ok', 'Instalado') : $insignia('sin_datos', 'No instalado') ?>
            </div>
            <p class="mt-1 text-sm text-neutral-600">
                <?php if ($clarity['instalado']): ?>
                    Proyecto <span class="font-mono"><?= e($clarity['id']) ?></span>: graba sesiones anónimas y mapas de calor de la tienda (no del panel).
                <?php elseif ($clarity['id'] !== ''): ?>
                    CLARITY_ID está configurado (<span class="font-mono"><?= e($clarity['id']) ?></span>), pero solo se carga en producción.
                <?php else: ?>
                    Falta CLARITY_ID en el .env del servidor.
                <?php endif; ?>
            </p>
            <?php $cd = $clarity['datos']; ?>
            <?php if ($cd && $cd['estado'] === 'ok'): ?>
                <dl class="mt-4 grid grid-cols-3 gap-3 text-sm">
                    <div><dt class="text-neutral-600">Sesiones</dt><dd class="mt-0.5 text-xl font-semibold tabular-nums"><?= e($num($cd['sesiones'])) ?></dd></div>
                    <div><dt class="text-neutral-600">Personas</dt><dd class="mt-0.5 text-xl font-semibold tabular-nums"><?= e($num($cd['usuarios'])) ?></dd></div>
                    <div><dt class="text-neutral-600">Páginas por sesión</dt><dd class="mt-0.5 text-xl font-semibold tabular-nums"><?= $cd['paginas'] === null ? '—' : e(number_format($cd['paginas'], 1, ',', '.')) ?></dd></div>
                </dl>
                <p class="mt-2 text-xs text-neutral-600">
                    Últimos 3 días<?= $cd['robots'] ? ' · ' . e($num($cd['robots'])) . ' sesiones de robots descartadas' : '' ?><?= $cd['desplazamiento'] !== null ? ' · bajan en promedio hasta el ' . (int) $cd['desplazamiento'] . ' % de la página' : '' ?>.
                    Datos de <?= e(monitoreo_hace((int) $cd['revisado'])) ?> (Clarity permite pocas consultas al día; se renuevan cada 4 h).
                </p>
            <?php elseif ($cd): ?>
                <p class="mt-3 text-sm"><?= $insignia('falla') ?> <?= e($cd['resumen']) ?></p>
            <?php elseif (!empty($clarity['sin_token'])): ?>
                <p class="mt-3 text-xs text-neutral-600">
                    Para ver aquí sesiones y personas de los últimos 3 días: en Clarity → <strong>Settings</strong> → <strong>Data Export</strong> → <strong>Generate new API token</strong>,
                    y pégalo en <a class="font-semibold text-rivera-rojo underline underline-offset-2" href="<?= e(url('admin/configuracion#monitoreo')) ?>">Configuración → Monitoreo</a>.
                </p>
            <?php endif; ?>
            <a href="<?= e($clarity['panel']) ?>" target="_blank" rel="noopener" class="btn-claro btn-pequeno mt-4 inline-flex bg-white">Abrir el panel de Clarity ↗</a>
        </div>
        <div class="tarjeta-clara p-4 sm:p-5">
            <div class="flex items-center justify-between gap-2">
                <h3 class="text-base not-italic">Google Search Console</h3>
                <?= $insignia('sin_datos', 'Solo enlace') ?>
            </div>
            <p class="mt-1 text-sm text-neutral-600">
                Cómo aparece la web en Google: búsquedas, clics, páginas indexadas y errores de rastreo.
                Search Console no se consulta desde aquí (su API requiere una cuenta de servicio de Google); ábrelo con el botón.
            </p>
            <a href="https://search.google.com/search-console?resource_id=sc-domain:riveraautomotriz.com" target="_blank" rel="noopener" class="btn-claro btn-pequeno mt-4 inline-flex bg-white">Abrir Search Console ↗</a>
        </div>
    </div>
</section>

<!-- 8. Enlaces rápidos -->
<section class="mt-8 mb-4" aria-labelledby="titulo-enlaces">
    <h2 id="titulo-enlaces" class="text-lg">Enlaces rápidos</h2>
    <div class="tarjeta-clara mt-3 p-4 text-sm sm:p-5">
        <ul class="grid gap-x-6 gap-y-2 sm:grid-cols-2">
            <?php foreach ($uptime['paginas'] ?? [] as $p): ?>
                <li><a class="font-semibold text-rivera-rojo underline underline-offset-2" href="<?= e($p['url']) ?>" target="_blank" rel="noopener">Página de estado pública: <?= e($p['nombre']) ?> ↗</a></li>
            <?php endforeach; ?>
            <li><a class="font-semibold text-rivera-rojo underline underline-offset-2" href="https://dashboard.uptimerobot.com/monitors" target="_blank" rel="noopener">Panel de UptimeRobot ↗</a></li>
            <li><a class="font-semibold text-rivera-rojo underline underline-offset-2" href="https://dash.cloudflare.com/" target="_blank" rel="noopener">Panel de Cloudflare (dominio, DNS y túnel) ↗</a></li>
            <li><a class="font-semibold text-rivera-rojo underline underline-offset-2" href="<?= e($clarity['panel']) ?>" target="_blank" rel="noopener">Microsoft Clarity ↗</a></li>
            <li><a class="font-semibold text-rivera-rojo underline underline-offset-2" href="https://search.google.com/search-console?resource_id=sc-domain:riveraautomotriz.com" target="_blank" rel="noopener">Google Search Console ↗</a></li>
        </ul>
        <p class="mt-4 border-t border-neutral-100 pt-3 text-neutral-700">
            Guía de producción (cómo reiniciar, respaldos, túnel, reposo), en esta Mac:<br>
            <code class="mt-1 inline-block break-all rounded bg-neutral-100 px-2 py-1 font-mono text-xs"><?= e(MONITOREO_GUIA) ?></code>
        </p>
    </div>
</section>

</div>

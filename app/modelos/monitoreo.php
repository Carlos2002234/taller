<?php
/**
 * Monitoreo (Admin → Monitoreo, solo el dueño): estado REAL de todo lo que sostiene la web.
 *
 * Cada bloque devuelve ['estado' => ok|aviso|falla|sin_datos, 'resumen' => texto, ...datos].
 * Nada se inventa: si algo no se puede leer, el bloque queda en 'sin_datos' con el motivo.
 * Las consultas externas (web pública, certificado, dominio, UptimeRobot, Clarity) se hacen
 * desde el servidor con tiempo límite corto y se guardan unos minutos en storage/cache, así
 * la página carga rápido y nunca se cae porque un servicio de afuera falle.
 * Las claves (UptimeRobot, Clarity) viven en settings y NUNCA se envían al navegador.
 */

declare(strict_types=1);

// Servicios de launchd de la Mac de producción: etiqueta => [nombre, tipo, puerto local]
// tipo: tcp (debe escuchar en el puerto) | tunel (cloudflared) | programada (corre a cierta hora)
const MONITOREO_SERVICIOS = [
    'sh.brew.caddy'                      => ['Caddy (servidor web)', 'tcp', 8080],
    'sh.brew.php'                        => ['PHP-FPM', 'tcp', 9081],
    'sh.brew.mariadb'                    => ['MariaDB (base de datos)', 'tcp', 3306],
    'com.rivera.cloudflared'             => ['Túnel de Cloudflare', 'tunel', null],
    'com.rivera.taller.respaldo'         => ['Respaldo diario (2:30 a.m.)', 'programada', null],
    'com.rivera.taller.pedidos-vencidos' => ['Cancelar pedidos vencidos (cada hora)', 'programada', null],
    'com.rivera.taller.estado'           => ['Estado del servidor (cada minuto)', 'programada', null],
];

const MONITOREO_ESTADOS = [
    'ok'        => 'Funciona',
    'aviso'     => 'Atención',
    'falla'     => 'Falla',
    'sin_datos' => 'Sin datos',
];

const MONITOREO_GUIA = '/Users/carlosrivera/taller-local/produccion/GUIA-PRODUCCION.md';
const MONITOREO_RESPALDO_MAX_HORAS = 26;

// ---------------------------------------------------------------------
// Utilidades
// ---------------------------------------------------------------------

/** URL pública que se vigila (MONITOREO_URL en .env; si no, APP_URL). */
function monitoreo_url(): string
{
    return rtrim((string) (env('MONITOREO_URL') ?: config('app.url')), '/') . '/';
}

/** Escribe JSON de forma atómica (nunca deja un archivo a medias). */
function monitoreo_escribir_json(string $archivo, mixed $datos): void
{
    $carpeta = dirname($archivo);
    if (!is_dir($carpeta)) {
        @mkdir($carpeta, 0770, true);
    }
    $tmp = $archivo . '.' . getmypid() . '.tmp';
    if (@file_put_contents($tmp, json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) !== false) {
        @rename($tmp, $archivo);
    }
}

function monitoreo_leer_json(string $archivo): ?array
{
    if (!is_file($archivo) || !is_readable($archivo)) {
        return null;
    }
    $datos = json_decode((string) file_get_contents($archivo), true);
    return is_array($datos) ? $datos : null;
}

/**
 * Guarda el resultado de $obtener() durante $vigencia segundos (y $vigenciaError si falló).
 * $forzar (botón "Actualizar ahora") ignora la caché, pero nunca más de una vez cada 20 s.
 */
function monitoreo_cache(string $clave, int $vigencia, callable $obtener, bool $forzar = false, ?int $vigenciaError = null): array
{
    $archivo = RAIZ . '/storage/cache/monitoreo-' . $clave . '.json';
    $guardado = monitoreo_leer_json($archivo);
    if ($guardado && isset($guardado['t'], $guardado['datos'])) {
        $edad = time() - (int) $guardado['t'];
        $limite = ($guardado['datos']['estado'] ?? '') === 'falla' ? ($vigenciaError ?? min($vigencia, 120)) : $vigencia;
        if ($edad < 20 || ($edad < $limite && !$forzar)) {
            return $guardado['datos'] + ['revisado' => (int) $guardado['t']];
        }
    }
    try {
        $datos = $obtener();
    } catch (Throwable $e) {
        error_log('Monitoreo ' . $clave . ': ' . $e->getMessage());
        $datos = ['estado' => 'falla', 'resumen' => 'Error al revisar: ' . mb_substr($e->getMessage(), 0, 150)];
    }
    monitoreo_escribir_json($archivo, ['t' => time(), 'datos' => $datos]);
    return $datos + ['revisado' => time()];
}

/**
 * Petición HTTP con tiempo límite corto. Devuelve ['codigo', 'cuerpo', 'ms', 'error'].
 * opciones: timeout, metodo (GET|POST), campos (POST), encabezados, seguir (redirecciones)
 */
function monitoreo_http(string $url, array $opciones = []): array
{
    if (!function_exists('curl_init')) {
        return ['codigo' => 0, 'cuerpo' => '', 'ms' => null, 'error' => 'PHP no tiene la extensión curl'];
    }
    $c = curl_init($url);
    curl_setopt_array($c, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $opciones['timeout'] ?? 6,
        CURLOPT_CONNECTTIMEOUT => min(4, $opciones['timeout'] ?? 6),
        CURLOPT_FOLLOWLOCATION => $opciones['seguir'] ?? false,
        CURLOPT_MAXREDIRS      => 3,
        CURLOPT_USERAGENT      => 'RiveraMonitoreo/1.0 (+panel del negocio)',
        CURLOPT_HTTPHEADER     => $opciones['encabezados'] ?? [],
        CURLOPT_PROTOCOLS      => CURLPROTO_HTTP | CURLPROTO_HTTPS,
    ]);
    if (($opciones['metodo'] ?? 'GET') === 'POST') {
        curl_setopt($c, CURLOPT_POST, true);
        curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($opciones['campos'] ?? []));
    }
    $cuerpo = curl_exec($c);
    $codigo = (int) curl_getinfo($c, CURLINFO_RESPONSE_CODE);
    $ms = $cuerpo === false ? null : (int) round(curl_getinfo($c, CURLINFO_TOTAL_TIME) * 1000);
    $error = $cuerpo === false ? curl_error($c) : null;
    return ['codigo' => $codigo, 'cuerpo' => is_string($cuerpo) ? $cuerpo : '', 'ms' => $ms, 'error' => $error];
}

/** ¿Hay algo escuchando en este puerto local? */
function monitoreo_puerto_abierto(int $puerto): bool
{
    $s = @fsockopen('127.0.0.1', $puerto, $codigo, $mensaje, 1.0);
    if ($s) {
        fclose($s);
        return true;
    }
    return false;
}

function monitoreo_bytes(int|float $bytes): string
{
    $unidades = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($unidades) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return number_format($bytes, $i >= 3 ? 1 : 0, ',', '.') . ' ' . $unidades[$i];
}

/** "hace 3 min", "hace 2 h", "hace 4 días" */
function monitoreo_hace(int $momento): string
{
    $s = max(0, time() - $momento);
    return match (true) {
        $s < 60    => 'hace ' . $s . ' s',
        $s < 3600  => 'hace ' . intdiv($s, 60) . ' min',
        $s < 86400 => 'hace ' . intdiv($s, 3600) . ' h',
        default    => 'hace ' . intdiv($s, 86400) . ' día' . (intdiv($s, 86400) === 1 ? '' : 's'),
    };
}

function monitoreo_duracion(int $segundos): string
{
    $d = intdiv($segundos, 86400);
    $h = intdiv($segundos % 86400, 3600);
    $m = intdiv($segundos % 3600, 60);
    return $d > 0 ? "{$d} d {$h} h" : ($h > 0 ? "{$h} h {$m} min" : "{$m} min");
}

function monitoreo_fecha(int $momento): string
{
    $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'set', 'oct', 'nov', 'dic'];
    return date('j', $momento) . ' ' . $meses[(int) date('n', $momento) - 1] . ' ' . date('Y', $momento);
}

function monitoreo_fecha_hora(int $momento): string
{
    return monitoreo_fecha($momento) . ', ' . date('g:i a', $momento);
}

/** Peor estado de una lista (para el resumen de cada bloque). */
function monitoreo_peor(array $estados): string
{
    foreach (['falla', 'aviso', 'sin_datos', 'ok'] as $e) {
        if (in_array($e, $estados, true)) {
            return $e;
        }
    }
    return 'sin_datos';
}

// ---------------------------------------------------------------------
// 1. Web pública: respuesta, historial (24 h), certificado HTTPS y dominio
// ---------------------------------------------------------------------

function monitoreo_web(bool $forzar): array
{
    $url = monitoreo_url();
    $host = (string) parse_url($url, PHP_URL_HOST);

    $respuesta = monitoreo_cache('web', 120, function () use ($url) {
        $r = monitoreo_http($url, ['timeout' => 8]);
        if ($r['error'] !== null || $r['codigo'] === 0) {
            return ['estado' => 'falla', 'resumen' => 'No responde: ' . ($r['error'] ?: 'sin respuesta'), 'codigo' => 0, 'ms' => null];
        }
        $estado = $r['codigo'] === 200 ? ($r['ms'] > 3000 ? 'aviso' : 'ok') : 'falla';
        $resumen = $r['codigo'] === 200
            ? ($r['ms'] > 3000 ? 'Responde, pero lento' : 'Responde bien')
            : 'Responde con error ' . $r['codigo'];
        return ['estado' => $estado, 'resumen' => $resumen, 'codigo' => $r['codigo'], 'ms' => $r['ms']];
    }, $forzar);

    // Historial de la tarea estado-servidor (una medición por minuto)
    $historial = monitoreo_leer_json(RAIZ . '/storage/estado/web-historial.json') ?? [];
    $buenas = count(array_filter($historial, fn ($p) => ($p['c'] ?? 0) === 200));
    $tiempos = array_values(array_filter(array_column($historial, 'ms'), 'is_int'));
    sort($tiempos);
    $serie = monitoreo_serie_tiempos($historial, 48);   // 48 tramos de 30 min

    $esHttps = str_starts_with($url, 'https://');
    $certificado = !$esHttps
        ? ['estado' => 'sin_datos', 'resumen' => 'No aplica: la dirección vigilada no usa HTTPS (' . $url . ').']
        : monitoreo_cache('certificado', 6 * 3600, fn () => monitoreo_certificado($host), $forzar, 600);

    $dominio = !filter_var($host, FILTER_VALIDATE_IP) && str_contains($host, '.') && $host !== 'localhost'
        ? monitoreo_cache('dominio', 12 * 3600, fn () => monitoreo_dominio($host), $forzar, 1800)
        : ['estado' => 'sin_datos', 'resumen' => 'No aplica: la dirección vigilada no es un dominio (' . $host . ').'];

    return [
        'url'         => $url,
        'respuesta'   => $respuesta,
        'historial'   => [
            'mediciones' => count($historial),
            'disponible' => $historial ? round($buenas / count($historial) * 100, 2) : null,
            'mediana'    => $tiempos ? $tiempos[intdiv(count($tiempos) - 1, 2)] : null,
            'p95'        => $tiempos ? $tiempos[max(0, (int) ceil(count($tiempos) * 0.95) - 1)] : null,
            'serie'      => $serie,
            'fallas'     => count($historial) - $buenas,
        ],
        'certificado' => $certificado,
        'dominio'     => $dominio,
        'estado'      => monitoreo_peor([$respuesta['estado'], $certificado['estado'] === 'sin_datos' ? 'ok' : $certificado['estado'], $dominio['estado'] === 'sin_datos' ? 'ok' : $dominio['estado']]),
    ];
}

/** Promedio de tiempo de respuesta por tramo (para el mini gráfico). null = sin mediciones en ese tramo. */
function monitoreo_serie_tiempos(array $historial, int $tramos): array
{
    $fin = time();
    $ancho = intdiv(86400, $tramos);
    $sumas = array_fill(0, $tramos, 0);
    $cuentas = array_fill(0, $tramos, 0);
    $fallas = array_fill(0, $tramos, 0);
    foreach ($historial as $p) {
        $i = $tramos - 1 - intdiv($fin - (int) ($p['t'] ?? 0), $ancho);
        if ($i < 0 || $i >= $tramos) {
            continue;
        }
        if (($p['c'] ?? 0) !== 200) {
            $fallas[$i]++;
        }
        if (is_int($p['ms'] ?? null)) {
            $sumas[$i] += $p['ms'];
            $cuentas[$i]++;
        }
    }
    $serie = [];
    for ($i = 0; $i < $tramos; $i++) {
        $serie[] = ['ms' => $cuentas[$i] ? (int) round($sumas[$i] / $cuentas[$i]) : null, 'fallas' => $fallas[$i], 'desde' => $fin - ($tramos - $i) * $ancho];
    }
    return $serie;
}

function monitoreo_certificado(string $host): array
{
    $contexto = stream_context_create(['ssl' => ['capture_peer_cert' => true, 'SNI_enabled' => true, 'peer_name' => $host]]);
    $s = @stream_socket_client("ssl://{$host}:443", $codigo, $mensaje, 6, STREAM_CLIENT_CONNECT, $contexto);
    if (!$s) {
        return ['estado' => 'falla', 'resumen' => 'No se pudo abrir HTTPS: ' . ($mensaje ?: 'sin respuesta')];
    }
    $cert = stream_context_get_params($s)['options']['ssl']['peer_certificate'] ?? null;
    fclose($s);
    $datos = $cert ? openssl_x509_parse($cert) : false;
    if (!$datos || empty($datos['validTo_time_t'])) {
        return ['estado' => 'falla', 'resumen' => 'No se pudo leer el certificado'];
    }
    $vence = (int) $datos['validTo_time_t'];
    $dias = (int) floor(($vence - time()) / 86400);
    $emisor = $datos['issuer']['O'] ?? ($datos['issuer']['CN'] ?? '');
    return [
        'estado'  => $dias < 0 ? 'falla' : ($dias <= 7 ? 'aviso' : 'ok'),
        'resumen' => $dias < 0 ? 'Vencido' : 'Válido, vence en ' . $dias . ' día' . ($dias === 1 ? '' : 's'),
        'vence'   => $vence,
        'dias'    => $dias,
        'emisor'  => is_array($emisor) ? implode(', ', $emisor) : (string) $emisor,
        'nota'    => 'Cloudflare lo renueva solo.',
    ];
}

/** Datos públicos del dominio por RDAP (registro oficial de dominios): vencimiento y registrador. */
function monitoreo_dominio(string $host): array
{
    $partes = explode('.', $host);
    $dominio = implode('.', array_slice($partes, -2));
    $tld = end($partes);
    $url = in_array($tld, ['com', 'net'], true)
        ? "https://rdap.verisign.com/{$tld}/v1/domain/" . rawurlencode($dominio)
        : 'https://rdap.org/domain/' . rawurlencode($dominio);
    $r = monitoreo_http($url, ['timeout' => 6, 'seguir' => true, 'encabezados' => ['Accept: application/rdap+json']]);
    $datos = $r['codigo'] === 200 ? json_decode($r['cuerpo'], true) : null;
    if (!is_array($datos)) {
        return ['estado' => 'sin_datos', 'resumen' => 'El registro de dominios no respondió (' . ($r['error'] ?: 'código ' . $r['codigo']) . '). Se reintenta en 30 min.'];
    }
    $vence = null;
    foreach ($datos['events'] ?? [] as $evento) {
        if (($evento['eventAction'] ?? '') === 'expiration') {
            $vence = strtotime((string) $evento['eventDate']) ?: null;
        }
    }
    $registrador = '';
    foreach ($datos['entities'] ?? [] as $entidad) {
        if (in_array('registrar', $entidad['roles'] ?? [], true)) {
            foreach ($entidad['vcardArray'][1] ?? [] as $campo) {
                if (($campo[0] ?? '') === 'fn') {
                    $registrador = rtrim((string) $campo[3], '. ');
                }
            }
        }
    }
    if ($vence === null) {
        return ['estado' => 'sin_datos', 'resumen' => 'El registro no indica la fecha de vencimiento.', 'registrador' => $registrador];
    }
    $dias = (int) floor(($vence - time()) / 86400);
    return [
        'estado'      => $dias < 0 ? 'falla' : ($dias <= 30 ? 'aviso' : 'ok'),
        'resumen'     => $dias < 0 ? 'Vencido' : 'Vence en ' . $dias . ' días',
        'dominio'     => $dominio,
        'vence'       => $vence,
        'dias'        => $dias,
        'registrador' => $registrador,
    ];
}

// ---------------------------------------------------------------------
// 2. Servidor (la Mac)
// ---------------------------------------------------------------------

function monitoreo_servidor(): array
{
    $estado = monitoreo_leer_json(RAIZ . '/storage/estado/servidor.json');
    $edad = $estado ? time() - (int) ($estado['generado'] ?? 0) : null;
    $tareaAlDia = $edad !== null && $edad <= 180;

    // Servicios: launchd (de la tarea) + comprobación en vivo del puerto / del túnel
    $servicios = [];
    $tunel = monitoreo_tunel();
    foreach (MONITOREO_SERVICIOS as $etiqueta => [$nombre, $tipo, $puerto]) {
        $ld = $estado['servicios'][$etiqueta] ?? null;
        $fila = ['nombre' => $nombre, 'etiqueta' => $etiqueta, 'detalle' => ''];
        if ($tipo === 'tcp') {
            $abierto = monitoreo_puerto_abierto((int) $puerto);
            $fila['estado'] = $abierto ? 'ok' : 'falla';
            $fila['detalle'] = $abierto ? "Escucha en 127.0.0.1:{$puerto}" : "Nada escucha en 127.0.0.1:{$puerto}";
            if ($abierto && $ld && !$ld['cargado']) {
                $fila['estado'] = 'aviso';
                $fila['detalle'] .= ' · no arranca solo (no está en launchd)';
            }
        } elseif ($tipo === 'tunel') {
            $fila['estado'] = $tunel['estado'];
            $fila['detalle'] = $tunel['resumen'];
        } else {
            if (!$ld) {
                $fila['estado'] = 'sin_datos';
                $fila['detalle'] = $estado ? 'Sin datos' : 'Sin datos de launchd';
            } elseif (!$ld['cargado']) {
                $fila['estado'] = 'falla';
                $fila['detalle'] = 'No está instalada en launchd';
            } elseif (($ld['ultima_salida'] ?? 0) !== 0) {
                $fila['estado'] = 'aviso';
                $fila['detalle'] = 'La última ejecución terminó con error (código ' . $ld['ultima_salida'] . ')';
            } else {
                $fila['estado'] = 'ok';
                $fila['detalle'] = 'Programada; última ejecución sin errores';
            }
        }
        $servicios[] = $fila;
    }
    if (!$tareaAlDia) {
        foreach ($servicios as &$fila) {
            if ($fila['etiqueta'] === 'com.rivera.taller.estado') {
                $fila['estado'] = $estado ? 'falla' : 'sin_datos';
                $fila['detalle'] = $estado ? 'No corre desde ' . monitoreo_hace((int) $estado['generado']) : 'No instalada todavía';
            }
        }
        unset($fila);
    }

    // Base de datos (en vivo)
    $bd = ['estado' => 'falla', 'version' => null, 'encendida' => null];
    try {
        $bd['version'] = (string) db()->query('SELECT VERSION()')->fetchColumn();
        $bd['encendida'] = (int) (db()->query("SHOW GLOBAL STATUS LIKE 'Uptime'")->fetch()['Value'] ?? 0);
        $bd['estado'] = 'ok';
    } catch (Throwable) {
    }

    // Disco (el volumen de datos de macOS comparte el espacio con el del sistema)
    $libre = @disk_free_space(RAIZ);
    $total = @disk_total_space(RAIZ);
    $disco = $libre !== false && $total ? [
        'libre'      => $libre,
        'total'      => $total,
        'porcentaje' => round($libre / $total * 100),
        'estado'     => $libre < 5 * 1024 ** 3 ? 'falla' : ($libre < 20 * 1024 ** 3 ? 'aviso' : 'ok'),
    ] : ['estado' => 'sin_datos'];

    // Energía (de la tarea): que la Mac no se duerma
    $energia = $estado['energia'] ?? [];
    $reposo = ['estado' => 'sin_datos', 'texto' => 'Sin datos (la tarea de estado no corre).'];
    if (isset($energia['sleep'])) {
        $reposo = $energia['sleep'] === 0
            ? ['estado' => 'ok', 'texto' => 'Reposo desactivado']
            : ['estado' => 'aviso', 'texto' => 'La Mac se duerme tras ' . $energia['sleep'] . ' min sin uso: la web dejaría de responder'];
        if (array_key_exists('autorestart', $energia)) {
            $reposo['texto'] .= $energia['autorestart'] ? ' · arranca sola tras un corte de luz' : ' · NO arranca sola tras un corte de luz';
            if (!$energia['autorestart'] && $reposo['estado'] === 'ok') {
                $reposo['estado'] = 'aviso';
            }
        }
    }

    $estados = array_merge(array_column($servicios, 'estado'), [$bd['estado'], $disco['estado'], $reposo['estado'] === 'sin_datos' ? 'ok' : $reposo['estado']]);
    return [
        'estado'      => monitoreo_peor($estados),
        'tarea'       => ['al_dia' => $tareaAlDia, 'generado' => $estado['generado'] ?? null],
        'servicios'   => $servicios,
        'tunel'       => $tunel,
        'bd'          => $bd,
        'disco'       => $disco,
        'reposo'      => $reposo,
        'arranque'    => $estado['arranque'] ?? null,
        'macos'       => $estado['macos'] ?? null,
        'php'         => PHP_VERSION,
        'php_sapi'    => PHP_SAPI,
    ];
}

/** Túnel de Cloudflare: endpoint local de métricas de cloudflared (127.0.0.1:20241 por defecto). */
function monitoreo_tunel(): array
{
    $base = 'http://' . (env('CLOUDFLARED_METRICS') ?: '127.0.0.1:20241');
    $r = monitoreo_http($base . '/ready', ['timeout' => 2]);
    $datos = json_decode($r['cuerpo'], true);
    if (!is_array($datos)) {
        return ['estado' => 'falla', 'resumen' => 'cloudflared no responde en ' . substr($base, 7) . ' (túnel apagado)', 'conexiones' => 0];
    }
    $conexiones = (int) ($datos['readyConnections'] ?? 0);
    $metricas = monitoreo_http($base . '/metrics', ['timeout' => 2])['cuerpo'];
    // Solo las ubicaciones actuales (valor 1); cloudflared deja en 0 las de conexiones anteriores
    preg_match_all('/edge_location="([a-z0-9]+)"\} 1\b/', $metricas, $m);
    $peticiones = preg_match('/^cloudflared_tunnel_total_requests (\d+)/m', $metricas, $t) ? (int) $t[1] : null;
    $errores = preg_match('/^cloudflared_tunnel_request_errors (\d+)/m', $metricas, $t) ? (int) $t[1] : null;
    $version = preg_match('/build_info\{[^}]*version="([^"]+)"/', $metricas, $t) ? $t[1] : null;
    return [
        'estado'     => $conexiones >= 2 ? 'ok' : ($conexiones === 1 ? 'aviso' : 'falla'),
        'resumen'    => $conexiones . ' conexi' . ($conexiones === 1 ? 'ón' : 'ones') . ' activa' . ($conexiones === 1 ? '' : 's') . ' con Cloudflare',
        'conexiones' => $conexiones,
        'centros'    => array_values(array_unique(array_map('strtoupper', $m[1] ?? []))),
        'peticiones' => $peticiones,
        'errores'    => $errores,
        'version'    => $version,
    ];
}

// ---------------------------------------------------------------------
// 3. Respaldos (carpeta RESPALDOS_DIR del .env)
// ---------------------------------------------------------------------

function monitoreo_respaldos(): array
{
    $carpeta = rtrim((string) env('RESPALDOS_DIR', ''), '/');
    if ($carpeta === '') {
        return ['estado' => 'sin_datos', 'resumen' => 'No configurado: agrega RESPALDOS_DIR al .env con la carpeta de los respaldos.'];
    }
    if (!is_dir($carpeta) || !is_readable($carpeta)) {
        return ['estado' => 'falla', 'resumen' => 'No se puede leer la carpeta de respaldos (' . $carpeta . ').'];
    }
    $lista = [];
    foreach (scandir($carpeta) ?: [] as $nombre) {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})_(\d{2})(\d{2})(\d{2})?$/', $nombre, $m) && is_dir("$carpeta/$nombre")) {
            $lista[$nombre] = mktime((int) $m[4], (int) $m[5], (int) ($m[6] ?? 0), (int) $m[2], (int) $m[3], (int) $m[1]);
        }
    }
    if (!$lista) {
        return ['estado' => 'falla', 'resumen' => 'No hay ningún respaldo en ' . $carpeta . '.', 'cantidad' => 0];
    }
    arsort($lista);
    $ultimo = array_key_first($lista);
    $momento = $lista[$ultimo];
    $tamano = 0;
    $archivos = [];
    foreach (['base.sql.gz', 'uploads.tar.gz', 'comprobantes.tar.gz', 'SHA256SUMS'] as $archivo) {
        $ruta = "$carpeta/$ultimo/$archivo";
        if (is_file($ruta)) {
            $archivos[$archivo] = filesize($ruta);
            $tamano += $archivos[$archivo];
        }
    }
    $completo = count($archivos) === 4 && $archivos['base.sql.gz'] > 100;
    $total = 0;
    foreach (array_keys($lista) as $nombre) {
        foreach (glob("$carpeta/$nombre/*") ?: [] as $f) {
            $total += (int) @filesize($f);
        }
    }
    $horas = (time() - $momento) / 3600;

    // Última línea del registro del respaldo (si está configurado)
    $registro = null;
    $log = (string) env('RESPALDOS_LOG', '');
    if ($log !== '' && is_readable($log)) {
        $lineas = array_values(array_filter(array_map('trim', file($log) ?: [])));
        $registro = $lineas ? mb_substr(end($lineas), 0, 200) : null;
    }

    $estado = !$completo ? 'falla' : ($horas > MONITOREO_RESPALDO_MAX_HORAS ? 'falla' : 'ok');
    return [
        'estado'   => $estado,
        'resumen'  => !$completo ? 'El último respaldo está incompleto'
            : ($horas > MONITOREO_RESPALDO_MAX_HORAS ? 'El último respaldo tiene más de ' . MONITOREO_RESPALDO_MAX_HORAS . ' h' : 'Al día'),
        'carpeta'  => $carpeta,
        'ultimo'   => ['nombre' => $ultimo, 'momento' => $momento, 'tamano' => $tamano, 'completo' => $completo, 'archivos' => $archivos],
        'cantidad' => count($lista),
        'total'    => $total,
        'mas_viejo' => end($lista),
        'registro' => $registro,
    ];
}

// ---------------------------------------------------------------------
// 4. UptimeRobot (clave de SOLO LECTURA en Configuración)
// ---------------------------------------------------------------------

const UPTIMEROBOT_ESTADOS = [0 => ['En pausa', 'aviso'], 1 => ['Aún sin revisar', 'sin_datos'], 2 => ['Arriba', 'ok'], 8 => ['Parece caído', 'aviso'], 9 => ['Caído', 'falla']];

function monitoreo_uptimerobot(bool $forzar): array
{
    $clave = ajuste('uptimerobot_api_key');
    if ($clave === '') {
        return ['estado' => 'sin_datos', 'resumen' => 'Sin clave de UptimeRobot', 'sin_clave' => true];
    }
    // La caché se separa por clave (si el dueño la cambia, se vuelve a consultar)
    return monitoreo_cache('uptimerobot-' . substr(hash('sha256', $clave), 0, 12), 300, function () use ($clave) {
        $hoy = strtotime('today');
        $rangos = [];
        for ($i = 29; $i >= 0; $i--) {
            $desde = strtotime("-{$i} day", $hoy);
            $rangos[] = $desde . '_' . min(time(), $desde + 86399);
        }
        $r = monitoreo_http('https://api.uptimerobot.com/v2/getMonitors', [
            'metodo' => 'POST', 'timeout' => 8,
            'encabezados' => ['Content-Type: application/x-www-form-urlencoded', 'Cache-Control: no-cache'],
            'campos' => [
                'api_key' => $clave, 'format' => 'json',
                'custom_uptime_ratios' => '1-7-30',
                'custom_uptime_ranges' => implode('-', $rangos),
                'logs' => 1, 'logs_limit' => 8,
                'response_times' => 1, 'response_times_average' => 30,
                'response_times_start_date' => time() - 86400, 'response_times_end_date' => time(),
            ],
        ]);
        $datos = json_decode($r['cuerpo'], true);
        if (!is_array($datos) || ($datos['stat'] ?? '') !== 'ok') {
            $motivo = $datos['error']['message'] ?? ($r['error'] ?: 'código ' . $r['codigo']);
            return ['estado' => 'falla', 'resumen' => 'UptimeRobot rechazó la consulta: ' . mb_substr((string) $motivo, 0, 160)];
        }
        $monitores = [];
        foreach ($datos['monitors'] ?? [] as $m) {
            [$texto, $estado] = UPTIMEROBOT_ESTADOS[(int) ($m['status'] ?? 1)] ?? ['Desconocido', 'sin_datos'];
            $ratios = array_map('floatval', explode('-', (string) ($m['custom_uptime_ratio'] ?? '')));
            $dias = array_map('floatval', explode('-', (string) ($m['custom_uptime_ranges'] ?? '')));
            $incidentes = [];
            foreach ($m['logs'] ?? [] as $log) {
                if ((int) ($log['type'] ?? 0) === 1) {
                    $incidentes[] = [
                        'momento'  => (int) $log['datetime'],
                        'duracion' => (int) ($log['duration'] ?? 0),
                        'motivo'   => trim(($log['reason']['code'] ?? '') . ' ' . ($log['reason']['detail'] ?? '')),
                    ];
                }
            }
            $tiempos = array_reverse(array_map(fn ($p) => ['t' => (int) $p['datetime'], 'ms' => (int) $p['value']], $m['response_times'] ?? []));
            $monitores[] = [
                'nombre'     => (string) ($m['friendly_name'] ?? ''),
                'url'        => (string) ($m['url'] ?? ''),
                'estado'     => $estado,
                'texto'      => $texto,
                'dia'        => $ratios[0] ?? null,
                'semana'     => $ratios[1] ?? null,
                'mes'        => $ratios[2] ?? null,
                'dias'       => count($dias) === 30 ? $dias : [],
                'incidentes' => array_slice($incidentes, 0, 5),
                'tiempos'    => $tiempos,
                'promedio'   => isset($m['average_response_time']) ? (int) round((float) $m['average_response_time']) : null,
                'intervalo'  => (int) ($m['interval'] ?? 0),
            ];
        }
        // Página de estado pública (si el dueño creó una)
        $paginas = [];
        $p = json_decode(monitoreo_http('https://api.uptimerobot.com/v2/getPSPs', [
            'metodo' => 'POST', 'timeout' => 5,
            'encabezados' => ['Content-Type: application/x-www-form-urlencoded'],
            'campos' => ['api_key' => $clave, 'format' => 'json'],
        ])['cuerpo'], true);
        foreach ($p['psps'] ?? [] as $psp) {
            $enlace = $psp['custom_url'] ?: ($psp['standard_url'] ?? '');
            if ($enlace && preg_match('#^https?://#', $enlace)) {
                $paginas[] = ['nombre' => (string) ($psp['friendly_name'] ?? 'Página de estado'), 'url' => $enlace];
            }
        }
        if (!$monitores) {
            return ['estado' => 'sin_datos', 'resumen' => 'La cuenta de UptimeRobot no tiene monitores todavía.', 'monitores' => [], 'paginas' => $paginas];
        }
        return [
            'estado'    => monitoreo_peor(array_column($monitores, 'estado')),
            'resumen'   => count($monitores) . ' monitor' . (count($monitores) === 1 ? '' : 'es'),
            'monitores' => $monitores,
            'paginas'   => $paginas,
        ];
    }, $forzar, 120);
}

// ---------------------------------------------------------------------
// 5. Actividad de la tienda (base de datos)
// ---------------------------------------------------------------------

function monitoreo_tienda(): array
{
    $consultas = [
        'pedidos_hoy'   => "SELECT COUNT(*) FROM orders WHERE creado_en >= CURDATE()",
        'pedidos_7'     => "SELECT COUNT(*) FROM orders WHERE creado_en >= CURDATE() - INTERVAL 6 DAY",
        'por_revisar'   => "SELECT COUNT(*) FROM orders WHERE estado = 'comprobante_enviado'",
        'por_pagar'     => "SELECT COUNT(*) FROM orders WHERE estado = 'pendiente_pago'",
        'consultas'     => "SELECT COUNT(*) FROM consultas WHERE estado = 'nueva'",
        'sin_stock'     => "SELECT COUNT(*) FROM products WHERE activo = 1 AND stock <= 0",
        'productos'     => "SELECT COUNT(*) FROM products WHERE activo = 1",
    ];
    $datos = [];
    foreach ($consultas as $clave => $sql) {
        try {
            $datos[$clave] = (int) db()->query($sql)->fetchColumn();
        } catch (Throwable) {
            $datos[$clave] = null;   // la tabla no existe en esta base
        }
    }
    return $datos;
}

// ---------------------------------------------------------------------
// 6. Microsoft Clarity (Data Export API: máx. 10 consultas al día → caché de 4 h)
// ---------------------------------------------------------------------

function monitoreo_clarity(): array
{
    $id = trim((string) env('CLARITY_ID', ''));
    $instalado = $id !== '' && config('app.entorno') === 'produccion';
    $base = [
        'id'        => $id,
        'instalado' => $instalado,
        'panel'     => $id !== '' ? 'https://clarity.microsoft.com/projects/view/' . rawurlencode($id) . '/dashboard' : 'https://clarity.microsoft.com/',
    ];
    $token = ajuste('clarity_api_token');
    if ($token === '') {
        return $base + ['estado' => $instalado ? 'ok' : 'sin_datos', 'datos' => null, 'sin_token' => true];
    }
    $datos = monitoreo_cache('clarity-' . substr(hash('sha256', $token), 0, 12), 4 * 3600, function () use ($token) {
        $r = monitoreo_http('https://www.clarity.ms/export-data/api/v1/project-live-insights?numOfDays=3', [
            'timeout' => 8,
            'encabezados' => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
        ]);
        $json = json_decode($r['cuerpo'], true);
        if ($r['codigo'] !== 200 || !is_array($json)) {
            $motivo = match ($r['codigo']) {
                401, 403 => 'el token no es válido o venció',
                429      => 'se alcanzó el límite de 10 consultas por día',
                default  => $r['error'] ?: 'código ' . $r['codigo'],
            };
            return ['estado' => 'falla', 'resumen' => 'Clarity no entregó datos: ' . $motivo];
        }
        $metricas = [];
        foreach ($json as $bloque) {
            $fila = $bloque['information'][0] ?? null;
            if (is_array($fila)) {
                $metricas[(string) ($bloque['metricName'] ?? '')] = $fila;
            }
        }
        $trafico = $metricas['Traffic'] ?? [];
        return [
            'estado'    => 'ok',
            'sesiones'  => isset($trafico['totalSessionCount']) ? (int) $trafico['totalSessionCount'] : null,
            'robots'    => isset($trafico['totalBotSessionCount']) ? (int) $trafico['totalBotSessionCount'] : null,
            'usuarios'  => isset($trafico['distinctUserCount']) ? (int) $trafico['distinctUserCount'] : null,
            'paginas'   => isset($trafico['pagesPerSessionPercentage']) ? round((float) $trafico['pagesPerSessionPercentage'], 1) : null,
            'desplazamiento' => isset($metricas['ScrollDepth']['averageScrollDepth']) ? round((float) $metricas['ScrollDepth']['averageScrollDepth']) : null,
            'clics_rabia' => isset($metricas['RageClickCount']['subTotal']) ? (int) $metricas['RageClickCount']['subTotal'] : null,
        ];
    }, false, 3600);
    return $base + ['estado' => $datos['estado'], 'datos' => $datos];
}

// ---------------------------------------------------------------------
// Resumen general
// ---------------------------------------------------------------------

/** Frase de arriba: "Todo funcionando" o qué está fallando. $partes: nombre => estado */
function monitoreo_resumen(array $partes): array
{
    $fallas = array_keys(array_filter($partes, fn ($e) => $e === 'falla'));
    $avisos = array_keys(array_filter($partes, fn ($e) => $e === 'aviso'));
    $lista = fn (array $n): string => count($n) > 1 ? implode(', ', array_slice($n, 0, -1)) . ' y ' . end($n) : (string) ($n[0] ?? '');
    if ($fallas) {
        return ['estado' => 'falla', 'titulo' => 'Hay un problema con ' . $lista($fallas), 'detalle' => $avisos ? 'Además, revisar: ' . $lista($avisos) . '.' : ''];
    }
    if ($avisos) {
        return ['estado' => 'aviso', 'titulo' => 'Todo funciona, con avisos en ' . $lista($avisos), 'detalle' => ''];
    }
    return ['estado' => 'ok', 'titulo' => 'Todo funcionando', 'detalle' => ''];
}

<?php
/**
 * Estado del servidor para Admin → Monitoreo. Se ejecuta CADA MINUTO con launchd (Mac de producción):
 *
 *   php /Users/carlosrivera/taller-produccion/app/tareas/estado-servidor.php
 *   (plantilla del servicio: scripts/launchd/com.rivera.taller.estado.plist)
 *
 * Lee lo que la web no puede leer por sí misma (y no debe: PHP-FPM nunca ejecuta comandos):
 * servicios de launchd, ajustes de energía y hora de arranque de la Mac. Además mide la
 * respuesta de la web pública (MONITOREO_URL o APP_URL) y guarda 24 h de historial.
 * Solo ejecuta comandos FIJOS: no recibe ninguna entrada de usuarios.
 *
 * Escribe: storage/estado/servidor.json y storage/estado/web-historial.json
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/config/config.php';
require RAIZ . '/app/modelos/monitoreo.php';

$carpeta = RAIZ . '/storage/estado';
if (!is_dir($carpeta)) {
    mkdir($carpeta, 0770, true);
}

/** Ejecuta un comando fijo y devuelve su salida ('' si falla). */
function comando(string $linea): string
{
    $salida = [];
    $codigo = 0;
    exec($linea . ' 2>/dev/null', $salida, $codigo);
    return $codigo === 0 ? implode("\n", $salida) : '';
}

// Servicios de launchd del usuario: cargado, PID (si está corriendo) y último código de salida
$servicios = [];
foreach (MONITOREO_SERVICIOS as $etiqueta => $_) {
    $texto = comando('/bin/launchctl list ' . escapeshellarg($etiqueta));
    $servicios[$etiqueta] = [
        'cargado'      => $texto !== '',
        'pid'          => preg_match('/"PID" = (\d+);/', $texto, $m) ? (int) $m[1] : null,
        'ultima_salida' => preg_match('/"LastExitStatus" = (-?\d+);/', $texto, $m) ? (int) $m[1] : null,
    ];
}

// Energía: reposo, reinicio tras corte de luz, despertar por red
$energia = [];
foreach (explode("\n", comando('/usr/bin/pmset -g')) as $linea) {
    if (preg_match('/^\s*(sleep|disksleep|displaysleep|autorestart|womp)\s+(\d+)/', $linea, $m)) {
        $energia[$m[1]] = (int) $m[2];
    }
}

$arranque = preg_match('/sec = (\d+)/', comando('/usr/sbin/sysctl -n kern.boottime'), $m) ? (int) $m[1] : null;

$estado = [
    'generado'  => time(),
    'arranque'  => $arranque,
    'macos'     => comando('/usr/bin/sw_vers -productVersion') ?: null,
    'servicios' => $servicios,
    'energia'   => $energia,
];
monitoreo_escribir_json("$carpeta/servidor.json", $estado);

// Web pública: una medición por minuto, se guardan las últimas 24 h
$url = monitoreo_url();
if ($url !== '') {
    $r = monitoreo_http($url, ['timeout' => 10]);
    $archivo = "$carpeta/web-historial.json";
    $historial = is_file($archivo) ? (json_decode((string) file_get_contents($archivo), true) ?: []) : [];
    $historial[] = ['t' => time(), 'c' => $r['codigo'], 'ms' => $r['ms']];
    $limite = time() - 86400;
    $historial = array_values(array_filter($historial, fn ($p) => is_array($p) && ($p['t'] ?? 0) >= $limite));
    monitoreo_escribir_json($archivo, $historial);
}

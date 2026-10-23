<?php
/**
 * Visitas a la tienda: registro anónimo y reporte para el dueño.
 *
 * Privacidad (sin cookies ni aviso de cookies):
 * - No se guarda la IP ni nada que identifique a la persona.
 * - "visitante" = hash(sal del día + IP + navegador). La sal cambia cada día y la anterior se
 *   descarta, así que el código no sirve para seguir a alguien de un día a otro.
 * - No se cuentan robots, ni las visitas de quien tiene sesión en el panel.
 */

declare(strict_types=1);

const VISITAS_DIAS_CONSERVAR = 400;   // poco más de un año, para comparar con el mismo mes anterior

// Sitios conocidos de los que llega gente (mismo criterio que public/js/app.js)
const VISITAS_SITIOS_CONOCIDOS = [
    'instagram' => 'instagram', 'facebook' => 'facebook', 'fb.' => 'facebook', 'google' => 'google',
    'tiktok' => 'tiktok', 'whatsapp' => 'whatsapp', 'bing' => 'bing', 'youtube' => 'youtube',
];

/**
 * Registra la visita a una página pública. Nunca debe romper la página: cualquier error se ignora.
 */
function registrar_visita(string $ruta): void
{
    try {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET' || visita_es_robot() || visita_de_admin()) {
            return;
        }
        // Precargas del navegador (no son visitas reales)
        $proposito = strtolower($_SERVER['HTTP_SEC_PURPOSE'] ?? $_SERVER['HTTP_PURPOSE'] ?? '');
        if (str_contains($proposito, 'prefetch') || str_contains($proposito, 'prerender')) {
            return;
        }

        $agente = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $visitante = substr(hash('sha256', sal_visitas_del_dia() . '|' . ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . $agente), 0, 16);
        $origen = origen_de_la_visita();
        // El seguimiento lleva el número de pedido en la dirección: no se guarda, solo se agrupa
        if (str_starts_with($ruta, 'mi-pedido/')) {
            $ruta = 'mi-pedido/seguimiento';
        }

        db()->prepare('INSERT INTO visitas (fecha, ruta, visitante, entrada, origen, dispositivo) VALUES (CURDATE(), ?, ?, ?, ?, ?)')
            ->execute([mb_substr($ruta, 0, 190), $visitante, $origen !== null ? 1 : 0, $origen, tipo_dispositivo($agente)]);

        // Limpieza ocasional de visitas viejas (≈ 1 de cada 500 visitas), sin necesidad de otra tarea programada
        if (random_int(1, 500) === 1) {
            db()->prepare('DELETE FROM visitas WHERE fecha < (CURDATE() - INTERVAL ? DAY)')->execute([VISITAS_DIAS_CONSERVAR]);
        }
    } catch (Throwable $e) {
        error_log('No se pudo registrar la visita: ' . $e->getMessage());
    }
}

/**
 * Sal secreta del día. Se guarda en settings como "AAAA-MM-DD|aleatorio" y se reemplaza al cambiar de día.
 */
function sal_visitas_del_dia(): string
{
    $hoy = date('Y-m-d');
    $guardada = ajuste('visitas_sal');
    if (str_starts_with($guardada, $hoy . '|')) {
        return $guardada;
    }
    $nueva = $hoy . '|' . bin2hex(random_bytes(16));
    db()->prepare('INSERT INTO settings (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)')
        ->execute(['visitas_sal', $nueva]);
    return $nueva;
}

/**
 * Si la persona llega desde fuera del sitio, de dónde viene (facebook, google, directo…).
 * Si está navegando dentro del sitio, devuelve null (no es una "entrada").
 */
function origen_de_la_visita(): ?string
{
    $utm = is_string($_GET['utm_source'] ?? null) ? strtolower(trim($_GET['utm_source'])) : '';
    if ($utm !== '') {
        return mb_substr(preg_replace('/[^a-z0-9_.-]/', '', $utm), 0, 40) ?: 'otro';
    }
    if (isset($_GET['fbclid'])) {
        return 'facebook';
    }
    if (isset($_GET['gclid'])) {
        return 'google';
    }

    $referencia = strtolower((string) parse_url($_SERVER['HTTP_REFERER'] ?? '', PHP_URL_HOST));
    $propio = strtolower((string) parse_url(config('app.url', ''), PHP_URL_HOST));
    if ($referencia === '') {
        return 'directo';
    }
    if ($referencia === $propio || $referencia === strtolower($_SERVER['HTTP_HOST'] ?? '')) {
        return null;
    }
    foreach (VISITAS_SITIOS_CONOCIDOS as $clave => $origen) {
        if (str_contains($referencia, $clave)) {
            return $origen;
        }
    }
    return 'otro';
}

function tipo_dispositivo(string $agente): string
{
    if (preg_match('/ipad|tablet|kindle|silk|(android(?!.*mobile))/i', $agente)) {
        return 'tablet';
    }
    return preg_match('/mobi|iphone|ipod|android|opera mini|iemobile/i', $agente) ? 'celular' : 'computadora';
}

function visita_es_robot(): bool
{
    $agente = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return $agente === ''
        || (bool) preg_match('/bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp|telegram|curl|wget|python|httpclient|headless|lighthouse|monitor/i', $agente);
}

/**
 * true si quien visita tiene sesión en el panel (el dueño o un empleado revisando la tienda).
 * Solo se abre la sesión si el navegador ya trae la cookie, para no crear sesiones a cada visitante.
 */
function visita_de_admin(): bool
{
    if (!isset($_COOKIE[config('sesion.nombre')])) {
        return false;
    }
    iniciar_sesion();
    return !empty($_SESSION['admin_id']);
}

// ---------------------------------------------------------------------
// Reporte
// ---------------------------------------------------------------------

/**
 * Totales del mes: visitas (páginas vistas), visitantes (únicos por día, sumados) y entradas.
 */
function visitas_resumen(string $inicioMes): array
{
    $stmt = db()->prepare(
        "SELECT COUNT(*) AS paginas,
                COUNT(DISTINCT fecha, visitante) AS visitantes,
                COALESCE(SUM(entrada), 0) AS entradas,
                COUNT(DISTINCT CASE WHEN dispositivo = 'celular' THEN CONCAT(fecha, visitante) END) AS celular
           FROM visitas
          WHERE fecha >= ? AND fecha < (? + INTERVAL 1 MONTH)"
    );
    $stmt->execute([$inicioMes, $inicioMes]);
    $r = $stmt->fetch();
    return array_map('intval', $r);
}

/**
 * Visitantes y páginas vistas por cada día del mes (incluye los días en cero).
 */
function visitas_por_dia(string $inicioMes): array
{
    $stmt = db()->prepare(
        'SELECT fecha, COUNT(DISTINCT visitante) AS visitantes, COUNT(*) AS paginas
           FROM visitas
          WHERE fecha >= ? AND fecha < (? + INTERVAL 1 MONTH)
          GROUP BY fecha'
    );
    $stmt->execute([$inicioMes, $inicioMes]);
    $porFecha = [];
    foreach ($stmt as $f) {
        $porFecha[$f['fecha']] = ['visitantes' => (int) $f['visitantes'], 'paginas' => (int) $f['paginas']];
    }

    $dias = [];
    $ultimo = min(strtotime('today'), strtotime($inicioMes . ' +1 month -1 day'));
    for ($t = strtotime($inicioMes); $t <= $ultimo; $t = strtotime('+1 day', $t)) {
        $fecha = date('Y-m-d', $t);
        $dias[$fecha] = $porFecha[$fecha] ?? ['visitantes' => 0, 'paginas' => 0];
    }
    return $dias;
}

/**
 * Personas que llegaron desde cada origen (entradas), ordenado de mayor a menor.
 */
function visitas_por_origen(string $inicioMes): array
{
    $stmt = db()->prepare(
        'SELECT origen, COUNT(*) AS entradas
           FROM visitas
          WHERE entrada = 1 AND fecha >= ? AND fecha < (? + INTERVAL 1 MONTH)
          GROUP BY origen
          ORDER BY entradas DESC'
    );
    $stmt->execute([$inicioMes, $inicioMes]);
    return $stmt->fetchAll();
}

/**
 * Páginas más vistas (sin contar las de productos, que van en su propia lista).
 */
function visitas_paginas_top(string $inicioMes, int $limite = 8): array
{
    $stmt = db()->prepare(
        "SELECT ruta, COUNT(*) AS vistas
           FROM visitas
          WHERE fecha >= ? AND fecha < (? + INTERVAL 1 MONTH) AND ruta NOT LIKE 'producto/%'
          GROUP BY ruta
          ORDER BY vistas DESC
          LIMIT " . $limite
    );
    $stmt->execute([$inicioMes, $inicioMes]);
    return $stmt->fetchAll();
}

/**
 * Productos más vistos, con su nombre actual (si el producto se borró, se muestra la dirección).
 */
function visitas_productos_top(string $inicioMes, int $limite = 8): array
{
    $stmt = db()->prepare(
        "SELECT v.ruta, COUNT(*) AS vistas, p.nombre
           FROM visitas v
           LEFT JOIN products p ON p.slug = SUBSTRING(v.ruta, 10)
          WHERE v.fecha >= ? AND v.fecha < (? + INTERVAL 1 MONTH) AND v.ruta LIKE 'producto/%'
          GROUP BY v.ruta, p.nombre
          ORDER BY vistas DESC
          LIMIT " . $limite
    );
    $stmt->execute([$inicioMes, $inicioMes]);
    return $stmt->fetchAll();
}

/**
 * Pedidos creados en el mes (para la tasa "visitantes que hicieron un pedido").
 */
function pedidos_creados_mes(string $inicioMes): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM orders WHERE creado_en >= ? AND creado_en < (? + INTERVAL 1 MONTH)');
    $stmt->execute([$inicioMes, $inicioMes]);
    return (int) $stmt->fetchColumn();
}

/**
 * Nombre legible de una página del sitio.
 */
function nombre_pagina_visita(string $ruta): string
{
    $nombres = [
        '' => 'Inicio', 'tienda' => 'Tienda', 'servicios' => 'Servicios', 'contacto' => 'Contacto',
        'carrito' => 'Carrito', 'checkout' => 'Confirmar pedido', 'pedido-confirmado' => 'Pedido confirmado',
        'mi-pedido' => 'Mi pedido (búsqueda)', 'terminos' => 'Términos', 'privacidad' => 'Privacidad',
    ];
    if (isset($nombres[$ruta])) {
        return $nombres[$ruta];
    }
    if ($ruta === 'mi-pedido/seguimiento') {
        return 'Seguimiento de un pedido';
    }
    return '/' . $ruta;
}

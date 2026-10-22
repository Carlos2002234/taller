<?php
/**
 * Funciones de apoyo usadas en todo el sitio.
 */

declare(strict_types=1);

/**
 * Escapa texto para mostrarlo en HTML (protección XSS).
 * Usar en TODA salida de datos: <?= e($producto['nombre']) ?>
 */
function e(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Formatea un monto en colones: 28000 → "₡28.000"
 */
function colones(float|int|string $monto): string
{
    return '₡' . number_format((float) $monto, 0, ',', '.');
}

/**
 * Construye una URL absoluta del sitio: url('tienda') → "https://dominio/tienda"
 */
function url(string $ruta = ''): string
{
    return config('app.url') . '/' . ltrim($ruta, '/');
}

/**
 * Redirige a otra página del sitio y termina la ejecución.
 */
function redirigir(string $ruta): never
{
    header('Location: ' . url($ruta));
    exit;
}

/**
 * Convierte un texto en slug para URLs: "Luces LED H11" → "luces-led-h11"
 */
function slug(string $texto): string
{
    $texto = strtr(mb_strtolower(trim($texto), 'UTF-8'), [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
    ]);
    $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);
    return trim($texto, '-');
}

/**
 * Indica si la petición actual llegó por HTTPS (también detrás de proxy).
 */
function es_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

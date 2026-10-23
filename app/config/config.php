<?php
/**
 * Arranque de la aplicación: carga .env, configura PHP y las funciones base.
 * Todas las páginas deben incluir este archivo primero.
 */

declare(strict_types=1);

// Carpeta raíz del proyecto (donde están /app, /public, /storage, .env)
define('RAIZ', dirname(__DIR__, 2));

require RAIZ . '/app/core/env.php';
cargar_env(RAIZ . '/.env');

/**
 * Lee un valor de configuración. Ej: config('db.nombre')
 */
function config(string $clave, mixed $porDefecto = null): mixed
{
    static $config = null;

    $config ??= [
        'app.entorno'   => env('APP_ENV', 'produccion'),
        // En producción NUNCA se muestran errores ni páginas de desarrollo, aunque APP_DEBUG quede en true
        'app.debug'     => env_bool('APP_DEBUG') && env('APP_ENV', 'produccion') !== 'produccion',
        'app.url'       => rtrim(env('APP_URL', ''), '/'),
        'app.zona'      => env('APP_TIMEZONE', 'America/Costa_Rica'),

        'db.host'       => env('DB_HOST', '127.0.0.1'),
        'db.puerto'     => env('DB_PORT', '3306'),
        'db.nombre'     => env('DB_NAME', ''),
        'db.usuario'    => env('DB_USER', ''),
        'db.clave'      => env('DB_PASS', ''),

        'sesion.nombre' => env('SESSION_NAME', 'rivera_sesion'),

        // Rutas de archivos
        'ruta.uploads'      => RAIZ . '/public/uploads',       // fotos de productos (públicas)
        'ruta.comprobantes' => RAIZ . '/storage/comprobantes', // comprobantes de pago (privados)
        'ruta.logs'         => RAIZ . '/storage/logs',
    ];

    return $config[$clave] ?? $porDefecto;
}

// Zona horaria y manejo de errores
date_default_timezone_set(config('app.zona'));
error_reporting(E_ALL);
ini_set('display_errors', config('app.debug') ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', config('ruta.logs') . '/php-errores.log');

require RAIZ . '/app/core/helpers.php';
require RAIZ . '/app/core/database.php';
require RAIZ . '/app/core/session.php';
require RAIZ . '/app/core/csrf.php';
require RAIZ . '/app/core/auth.php';

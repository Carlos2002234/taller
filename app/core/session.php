<?php
/**
 * Sesiones seguras: cookie solo HTTP, segura en HTTPS y SameSite=Lax.
 */

declare(strict_types=1);

function iniciar_sesion(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_name(config('sesion.nombre'));
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => es_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

/**
 * Cambia el ID de sesión (usar justo después de iniciar sesión en el admin).
 */
function regenerar_sesion(): void
{
    session_regenerate_id(true);
}

/**
 * Cierra la sesión por completo (logout).
 */
function cerrar_sesion(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $p['path'],
            'secure'   => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => $p['samesite'],
        ]);
    }

    session_destroy();
}

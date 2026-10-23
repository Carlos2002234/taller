<?php
/**
 * Autenticación del panel de administración.
 *
 *   $usuario = requerir_admin();           // cualquier usuario activo del panel
 *   $usuario = requerir_admin('duenio');   // solo el dueño (productos, categorías, usuarios, aprobar pagos)
 */

declare(strict_types=1);

require_once RAIZ . '/app/modelos/usuarios.php';
require_once RAIZ . '/app/modelos/intentos.php';

const ADMIN_INACTIVIDAD_SEGUNDOS = 2 * 60 * 60; // la sesión se cierra tras 2 horas sin uso

/**
 * Usuario con sesión iniciada en el panel, o null.
 */
function usuario_actual(): ?array
{
    static $usuario = false;
    if ($usuario !== false) {
        return $usuario;
    }

    iniciar_sesion();
    $id = $_SESSION['admin_id'] ?? null;
    if (!$id) {
        return $usuario = null;
    }

    // Cierre por inactividad
    if (time() - ($_SESSION['admin_ultimo'] ?? 0) > ADMIN_INACTIVIDAD_SEGUNDOS) {
        cerrar_sesion_admin();
        avisar('info', 'Tu sesión se cerró por inactividad. Vuelve a ingresar.');
        return $usuario = null;
    }

    // Si el usuario fue desactivado o borrado, se cierra su sesión
    $datos = usuario_por_id((int) $id);
    if (!$datos || !$datos['activo']) {
        cerrar_sesion_admin();
        return $usuario = null;
    }

    $_SESSION['admin_ultimo'] = time();
    return $usuario = $datos;
}

/**
 * Igual que usuario_actual() pero SIN renovar la sesión: para consultas automáticas del panel
 * (ej. revisar si hay leads nuevos cada 30 s), que no deben impedir el cierre por inactividad.
 */
function usuario_sin_renovar(): ?array
{
    iniciar_sesion();
    $id = $_SESSION['admin_id'] ?? null;
    $vigente = $id && time() - ($_SESSION['admin_ultimo'] ?? 0) <= ADMIN_INACTIVIDAD_SEGUNDOS;
    session_write_close(); // libera la sesión enseguida para no frenar otras páginas abiertas
    if (!$vigente) {
        return null;
    }
    $datos = usuario_por_id((int) $id);
    return $datos && $datos['activo'] ? $datos : null;
}

/**
 * Exige sesión del panel (y opcionalmente un rol). Sin sesión, redirige al login.
 */
function requerir_admin(?string $rol = null): array
{
    // El navegador no debe guardar copias de las páginas del panel
    header('Cache-Control: no-store, private');
    header('X-Robots-Tag: noindex, nofollow');

    $usuario = usuario_actual();
    if (!$usuario) {
        redirigir('admin/login?volver=' . rawurlencode(ruta_actual()));
    }
    if ($rol !== null && $usuario['rol'] !== $rol) {
        prohibido();
    }
    return $usuario;
}

function es_duenio(?array $usuario): bool
{
    return ($usuario['rol'] ?? null) === 'duenio';
}

/**
 * Intenta iniciar sesión. Devuelve null si entró, o el mensaje de error.
 * El mensaje es el mismo para correo inexistente y contraseña incorrecta.
 */
function iniciar_sesion_admin(string $email, string $clave, string $ip): ?string
{
    $email = strtolower(trim($email));

    if (intentos_bloqueado('login', $ip) || intentos_bloqueada_clave('login', $email)) {
        return 'Demasiados intentos fallidos. Espera ' . INTENTOS_MINUTOS_BLOQUEO . ' minutos e intenta de nuevo.';
    }

    $stmt = db()->prepare('SELECT id, password_hash, activo FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    // Si el correo no existe se hace un cálculo equivalente, para no revelarlo por el tiempo de respuesta
    $hash = $usuario['password_hash'] ?? password_hash(random_bytes(12), PASSWORD_DEFAULT);
    $correcto = password_verify($clave, $hash) && $usuario && $usuario['activo'];

    registrar_intento('login', $email, $ip, $correcto);
    if (!$correcto) {
        return 'Correo o contraseña incorrectos.';
    }

    if (password_needs_rehash($usuario['password_hash'], PASSWORD_DEFAULT)) {
        usuario_cambiar_clave((int) $usuario['id'], $clave);
    }

    iniciar_sesion();
    regenerar_sesion();
    $_SESSION['admin_id'] = (int) $usuario['id'];
    $_SESSION['admin_ultimo'] = time();
    db()->prepare('UPDATE users SET ultimo_acceso = NOW() WHERE id = ?')->execute([$usuario['id']]);

    return null;
}

function cerrar_sesion_admin(): void
{
    iniciar_sesion();
    unset($_SESSION['admin_id'], $_SESSION['admin_ultimo']);
    regenerar_sesion();
}

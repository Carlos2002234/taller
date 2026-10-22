<?php
/**
 * Protección CSRF para TODOS los formularios POST.
 *
 *   En el formulario:  <?= csrf_campo() ?>
 *   Al procesarlo:     verificar_csrf();
 */

declare(strict_types=1);

function csrf_token(): string
{
    iniciar_sesion();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Detiene la petición si el token no coincide.
 */
function verificar_csrf(): void
{
    $enviado = $_POST['csrf_token'] ?? '';

    if (!is_string($enviado) || !hash_equals(csrf_token(), $enviado)) {
        http_response_code(403);
        exit('La sesión expiró o el formulario no es válido. Recarga la página e intenta de nuevo.');
    }
}

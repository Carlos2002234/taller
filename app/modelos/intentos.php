<?php
/**
 * Control de intentos fallidos (fuerza bruta) para el login del admin y la consulta de pedidos.
 */

declare(strict_types=1);

const INTENTOS_MAXIMOS = 10;          // por conexión (IP)
const INTENTOS_MAXIMOS_CLAVE = 5;     // por correo en el login del admin
const INTENTOS_MINUTOS_BLOQUEO = 15;

function registrar_intento(string $tipo, string $clave, string $ip, bool $exitoso): void
{
    db()->prepare('INSERT INTO intentos (tipo, clave, ip, exitoso) VALUES (?, ?, ?, ?)')
        ->execute([$tipo, mb_substr($clave, 0, 150), $ip, $exitoso ? 1 : 0]);
}

/**
 * true si esta IP superó los intentos fallidos permitidos en la ventana de bloqueo.
 */
function intentos_bloqueado(string $tipo, string $ip): bool
{
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM intentos
          WHERE tipo = ? AND ip = ? AND exitoso = 0 AND fecha > (NOW() - INTERVAL ? MINUTE)'
    );
    $stmt->execute([$tipo, $ip, INTENTOS_MINUTOS_BLOQUEO]);
    return (int) $stmt->fetchColumn() >= INTENTOS_MAXIMOS;
}

/**
 * true si la clave (ej. un correo) superó los intentos fallidos permitidos,
 * sin importar desde qué conexión se intentó.
 */
function intentos_bloqueada_clave(string $tipo, string $clave): bool
{
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM intentos
          WHERE tipo = ? AND clave = ? AND exitoso = 0 AND fecha > (NOW() - INTERVAL ? MINUTE)'
    );
    $stmt->execute([$tipo, mb_substr($clave, 0, 150), INTENTOS_MINUTOS_BLOQUEO]);
    return (int) $stmt->fetchColumn() >= INTENTOS_MAXIMOS_CLAVE;
}

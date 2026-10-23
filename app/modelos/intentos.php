<?php
/**
 * Control de intentos fallidos (fuerza bruta) para el login del admin y la consulta de pedidos.
 */

declare(strict_types=1);

const INTENTOS_MAXIMOS = 10;
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

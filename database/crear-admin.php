<?php
/**
 * Crea un usuario del panel de administración desde la terminal.
 * Así ninguna contraseña queda escrita en el código ni en seed.sql.
 *
 * Uso:  php database/crear-admin.php
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/config/config.php';

function preguntar(string $texto): string
{
    echo $texto;
    return trim((string) fgets(STDIN));
}

echo "== Crear usuario del panel de administración ==\n\n";

$nombre = preguntar('Nombre: ');
$email  = strtolower(preguntar('Correo: '));
$rol    = preguntar('Rol (duenio / empleado) [duenio]: ') ?: 'duenio';
$clave  = preguntar('Contraseña (mínimo 10 caracteres): ');

$errores = [];
if ($nombre === '') {
    $errores[] = 'El nombre es obligatorio.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errores[] = 'El correo no es válido.';
}
if (!in_array($rol, ['duenio', 'empleado'], true)) {
    $errores[] = 'El rol debe ser "duenio" o "empleado".';
}
if (mb_strlen($clave) < 10) {
    $errores[] = 'La contraseña debe tener al menos 10 caracteres.';
}

if ($errores) {
    echo "\nNo se creó el usuario:\n - " . implode("\n - ", $errores) . "\n";
    exit(1);
}

$existe = db()->prepare('SELECT 1 FROM users WHERE email = ?');
$existe->execute([$email]);
if ($existe->fetchColumn()) {
    echo "\nYa existe un usuario con ese correo.\n";
    exit(1);
}

$stmt = db()->prepare('INSERT INTO users (nombre, email, password_hash, rol) VALUES (?, ?, ?, ?)');
$stmt->execute([$nombre, $email, password_hash($clave, PASSWORD_DEFAULT), $rol]);

echo "\nUsuario creado: {$email} ({$rol})\n";

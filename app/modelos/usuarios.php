<?php
/**
 * Usuarios del panel de administración.
 * Roles: 'duenio' (todo; único que aprueba pagos) y 'empleado' (solo pedidos).
 */

declare(strict_types=1);

const CLAVE_MINIMA = 10;

const ROLES = [
    'duenio'   => 'Dueño',
    'empleado' => 'Empleado',
];

function usuario_por_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT id, nombre, email, rol, activo, ultimo_acceso, creado_en FROM users WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function usuarios_listar(): array
{
    return db()->query(
        "SELECT id, nombre, email, rol, activo, ultimo_acceso FROM users ORDER BY activo DESC, rol = 'duenio' DESC, nombre"
    )->fetchAll();
}

function usuario_email_existe(string $email, ?int $exceptoId = null): bool
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?');
    $stmt->execute([$email, $exceptoId ?? 0]);
    return (int) $stmt->fetchColumn() > 0;
}

/**
 * Cantidad de dueños activos (siempre debe quedar al menos uno).
 */
function duenios_activos(?int $exceptoId = null): int
{
    $stmt = db()->prepare("SELECT COUNT(*) FROM users WHERE rol = 'duenio' AND activo = 1 AND id <> ?");
    $stmt->execute([$exceptoId ?? 0]);
    return (int) $stmt->fetchColumn();
}

/**
 * Crea o actualiza un usuario. Si $clave es '' al editar, la contraseña no cambia.
 */
function usuario_guardar(?int $id, string $nombre, string $email, string $rol, bool $activo, string $clave): int
{
    if ($id === null) {
        db()->prepare('INSERT INTO users (nombre, email, password_hash, rol, activo) VALUES (?, ?, ?, ?, ?)')
            ->execute([$nombre, $email, password_hash($clave, PASSWORD_DEFAULT), $rol, $activo ? 1 : 0]);
        return (int) db()->lastInsertId();
    }

    db()->prepare('UPDATE users SET nombre = ?, email = ?, rol = ?, activo = ? WHERE id = ?')
        ->execute([$nombre, $email, $rol, $activo ? 1 : 0, $id]);
    if ($clave !== '') {
        usuario_cambiar_clave($id, $clave);
    }
    return $id;
}

function usuario_cambiar_clave(int $id, string $clave): void
{
    db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
        ->execute([password_hash($clave, PASSWORD_DEFAULT), $id]);
}

function usuario_verificar_clave(int $id, string $clave): bool
{
    $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $hash = $stmt->fetchColumn();
    return is_string($hash) && password_verify($clave, $hash);
}

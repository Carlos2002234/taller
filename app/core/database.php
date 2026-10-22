<?php
/**
 * Conexión única a MySQL con PDO.
 * Regla del proyecto: SIEMPRE consultas preparadas, nunca SQL concatenado.
 *
 *   $stmt = db()->prepare('SELECT * FROM products WHERE id = ?');
 *   $stmt->execute([$id]);
 */

declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            config('db.host'),
            config('db.puerto'),
            config('db.nombre')
        );

        try {
            $pdo = new PDO($dsn, config('db.usuario'), config('db.clave'), [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            // Costa Rica: UTC-6 todo el año (sin horario de verano)
            $pdo->exec("SET time_zone = '-06:00'");
        } catch (PDOException $e) {
            // El detalle va al log; nunca se muestran credenciales al visitante
            error_log('Error de conexión a la base de datos: ' . $e->getMessage());
            throw new RuntimeException('No se pudo conectar a la base de datos.');
        }
    }

    return $pdo;
}

/**
 * Lee un valor de la tabla settings (con caché por petición).
 */
function ajuste(string $clave, string $porDefecto = ''): string
{
    static $ajustes = null;

    if ($ajustes === null) {
        $ajustes = db()->query('SELECT clave, valor FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    return $ajustes[$clave] ?? $porDefecto;
}

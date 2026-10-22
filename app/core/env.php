<?php
/**
 * Lector mínimo de archivos .env (sin Composer).
 * Formato: CLAVE=valor por línea. Ignora líneas vacías y comentarios (#).
 * Los valores pueden ir entre comillas simples o dobles.
 */

declare(strict_types=1);

function cargar_env(string $ruta): void
{
    if (!is_readable($ruta)) {
        throw new RuntimeException('No se encontró el archivo .env. Copia .env.example como .env.');
    }

    foreach (file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
        $linea = trim($linea);
        if ($linea === '' || str_starts_with($linea, '#') || !str_contains($linea, '=')) {
            continue;
        }

        [$clave, $valor] = array_map('trim', explode('=', $linea, 2));

        // Quitar comillas envolventes
        if (strlen($valor) >= 2 && ($valor[0] === '"' || $valor[0] === "'") && $valor[-1] === $valor[0]) {
            $valor = substr($valor, 1, -1);
        }

        $_ENV[$clave] = $valor;
    }
}

function env(string $clave, ?string $porDefecto = null): ?string
{
    return $_ENV[$clave] ?? $porDefecto;
}

function env_bool(string $clave, bool $porDefecto = false): bool
{
    $valor = env($clave);
    if ($valor === null) {
        return $porDefecto;
    }
    return in_array(strtolower($valor), ['1', 'true', 'si', 'sí', 'on', 'yes'], true);
}

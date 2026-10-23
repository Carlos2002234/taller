<?php
/**
 * Enrutador simple: convierte la URL en una página de /app/paginas
 * y la muestra dentro del layout común.
 */

declare(strict_types=1);

/**
 * Rutas del sitio: 'url' => ['página', solo_en_desarrollo]
 * Las páginas "en-construccion" se reemplazan en los módulos siguientes.
 */
function rutas(): array
{
    return [
        ''            => ['inicio', false],
        'tienda'      => ['en-construccion', false],
        'carrito'     => ['en-construccion', false],
        'servicios'   => ['en-construccion', false],
        'contacto'    => ['en-construccion', false],
        'mi-pedido'   => ['en-construccion', false],
        'estilos'     => ['estilos', true],
        'instalacion' => ['instalacion', true],
    ];
}

/**
 * Ruta actual sin barras: "/tienda/" → "tienda"
 */
function ruta_actual(): string
{
    static $ruta = null;
    $ruta ??= trim(rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: ''), '/');
    return $ruta;
}

function despachar(): void
{
    $ruta = rutas()[ruta_actual()] ?? null;

    // Las páginas de desarrollo no existen en producción
    if ($ruta === null || ($ruta[1] && !config('app.debug'))) {
        mostrar_pagina('404', 404);
        return;
    }

    mostrar_pagina($ruta[0]);
}

/**
 * Ejecuta una página y la envuelve en el layout.
 * La página puede cambiar $meta (título, descripción, fondo claro, noindex).
 */
function mostrar_pagina(string $pagina, int $codigo = 200): void
{
    http_response_code($codigo);

    $meta = [
        'titulo'      => null,
        'descripcion' => 'Taller mecánico y tienda de accesorios y repuestos para tu auto. Paga por SINPE Móvil o transferencia.',
        'fondo_claro' => false,
        'noindex'     => false,
    ];

    ob_start();
    require RAIZ . '/app/paginas/' . $pagina . '.php';
    $contenido = ob_get_clean();

    require RAIZ . '/app/vistas/layout.php';
}

/**
 * Incluye un componente reutilizable con sus datos: componente('aviso', ['tipo' => 'exito', ...])
 */
function componente(string $nombre, array $datos = []): void
{
    extract($datos, EXTR_SKIP);
    require RAIZ . '/app/vistas/componentes/' . $nombre . '.php';
}

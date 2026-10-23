<?php
/**
 * Enrutador simple: convierte la URL en una página de /app/paginas
 * y la muestra dentro del layout común.
 */

declare(strict_types=1);

/**
 * Se lanza desde una página para mostrar el 404 (ej. producto inexistente).
 */
final class PaginaNoEncontrada extends Exception
{
}

function no_encontrado(): never
{
    throw new PaginaNoEncontrada();
}

/**
 * Rutas del sitio: 'url' => ['página', solo_en_desarrollo, tipo]
 * - {slug} captura un segmento (letras minúsculas, números y guiones) y llega a la página en $parametros['slug'].
 * - tipo 'json' no usa el layout (la página imprime su propia respuesta).
 * Las páginas "en-construccion" se reemplazan en los módulos siguientes.
 */
function rutas(): array
{
    return [
        ''                => ['inicio', false, 'html'],
        'tienda'          => ['tienda', false, 'html'],
        'producto/{slug}' => ['producto', false, 'html'],
        'carrito'         => ['carrito', false, 'html'],
        'checkout'          => ['checkout', false, 'html'],
        'pedido-confirmado' => ['pedido-confirmado', false, 'html'],
        'servicios'       => ['en-construccion', false, 'html'],
        'contacto'        => ['en-construccion', false, 'html'],
        'mi-pedido'          => ['mi-pedido', false, 'html'],
        'mi-pedido/{numero}' => ['pedido-detalle', false, 'html'],
        'api/carrito'     => ['api-carrito', false, 'json'],
        'estilos'         => ['estilos', true, 'html'],
        'instalacion'     => ['instalacion', true, 'html'],
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

/**
 * Busca la ruta que coincide con la URL. Devuelve [definición, parámetros] o null.
 */
function buscar_ruta(string $url): ?array
{
    foreach (rutas() as $patron => $definicion) {
        if (!str_contains($patron, '{')) {
            if ($patron === $url) {
                return [$definicion, []];
            }
            continue;
        }

        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[a-z0-9-]+)', $patron) . '$#';
        if (preg_match($regex, $url, $coincidencias)) {
            return [$definicion, array_filter($coincidencias, 'is_string', ARRAY_FILTER_USE_KEY)];
        }
    }

    return null;
}

function despachar(): void
{
    $encontrada = buscar_ruta(ruta_actual());

    // Las páginas de desarrollo no existen en producción
    if ($encontrada === null || ($encontrada[0][1] && !config('app.debug'))) {
        mostrar_pagina('404', 404);
        return;
    }

    [[$pagina, , $tipo], $parametros] = $encontrada;

    if ($tipo === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        require RAIZ . '/app/paginas/' . $pagina . '.php';
        return;
    }

    mostrar_pagina($pagina, 200, $parametros);
}

/**
 * Ejecuta una página y la envuelve en el layout.
 * La página puede cambiar $meta (título, descripción, imagen, fondo claro, noindex).
 */
function mostrar_pagina(string $pagina, int $codigo = 200, array $parametros = []): void
{
    $meta = [
        'titulo'      => null,
        'descripcion' => 'Taller mecánico y tienda de accesorios y repuestos para tu auto. Paga por SINPE Móvil o transferencia.',
        'imagen'      => null,
        'fondo_claro' => false,
        'noindex'     => false,
    ];

    // Se fija antes de ejecutar la página para que ella pueda cambiarlo (ej. 422 en formularios con errores)
    http_response_code($codigo);

    ob_start();
    try {
        require RAIZ . '/app/paginas/' . $pagina . '.php';
    } catch (PaginaNoEncontrada) {
        ob_end_clean();
        mostrar_pagina('404', 404);
        return;
    }
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

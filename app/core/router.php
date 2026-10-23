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
 * Se lanza cuando un usuario del panel no tiene permiso (ej. empleado en Productos).
 */
final class AccesoDenegado extends Exception
{
}

function prohibido(): never
{
    throw new AccesoDenegado();
}

/**
 * Rutas del sitio: 'url' => ['página', solo_en_desarrollo, tipo]
 * - {slug} captura un segmento (letras minúsculas, números y guiones) y llega a la página en $parametros['slug'].
 * - tipo 'json' no usa el layout (la página imprime su propia respuesta).
 * - tipo 'archivo' envía sus propios encabezados (ej. comprobantes privados).
 */
function rutas(): array
{
    return [
        // Tienda pública
        ''                   => ['inicio', false, 'html'],
        'tienda'             => ['tienda', false, 'html'],
        'producto/{slug}'    => ['producto', false, 'html'],
        'carrito'            => ['carrito', false, 'html'],
        'checkout'           => ['checkout', false, 'html'],
        'pedido-confirmado'  => ['pedido-confirmado', false, 'html'],
        'servicios'          => ['servicios', false, 'html'],
        'contacto'           => ['contacto', false, 'html'],
        'terminos'           => ['legal', false, 'html'],
        'privacidad'         => ['legal', false, 'html'],
        'mi-pedido'          => ['mi-pedido', false, 'html'],
        'mi-pedido/{numero}' => ['pedido-detalle', false, 'html'],
        'api/carrito'        => ['api-carrito', false, 'json'],
        'sitemap.xml'        => ['sitemap', false, 'archivo'],
        'robots.txt'         => ['robots', false, 'archivo'],

        // Panel de administración ("nuevo"/"nueva" deben ir antes de {id})
        'admin'                  => ['admin/inicio', false, 'html'],
        'admin/login'            => ['admin/login', false, 'html'],
        'admin/salir'            => ['admin/salir', false, 'html'],
        'admin/cuenta'           => ['admin/cuenta', false, 'html'],
        'admin/productos'        => ['admin/productos', false, 'html'],
        'admin/productos/nuevo'  => ['admin/producto-form', false, 'html'],
        'admin/productos/{id}'   => ['admin/producto-form', false, 'html'],
        'admin/categorias'       => ['admin/categorias', false, 'html'],
        'admin/categorias/nueva' => ['admin/categoria-form', false, 'html'],
        'admin/categorias/{id}'  => ['admin/categoria-form', false, 'html'],
        'admin/pedidos'          => ['admin/pedidos', false, 'html'],
        'admin/pedidos/{numero}' => ['admin/pedido', false, 'html'],
        'admin/comprobantes/{id}' => ['admin/comprobante-archivo', false, 'archivo'],
        'admin/configuracion'    => ['admin/configuracion', false, 'html'],
        'admin/reportes'         => ['admin/reportes', false, 'html'],
        'admin/servicios'        => ['admin/servicios', false, 'html'],
        'admin/servicios/nuevo'  => ['admin/servicio-form', false, 'html'],
        'admin/servicios/{id}'   => ['admin/servicio-form', false, 'html'],
        'admin/usuarios'         => ['admin/usuarios', false, 'html'],
        'admin/usuarios/nuevo'   => ['admin/usuario-form', false, 'html'],
        'admin/usuarios/{id}'    => ['admin/usuario-form', false, 'html'],

        // Solo en desarrollo
        'estilos'            => ['estilos', true, 'html'],
        'instalacion'        => ['instalacion', true, 'html'],
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

/**
 * Encabezados de seguridad para las páginas:
 * - CSP: el navegador solo carga scripts, estilos, fuentes e imágenes del propio sitio.
 *   (No hay JavaScript ni estilos escritos dentro del HTML; los datos JSON-LD no se ejecutan.)
 * - HSTS: una vez visto por HTTPS, el navegador nunca vuelve a usar HTTP.
 */
function encabezados_seguridad(): void
{
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; "
        . "font-src 'self'; connect-src 'self'; form-action 'self'; frame-ancestors 'self'; base-uri 'self'; object-src 'none'");
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
    if (es_https()) {
        header('Strict-Transport-Security: max-age=31536000');
    }
}

function despachar(): void
{
    encabezados_seguridad();

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

    // Archivos (ej. comprobantes privados): la página envía sus propios encabezados y contenido
    if ($tipo === 'archivo') {
        try {
            require RAIZ . '/app/paginas/' . $pagina . '.php';
        } catch (PaginaNoEncontrada) {
            mostrar_pagina('404', 404);
        } catch (AccesoDenegado) {
            mostrar_pagina('admin/sin-permiso', 403);
        }
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
        'layout'      => 'layout',   // 'admin/layout' para el panel
        'datos'       => [],         // datos estructurados para Google (JSON-LD)
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
    } catch (AccesoDenegado) {
        ob_end_clean();
        mostrar_pagina('admin/sin-permiso', 403);
        return;
    }
    $contenido = ob_get_clean();

    require RAIZ . '/app/vistas/' . $meta['layout'] . '.php';
}

/**
 * Incluye un componente reutilizable con sus datos: componente('aviso', ['tipo' => 'exito', ...])
 */
function componente(string $__componente, array $__datos = []): void
{
    // Nombres internos con "__" para que nunca choquen con los datos del componente
    // (ej. un componente que recibe 'nombre' o 'datos').
    extract($__datos);
    require RAIZ . '/app/vistas/componentes/' . $__componente . '.php';
}

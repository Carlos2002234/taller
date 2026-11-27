<?php
/**
 * Preguntas frecuentes: lee app/datos/preguntas-frecuentes.php, llena los datos de Configuración
 * ({horario}, {direccion}…) y arma el JSON-LD FAQPage con EXACTAMENTE el mismo texto que se ve.
 */

declare(strict_types=1);

/**
 * @return list<array{pregunta: string, respuesta: string, enlace: ?array{texto: string, url: string, externo: bool}}>
 */
function preguntas_frecuentes(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $reemplazos = [
        '{negocio}'    => ajuste('negocio_nombre', 'Rivera Grupo Automotriz'),
        '{telefono}'   => ajuste('telefono'),
        '{direccion}'  => ajuste('direccion'),
        '{horario}'    => horario_resumen(),
        '{horas_pago}' => ajuste('horas_expiracion_pedido', '48'),
    ];

    $lista = [];
    foreach (require RAIZ . '/app/datos/preguntas-frecuentes.php' as $p) {
        $pregunta = trim(strtr((string) ($p['pregunta'] ?? ''), $reemplazos));
        $respuesta = trim(strtr((string) ($p['respuesta'] ?? ''), $reemplazos));
        if ($pregunta === '' || $respuesta === '') {
            continue;
        }

        $enlace = null;
        $datos = $p['enlace'] ?? null;
        if (is_array($datos) && trim((string) ($datos['texto'] ?? '')) !== '') {
            if (isset($datos['whatsapp'])) {
                $enlace = ['texto' => trim($datos['texto']), 'url' => whatsapp_url((string) $datos['whatsapp']), 'externo' => true];
            } elseif (isset($datos['ruta'])) {
                $enlace = ['texto' => trim($datos['texto']), 'url' => url((string) $datos['ruta']), 'externo' => false];
            }
        }

        $lista[] = ['pregunta' => $pregunta, 'respuesta' => $respuesta, 'enlace' => $enlace];
    }

    return $cache = $lista;
}

/**
 * Bloque schema.org FAQPage (para $meta['datos']). La respuesta lleva el mismo texto visible y,
 * si hay enlace, el mismo enlace en HTML (Google acepta <a> dentro de "text").
 */
function preguntas_frecuentes_schema(): array
{
    $preguntas = [];
    foreach (preguntas_frecuentes() as $p) {
        $texto = $p['respuesta'];
        if ($p['enlace']) {
            $texto .= ' <a href="' . e($p['enlace']['url']) . '">' . e($p['enlace']['texto']) . '</a>';
        }
        $preguntas[] = [
            '@type'          => 'Question',
            'name'           => $p['pregunta'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $texto],
        ];
    }

    return [
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => $preguntas,
    ];
}

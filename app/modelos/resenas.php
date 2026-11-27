<?php
/**
 * Reseñas de Google: lee app/datos/resenas-google.php y calcula el resumen que se muestra
 * ("5.0", "7 reseñas en Google") y el que se le da a Google en el JSON-LD (aggregateRating).
 */

declare(strict_types=1);

/**
 * @return array{calificacion: float, total: int, url: string, resenas: list<array{nombre: string, estrellas: int, texto: string, inicial: string}>}
 */
function resenas_google(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $datos = require RAIZ . '/app/datos/resenas-google.php';
    $resumen = $datos['resumen'] ?? [];
    $resenas = [];
    foreach ($datos['resenas'] ?? [] as $r) {
        $nombre = trim((string) ($r['nombre'] ?? ''));
        $texto = trim((string) ($r['texto'] ?? ''));
        if ($nombre === '' || $texto === '') {
            continue;
        }
        $resenas[] = [
            'nombre'    => $nombre,
            'estrellas' => max(1, min(5, (int) ($r['estrellas'] ?? 5))),
            'texto'     => $texto,
            'inicial'   => mb_strtoupper(mb_substr($nombre, 0, 1)),
        ];
    }

    // Total: el de Google (incluye reseñas solo de estrellas); si la lista ya tiene más, manda la lista.
    $enLista = count($resenas);
    $total = max((int) ($resumen['total'] ?? 0), $enLista);

    // Calificación: el promedio de Google; si la lista tiene más reseñas que el resumen, el promedio de la lista.
    $calificacion = (float) ($resumen['calificacion'] ?? 0);
    if ($enLista > (int) ($resumen['total'] ?? 0) || $calificacion <= 0) {
        $calificacion = $enLista ? array_sum(array_column($resenas, 'estrellas')) / $enLista : 0.0;
    }

    return $cache = [
        'calificacion' => round($calificacion, 1),
        'total'        => $total,
        'url'          => (string) ($resumen['url'] ?? ''),
        'resenas'      => $resenas,
    ];
}

/** "5.0" (siempre un decimal, con punto como lo muestra Google) */
function calificacion_texto(float $calificacion): string
{
    return number_format($calificacion, 1, '.', '');
}

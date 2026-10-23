<?php
/**
 * Datos estructurados (JSON-LD) para Google. Se imprimen en el <head> desde el layout.
 * No es JavaScript ejecutable: el navegador lo ignora y Google lo lee.
 * Datos: $bloques (lista de arreglos schema.org)
 */

foreach ($bloques as $bloque) {
    // JSON_HEX_TAG evita que un texto con "</script>" pueda cerrar la etiqueta
    echo '<script type="application/ld+json">'
        . json_encode($bloque, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP)
        . "</script>\n";
}

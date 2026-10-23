<?php
/**
 * Fotos de productos y categorías subidas desde el admin.
 * Se aceptan JPG, PNG o WebP de hasta 10 MB y se convierten a WebP en dos tamaños:
 *   productos/abc123.webp       (grande, máx. 1200 px)  → página del producto
 *   productos/abc123-mini.webp  (pequeña, máx. 400 px)  → tarjetas y miniaturas
 * El archivo original nunca se guarda: se vuelve a dibujar con GD, lo que descarta
 * cualquier contenido extraño escondido en la imagen.
 */

declare(strict_types=1);

const IMAGEN_MAX_BYTES = 10 * 1024 * 1024;
const IMAGEN_MAX_PIXELES = 40_000_000;  // ~ 8000 × 5000; protege la memoria del servidor
const IMAGEN_TIPOS = ['image/jpeg', 'image/png', 'image/webp'];
const IMAGEN_ANCHO_GRANDE = 1200;
const IMAGEN_ANCHO_MINI = 400;

/**
 * Convierte una lista de archivos de un <input type="file" multiple> en una lista simple.
 */
function archivos_subidos(array $campo): array
{
    if (!isset($campo['name']) || !is_array($campo['name'])) {
        return isset($campo['error']) && $campo['error'] !== UPLOAD_ERR_NO_FILE ? [$campo] : [];
    }

    $lista = [];
    foreach ($campo['name'] as $i => $nombre) {
        if ($campo['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $lista[] = [
            'name'     => $nombre,
            'tmp_name' => $campo['tmp_name'][$i],
            'error'    => $campo['error'][$i],
            'size'     => $campo['size'][$i],
        ];
    }
    return $lista;
}

/**
 * Valida y guarda una foto. Devuelve la ruta relativa a /public/uploads (ej. "productos/abc.webp")
 * o un mensaje de error.
 */
function guardar_imagen(array $archivo, string $carpeta): string|array
{
    $nombre = mb_substr(basename((string) ($archivo['name'] ?? 'foto')), 0, 60);
    $error = $archivo['error'] ?? UPLOAD_ERR_NO_FILE;

    if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
        return ['error' => "«{$nombre}» pesa más de 10 MB."];
    }
    if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'] ?? '')) {
        return ['error' => "No se pudo recibir «{$nombre}». Intenta de nuevo."];
    }
    if (filesize($archivo['tmp_name']) > IMAGEN_MAX_BYTES) {
        return ['error' => "«{$nombre}» pesa más de 10 MB."];
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
    $info = @getimagesize($archivo['tmp_name']);
    if (!in_array($mime, IMAGEN_TIPOS, true) || $info === false) {
        return ['error' => "«{$nombre}» no es una imagen JPG, PNG o WebP válida."];
    }
    if ($info[0] * $info[1] > IMAGEN_MAX_PIXELES) {
        return ['error' => "«{$nombre}» tiene demasiada resolución. Usa una foto más pequeña."];
    }

    // Las fotos de cámara son grandes: se da memoria suficiente solo para este proceso
    ini_set('memory_limit', '512M');

    $imagen = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($archivo['tmp_name']),
        'image/png'  => @imagecreatefrompng($archivo['tmp_name']),
        'image/webp' => @imagecreatefromwebp($archivo['tmp_name']),
    };
    if (!$imagen) {
        return ['error' => "No se pudo leer «{$nombre}». Intenta con otra foto."];
    }

    // Fotos de celular: respetar la orientación guardada por la cámara
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $orientacion = (int) (@exif_read_data($archivo['tmp_name'])['Orientation'] ?? 1);
        $rotada = match ($orientacion) {
            3 => imagerotate($imagen, 180, 0),
            6 => imagerotate($imagen, -90, 0),
            8 => imagerotate($imagen, 90, 0),
            default => null,
        };
        if ($rotada) {
            imagedestroy($imagen);
            $imagen = $rotada;
        }
    }

    $directorio = config('ruta.uploads') . '/' . $carpeta;
    if (!is_dir($directorio) && !mkdir($directorio, 0755, true) && !is_dir($directorio)) {
        imagedestroy($imagen);
        error_log('No se pudo crear la carpeta de fotos: ' . $directorio);
        return ['error' => 'No se pudo guardar la foto. Revisa los permisos de la carpeta de fotos.'];
    }

    // La versión pequeña se saca de la grande ya reducida (mucho más rápido que desde la original)
    $base = bin2hex(random_bytes(12));
    $grande = redimensionar($imagen, IMAGEN_ANCHO_GRANDE);
    imagedestroy($imagen);
    $mini = redimensionar($grande, IMAGEN_ANCHO_MINI);
    $ok = imagewebp($grande, $directorio . '/' . $base . '.webp', 80)
       && imagewebp($mini, $directorio . '/' . $base . '-mini.webp', 80);
    imagedestroy($grande);
    imagedestroy($mini);

    if (!$ok) {
        borrar_imagen($carpeta . '/' . $base . '.webp');
        return ['error' => "No se pudo convertir «{$nombre}». Intenta con otra foto."];
    }

    return $carpeta . '/' . $base . '.webp';
}

/**
 * Copia redimensionada (sin agrandar) que conserva la transparencia. El lado mayor queda en $maximo.
 */
function redimensionar(GdImage $origen, int $maximo): GdImage
{
    $ancho = imagesx($origen);
    $alto = imagesy($origen);
    $escala = min(1, $maximo / max($ancho, $alto));
    $nuevoAncho = max(1, (int) round($ancho * $escala));
    $nuevoAlto = max(1, (int) round($alto * $escala));

    $lienzo = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
    imagealphablending($lienzo, false);
    imagesavealpha($lienzo, true);
    imagecopyresampled($lienzo, $origen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);
    return $lienzo;
}

/**
 * Borra las dos versiones (grande y mini) de una foto.
 */
function borrar_imagen(string $ruta): void
{
    foreach ([$ruta, ruta_mini($ruta)] as $relativa) {
        $archivo = config('ruta.uploads') . '/' . $relativa;
        // Solo dentro de /uploads (nunca rutas con "..")
        if (!str_contains($relativa, '..') && is_file($archivo)) {
            unlink($archivo);
        }
    }
}

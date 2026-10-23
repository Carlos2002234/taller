<?php
/**
 * Comprobantes de pago subidos por el cliente.
 * - Solo JPG, PNG o PDF de máximo 5 MB (se revisa el tipo REAL del archivo, no la extensión).
 * - Se guardan con nombre aleatorio en /storage/comprobantes (sin acceso público).
 * - Un solo comprobante activo por pedido: solo se puede subir otro si el anterior fue rechazado.
 * - Subirlo NUNCA marca el pedido como pagado: pasa a 'comprobante_enviado' y el dueño lo revisa.
 */

declare(strict_types=1);

require_once RAIZ . '/app/modelos/pedidos.php';

const COMPROBANTE_MAX_BYTES = 5 * 1024 * 1024;
const COMPROBANTE_TIPOS = [
    'image/jpeg'      => 'jpg',
    'image/png'       => 'png',
    'application/pdf' => 'pdf',
];

/**
 * Último comprobante del pedido (o null).
 */
function comprobante_ultimo(int $pedidoId): ?array
{
    $stmt = db()->prepare('SELECT * FROM payment_proofs WHERE order_id = ? ORDER BY id DESC LIMIT 1');
    $stmt->execute([$pedidoId]);
    return $stmt->fetch() ?: null;
}

/**
 * true si el pedido acepta un comprobante ahora mismo.
 */
function comprobante_permitido(array $pedido): bool
{
    if ($pedido['estado'] !== 'pendiente_pago' || strtotime($pedido['expira_en']) <= time()) {
        return false;
    }
    $ultimo = comprobante_ultimo((int) $pedido['id']);
    return $ultimo === null || $ultimo['estado_revision'] === 'rechazado';
}

/**
 * Valida el archivo subido. Devuelve [mime, extension] o un mensaje de error.
 */
function validar_comprobante(array $archivo): array|string
{
    $error = $archivo['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($error === UPLOAD_ERR_NO_FILE) {
        return 'Elige la foto o el PDF de tu comprobante.';
    }
    if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
        return 'El archivo pesa más de 5 MB. Sube una captura de pantalla o un PDF más liviano.';
    }
    if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'] ?? '')) {
        return 'No pudimos recibir el archivo. Intenta de nuevo.';
    }

    $tamano = (int) filesize($archivo['tmp_name']);
    if ($tamano === 0) {
        return 'El archivo está vacío.';
    }
    if ($tamano > COMPROBANTE_MAX_BYTES) {
        return 'El archivo pesa más de 5 MB. Sube una captura de pantalla o un PDF más liviano.';
    }

    // Tipo real según el contenido (no se confía en el nombre ni en lo que diga el navegador)
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
    if (!isset(COMPROBANTE_TIPOS[$mime])) {
        return 'Solo se aceptan imágenes JPG o PNG, o archivos PDF.';
    }

    // Segunda revisión: que la imagen se pueda leer como imagen y el PDF empiece como PDF
    if ($mime === 'application/pdf') {
        $inicio = (string) file_get_contents($archivo['tmp_name'], false, null, 0, 5);
        if ($inicio !== '%PDF-') {
            return 'El PDF parece estar dañado. Intenta con otro archivo.';
        }
    } elseif (@getimagesize($archivo['tmp_name']) === false) {
        return 'La imagen parece estar dañada. Intenta con otra captura.';
    }

    return [$mime, COMPROBANTE_TIPOS[$mime]];
}

/**
 * Guarda el comprobante y pasa el pedido a 'comprobante_enviado'.
 * Devuelve null si todo salió bien, o un mensaje de error para el cliente.
 */
function guardar_comprobante(array $pedido, array $archivo): ?string
{
    $validacion = validar_comprobante($archivo);
    if (is_string($validacion)) {
        return $validacion;
    }
    [$mime, $extension] = $validacion;

    // Carpeta por año/mes y nombre imposible de adivinar
    $relativa = date('Y/m') . '/' . bin2hex(random_bytes(16)) . '.' . $extension;
    $destino = config('ruta.comprobantes') . '/' . $relativa;
    if (!is_dir(dirname($destino)) && !mkdir(dirname($destino), 0750, true) && !is_dir(dirname($destino))) {
        error_log('No se pudo crear la carpeta de comprobantes: ' . dirname($destino));
        return 'No pudimos guardar el archivo. Intenta de nuevo en un momento.';
    }
    if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
        error_log('No se pudo mover el comprobante a ' . $destino);
        return 'No pudimos guardar el archivo. Intenta de nuevo en un momento.';
    }

    $db = db();
    $db->beginTransaction();
    try {
        // Revisar de nuevo con bloqueo: el pedido pudo vencer o cambiar mientras se subía el archivo
        $stmt = $db->prepare('SELECT * FROM orders WHERE id = ? FOR UPDATE');
        $stmt->execute([$pedido['id']]);
        $actual = $stmt->fetch();

        if (!$actual || !comprobante_permitido($actual)) {
            $db->rollBack();
            unlink($destino);
            $vencido = $actual && ($actual['estado'] === 'cancelado'
                || ($actual['estado'] === 'pendiente_pago' && strtotime($actual['expira_en']) <= time()));
            return $vencido
                ? 'El plazo para pagar este pedido venció y se canceló. Si ya pagaste, escríbenos por WhatsApp con tu número de pedido.'
                : 'Este pedido ya no acepta comprobantes.';
        }

        $db->prepare(
            'INSERT INTO payment_proofs (order_id, ruta_archivo, nombre_original, mime, tamano) VALUES (?, ?, ?, ?, ?)'
        )->execute([
            $actual['id'],
            $relativa,
            mb_substr(basename((string) ($archivo['name'] ?? '')), 0, 255),
            $mime,
            (int) filesize($destino),
        ]);
        $db->prepare("UPDATE orders SET estado = 'comprobante_enviado' WHERE id = ?")->execute([$actual['id']]);
        registrar_estado($db, (int) $actual['id'], 'pendiente_pago', 'comprobante_enviado', 'cliente', 'Comprobante subido desde la web');
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        @unlink($destino);
        error_log('Error al guardar comprobante: ' . $e->getMessage());
        return 'No pudimos guardar el comprobante. Intenta de nuevo en un momento.';
    }

    return null;
}

<?php
/**
 * Notificaciones al cliente.
 *
 * FASE 2 (NO implementar todavía): enviar por WhatsApp el número de pedido
 * y las instrucciones para mandar el comprobante de SINPE Móvil.
 * El dueño decidirá entre la API oficial de WhatsApp Business o un enlace prellenado.
 */

declare(strict_types=1);

/**
 * Se llama justo después de crear cada pedido (ver crear_pedido()).
 * $pedido trae: id, numero_pedido, nombre_cliente, telefono, email, total, tipo_entrega, expira_en.
 */
function notificarPedido(array $pedido): void
{
    // Intencionalmente vacía hasta la Fase 2.
}

// ---------------------------------------------------------------------
// Alertas al equipo por Telegram cuando entra una consulta (lead)
// Se activan con TELEGRAM_BOT_TOKEN y TELEGRAM_CHAT_ID en .env (guía: docs/alertas-telegram.md).
// ---------------------------------------------------------------------

function telegram_configurado(): bool
{
    return config('telegram.token') !== '' && config('telegram.chat') !== '';
}

/**
 * Avisa por Telegram que llegó una consulta. Nunca detiene al cliente: si falla, solo queda en el log.
 * $consulta = fila de la tabla consultas.
 */
function notificar_consulta(array $consulta): void
{
    if (!telegram_configurado()) {
        return;
    }

    $e = fn (?string $texto): string => htmlspecialchars((string) $texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $lineas = [
        '🔔 <b>Nueva consulta en la página</b>',
        '',
        '👤 ' . $e($consulta['nombre']),
        '📱 ' . $e($consulta['telefono']),
    ];
    if ($consulta['email']) {
        $lineas[] = '✉️ ' . $e($consulta['email']);
    }
    if ($consulta['mensaje']) {
        $lineas[] = '';
        $lineas[] = '💬 ' . $e(mb_strimwidth($consulta['mensaje'], 0, 700, '…'));
    }
    $origen = array_filter([$consulta['utm_source'], $consulta['utm_campaign']]);
    $lineas[] = '';
    $lineas[] = '<i>Llegó desde: ' . $e($origen ? implode(' / ', $origen) : 'directo') . '</i>';

    $primerNombre = explode(' ', $consulta['nombre'])[0];
    $botones = [
        ['text' => 'Escribir por WhatsApp', 'url' => whatsapp_cliente_url($consulta['telefono'],
            'Hola ' . $primerNombre . ', le saluda ' . ajuste('negocio_nombre', 'Rivera Grupo Automotriz') . '. Recibimos su consulta desde la página web.')],
    ];
    // Telegram no acepta botones que apunten a una dirección local (ej. 127.0.0.1 mientras se prueba en la PC)
    $panel = url('admin/consultas/' . $consulta['id']);
    if (!preg_match('#^https?://(localhost|127\.|192\.168\.|10\.)#i', $panel) && !str_ends_with((string) parse_url($panel, PHP_URL_HOST), '.test')) {
        $botones[] = ['text' => 'Ver en el panel', 'url' => $panel];
    }

    enviar_telegram(implode("\n", $lineas), $botones);
}

/**
 * Envía un mensaje al chat configurado. Devuelve null si se envió, o el motivo del error.
 */
function enviar_telegram(string $html, array $botones = []): ?string
{
    if (!telegram_configurado()) {
        return 'Faltan TELEGRAM_BOT_TOKEN o TELEGRAM_CHAT_ID en el archivo .env.';
    }
    if (!function_exists('curl_init')) {
        return 'El servidor no tiene la extensión curl de PHP.';
    }

    $datos = [
        'chat_id'                  => config('telegram.chat'),
        'text'                     => $html,
        'parse_mode'               => 'HTML',
        'disable_web_page_preview' => true,
    ];
    if ($botones) {
        $datos['reply_markup'] = json_encode(['inline_keyboard' => array_map(fn ($b) => [$b], $botones)]);
    }

    $curl = curl_init('https://api.telegram.org/bot' . config('telegram.token') . '/sendMessage');
    $opciones = [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $datos,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT        => 5,   // el cliente no espera más de esto aunque Telegram tarde
    ];
    // En Windows (solo pruebas en la PC) se usan los certificados del sistema; la verificación SIEMPRE queda activa
    if (PHP_OS_FAMILY === 'Windows' && defined('CURLSSLOPT_NATIVE_CA')) {
        $opciones[CURLOPT_SSL_OPTIONS] = CURLSSLOPT_NATIVE_CA;
    }
    curl_setopt_array($curl, $opciones);
    $respuesta = curl_exec($curl);
    $errorCurl = curl_error($curl);
    curl_close($curl);

    $json = is_string($respuesta) ? json_decode($respuesta, true) : null;
    if (!is_array($json) || empty($json['ok'])) {
        // El token nunca se escribe en el log
        $motivo = $errorCurl !== '' ? $errorCurl : (string) ($json['description'] ?? 'respuesta inválida');
        error_log('Telegram: no se pudo enviar la alerta: ' . $motivo);
        return $motivo;
    }
    return null;
}

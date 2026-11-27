<?php
/**
 * Notificaciones al cliente.
 *
 * FASE 2 (NO implementar todavía): enviar por WhatsApp el número de pedido
 * y las instrucciones para mandar el comprobante de SINPE Móvil.
 * El dueño decidirá entre la API oficial de WhatsApp Business o un enlace prellenado.
 */

declare(strict_types=1);

require_once __DIR__ . '/correo.php';

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
 * Avisa que llegó una consulta: por Telegram y por correo (cada uno solo si está configurado).
 * Nunca detiene al cliente: si un aviso falla, solo queda en el log y la consulta ya está guardada.
 * $consulta = fila de la tabla consultas.
 */
function notificar_consulta(array $consulta): void
{
    if (telegram_configurado()) {
        notificar_consulta_telegram($consulta);
    }
    if (correo_configurado()) {
        notificar_consulta_correo($consulta);
    }
}

function notificar_consulta_telegram(array $consulta): void
{

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

// ---------------------------------------------------------------------
// Aviso por correo (Gmail) cuando entra una consulta
// Se activa con GMAIL_USUARIO y GMAIL_CLAVE_APP en .env (guía: docs/alertas-correo.md).
// ---------------------------------------------------------------------

function notificar_consulta_correo(array $consulta): ?string
{
    $correo = correo_consulta($consulta);
    return enviar_correo($correo['asunto'], $correo['html'], $correo['texto'], ['reply_to' => $correo['reply_to']]);
}

/** Fecha y hora de Costa Rica legible: "27/11/2026, 3:05 p. m." */
function fecha_hora_cr(?string $fecha = null): string
{
    $zona = new DateTimeZone('America/Costa_Rica');
    $momento = new DateTimeImmutable($fecha ?? 'now', $zona);
    return $momento->format('d/m/Y') . ', ' . $momento->format('g:i') . ($momento->format('A') === 'AM' ? ' a. m.' : ' p. m.');
}

/** Dominio del sitio para el asunto (ej. "riveraautomotriz.com"). */
function dominio_sitio(): string
{
    $host = (string) parse_url(config('app.url'), PHP_URL_HOST);
    return $host !== '' ? preg_replace('/^www\./i', '', $host) : 'riveraautomotriz.com';
}

/**
 * Arma el correo de una consulta: asunto, HTML, texto y Reply-To. Todo lo que escribió el cliente
 * se escapa en el HTML y se limpia de saltos de línea donde va en una cabecera.
 */
function correo_consulta(array $consulta): array
{
    $e = fn (?string $texto): string => htmlspecialchars((string) $texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    $nombre = mb_substr(limpiar_cabecera((string) $consulta['nombre']), 0, 120);
    $telefono = limpiar_cabecera((string) $consulta['telefono']);
    $email = limpiar_cabecera((string) ($consulta['email'] ?? ''));
    $email = filter_var($email, FILTER_VALIDATE_EMAIL) !== false ? $email : '';
    $mensaje = trim((string) ($consulta['mensaje'] ?? ''));
    $pagina = trim((string) ($consulta['pagina'] ?? ''), '/');
    $paginaUrl = url($pagina);
    $origen = array_filter([$consulta['utm_source'] ?? null, $consulta['utm_campaign'] ?? null]);
    $origen = $origen ? implode(' / ', $origen) : 'directo';
    $fecha = fecha_hora_cr($consulta['creado_en'] ?? null);
    $panel = url('admin/consultas/' . (int) $consulta['id']);
    $negocio = ajuste('negocio_nombre', 'Rivera Grupo Automotriz');
    $primerNombre = explode(' ', $nombre)[0];
    $whatsapp = whatsapp_cliente_url($telefono,
        'Hola ' . $primerNombre . ', le saluda ' . $negocio . '. Recibimos su consulta desde la página web.');

    $asunto = 'Nueva consulta en ' . dominio_sitio() . ' — ' . $nombre;

    // Versión de texto
    $texto = "Nueva consulta en la página\n\n"
        . "Nombre: {$nombre}\n"
        . "Teléfono / WhatsApp: {$telefono}\n"
        . "Escribirle por WhatsApp: {$whatsapp}\n"
        . ($email !== '' ? "Correo: {$email}\n" : '')
        . "\nMensaje:\n" . ($mensaje !== '' ? $mensaje : '(no dejó mensaje)') . "\n\n"
        . 'Página: ' . ($pagina !== '' ? $paginaUrl : 'no registrada') . "\n"
        . "Llegó desde: {$origen}\n"
        . "Fecha: {$fecha} (hora de Costa Rica)\n\n"
        . "Ver la consulta en el panel: {$panel}\n";

    // Versión HTML (estilos en línea: los programas de correo ignoran las hojas de estilo)
    $fila = fn (string $etiqueta, string $valorHtml): string =>
        '<tr><td style="padding:6px 12px 6px 0;color:#555;vertical-align:top;white-space:nowrap">' . $etiqueta . '</td>'
        . '<td style="padding:6px 0;color:#111">' . $valorHtml . '</td></tr>';
    $filas = $fila('Nombre', '<strong>' . $e($nombre) . '</strong>')
        . $fila('Teléfono / WhatsApp', $e($telefono) . ' · <a href="' . $e($whatsapp) . '" style="color:#D11A21">Escribir por WhatsApp</a>')
        . ($email !== '' ? $fila('Correo', '<a href="mailto:' . $e($email) . '" style="color:#D11A21">' . $e($email) . '</a>') : '')
        . $fila('Página', $pagina !== '' ? '<a href="' . $e($paginaUrl) . '" style="color:#D11A21">' . $e('/' . $pagina) . '</a>' : 'no registrada')
        . $fila('Llegó desde', $e($origen))
        . $fila('Fecha', $e($fecha) . ' (hora de Costa Rica)');
    $mensajeHtml = $mensaje !== '' ? nl2br($e($mensaje)) : '<em style="color:#777">No dejó mensaje.</em>';

    $html = '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>' . $e($asunto) . '</title></head>'
        . '<body style="margin:0;padding:0;background:#f2f2f2;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.5">'
        . '<div style="max-width:560px;margin:0 auto;padding:24px 16px">'
        . '<div style="background:#0b0b0b;color:#fff;padding:16px 20px;border-radius:8px 8px 0 0;border-bottom:4px solid #D11A21">'
        . '<strong style="font-size:18px">Nueva consulta en la página</strong></div>'
        . '<div style="background:#fff;padding:20px;border-radius:0 0 8px 8px">'
        . '<table role="presentation" style="border-collapse:collapse;width:100%">' . $filas . '</table>'
        . '<p style="margin:18px 0 6px;color:#555">Mensaje:</p>'
        . '<div style="background:#f7f7f7;border-left:3px solid #D11A21;padding:12px 14px;color:#111">' . $mensajeHtml . '</div>'
        . '<p style="margin:24px 0 8px"><a href="' . $e($panel) . '" style="display:inline-block;background:#D11A21;color:#fff;text-decoration:none;font-weight:bold;padding:12px 20px;border-radius:6px">Ver la consulta en el panel</a></p>'
        . '<p style="margin:0;font-size:12px;color:#777">Si el botón no funciona: ' . $e($panel) . '</p>'
        . '</div>'
        . '<p style="font-size:12px;color:#777;text-align:center">Aviso automático de ' . $e($negocio) . '. '
        . ($email !== '' ? 'Si respondes este correo, le llega a ' . $e($email) . '.' : 'El cliente no dejó correo: contáctalo por teléfono o WhatsApp.')
        . '</p></div></body></html>';

    return ['asunto' => $asunto, 'html' => $html, 'texto' => $texto, 'reply_to' => $email];
}

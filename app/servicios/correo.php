<?php
/**
 * Envío de correos por SMTP (Gmail con contraseña de aplicación), sin Composer ni librerías externas.
 *
 * Por qué un cliente propio y no PHPMailer: el sitio solo necesita UNA cosa (mandar un aviso de
 * texto + HTML a una cuenta conocida por smtp.gmail.com). Este archivo es corto, se lee de una vez,
 * no hay que actualizar ni vigilar una librería de terceros y no agrega avisos de licencia.
 * Usa TLS con verificación de certificado SIEMPRE (STARTTLS en 587 o SSL directo en 465).
 *
 * Configuración (.env): GMAIL_USUARIO, GMAIL_CLAVE_APP y, opcional, AVISOS_CORREO_A.
 * Guía: docs/alertas-correo.md
 */

declare(strict_types=1);

/** Datos de conexión a Gmail tomados del .env. La clave de aplicación se usa sin espacios. */
function correo_config(): array
{
    $usuario = limpiar_cabecera((string) config('correo.usuario'));
    return [
        'host'      => 'smtp.gmail.com',
        'puerto'    => 587,
        'seguridad' => 'starttls',   // 'starttls' (587) o 'ssl' (465)
        'usuario'   => $usuario,
        'clave'     => str_replace(' ', '', (string) config('correo.clave')),
        'de'        => $usuario,
        'para'      => limpiar_cabecera((string) config('correo.para')) ?: $usuario,
        'timeout'   => 8,            // segundos por operación; el cliente nunca espera mucho más
    ];
}

/** El aviso por correo está activo solo si hay cuenta Gmail, clave de aplicación y destinatario válidos. */
function correo_configurado(): bool
{
    $c = correo_config();
    return $c['clave'] !== ''
        && filter_var($c['usuario'], FILTER_VALIDATE_EMAIL) !== false
        && filter_var($c['para'], FILTER_VALIDATE_EMAIL) !== false;
}

/** Quita saltos de línea y caracteres de control: evita inyectar cabeceras (ej. "Bcc:") desde un formulario. */
function limpiar_cabecera(string $texto): string
{
    return trim((string) preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $texto));
}

/**
 * Envía un correo con versión HTML y texto. Devuelve null si el servidor lo aceptó, o el motivo del error.
 * $opciones: reply_to (correo del cliente, opcional). $config permite cambiar el servidor (solo pruebas).
 * La clave nunca se escribe en el log ni en el mensaje de error.
 */
function enviar_correo(string $asunto, string $html, string $texto, array $opciones = [], ?array $config = null): ?string
{
    $c = $config ?? correo_config();
    if ($config === null && !correo_configurado()) {
        return 'Faltan GMAIL_USUARIO o GMAIL_CLAVE_APP en el archivo .env.';
    }

    $replyTo = limpiar_cabecera((string) ($opciones['reply_to'] ?? ''));
    if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL) === false) {
        $replyTo = '';
    }

    try {
        $mensaje = armar_mensaje_correo($c, limpiar_cabecera($asunto), $html, $texto, $replyTo);
        smtp_enviar($c, $mensaje);
        return null;
    } catch (Throwable $e) {
        $motivo = $e->getMessage();
        error_log('Correo: no se pudo enviar el aviso: ' . $motivo);
        return $motivo;
    }
}

/** Arma el mensaje MIME (multipart/alternative, UTF-8, cuerpos en base64 para no tener líneas largas). */
function armar_mensaje_correo(array $c, string $asunto, string $html, string $texto, string $replyTo): string
{
    $nombre = limpiar_cabecera(ajuste('negocio_nombre', 'Rivera Grupo Automotriz'));
    $dominio = substr((string) strrchr($c['de'], '@'), 1) ?: 'localhost';
    $limite = 'limite-' . bin2hex(random_bytes(12));

    $cabeceras = [
        'Date: ' . date(DATE_RFC2822),
        'From: ' . mb_encode_mimeheader($nombre, 'UTF-8', 'B') . ' <' . $c['de'] . '>',
        'To: <' . $c['para'] . '>',
        'Subject: ' . mb_encode_mimeheader($asunto, 'UTF-8', 'B', "\r\n"),
        'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . $dominio . '>',
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $limite . '"',
    ];
    if ($replyTo !== '') {
        $cabeceras[] = 'Reply-To: <' . $replyTo . '>';
    }

    $parte = fn (string $tipo, string $cuerpo): string =>
        "--{$limite}\r\nContent-Type: {$tipo}; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
        . rtrim(chunk_split(base64_encode($cuerpo), 76, "\r\n")) . "\r\n";

    return implode("\r\n", $cabeceras) . "\r\n\r\n"
        . $parte('text/plain', $texto)
        . $parte('text/html', $html)
        . "--{$limite}--\r\n";
}

/**
 * Conversación SMTP mínima: EHLO → STARTTLS → EHLO → AUTH LOGIN → MAIL FROM → RCPT TO → DATA → QUIT.
 * Lanza RuntimeException con un motivo legible si algo falla.
 */
function smtp_enviar(array $c, string $mensaje): void
{
    $tls = ['verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => $c['host']];
    if (!empty($c['cafile'])) {
        $tls['cafile'] = $c['cafile'];   // solo pruebas locales con un servidor falso
    }
    $contexto = stream_context_create(['ssl' => $tls]);
    $esquema = $c['seguridad'] === 'ssl' ? 'ssl' : 'tcp';

    $socket = @stream_socket_client("{$esquema}://{$c['host']}:{$c['puerto']}", $errno, $errstr,
        $c['timeout'], STREAM_CLIENT_CONNECT, $contexto);
    if (!$socket) {
        throw new RuntimeException("No se pudo conectar con {$c['host']}:{$c['puerto']} ({$errstr})");
    }
    stream_set_timeout($socket, (int) $c['timeout']);

    try {
        $equipo = 'localhost';
        smtp_esperar($socket, [220]);
        smtp_comando($socket, "EHLO {$equipo}", [250]);

        if ($c['seguridad'] === 'starttls') {
            smtp_comando($socket, 'STARTTLS', [220]);
            if (!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                throw new RuntimeException('No se pudo activar TLS con el servidor de correo.');
            }
            smtp_comando($socket, "EHLO {$equipo}", [250]);
        }

        if ($c['usuario'] !== '') {
            smtp_comando($socket, 'AUTH LOGIN', [334]);
            smtp_comando($socket, base64_encode($c['usuario']), [334], true);
            smtp_comando($socket, base64_encode($c['clave']), [235], true);
        }

        smtp_comando($socket, 'MAIL FROM:<' . $c['de'] . '>', [250]);
        smtp_comando($socket, 'RCPT TO:<' . $c['para'] . '>', [250, 251]);
        smtp_comando($socket, 'DATA', [354]);
        // Normaliza saltos de línea y duplica el punto al inicio de línea (regla de SMTP)
        $cuerpo = preg_replace('/\r?\n/', "\r\n", $mensaje);
        $cuerpo = preg_replace('/^\./m', '..', $cuerpo);
        smtp_comando($socket, rtrim($cuerpo, "\r\n") . "\r\n.", [250]);
        @fwrite($socket, "QUIT\r\n");
    } finally {
        fclose($socket);
    }
}

/** Envía una línea y valida el código de respuesta. $secreto = no mostrar el contenido en errores. */
function smtp_comando($socket, string $linea, array $codigos, bool $secreto = false): string
{
    if (@fwrite($socket, $linea . "\r\n") === false) {
        throw new RuntimeException('Se cortó la conexión con el servidor de correo.');
    }
    try {
        return smtp_esperar($socket, $codigos);
    } catch (RuntimeException $e) {
        $comando = $secreto ? 'la autenticación' : strtok($linea, ' :');
        throw new RuntimeException("El servidor de correo rechazó {$comando}: " . $e->getMessage());
    }
}

/** Lee la respuesta (puede tener varias líneas "250-...") y verifica el código. */
function smtp_esperar($socket, array $codigos): string
{
    $respuesta = '';
    while (($linea = fgets($socket, 1024)) !== false) {
        $respuesta .= $linea;
        if (strlen($linea) < 4 || $linea[3] !== '-') {
            break;
        }
    }
    if ($respuesta === '') {
        $info = stream_get_meta_data($socket);
        throw new RuntimeException(!empty($info['timed_out']) ? 'tiempo de espera agotado' : 'sin respuesta');
    }
    $codigo = (int) substr($respuesta, 0, 3);
    if (!in_array($codigo, $codigos, true)) {
        throw new RuntimeException(trim(preg_replace('/\s+/', ' ', $respuesta)));
    }
    return $respuesta;
}

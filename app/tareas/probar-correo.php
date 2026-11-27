<?php
/**
 * Prueba del aviso por correo: manda un correo de ejemplo (una consulta ficticia) con la
 * configuración del .env (GMAIL_USUARIO, GMAIL_CLAVE_APP, AVISOS_CORREO_A).
 *
 *   php app/tareas/probar-correo.php
 *
 * No guarda nada en la base de datos. Nunca muestra la clave.
 * Termina con código 0 si Gmail aceptó el correo, 1 si falta configuración o falló el envío.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/config/config.php';
require RAIZ . '/app/servicios/notificaciones.php';

if (!correo_configurado()) {
    fwrite(STDERR, "El aviso por correo NO está configurado: faltan GMAIL_USUARIO o GMAIL_CLAVE_APP en .env\n"
        . "(o AVISOS_CORREO_A no es un correo válido). Guía: docs/alertas-correo.md\n");
    exit(1);
}

$c = correo_config();
echo "Enviando correo de prueba desde {$c['usuario']} hacia {$c['para']} por {$c['host']}:{$c['puerto']}…\n";

$correo = correo_consulta([
    'id'           => 0,
    'nombre'       => 'Prueba del aviso por correo',
    'telefono'     => ajuste('telefono', '8702-6760'),
    'email'        => '',
    'mensaje'      => "Este es un correo de prueba.\nAsí te llegará el aviso cada vez que alguien deje una consulta en la página.",
    'pagina'       => 'contacto',
    'utm_source'   => null,
    'utm_campaign' => null,
    'creado_en'    => date('Y-m-d H:i:s'),
]);
$error = enviar_correo('[Prueba] ' . $correo['asunto'], $correo['html'], $correo['texto']);

if ($error !== null) {
    fwrite(STDERR, "No se pudo enviar: {$error}\n");
    exit(1);
}
echo "Listo: Gmail aceptó el correo. Revisa la bandeja de {$c['para']} (y la carpeta Spam la primera vez).\n";

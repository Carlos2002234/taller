# Aviso de consultas por correo (Gmail)

Cada vez que alguien deja sus datos en el formulario "¿Tienes preguntas?" o en Contacto, además del
aviso por Telegram te llega un **correo** con el nombre, el teléfono (con enlace a WhatsApp), el correo
del cliente si lo dejó, el mensaje, la página desde donde escribió, la fecha y hora de Costa Rica y un
botón para abrir la consulta en el panel. Si el cliente dejó su correo, puedes **responder ese correo
directamente** y le llega a él.

El correo se envía desde tu propio Gmail (gratis, sin servicios extra).

## 1. Activa la verificación en dos pasos

Gmail solo permite "contraseñas de aplicación" si la cuenta tiene verificación en dos pasos.

1. Entra a <https://myaccount.google.com/security> con la cuenta que enviará los avisos.
2. En **Cómo inicias sesión en Google**, activa **Verificación en dos pasos** (si ya está, sigue).

## 2. Crea una contraseña de aplicación

1. Entra a <https://myaccount.google.com/apppasswords>.
2. En el nombre escribe por ejemplo `Pagina Rivera` y toca **Crear**.
3. Google muestra una clave de **16 letras** (ej. `abcd efgh ijkl mnop`). **Cópiala**: solo se ve una vez.
   Es como una contraseña: no la compartas ni la subas a GitHub.

> Esta clave NO es tu contraseña normal de Gmail. Si un día la borras en esa misma página, los avisos
> dejan de llegar (y la página sigue funcionando normal).

## 3. Ponla en el archivo `.env` del servidor

```
GMAIL_USUARIO=criverav04@gmail.com
GMAIL_CLAVE_APP=abcdefghijklmnop
AVISOS_CORREO_A=
```

- `GMAIL_USUARIO`: la cuenta Gmail que envía los avisos.
- `GMAIL_CLAVE_APP`: la clave de 16 letras (con o sin espacios, da igual).
- `AVISOS_CORREO_A`: a quién le llegan. **Vacío = al mismo `GMAIL_USUARIO`.**

Si falta alguno de los dos primeros, no se envía ningún correo y la página funciona igual.

## 4. Pruébalo

En la terminal del servidor, desde la carpeta del proyecto:

```
php app/tareas/probar-correo.php
```

Debe decir `Listo: Gmail aceptó el correo.` y te llega un correo "[Prueba] Nueva consulta en …".
La primera vez revisa **Spam** y márcalo como "No es spam".

También puedes probarlo desde el panel: **Admin → Consultas → Aviso por correo → Enviar correo de prueba**
(solo el dueño). Ahí mismo se ve si el aviso está activo y a qué correo llega (la clave nunca se muestra).

## Si algo falla

- El cliente **siempre** ve su consulta como enviada: queda guardada en el panel aunque el correo falle.
- El motivo queda en `storage/logs/php-errores.log` (línea que empieza con `Correo:`).
- `rechazó la autenticación … 535` → la clave de aplicación está mal o fue borrada: crea otra.
- `No se pudo conectar con smtp.gmail.com:587` → el servidor no tiene salida al puerto 587 (pregunta al hosting).

## Detalles técnicos

- Envío por `smtp.gmail.com:587` con STARTTLS y verificación del certificado (TLS 1.2 o 1.3).
- Cliente SMTP propio y mínimo en `app/servicios/correo.php` (sin Composer ni librerías externas).
  Espera como máximo 8 s por paso; el aviso se arma en `correo_consulta()` (`app/servicios/notificaciones.php`).
- Todo lo que escribe el cliente se escapa en el HTML, y se le quitan los saltos de línea a lo que va en
  cabeceras (nombre en el asunto, correo en Reply-To) para que nadie pueda inyectar cabeceras.

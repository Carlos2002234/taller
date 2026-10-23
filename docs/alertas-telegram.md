# Alertas de consultas por Telegram

Cada vez que alguien deja sus datos en el formulario "¿Tienes preguntas?", te llega un mensaje
**al instante** a Telegram con el nombre, el teléfono, la pregunta y un botón para escribirle por WhatsApp.
Es gratis y toma unos 5 minutos configurarlo.

## 1. Crea tu "bot" de alertas

1. Instala **Telegram** en tu celular (si no lo tienes) y abre la app.
2. En el buscador escribe **@BotFather** (tiene una palomita azul de verificado) y ábrelo.
3. Toca **Iniciar** y escribe: `/newbot`
4. Te pide un **nombre**: escribe por ejemplo `Alertas Rivera`.
5. Te pide un **usuario** que termine en `bot`: por ejemplo `rivera_alertas_bot`
   (si ya existe, prueba otro, como `riveragrupo_alertas_bot`).
6. BotFather te responde con un **token**, parecido a:
   `7123456789:AAH4k...` → **cópialo**. Es como una contraseña: no lo compartas.

## 2. Activa el bot

1. En el mismo mensaje de BotFather hay un enlace a tu bot (`t.me/rivera_alertas_bot`). Ábrelo.
2. Toca **Iniciar**. (Sin este paso el bot no puede escribirte.)

## 3. Averigua tu número de chat

1. En el buscador de Telegram escribe **@userinfobot** y ábrelo.
2. Toca **Iniciar**. Te responde con tu **Id**, un número como `123456789` → **cópialo**.

> **¿Quieres que también le lleguen al empleado?** Crea un grupo de Telegram con él, agrega tu bot
> al grupo y escríbeme: te ayudo a sacar el número del grupo (empieza con `-`).

## 4. Pon los datos en el archivo `.env`

Abre el archivo `.env` del sitio y completa estas dos líneas (sin espacios ni comillas):

```
TELEGRAM_BOT_TOKEN=7123456789:AAH4k...
TELEGRAM_CHAT_ID=123456789
```

- **En tu computadora:** el archivo `.env` está en la carpeta del proyecto.
- **En Hostinger:** hPanel → Administrador de archivos → carpeta del sitio → `.env` → Editar.

## 5. Prueba

Entra al panel → **Consultas** → **Enviar mensaje de prueba**. Te debe llegar un mensaje a Telegram.

## Si algo falla

- **"chat not found"**: no tocaste **Iniciar** en tu bot (paso 2), o el número de chat está mal.
- **"Unauthorized"**: el token está mal copiado. Vuelve a copiarlo de BotFather.
- **Si el token se filtró** (por ejemplo, lo mandaste por error a alguien): en BotFather escribe
  `/revoke`, elige tu bot, y pon el token nuevo en `.env`.

Las consultas **siempre** quedan guardadas en el panel, aunque Telegram falle.

> **Privacidad:** el aviso pasa por Telegram (nombre, teléfono y pregunta del cliente). Menciónale al
> abogado que revise la Política de privacidad que usas Telegram para avisar al personal.

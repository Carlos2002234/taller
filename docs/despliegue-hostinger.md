# Publicar la tienda en Hostinger — guía paso a paso

Esta guía es para hacerla tú mismo, sin conocimientos técnicos. Síguela en orden y marca cada casilla.
Calcula unas **2 a 3 horas** la primera vez. Si algo no coincide con lo que ves en pantalla
(Hostinger cambia sus menús de vez en cuando), busca el nombre parecido o pregúntame.

En los ejemplos uso:
- `tudominio.com` → cámbialo por tu dominio real.
- `u123456789` → tu usuario de Hostinger (lo verás en hPanel; empieza con "u" y números).

> ⚠️ Nunca compartas por WhatsApp, correo o capturas: la contraseña de la base de datos, el archivo `.env`
> ni tu contraseña del panel. Guárdalas en un lugar seguro (por ejemplo, un gestor de contraseñas).

---

## Paso 0 — Antes de contratar

Al elegir el plan de **hosting web** de Hostinger, revisa que incluya:

- [ ] **PHP 8.2 o mayor** y **bases de datos MySQL** (todos los planes de hosting web lo traen).
- [ ] **Acceso SSH** — muy recomendado: hace la instalación y las actualizaciones mucho más fáciles.
      Si tu plan no lo tiene, usa el *Método B* del Paso 3.
- [ ] **Cron Jobs** (tareas programadas).
- [ ] **Certificado SSL gratis** (el candado de https).
- [ ] **Copias de seguridad**: semanales o diarias según el plan. La tienda además hace su propio respaldo diario de la base de datos.

Para menos de 50 productos, cualquier plan con lo anterior alcanza. El dominio puedes comprarlo en el mismo Hostinger.

---

## Paso 1 — Dominio y SSL

1. [ ] Compra o conecta tu dominio en hPanel (**Dominios**).
2. [ ] En **Sitios web**, crea el sitio para ese dominio (si te pregunta, elige *sitio vacío* / *subir mis archivos*, **no** WordPress ni el creador de sitios).
3. [ ] En **Seguridad → SSL**, verifica que el certificado esté **activo** para `tudominio.com`. Si no, presiona *Instalar SSL* (es gratis). Puede tardar unos minutos.

---

## Paso 2 — Crear la base de datos

1. [ ] hPanel → **Bases de datos → Administración** (o *Bases de datos MySQL*).
2. [ ] Crea una base nueva:
   - Nombre: `taller` (Hostinger le agregará tu prefijo, quedará como `u123456789_taller`)
   - Usuario: `taller` (quedará `u123456789_taller`)
   - Contraseña: usa el botón para **generar una contraseña fuerte** y **guárdala**.
3. [ ] Anota exactamente los tres datos (nombre de la base, usuario y contraseña).
4. [ ] Junto a la base, abre **phpMyAdmin**.
5. [ ] En phpMyAdmin, a la izquierda, haz clic en tu base. Luego pestaña **Importar**:
   - [ ] Elige el archivo `database/schema.sql` (de tu computadora) → **Importar**. Debe decir que se ejecutó correctamente.
   - [ ] Repite con `database/seed.sql`.
   - A la izquierda deben aparecer **13 tablas**.

> Los archivos `schema.sql` y `seed.sql` los tienes en tu computadora dentro de la carpeta del proyecto,
> o puedes descargarlos de GitHub (abres el archivo → botón de descarga).

---

## Paso 3 — Subir los archivos

La idea: el proyecto queda en una carpeta **privada** y solo la carpeta `public` se ve en internet.

### Método A — con SSH (recomendado)

1. [ ] hPanel → **Avanzado → Acceso SSH** → activa el acceso y anota: IP, puerto (normalmente `65002`) y usuario.
2. [ ] En tu computadora abre **PowerShell** y conéctate (cambia los datos por los tuyos):
   ```
   ssh -p 65002 u123456789@TU_IP
   ```
   Escribe tu contraseña de hosting (no se ve mientras escribes; es normal).
3. [ ] Copia y pega estos comandos uno por uno:
   ```bash
   cd ~/domains/tudominio.com
   git clone https://github.com/Carlos2002234/taller.git taller
   ```
   Si tu repositorio de GitHub es **privado**, te pedirá usuario y contraseña: usa tu usuario de GitHub y,
   como contraseña, un **token** (GitHub → Settings → Developer settings → Personal access tokens →
   *Fine-grained token* con permiso de solo lectura al repositorio `taller`).
4. [ ] Haz que la carpeta pública del sitio sea `taller/public`:
   ```bash
   mv public_html public_html_original
   ln -s taller/public public_html
   ls -l
   ```
   Debe aparecer `public_html -> taller/public`.

**Tu carpeta del proyecto es:** `/home/u123456789/domains/tudominio.com/taller`
(escribe `pwd` dentro de `taller` para ver la ruta exacta y anótala: la usarás en los pasos 6 y 8).

### Método B — sin SSH (Administrador de archivos)

1. [ ] En GitHub, en tu repositorio: botón verde **Code → Download ZIP**.
2. [ ] hPanel → **Archivos → Administrador de archivos** → entra a `domains/tudominio.com`.
3. [ ] Sube el ZIP ahí y extráelo (clic derecho → *Extraer*). Se crea una carpeta como `taller-main`.
4. [ ] Entra a `taller-main`, selecciona **todo** (activa *mostrar archivos ocultos* para ver `.htaccess` y `.env.example`)
      y **muévelo** a `domains/tudominio.com` (un nivel arriba).
5. [ ] Abre `public_html` y borra lo que haya (por ejemplo `default.php`).
6. [ ] Abre la carpeta `public`, selecciona **todo** su contenido (incluido `.htaccess`) y **muévelo** a `public_html`.
7. [ ] Borra las carpetas vacías `public` y `taller-main`, y el ZIP.

**Tu carpeta del proyecto es:** `/home/u123456789/domains/tudominio.com`
(adentro debes ver `app`, `storage`, `database`… junto a `public_html`).

> En cualquiera de los dos métodos, **nunca** pongas las carpetas `app`, `storage` o el archivo `.env`
> dentro de `public_html`. (Si pasara por error, el sitio las bloquea, pero no es la forma correcta.)

---

## Paso 4 — Configurar `.env`

1. [ ] En el Administrador de archivos, entra a **tu carpeta del proyecto** (activa *mostrar archivos ocultos*).
2. [ ] Copia `.env.example` y renombra la copia a `.env`.
3. [ ] Ábrelo con el editor y deja así (con tus datos del Paso 2):
   ```
   APP_ENV=produccion
   APP_DEBUG=false
   APP_URL=https://tudominio.com
   APP_TIMEZONE=America/Costa_Rica

   DB_HOST=localhost
   DB_PORT=3306
   DB_NAME=u123456789_taller
   DB_USER=u123456789_taller
   DB_PASS=la-contraseña-que-guardaste

   SESSION_NAME=rivera_sesion
   ```
   - `APP_URL` con **https** y **sin** barra al final.
4. [ ] Guarda.

---

## Paso 5 — Configurar PHP

hPanel → **Avanzado → Configuración de PHP**:

1. [ ] **Versión de PHP:** 8.2 o mayor (8.3 está bien).
2. [ ] **Extensiones:** que estén activas `pdo_mysql`, `mbstring`, `gd`, `fileinfo` y `exif` (normalmente ya lo están).
3. [ ] **Opciones de PHP:**
   - `upload_max_filesize` = **10M** (fotos de productos de hasta 10 MB)
   - `post_max_size` = **64M** (varias fotos a la vez)
   - `memory_limit` = **256M** (convertir fotos de cámara)
   - `max_execution_time` = **180**
4. [ ] Guarda.

---

## Paso 6 — Crear tu usuario de dueño

### Con SSH (Método A)
```bash
cd ~/domains/tudominio.com/taller
php -v                     # debe decir 8.2 o mayor
php database/crear-admin.php
```
Escribe tu nombre, tu correo, rol `duenio` y una contraseña de **12 caracteres o más**.

> Si `php -v` muestra una versión menor a 8.2, usa la ruta completa, por ejemplo
> `/opt/alt/php82/usr/bin/php database/crear-admin.php` (pregúntame si no la encuentras).

### Sin SSH (Método B)
1. En tu computadora, abre la **Terminal de Laragon** y escribe (con tu contraseña real entre comillas):
   ```
   php -r "echo password_hash('TuContraseñaSegura123', PASSWORD_DEFAULT), PHP_EOL;"
   ```
   Copia el resultado (empieza con `$2y$`).
2. En phpMyAdmin → tu base → pestaña **SQL**, pega (con tus datos) y presiona **Continuar**:
   ```sql
   INSERT INTO users (nombre, email, password_hash, rol)
   VALUES ('Carlos Rivera', 'tucorreo@ejemplo.com', 'PEGA_AQUI_EL_RESULTADO', 'duenio');
   ```

---

## Paso 7 — Primera revisión

1. [ ] Abre `https://tudominio.com` → debe verse la tienda con su diseño.
2. [ ] Abre `http://tudominio.com` (sin la "s") → debe pasar solo a `https://`.
3. [ ] Abre `https://tudominio.com/admin` → ingresa con tu correo y contraseña.
4. [ ] Abre `https://tudominio.com/.env` → debe decir **prohibido** (403). Si muestra texto, **detente** y avísame.

---

## Paso 8 — Tareas automáticas (Cron Jobs)

hPanel → **Avanzado → Cron Jobs** → crea dos tareas tipo *Personalizado* (usa **tu carpeta del proyecto** del Paso 3):

| Tarea | Comando | Frecuencia |
|---|---|---|
| Cancelar pedidos sin pagar vencidos y devolver el stock | `php /home/u123456789/domains/tudominio.com/taller/app/tareas/liberar-pedidos-vencidos.php` | Cada hora (minuto 0) |
| Respaldo diario de la base de datos | `php /home/u123456789/domains/tudominio.com/taller/app/tareas/respaldar-base.php` | Todos los días, 3:00 a. m. |

- [ ] Al día siguiente, revisa en el Administrador de archivos que exista `storage/respaldos/` con un archivo `.sql.gz`.

---

## Paso 9 — Prueba completa antes de abrir

Con la tienda publicada **pero todavía sin anunciarla**:

1. [ ] Desde tu computadora, en la Terminal de Laragon (o *Git Bash*), dentro de la carpeta del proyecto:
   ```bash
   BASE=https://tudominio.com ADMIN_EMAIL=tucorreo@ejemplo.com ADMIN_CLAVE='tu-contraseña' bash tests/flujo-completo.sh
   ```
   Debe terminar con **"0 fallidas"**. Si algo falla, copia el resultado y envíamelo.
2. [ ] Haz tú una compra de prueba desde tu celular, sube una captura como comprobante y apruébala en el panel.
3. [ ] **Limpia las pruebas** para que tu primer pedido real sea el `RGA-…-0001`. En phpMyAdmin → SQL:
   ```sql
   DELETE FROM orders;
   DELETE FROM order_sequences;
   DELETE FROM intentos;
   ```
   Y en el Administrador de archivos borra el contenido de `storage/comprobantes/` (deja el archivo `.gitkeep`).
   > ⚠️ Esto borra **todos** los pedidos. Hazlo **solo antes de abrir**, nunca con clientes reales.

---

## Paso 10 — Cargar tus datos reales

En `https://tudominio.com/admin`:

1. [ ] **Configuración:** titular del SINPE, banco, IBAN, titular y cédula; enlace de Google Maps; redes sociales; revisa el banner.
2. [ ] **Configuración → Zonas de envío:** tus tarifas reales (las actuales son de ejemplo).
3. [ ] **Servicios:** revisa y corrige los 6 servicios cargados.
4. [ ] **Categorías:** ajusta nombres y agrega fotos.
5. [ ] **Productos:** crea tus productos reales con fotos, precio y stock.
6. [ ] **Borra los 6 productos de ejemplo** (dicen "Producto de ejemplo" en la descripción).
7. [ ] **Usuarios:** si un empleado va a atender pedidos, créale su usuario (rol *Empleado*).
8. [ ] Revisa la lista "Antes de publicar" de `docs/seguridad.md`.

---

## Paso 11 — Después de abrir

1. [ ] **Google Search Console** (search.google.com/search-console): agrega `tudominio.com`, verifícalo
   (la opción por DNS funciona bien con dominios de Hostinger) y en **Sitemaps** envía `https://tudominio.com/sitemap.xml`.
2. [ ] **Perfil de negocio en Google (Google Maps):** agrega el enlace de tu tienda como sitio web.
3. [ ] Pon el enlace de la tienda en tu Facebook, Instagram y en el estado de WhatsApp.

---

## Respaldos

- **Automáticos:** Hostinger (según tu plan) + el respaldo diario de la base en `storage/respaldos/` (guarda los últimos 14).
- **Recomendado una vez por semana:** en el Administrador de archivos, comprime y **descarga a tu computadora**:
  - `storage/respaldos/` (base de datos)
  - `storage/comprobantes/` (comprobantes de pago)
  - `public/uploads/` o `public_html/uploads/` (fotos de productos)

**Restaurar la base de datos:** descomprime el `.sql.gz` (por ejemplo con 7-Zip) y, en phpMyAdmin → tu base → **Importar**, sube el `.sql`.
Esto reemplaza los datos actuales por los del respaldo.

---

## Actualizar la tienda (cambios futuros)

- **Método A (SSH):**
  ```bash
  cd ~/domains/tudominio.com/taller
  git pull
  ```
- **Método B:** vuelve a subir solo los archivos que cambiaron (te diré cuáles).

Si un cambio necesita modificar la base de datos, vendrá con instrucciones aparte. **Haz un respaldo antes de actualizar.**

---

## Problemas frecuentes

| Lo que ves | Qué revisar |
|---|---|
| "Error 500" o página en blanco | `storage/logs/php-errores.log` en tu carpeta del proyecto. Revisa que exista `.env` y la versión de PHP (8.2+). |
| La página sale sin diseño (todo en texto) | `APP_URL` en `.env` debe ser exactamente `https://tudominio.com`, sin barra final. |
| "No se pudo conectar a la base de datos" | `DB_HOST=localhost` y los tres datos del Paso 2 (con el prefijo `u123456789_`). |
| Las fotos no suben o salen muy pesadas | Opciones de PHP del Paso 5. |
| Todo dice "prohibido" (403) | La carpeta pública: en el Método A, `public_html` debe apuntar a `taller/public`; en el Método B, el contenido de `public` debe estar dentro de `public_html`. |
| Los pedidos sin pagar no se cancelan | El Cron Job del Paso 8 (la ruta debe ser la de tu carpeta del proyecto). |

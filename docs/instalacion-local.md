# Instalación local con Laragon (Windows)

Guía para correr la tienda en tu computadora antes de subirla a Hostinger.

## 1. Instalar Laragon

1. Descarga **Laragon Full** desde https://laragon.org/download/ e instálalo con las opciones por defecto.
2. Abre Laragon y presiona **Iniciar todo** (Start All). Deben quedar activos *Apache* y *MySQL*.
3. Verifica la versión de PHP: clic derecho en Laragon → **PHP** → versión. Debe ser **8.2 o mayor**.
   Si es menor, en Laragon: *PHP → Versión → Obtener más versiones* y elige 8.2 o 8.3.

## 2. Copiar el proyecto

Abre la **Terminal** de Laragon (botón *Terminal*) y ejecuta:

```bash
cd C:\laragon\www
git clone https://github.com/Carlos2002234/taller.git
```

Luego, en Laragon: clic derecho → **Recargar** (Reload). Laragon crea automáticamente el sitio
**http://taller.test** apuntando a la carpeta `public` del proyecto.

> Si `http://taller.test` muestra la lista de carpetas en vez de la página, clic derecho en Laragon →
> *Apache → sites-enabled → auto.taller.test.conf* y verifica que `DocumentRoot` termine en `taller/public`.

## 3. Crear la base de datos

1. En Laragon presiona **Base de datos** (abre HeidiSQL) y conéctate (usuario `root`, sin contraseña).
2. Clic derecho en la conexión → *Crear nuevo → Base de datos*:
   - Nombre: `taller`
   - Collation: `utf8mb4_unicode_ci`
3. Selecciona la base `taller` → *Archivo → Ejecutar archivo SQL…* → elige `database/schema.sql`.
4. Repite con `database/seed.sql`.

## 4. Configurar `.env`

En la carpeta del proyecto copia `.env.example` como `.env`. En local, los valores por defecto ya
funcionan con Laragon. El archivo `.env` nunca se sube a GitHub.

## 5. Crear tu usuario administrador

En la Terminal de Laragon:

```bash
cd C:\laragon\www\taller
php database/crear-admin.php
```

Escribe tu nombre, correo, rol `duenio` y una contraseña de al menos 10 caracteres.

## 6. Verificar

Abre **http://taller.test/instalacion**. La página de revisión debe mostrar todo en verde:

- PHP 8.2 o mayor
- Extensiones PDO MySQL y GD con WebP
- Carpetas de fotos y comprobantes escribibles
- Base de datos conectada (12 tablas)

Si algo sale en rojo, revisa el archivo `storage/logs/php-errores.log`.

Las páginas `/instalacion` y `/estilos` (guía de estilos) solo existen con `APP_DEBUG=true`;
en el sitio publicado responden 404.

## 7. Cambiar el diseño (solo si vas a editar estilos)

El servidor **no** necesita Node.js: el CSS ya compilado (`public/css/app.css`) se sube a GitHub.
Solo para modificar el diseño, instala Node.js LTS y en la carpeta del proyecto:

```bash
npm install          # una sola vez
npm run css:watch    # recompila el CSS mientras editas
npm run css          # versión final minificada (antes de hacer commit)
npm run imagenes     # regenera logo y fotos optimizadas desde /assets
```

# Plan aprobado — Módulo 1: estructura, base de datos y configuración

Aprobado por el dueño el 2026-10-22. Sin páginas de la tienda todavía.

## Archivos a crear

```
.env.example                   Plantilla de configuración (sin datos reales)
README.md                      Qué es el proyecto y cómo correrlo
docs/instalacion-local.md      Paso a paso con Laragon

public/index.php               Página temporal que verifica la conexión a la BD
public/.htaccess               HTTPS forzado, todo pasa por index.php, bloquea archivos sensibles

app/config/config.php          Carga la configuración desde .env
app/core/env.php               Lector de .env (sin Composer)
app/core/database.php          Conexión PDO segura (consultas preparadas, utf8mb4)
app/core/helpers.php           Escapar salida (XSS), formato ₡, URLs
app/core/session.php           Sesiones seguras (cookies httponly, secure, samesite)
app/core/csrf.php              Tokens CSRF para formularios

database/schema.sql            Todas las tablas
database/seed.sql              Datos iniciales

uploads/.htaccess              Bloquea ejecución de PHP en archivos subidos
uploads/productos/.gitkeep
storage/comprobantes/.gitkeep  Comprobantes de pago, fuera de la web pública
storage/logs/.gitkeep
```

## Base de datos (`schema.sql`)

Las 9 tablas de `REQUISITOS.md` §8, más:

- `products`: `marca`, `precio_anterior` (nullable) para filtros y ofertas.
- `orders`: `expira_en` (creado + 48 h, configurable), `zona_envio_id`, `costo_envio`, `subtotal`, `utm_source`, `utm_medium`, `utm_campaign`.
- `users.rol`: `duenio` | `empleado` (solo `duenio` puede aprobar pagos).
- `shipping_zones`: id, nombre, tarifa, activa, orden.
- `login_attempts`: control de intentos fallidos de login.

## Datos iniciales (`seed.sql`)

- `settings`: teléfono/WhatsApp/SINPE 8702-6760, dirección, horario, horas de expiración = 48. IBAN y titulares = "PENDIENTE".
- Categorías y productos de ejemplo; zonas de envío de ejemplo (tarifas a confirmar por el dueño).
- Sin usuario admin con contraseña fija: se crea durante la instalación.

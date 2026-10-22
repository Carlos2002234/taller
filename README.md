# Rivera Grupo Automotriz — Tienda web

Tienda en línea del taller **Rivera Grupo Automotriz** (Costa Rica): venta de accesorios y repuestos
con pago por **SINPE Móvil o transferencia** y **aprobación manual** de cada pedido. Sin servicios de
pago externos ni mensualidades.

## Tecnología

- PHP 8.2+ (sin dependencias externas) y MySQL/MariaDB
- Tailwind CSS compilado + JavaScript ligero
- Pensado para hosting compartido de Hostinger

## Estructura

```
app/        Lógica (configuración, conexión, funciones base)
public/     Carpeta pública del sitio (raíz web)
  uploads/  Fotos de productos
storage/    Archivos privados: comprobantes de pago y logs
database/   schema.sql, seed.sql y script para crear el admin
assets/     Logo, fotos del taller y prototipo de diseño
docs/       Guías y planes
```

## Empezar

Sigue [docs/instalacion-local.md](docs/instalacion-local.md).

## Reglas clave

- El pago **nunca** se aprueba automáticamente: el dueño lo valida a mano.
- Las credenciales van en `.env`, que no se sube al repositorio.
- Consultas SQL solo con PDO preparado; toda salida se escapa con `e()`.

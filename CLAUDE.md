# Tienda web — Taller mecánico y accesorios

## Visión
E-commerce propio y sin mensualidades para un taller mecánico que vende accesorios, con pagos solo por SINPE Móvil/transferencia y aprobación manual de cada pedido.

## Stack
- PHP 8.2 + MySQL (Hostinger), Tailwind CSS, JS ligero
- Imágenes en `/uploads`, assets del negocio en `/assets`

## Reglas importantes
- **LEE `REQUISITOS.md` al inicio de cada sesión.**
- Si tienes dudas, **PREGUNTA** al dueño antes de programar. No asumas.
- Explica tu plan antes de codear y espera confirmación.
- Un módulo por sesión; commit de git al terminar cada módulo.
- No agregar funciones fuera del MVP sin preguntar.
- Nunca guardar credenciales en el código: usar `.env`.
- **Nunca aprobar un pago automáticamente.**
- No leer carpetas enteras; leer solo los archivos necesarios.
- Código y comentarios claros; textos de la interfaz en español.

## Convenciones
- Archivos y carpetas en minúsculas con guiones.
- Consultas SQL solo con PDO preparado.
- Estructura: `/public` (web), `/app` (lógica), `/admin`, `/uploads`, `/assets`, `/database`, `/docs`

## Estado actual del proyecto
- **Fase:** MVP
- **Último módulo completado:** 0 — análisis de assets, preguntas al dueño, stack y plan aprobados (2026-10-22)
- **Próximo módulo:** 1 — estructura, base de datos y configuración. Plan aprobado en `docs/plan-modulo-1.md`.
- **Repositorio:** https://github.com/Carlos2002234/taller (rama `main`). Cada commit se sube con `git push`.

### Datos del negocio (confirmados)
- **Rivera Grupo Automotriz** — Costa Rica, colones (₡).
- Teléfono, WhatsApp y SINPE Móvil: **8702-6760**.
- Dirección: 150 m oeste de la plaza de deportes de La Puebla, antes de llegar a los semáforos.
- Horario: lunes a sábado, 8:00 a.m. – 5:00 p.m.
- Servicios del taller: sin precios en la web; botón "Cotizar por WhatsApp".

### Pagos y entregas (confirmados)
- El cliente sube el comprobante SOLO en la web (página de seguimiento).
- Plazo de pago: 48 h; luego el pedido se cancela y se libera el stock.
- Entrega: retiro en taller, o envío solo en la GAM con mensajero propio; plazo 48 h.
- Costo de envío: **tarifas por zona** editables desde el admin (el cliente ve el total exacto antes de pagar).

### Stack final (aprobado)
- PHP 8.2 sin Composer ni dependencias externas, MySQL con PDO, Tailwind compilado localmente (Node solo en la PC), JS ligero.
- Desarrollo local con **Laragon**. Hostinger aún no contratado; el stack funciona en cualquier plan compartido.
- Comprobantes en `/storage/comprobantes` (fuera de la web pública).

### Admin
- Roles: **dueño** (todo, único que aprueba pagos) y **empleado** (ve pedidos, marca en preparación/entregado; NUNCA aprueba pagos).

### Identidad visual
- Logo oficial: `assets/logo.jpg` (engrane + R). Header: símbolo + texto "RIVERA / Grupo Automotriz".
- Paleta (extraída del logo): Rojo Rivera `#D11A21` (hover `#A8141A`) · Negro `#0B0B0B` · Carbón `#1A1A1A` · Blanco `#FFFFFF` · Gris apoyo `#F2F2F2`.
- Referencia: `assets/referencia/prototipo.png` — fondos oscuros, acentos rojos, títulos en dos líneas (blanco + rojo), tarjetas de categoría/producto, catálogo con filtros sobre fondo claro, barra inferior en móvil.
- Fotos: `assets/fotos/rivera-XX.jpg` (fachada 14/18, recepción 23, rótulo 01, auto cubierto 07).
- Del prototipo SÍ: filtros categoría + marca + rango de precio; etiquetas "Oferta" (`precio_anterior`) y "Destacado"; banner promocional editable; barra inferior móvil (Inicio, Tienda, Carrito, Mi pedido); franja de beneficios.
- Del prototipo NO (MVP): reseñas/estrellas, cuentas de cliente (checkout sin registro), newsletter (Fase 3). Precios siempre en ₡.

### Pendiente (el dueño lo enviará; no bloquea el Módulo 1)
- Titular del SINPE; datos bancarios (banco, IBAN, titular, cédula). En `seed.sql` quedan como "PENDIENTE".
- ¿Precios con IVA incluido?
- Provincia/cantón de La Puebla y enlace de Google Maps/Waze.
- Lista completa de servicios del taller y redes sociales.
- Zonas de envío y sus tarifas.
- Correo para avisos de pedidos; dominio; plan de Hostinger.

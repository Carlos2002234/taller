# REQUISITOS — Tienda web del taller mecánico

> Claude Code: lee este archivo completo al inicio de cada sesión, junto con `CLAUDE.md`.
>
> **REGLA #1:** si tienes CUALQUIER duda, pregunta al dueño antes de programar. No asumas.

## 1. Contexto del negocio
- Taller de mecánica automotriz que también vende accesorios para autos.
- Objetivo: proyectar un negocio más profesional, generar más ventas y tener una base para anuncios (Facebook/Instagram/Google).
- País asumido: Costa Rica (pagos por SINPE Móvil). Moneda: colones (₡). **Confirmar con el dueño.**
- Catálogo inicial: menos de 50 productos.

## 2. Restricciones (obligatorias)
- SIN servicios externos de pago (nada de Shopify, Stripe, Wix, etc.).
- Hosting: Hostinger. Confirmar con el dueño si es hosting compartido (PHP + MySQL) o VPS/Node antes de elegir stack final.
- Pagos: SOLO SINPE Móvil o transferencia bancaria. NO tarjetas.
- Todo se almacena en la base de datos y carpetas propias del sitio.
- Sitio responsive (la mayoría de clientes entrará desde el celular) y en español.

## 3. Stack propuesto (compatible con Hostinger compartido)
- Backend: PHP 8.2+ con PDO (consultas preparadas).
- Base de datos: MySQL (incluida en Hostinger).
- Frontend: HTML + Tailwind CSS compilado + JS ligero (sin frameworks pesados).
- Imágenes: carpeta `/uploads` en el servidor, redimensionadas y optimizadas (WebP).
- Si el plan de Hostinger soporta Node.js y el dueño lo prefiere, proponer alternativa ANTES de empezar.

## 4. Identidad visual
- Colores predominantes: rojo, blanco y negro (los del logo del taller).
- Paleta final (extraída de `assets/logo.jpg` en el Módulo 0):
  - Rojo: `#D11A21` (extraído del logo; hover `#A8141A`)
  - Negro: `#0B0B0B`
  - Blanco: `#FFFFFF`
  - Gris apoyo: `#F2F2F2`
- Usar el logo, las fotos del taller y la imagen de referencia de diseño que están en `/assets`.
- Estilo: moderno, limpio, profesional, con aire "automotriz" (bloques negros, acentos rojos, tipografía fuerte).
- ANTES de diseñar: analizar las imágenes de `/assets` y resumir al dueño qué entendiste del estilo; esperar confirmación.

## 5. Páginas públicas
1. **Inicio:** hero con logo, llamada a la acción, servicios destacados, productos destacados, ubicación/horario, botón de WhatsApp.
2. **Tienda:** catálogo con categorías, búsqueda y orden por precio.
3. **Producto:** galería, descripción, precio, stock, botón "Agregar al carrito".
4. **Carrito.**
5. **Checkout:** nombre, teléfono, correo (opcional), tipo de entrega (retiro en taller / envío), notas. Sin campos de tarjeta.
6. **Confirmación de pedido:** muestra NÚMERO DE PEDIDO y las instrucciones de pago (SINPE Móvil/transferencia con el monto exacto) y cómo enviar el comprobante.
7. **Seguimiento de pedido:** el cliente ingresa número de pedido + teléfono, ve el estado y sube el comprobante.
8. **Servicios del taller** (mecánica), con botón de contacto.
9. **Contacto y ubicación** (mapa, teléfono, horario).

## 6. Flujo de pedido y pagos (CRÍTICO)

**Estados del pedido:**

```
pendiente_pago → comprobante_enviado → pagado → en_preparacion → entregado
```
(también `cancelado` y `rechazado`)

**Reglas:**
- Al crear el pedido se genera un número único legible, ej. `TAL-2026-0001`.
- El pedido nace en `pendiente_pago`. NO se procesa, prepara ni entrega hasta que el dueño lo marque como `pagado` desde el admin.
- El SINPE Móvil puede falsificarse: el dueño debe verificar el comprobante Y confirmar el ingreso real en su cuenta antes de aprobar. **El sistema nunca aprueba pagos automáticamente.**
- El cliente sube el comprobante (JPG/PNG/PDF, máx. 5 MB) desde la página de seguimiento.
- El stock se reserva al crear el pedido y se libera si pasa a `cancelado` / `rechazado` o si no se paga en 48 h (configurable).
- Datos de pago (número SINPE, nombre del titular, cuenta bancaria) editables desde el admin, no hardcodeados.

## 7. Panel de administración (`/admin`)
- Login seguro para el dueño (y opcionalmente empleados).
- **Productos:** crear/editar/borrar/ocultar; nombre, descripción, precio, stock, categoría, hasta 5 fotos, destacado sí/no.
- **Categorías:** crear/editar.
- **Pedidos:** lista con filtro por estado, detalle, ver comprobante, botones Aprobar pago / Rechazar / Cambiar estado, notas internas.
- **Configuración:** datos de pago, teléfono/WhatsApp, horario, textos del banner.
- **Dashboard simple:** pedidos pendientes, ventas del mes.
- Debe ser usable desde el celular.

## 8. Base de datos (mínimo)
| Tabla | Campos |
|---|---|
| `users` (admin) | id, nombre, email, password_hash, rol, creado_en |
| `categories` | id, nombre, slug, activa |
| `products` | id, category_id, nombre, slug, descripcion, precio, stock, destacado, activo, creado_en |
| `product_images` | id, product_id, ruta, orden |
| `orders` | id, numero_pedido (único), nombre_cliente, telefono, email, tipo_entrega, direccion, notas, total, estado, creado_en, actualizado_en |
| `order_items` | id, order_id, product_id, nombre_snapshot, precio_snapshot, cantidad |
| `payment_proofs` | id, order_id, ruta_archivo, subido_en, revisado_por, estado_revision |
| `settings` | clave, valor |
| `order_status_log` | id, order_id, estado_anterior, estado_nuevo, usuario, fecha |

## 9. Seguridad (obligatorio)
- Contraseñas con `password_hash`. Sesiones seguras, protección CSRF en todos los formularios.
- Consultas preparadas siempre (cero SQL concatenado). Escapar toda salida (XSS).
- Subidas: validar tipo real (MIME) y tamaño, renombrar archivos, bloquear ejecución de PHP en `/uploads` vía `.htaccess`.
- Límite de intentos de login. HTTPS forzado.
- Credenciales en `.env` fuera de la carpeta pública, nunca en el repositorio.
- Los comprobantes solo los ve el admin (no URL pública adivinable).

## 10. Automatización de WhatsApp (FASE 2 — NO implementar ahora)
- Meta futura: al crear un pedido, enviar mensaje con el número de pedido y pedir el comprobante de SINPE Móvil.
- Ahora: solo crear una función vacía `notificarPedido($pedido)` llamada al crear el pedido, para conectarla después sin reescribir nada.
- El dueño decidirá más adelante entre API oficial de WhatsApp Business (con costo) o enlace gratis con mensaje prellenado.

## 11. Anuncios y marketing (FASE 3)
- SEO básico, metaetiquetas Open Graph, `sitemap.xml`, velocidad optimizada.
- Espacio para Meta Pixel / Google Analytics (sin activarlos hasta que el dueño dé los IDs).
- Soporte de parámetros UTM en los pedidos para medir qué anuncio vende.

## 12. Despliegue en Hostinger
- Entregar guía paso a paso: crear base MySQL en hPanel, importar `schema.sql`, subir archivos, configurar `.env`, dominio y SSL.
- Incluir script `schema.sql` y datos de ejemplo (`seed.sql`).
- Guía de respaldo (base de datos + carpeta uploads).

## 13. Hoja de ruta por módulos (un módulo por sesión)
0. Análisis de `/assets`, preguntas al dueño, confirmación de estilo y stack.
1. Estructura del proyecto, base de datos (`schema.sql`), configuración.
2. Diseño base: layout, header, footer, paleta, componentes.
3. Tienda pública: catálogo, producto, carrito.
4. Checkout y creación de pedido con número único.
5. Seguimiento de pedido y subida de comprobante.
6. Admin: login, productos, categorías.
7. Admin: pedidos y aprobación de pagos.
8. Páginas de Inicio, Servicios y Contacto.
9. Seguridad, pruebas y optimización.
10. Guía y despliegue en Hostinger.
11. (Fase 2) WhatsApp. (Fase 3) Marketing.

## 14. Criterios de aceptación del MVP
- [ ] Un cliente puede navegar, agregar al carrito y crear un pedido desde el celular.
- [ ] Recibe un número de pedido y las instrucciones de SINPE Móvil/transferencia.
- [ ] Puede subir el comprobante.
- [ ] Ningún pedido avanza de `pendiente_pago` sin aprobación manual del admin.
- [ ] El dueño puede gestionar productos y pedidos sin tocar código.
- [ ] Diseño en rojo, blanco y negro, coherente con el logo.
- [ ] Sin dependencias de servicios de pago externos.

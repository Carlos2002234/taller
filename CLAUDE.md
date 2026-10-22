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
- **Fase:** MVP — Módulo 0 (análisis y preguntas) — EN CURSO
- **Último módulo completado:** ninguno
- **Próximo módulo:** 0 (terminar preguntas grupos 2–5), luego 1
- **Repositorio:** https://github.com/Carlos2002234/taller (rama `main`)

### Confirmado por el dueño
- Negocio: **Rivera Grupo Automotriz** — Costa Rica, colones (₡).
- Assets: `assets/logo.jpg` (símbolo engrane + R), `assets/fotos/rivera-XX.jpg` (10 fotos del taller), `assets/referencia/prototipo.png` (diseño de referencia).

### Paleta (extraída de `assets/logo.jpg`)
- Rojo Rivera `#D11A21` (hover `#A8141A`) · Negro `#0B0B0B` · Carbón `#1A1A1A` · Blanco `#FFFFFF` · Gris apoyo `#F2F2F2`

- Teléfono, WhatsApp y SINPE Móvil: **8702-6760**.
- Comprobante: el cliente lo sube SOLO en la web (página de seguimiento).
- Plazo de pago: 48 h; luego se cancela y se libera stock.
- Dirección: 150 m oeste de la plaza de deportes de La Puebla, antes de llegar a los semáforos.
- Horario: lunes a sábado, 8:00 a.m. – 5:00 p.m.
- Servicios del taller: sin precios en la web; botón "Cotizar por WhatsApp".
- Entrega: retiro en taller o envío solo en la GAM con mensajero propio; plazo 48 h. Costo de envío variable (forma de cálculo por definir).
- Hostinger: aún no contratado (se configura después). No hay PHP/MySQL instalado en la PC local.
- Logo: `assets/logo.jpg` (engrane + R) es el oficial; en el header va el símbolo + texto "RIVERA / Grupo Automotriz".

### Decisiones sobre el prototipo (delegadas por el dueño)
- SÍ: filtros categoría + marca + rango de precio; etiquetas "Oferta" (campo `precio_anterior`) y "Destacado"; banner promocional editable; barra inferior móvil (Inicio, Tienda, Carrito, Mi pedido); franja de beneficios.
- NO (MVP): reseñas/estrellas, cuentas de cliente (checkout sin registro), newsletter (Fase 3).
- Precios siempre en ₡ (el prototipo muestra €, se corrige).

### Pendiente de confirmar
- Titular del SINPE; datos bancarios (IBAN, banco, titular) — el dueño los enviará.
- ¿Precios con IVA incluido?
- Provincia/cantón de La Puebla y enlace de Google Maps/Waze.
- Lista completa de servicios del taller (el dueño la enviará).
- Redes sociales (el dueño las enviará).
- Cómo se cobra el envío (tarifa por zona vs. por confirmar).
- Dominio, correo para avisos, usuarios del admin.

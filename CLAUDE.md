# Tienda web — Taller mecánico y accesorios

## Visión
E-commerce propio y sin mensualidades para un taller mecánico que vende accesorios, con pagos solo por SINPE Móvil/transferencia y aprobación manual de cada pedido.

## Stack
- PHP 8.2 + MySQL (Hostinger), Tailwind CSS, JS ligero
- Fotos de productos en `/public/uploads/productos`, comprobantes en `/storage/comprobantes` (privado), assets del negocio en `/assets`

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
- Estructura: `/public` (raíz web; incluye `/public/uploads`), `/app` (lógica), `/admin`, `/storage` (privado), `/assets`, `/database`, `/docs`
- Consultas en `app/modelos/*.php` (cargar con `require_once` en la página). Rutas con parámetro `{slug}` llegan en `$parametros`; `no_encontrado()` muestra el 404; rutas tipo `json` no usan layout.
- Rutas en `app/core/router.php` (`rutas()`); páginas en `app/paginas/*.php` (pueden cambiar `$meta`); componentes con `componente('nombre', [...])` desde `app/vistas/componentes/`.
- CSS: editar `resources/css/app.css` y compilar con `npm run css` (el compilado `public/css/app.css` SÍ se versiona). Las clases deben escribirse completas en el código (Tailwind no detecta nombres armados por partes).
- Íconos: `icono('nombre')` en `helpers.php`. Enlaces de WhatsApp: `whatsapp_url($mensaje)`.
- `public/index.php` solo carga config y llama `despachar()`. Usar `db()`, `ajuste()`, `e()`, `colones()`, `url()`, `csrf_campo()` / `verificar_csrf()`.

## Estado actual del proyecto
- **Fase:** MVP completo (módulos 0–10). Siguiente: publicación por el dueño; luego Fase 2 (WhatsApp) y Fase 3 (marketing).
- **Último módulo completado:** 12 — reporte "¿Qué anuncio vende?" (2026-10-22). Probado: cifras exactas contra datos de prueba (incluye cancelado tras pagar y mes anterior), detección de origen, accesibilidad, 32/32 pruebas.
- **Próximo:** el MVP + extras están completos. Pendiente del dueño: publicar (guía), completar [COMPLETAR] legales con abogado. A pedido: Meta Pixel / Analytics (IDs + aviso de cookies + ajustar CSP) y API de WhatsApp (2B, con costo).
- Reportes (`/admin/reportes`, solo dueño, `app/modelos/reportes.php`): ventas por origen = misma regla que ventas_del_mes (fecha de aprobación, sin cancelados). Barras con clases `w-[N%]` en pasos de 5% (`clase_ancho_barra()`, sin estilos inline por la CSP) y color `bg-dato` (#2a78d6, validado con la guía dataviz; NO usar el rojo de marca para datos).
- Origen del cliente (`public/js/app.js`): UTM del enlace; si no hay, fbclid→facebook, gclid→google, o el sitio de referencia (instagram, facebook, google, tiktok…). Último origen gana; dura 30 días en localStorage.
- Legal: `/terminos` y `/privacidad` desde settings `terminos_texto` / `privacidad_texto` (editables en Configuración; `texto_legal_html()` escapa todo, "## " = título, "- " = viñeta, {negocio} {telefono} {direccion} {horario} {horas_pago} {envio_plazo}). Base redactada por Claude con [COMPLETAR] (razón social, IVA, devoluciones, garantía, conservación): el dueño completa y un abogado revisa. Checkout exige casilla `acepto` (Ley 8968).
- WhatsApp Fase 2 (decisión: opción gratuita 2A): botón "Avisar por WhatsApp" (cliente → dueño) en confirmación y Mi pedido. `notificarPedido()` sigue vacía para una futura API (2B).
- Carpeta pública: `index.php` define `PUBLICO` (funciona como `public` o `public_html`). Usar `PUBLICO`, nunca `RAIZ . '/public'`.
- Cada carpeta interna (app, database, docs, tests, resources, scripts, assets, storage) tiene `.htaccess` con `Require all denied`; la raíz bloquea archivos ocultos y internos.
- Respaldo: `app/tareas/respaldar-base.php` (cron diario) → `storage/respaldos/*.sql.gz`, conserva 14.
- Seguridad: encabezados (CSP estricta, HSTS, etc.) en `encabezados_seguridad()` del router; NO usar JS ni `style=` dentro del HTML (rompería la CSP). `APP_ENV=produccion` fuerza debug apagado. Ver `docs/seguridad.md`.
- Movimiento (sistema aprobado 2026-11-21, ver bloque "Movimiento" en `resources/css/app.css`): UNA curva `--curva` cubic-bezier(.22,1,.36,1) y tres duraciones `--dur-rapida` 300 ms (hover/foco), `--dur-media` 450 ms (estados, tarjetas), `--dur-lenta` 700 ms (entradas); en Tailwind `ease-suave`, `duration-rapida|media|lenta` y `transition` sin más ya usa curva + 300 ms. Entrada = opacidad + 14px + blur 6px; `.aparecer` una vez al 15%; `.escalonado` 70 ms, máx. 4. Solo opacity/transform/filter. Transforms en hover con utilidades: usar `motion-safe:hover:...`. Botones: base compartida por lista de selectores, NUNCA `@apply btn` (copiaba las reglas de @media reduce en medio del CSS). Bloque reduced-motion al FINAL del archivo.
- Portada del inicio: fachada `rivera-14` (960/1920) COMPLETA (16:9, sin recortar), SIN texto, sombras ni fundidos encima, con `.foto-entrar` y línea roja de 3px debajo (decisión del dueño 2026-11-08, RATIFICADA 2026-11-21: "el inicio no lo tenías que cambiar"; NO reemplazar la foto). Debajo, bloque racing sobre fondo oscuro liso (`.portada-racing`, sin foto): franjas rojas diagonales a la derecha del título (solo ≥1024px), etiqueta RIVERA inclinada, h1 Saira 900 itálica mayúsculas (`.titulo-racing`, clamp 3–7rem; última palabra de `banner_titulo` en contorno, `banner_titulo_rojo` en rojo + líneas de velocidad), botones `.btn-sesgo`, beneficios (componente `beneficios`, `tabindex=0`, relleno rojo con hover y foco de teclado) y cinta roja en bucle con las categorías reales (aria-hidden). Sus entradas esperan a que el bloque se vea (app.js le pone `visible`). Curva `--curva-corte` solo para franjas y relleno. Encabezado SIEMPRE en barra negra sólida, SIN franja superior de horario/WhatsApp (el dueño la quitó 2026-11-08). Beneficios numerados 01–04 sin íconos. Diseño animado general "elegante y sutil": Entrada escalonada; transiciones (2026-11-17): curva común `--curva-suave`, fundido entre páginas con `@view-transition` (el encabezado queda fijo), foto del inicio con `.foto-entrar`, sombra del encabezado al bajar (`.encabezado-sombra`, app.js), botones que se hunden al tocarlos y zoom de fotos 105%; secciones `.aparecer` y grillas `.escalonado` (IntersectionObserver en app.js; `js/precarga.js` sin defer pone la clase `js`); tarjetas `.tarjeta-interactiva`. Todo se apaga con prefers-reduced-motion. Sin estilos inline (CSP).
- Video del taller (decisión del dueño: "que no se vea lenta"): original vertical de TikTok en `assets/video/taller-original.mp4` → `npm run video` (ffmpeg-static) genera `public/video/taller.mp4` (sin audio, 540x960, ~2,4 MB) + `taller-portada.webp`. Componente `video-taller` ("Así trabajamos") en Inicio y Servicios: `preload="none"`, app.js lo reproduce solo en pantalla y lo pausa al salir; con reduced-motion o ahorro de datos no arranca solo; botón ▶/❚❚ (WCAG 2.2.2).
- Testimonios (componente `testimonios`, en Inicio; rediseño aprobado 2026-11-21): tarjeta grande con UN testimonio (cita en Saira, resto, avatar, captura en marco de teléfono) + lista de 3 botones con barra de progreso, flechas y "Pausar". Rota cada 8 s (app.js `[data-testimonios]`; el tiempo lo marca la animación de la barra), se pausa con mouse/foco de teclado/botón/fuera de pantalla; con reduced-motion no rota. Sin JS se ven los 3 completos. aria-live off al rotar solo, polite al elegir. Mantiene franja "¿Ya nos visitaste?". Capturas de WhatsApp RECORTADAS a la burbuja (sin teléfonos; nombres sí), placas tapadas. Originales en `/testimoniales` (en .gitignore, NUNCA subir). Salida en `public/img/testimonios/testimonio-N.webp` (sin EXIF); texto completo en el `alt`. Sin estrellas.
- Visitas (`/admin/visitas`, solo dueño, `app/modelos/visitas.php`): `registrar_visita()` desde `mostrar_pagina()` (solo layout público, código 200, GET; sin robots ni quien tiene sesión en el panel; sin prefetch). Anónimo: SIN IP ni cookies; `visitante` = hash con sal diaria (`settings.visitas_sal`, se reemplaza cada día). Origen solo en "entradas" (UTM/fbclid/gclid/referer externo; navegación interna = null). Seguimiento de pedidos se guarda como `mi-pedido/seguimiento` (no el número). Se borran tras 400 días (limpieza aleatoria 1/500). Barras verticales con `clase_alto_barra()` (pasos 5%).
- Consultas/leads (decisión del dueño): la ventana `ventana-consulta` (`<dialog>`) la incluye `layout.php` en todas las páginas públicas salvo carrito, checkout, pedido-confirmado, mi-pedido, contacto, términos, privacidad y errores; se prepara ANTES de enviar el HTML (su formulario abre la sesión por el CSRF). Se abre SOLA al instante, una vez por visita (`sessionStorage.consulta_popup_visto`; decisión del dueño: "siempre el form de primero"); `?consulta=abrir` la abre siempre. Franja `cta-consulta` (Inicio y Servicios) con botón que la abre; formulario fijo en `/contacto#consulta`. Todo POST va a `/contacto` (PRG → `?consulta=enviada`). Obligatorios nombre + teléfono; correo y mensaje opcionales; casilla de consentimiento (Ley 8968); campo trampa; 5/hora por IP y máx. 3 'nueva' por teléfono. Admin `/admin/consultas`: dueño y empleado ven/atienden (nueva → contactada → cerrada + notas); solo el dueño borra. Contador de nuevas en el menú. Tablas nuevas en `schema.sql`; bases viejas: `database/migraciones/2026-10-23-visitas-y-consultas.sql`.
- Alertas de leads (decisión del dueño: Telegram): `notificar_consulta()` en `app/servicios/notificaciones.php` (tras guardar; timeout 5 s; si falla solo va al log). Token y chat en `.env` (`TELEGRAM_BOT_TOKEN`, `TELEGRAM_CHAT_ID`; guía `docs/alertas-telegram.md`); prueba en Admin → Consultas (dueño). En el panel: `admin.js` consulta `/admin/api/novedades` cada 30 s → aviso, sonido, notificación del navegador, contador y título. Ese endpoint usa `usuario_sin_renovar()` (NO renueva la sesión: se respeta el cierre por 2 h sin uso).
- Clientes / CRM (`/admin/clientes`, `app/modelos/clientes.php`; decisión del dueño): NO hay tabla de clientes; se arma con consultas + pedidos unidos por teléfono ("8888-8888"). Cliente = ≥1 pedido en `ESTADOS_COMPRA` (pagado, en_preparacion, entregado); si no, posible cliente. Ficha `/admin/clientes/{telefono}` con resumen, pedidos, consultas y notas (`clientes_notas`). Dueño y empleado ven y escriben notas; sin exportar. Enlaces "Ver ficha del cliente" en consulta y pedido. Migración para bases viejas: `database/migraciones/2026-10-23-clientes-notas.sql`.
- Datos de prueba (decisión del dueño 2026-10-23: "el CRM sin data de prueba, todo será real"): se borraron las consultas y TODOS los pedidos locales (stock repuesto, comprobantes borrados, numeración reiniciada). Quedan productos, categorías, servicios, visitas y el usuario demo. `tests/flujo-completo.sh` CREA pedidos "Prueba Automatica": NO correrlo contra la base local sin avisar al dueño.
- Colores: `rivera-rojo-claro` (#F04A4F) SOLO para texto pequeño sobre fondo oscuro (contraste); el rojo de marca para botones, títulos grandes e íconos.
- `componente()` usa variables internas `$__componente`/`$__datos` para no chocar con los datos (ej. 'nombre').
- SEO: `/sitemap.xml` y `/robots.txt` dinámicos; datos estructurados (AutoRepair y Product) vía `$meta['datos']`.
- Pruebas: `BASE=... ADMIN_EMAIL=... ADMIN_CLAVE=... bash tests/flujo-completo.sh` (solo local o sitio de pruebas).
- Servicios (decisión del dueño): editables en Admin → Servicios (tabla `services`, sin precios, botón "Cotizar por WhatsApp"). Se cargaron 6 de las fotos para que el dueño los revise.
- Página Servicios (rediseño 2026-11-08, pedido del dueño: "menos genérica, más profesional"): portada en dos columnas (texto + foto `rivera-01`), franja de datos, lista numerada sobre fondo claro (componente `fila-servicio`, un botón Cotizar por servicio con el nombre en el mensaje), fotos 08 y 25, "Cómo trabajamos" (4 pasos, texto propuesto: confirmar con el dueño), marcas en texto (Havoline, Castrol, Delo, Chevron, Vonixx, Malco; confirmar) y galería 07/06/18. `tarjeta-servicio` se sigue usando en Inicio.
- Mapa: NO se incrusta un mapa (riesgo de ubicación equivocada: "La Puebla" existe en varios lugares). Botón "Cómo llegar" = `mapa_url()` (enlace de Configuración o búsqueda del negocio). El dueño aún no tiene el enlace de Google Maps. Waze: setting `waze_url` (editable en Configuración; vacío = sin botón), botón "Waze" en Visítanos y "Abrir en Waze" en Contacto. Coordenadas del taller: 9.994919, -84.111607. Sin `mapa_url`, "Cómo llegar" abre Google Maps en las coordenadas del enlace de Waze (`ll=`); si tampoco hay, busca por nombre.
- Pedidos admin (`app/modelos/pedidos-admin.php`): todo cambio pasa por `transicion_pedido()` (FOR UPDATE + estados permitidos + historial). **JAMÁS aprobar sin comprobante** (decisión del dueño): `aprobar_pago()` exige dueño, estado `comprobante_enviado`, comprobante `pendiente` y casilla "verifiqué el ingreso".
- Permisos (decisión del dueño): empleado VE pedidos y comprobantes y marca en preparación/entregado; NO aprueba, rechaza ni cancela. Cancelar (aun pagado) solo el dueño, con motivo y aviso de devolver el dinero.
- Comprobantes se sirven por `/admin/comprobantes/{id}` (ruta tipo 'archivo', con sesión). Ventas del mes = pedidos cuyo paso a `pagado` fue en el mes y no se cancelaron después.
- Configuración (`/admin/configuracion`, solo dueño): datos de pago, contacto, prefijo/plazo, banner, redes y zonas de envío. WhatsApp se guarda como 506XXXXXXXX. Botón "WhatsApp al cliente" = enlace manual (no es la Fase 2).
- Admin: `requerir_admin()` / `requerir_admin('duenio')` (`app/core/auth.php`). Páginas en `app/paginas/admin/`, `$meta['layout'] = 'admin/layout'`. Avisos de una vez: `avisar()` / `tomar_avisos()`. Sin permiso → `prohibido()` (403).
- Roles (decisión del dueño): **empleado** = SOLO pedidos (no productos, categorías ni usuarios; nunca aprueba pagos). **dueño** = todo. Pantalla Usuarios solo para el dueño; siempre queda ≥1 dueño activo y nadie se desactiva/degrada a sí mismo.
- Seguridad login: 5 fallos por correo o 10 por IP = 15 min de bloqueo; sesión expira tras 2 h sin uso; logout solo por POST.
- Fotos (decisión del dueño): JPG/PNG/WebP ≤ 10 MB, máx. 5 por producto, se re-dibujan con GD a WebP `xxx.webp` (1200 px) + `xxx-mini.webp` (400 px). Usar `url_foto($ruta, $mini)`. Slug de producto/categoría se crea una vez y no cambia al editar.
- Productos con pedidos sin terminar no se pueden borrar (solo ocultar). Categorías con productos no se pueden borrar.
- Seguimiento: `/mi-pedido` (número + teléfono) → `/mi-pedido/{numero en minúsculas}`. Acceso por sesión (`autorizar_pedido()` / `pedido_autorizado()`); quien crea el pedido queda autorizado. Bloqueo: 10 fallidos por IP = 15 min (`app/modelos/intentos.php`).
- Comprobantes (decisión del dueño): UN comprobante activo por pedido; desde celular o computadora; JPG/PNG/PDF ≤ 5 MB validado por contenido (finfo + getimagesize/%PDF-); nombre aleatorio en `storage/comprobantes/AAAA/MM/`. Subir → `comprobante_enviado` (nunca `pagado`).
- Rechazo de comprobante (para el Módulo 7, decisión del dueño): el dueño escribe el MOTIVO (`payment_proofs.nota_revision`, lo ve el cliente), el pedido vuelve a `pendiente_pago` y el cliente puede subir otro. Nuevo plazo de pago tras el rechazo: **24 h** (confirmado por el dueño). "Rechazar pedido" es aparte y final (devuelve stock).
- Pedidos: `crear_pedido()` en `app/modelos/pedidos.php` (transacción con `FOR UPDATE`, precios de la BD, reserva stock, historial). Número `RGA-AAAA-NNNN` (prefijo en `settings.prefijo_pedido`, contador `order_sequences`). Tras crear, llama `notificarPedido()` (vacía, Fase 2).
- Vencimiento: SOLO pedidos en `pendiente_pago` vencen (48 h, `settings.horas_expiracion_pedido`) → `cancelado` + `devolver_stock()`. Los `comprobante_enviado` NUNCA vencen solos. Cron: `app/tareas/liberar-pedidos-vencidos.php` cada hora (además se ejecuta al crear pedidos).
- `generar_numero_pedido()` salta números ya usados si el contador `order_sequences` quedó atrasado (ej. respaldo restaurado a medias): nunca bloquea las ventas.
- Anti-abuso aprobado por el dueño: campo trampa, máx. 3 pedidos `pendiente_pago` por teléfono, máx. 5 pedidos/hora por IP.
- Carrito: vive en `localStorage` como `[{id, cantidad}]` SOLAMENTE. Precios y stock siempre del servidor (`/api/carrito`); el checkout DEBE revalidar todo en el servidor dentro de una transacción.
- Stock al cliente: "Disponible" / "¡Últimas N unidades!" (≤3) / "Agotado" (`estado_stock()`). La tarjeta de producto muestra esa línea bajo el precio.
- Tienda (rediseño aprobado 2026-11-08): encabezado negro con título grande + foto `rivera-25` al lado (sin texto encima) + línea roja; accesos rápidos por categoría (solo las que tienen productos); filtros en tarjeta blanca (`filtros-tienda`); conteo "N productos en Categoría"; franja negra "¿No encuentras lo que buscas?" con WhatsApp; sin íconos decorativos.
- Admin: se crea con `php database/crear-admin.php` (no hay usuarios en `seed.sql`).
- **Repositorio:** https://github.com/Carlos2002234/taller (rama `main`). Cada commit se sube con `git push`.

### Datos del negocio (confirmados)
- Redes: Facebook profile.php?id=61592820894749, Instagram @riveragrupoautomotriz, TikTok @riveragrupoautomotriz (en `seed.sql`, sin parámetros de rastreo).
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
- Logo oficial: el de la página 1 de `MANUAL DE MARCA (1).pdf` (vectorial; recorte en alta: `assets/logo/logo-completo-manual.png`). En la web: `public/img/logo-completo.webp` (logo apilado completo, fondo transparente) en encabezado y pie (decisión del dueño 2026-11-08: "el logo a usar es el del pie de página"). `logo.webp` + `rivera-texto.webp` quedan para íconos/favicons.
- Tipografía (decisión del dueño): "RIVERA" es letra dibujada a mano (no existe como fuente) → se usa como imagen `img/rivera-texto.webp`, recortada de `assets/logo/logo-oscuro.png` (versión oficial para fondo oscuro) por `npm run imagenes`. Títulos (`font-display`) en **Saira** itálica (la fuente libre más parecida a "GRUPO AUTOMOTRIZ"); h1–h3 en itálica. Textos en Inter.
- Paleta (extraída del logo): Rojo Rivera `#D11A21` (hover `#A8141A`) · Negro `#0B0B0B` · Carbón `#1A1A1A` · Blanco `#FFFFFF` · Gris apoyo `#F2F2F2`.
- Referencia: `assets/referencia/prototipo.png` — fondos oscuros, acentos rojos, títulos en dos líneas (blanco + rojo), tarjetas de categoría/producto, catálogo con filtros sobre fondo claro, barra inferior en móvil.
- Fotos: `assets/fotos/rivera-XX.jpg` (fachada 14/18, recepción 23, rótulo 01, auto cubierto 07).
- Del prototipo SÍ: filtros categoría + marca + rango de precio; etiquetas "Oferta" (`precio_anterior`) y "Destacado"; banner promocional editable; barra inferior móvil (Inicio, Tienda, Carrito, Mi pedido); franja de beneficios.
- Del prototipo NO (MVP): reseñas/estrellas, cuentas de cliente (checkout sin registro), newsletter (Fase 3). Precios siempre en ₡.

### Pendiente (el dueño lo enviará; no bloquea el Módulo 1)
- Titular del SINPE; datos bancarios (banco, IBAN, titular, cédula): el dueño los carga en Admin → Configuración.
- ¿Precios con IVA incluido?
- Provincia/cantón de La Puebla y enlace de Google Maps/Waze.
- Revisar los 6 servicios cargados (desde el admin).
- Tarifas reales de envío: el dueño las ajusta en Admin → Configuración antes de publicar.
- Correo para avisos de pedidos; dominio; plan de Hostinger.

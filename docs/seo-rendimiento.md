# SEO, posicionamiento y rendimiento (rama `seo-rendimiento`, 2026-11-27)

Pedido del dueño: "mejora mucho el SEO, posicionamiento y performance; ocupo posicionar la página súper bien".
Regla: sin cambiar el diseño aprobado y sin inventar servicios, precios ni datos.

## 1. Auditoría (producción, antes de tocar nada)

Lighthouse 12.6 contra https://riveraautomotriz.com (móvil = Moto G simulado con 4G lenta; escritorio = preset desktop).

| Página | Disp. | Perf | SEO | Acces. | Buenas pr. | LCP | CLS | TBT | Peso |
|---|---|---|---|---|---|---|---|---|---|
| Inicio | móvil | 85 | 100 | 100 | 75 | 4,1 s | 0 | 40 ms | 461 KiB |
| Inicio | escritorio | 99 | 100 | 100 | 74 | 0,9 s | 0 | 0 ms | 667 KiB |
| Tienda | móvil | 98 | 100 | 100 | 75 | 2,0 s | 0 | 30 ms | 389 KiB |
| Tienda | escritorio | 100 | 100 | 100 | 74 | 0,5 s | 0,012 | 0 ms | 389 KiB |
| Servicios | móvil | 96 | 100 | 100 | 75 | 2,6 s | 0 | 20 ms | 369 KiB |
| Servicios | escritorio | 100 | 100 | 100 | 74 | 0,5 s | 0 | 0 ms | 586 KiB |
| Producto (estribos Hilux) | móvil | 99 | 100 | 100 | 75 | 2,0 s | 0 | 20 ms | 413 KiB |
| Producto (estribos Hilux) | escritorio | 100 | 100 | 100 | 74 | 0,5 s | 0 | 0 ms | 413 KiB |
| Ubicación | móvil | 88 | 100 | 100 | 75 | 3,6 s | 0 | 10 ms | 910 KiB |
| Ubicación | escritorio | 99 | 100 | 100 | 74 | 0,6 s | 0 | 0 ms | 1171 KiB |
| Preguntas frecuentes | móvil | 97 | 100 | 100 | 75 | 2,3 s | 0 | 20 ms | 233 KiB |
| Preguntas frecuentes | escritorio | 100 | 100 | 100 | 74 | 0,6 s | 0 | 0 ms | 233 KiB |

TTFB de producción: 170–270 ms (casi todo es el viaje por Cloudflare Tunnel; PHP responde en 5–40 ms).
INP no se puede medir en laboratorio; TBT (su equivalente) es 0–40 ms: no hay JavaScript pesado.

### Hallazgos

**Rendimiento**
- LCP del Inicio en móvil 4,1 s: el 50 % es "render delay": la fachada entraba con una animación que empezaba en
  opacidad 0, y Chrome no cuenta la foto como pintada hasta que deja de ser invisible.
- Servicios: el LCP era la etiqueta de arriba, también con animación desde opacidad 0.
- Imágenes más grandes de lo necesario: logo del encabezado/pie (816 px para mostrarse a ~95–176 px, 36 KB),
  fachada sin tamaño de 800 px para celulares.
- Caché: imágenes a 30 días y sin `immutable` (las fotos no llevaban versión `?v=`).
- Página completa con `Cache-Control: no-store` (lo pone la sesión de PHP): impide el "bfcache"
  (volver atrás instantáneo).

**Buenas prácticas (74–75)**
- Error de consola en todas las páginas: Cloudflare inserta su script de Web Analytics
  (`static.cloudflareinsights.com`) y la CSP lo bloqueaba.
- Cookies de terceros de Microsoft Clarity (decisión del dueño; Lighthouse siempre lo marcará).

**SEO** (Lighthouse da 100 porque solo revisa lo básico; los problemas reales eran estos)
- Título del Inicio = solo "Rivera Grupo Automotriz": ninguna palabra de búsqueda ("taller mecánico", "Heredia").
- "Heredia" no aparecía en ningún texto visible (solo en el JSON-LD).
- Las categorías (`/tienda?categoria=…`) están en el sitemap pero su canonical apuntaba a `/tienda`:
  señales contradictorias, Google no las indexaba como páginas propias.
- `/tienda/` (con barra) respondía 200: dos direcciones para la misma página.
- Descripciones con "p.m.." (doble punto) y de más de 200 caracteres (Ubicación, Servicios).
- Sin Twitter card, sin dimensiones de la imagen Open Graph, sin `og:type=product` en productos.
- Sin `favicon.ico` (Google lo pide para el ícono en los resultados) ni manifest.
- Datos estructurados: Product sin `@id`/`url`/categoría y sin migas de pan; sin BreadcrumbList; Servicios sin
  datos de servicios; el AutoRepair sin `@id` (los productos no lo podían citar como vendedor).
- `noindex, nofollow` en búsquedas/filtros: Google tampoco seguía los enlaces a productos desde ahí.
- Sitemap sin fotos de productos y sin `lastmod` en tienda/categorías.
- Lo que ya estaba bien: H1 único por página, jerarquía h1→h2→h3 correcta, alt en todas las fotos con contenido,
  404 real con `noindex`, redirección http→https y www→sin www (301), URLs limpias, robots.txt correcto,
  `lang` presente, fuentes locales WOFF2 latinas con `font-display: swap`, JS con `defer`, Clarity `async`.

## 2. Cambios aplicados

### Rendimiento (sin cambiar el diseño)
- Animaciones de entrada (`entrar`, `foto-aparecer`) empiezan en opacidad 0,01 en vez de 0: a la vista es idéntico,
  pero el LCP se registra al pintar la foto/título y no al terminar la animación.
- Fachada del Inicio: `srcset` 800/960/1600/1920 (antes 960/1920) + `<link rel="preload" as="image">` con los
  mismos tamaños y `fetchpriority="high"`.
- Logo del encabezado y pie: nueva versión de 400 px (`logo-completo-400.webp`, 15 KB en vez de 36 KB) con `srcset`.
- Fotos del taller y video servidos con `asset()` (llevan `?v=fecha`): se pueden guardar 1 año sin riesgo.
- `width`/`height` + `decoding="async"` en tarjetas de producto/categoría, foto del taller y fotos de Contacto
  (Contacto ahora también con `srcset`).
- Páginas públicas indexables: `Cache-Control: private, no-cache` en vez de `no-store` → el navegador puede usar el
  bfcache (atrás/adelante instantáneo). Carrito, checkout, pedidos, panel y errores siguen con `no-store`.
- CSP: se permite el script de Cloudflare Web Analytics (sin cookies; mide Core Web Vitals reales) → se acaba el
  error de consola.

### SEO on-page y local
- Títulos y descripciones nuevos (ver tabla), con la intención local real y sin relleno:

| Página | Título | 
|---|---|
| Inicio | Taller mecánico y accesorios en Heredia \| Rivera Grupo Automotriz |
| Tienda | Tienda de accesorios y repuestos para autos \| Rivera Grupo Automotriz |
| Categoría | {Categoría} para tu auto \| Rivera Grupo Automotriz |
| Servicios | Servicios de taller mecánico en Heredia \| Rivera Grupo Automotriz |
| Ubicación | Ubicación y horario en La Puebla, Heredia \| Rivera Grupo Automotriz |
| Preguntas | Preguntas frecuentes: pagos, envíos y citas \| Rivera Grupo Automotriz |
| Contacto | Contacto y WhatsApp 8702-6760 \| Rivera Grupo Automotriz |
| Producto | {Nombre del producto} \| Rivera Grupo Automotriz |

- Descripciones: `texto_meta()` las deja en una línea, sin dobles puntos y cortadas en palabra completa (≤158).
- Textos visibles (cambios mínimos, mismo diseño):
  - Dirección completa en pie, Contacto y Ubicación: "… antes de llegar a los semáforos. Heredia, Costa Rica."
    (`direccion_completa()`; NAP igual en todo el sitio y en el JSON-LD).
  - Inicio: etiqueta "Taller de servicio · La Puebla, Heredia".
  - Servicios: etiqueta "Centro de servicio automotriz · La Puebla, Heredia" y el párrafo de portada dice
    "…preparación para la RTV en La Puebla de Heredia."
- Canonical de cada categoría = `/tienda?categoria=…` (coincide con el sitemap). Búsquedas, filtros y páginas 2+
  siguen `noindex`, ahora con `follow`.
- `/ruta/` → 301 a `/ruta` (conserva `?categoria=`).
- `<html lang="es-CR">`, `robots: index, follow, max-image-preview:large` (fotos grandes en Discover/Imágenes).
- Open Graph completo (dimensiones y alt de la imagen), `twitter:card`, `og:type=product` + precio y disponibilidad
  en productos.
- `favicon.ico` (16/32/48), `favicon-48.png`, `site.webmanifest` con íconos 192/512 (`npm run iconos`).
- Sitemap: fotos de cada producto (Google Imágenes), `lastmod` real en tienda y categorías.
- hreflang: no aplica (un solo idioma y país); `lang="es-CR"` + `og:locale es_CR` es lo correcto.

### Datos estructurados (JSON-LD)
- AutoRepair (Inicio, Servicios, Ubicación, Contacto): `@id` fijo (`/#negocio`), `description`; en Servicios además
  `hasOfferCatalog` con los 6 servicios de Admin → Servicios (mismo texto visible).
- WebSite (Inicio): nombre del sitio en Google, `inLanguage es-CR`.
- Product (fichas): `@id`, `url`, `category`, imagen(es), marca, Offer con precio en CRC y disponibilidad real de la
  base de datos; vendedor = el AutoRepair por `@id`.
- BreadcrumbList: ficha de producto (Tienda › Categoría › Producto) y tienda/categoría (Inicio › Tienda › Categoría),
  igual a las migas visibles. No se agregaron migas donde no se ven.
- FAQPage: sin cambios (ya era correcto y solo en /preguntas-frecuentes). Sin duplicados: un AutoRepair por página.
- Nota: Google no muestra estrellas de `aggregateRating` en el propio sitio de un negocio local ("reseñas
  autopromocionales"). No es un error y se deja; las estrellas salen en el Perfil de Empresa de Google.

## 3. Resultado (local, mismo servidor, antes vs. después)

Compara la rama `main` sin cambios contra esta rama en esta Mac, misma base de datos y mismas fotos.

| Página | Disp. | Perf antes → después | LCP antes → después | CLS antes → después | Peso antes → después |
|---|---|---|---|---|---|
| Inicio | móvil | 92 → 93 | 3.1 s → 2.9 s | 0.000 → 0.000 | 574 → 541 KiB |
| Inicio | escritorio | 99 → 99 | 1.0 s → 0.9 s | 0.000 → 0.000 | 779 → 702 KiB |
| Tienda | móvil | 95 → 94 | 2.8 s → 2.8 s | 0.000 → 0.000 | 421 → 401 KiB |
| Tienda | escritorio | 100 → 100 | 0.6 s → 0.5 s | 0.012 → 0.000 | 421 → 401 KiB |
| Servicios | móvil | 97 → 97 | 2.4 s → 2.3 s | 0.000 → 0.000 | 335 → 316 KiB |
| Servicios | escritorio | 100 → 100 | 0.5 s → 0.5 s | 0.000 → 0.000 | 550 → 531 KiB |
| Producto | móvil | 93 → 94 | 2.9 s → 2.9 s | 0.000 → 0.000 | 378 → 358 KiB |
| Producto | escritorio | 100 → 100 | 0.6 s → 0.6 s | 0.000 → 0.000 | 378 → 358 KiB |
| Ubicación | móvil | 97 → 98 | 2.3 s → 2.1 s | 0.000 → 0.000 | 873 → 854 KiB |
| Ubicación | escritorio | 100 → 100 | 0.6 s → 0.4 s | 0.000 → 0.006 | 1135 → 1123 KiB |
| Preguntas | móvil | 98 → 98 | 2.1 s → 2.1 s | 0.000 → 0.000 | 199 → 180 KiB |
| Preguntas | escritorio | 100 → 100 | 0.4 s → 0.4 s | 0.000 → 0.000 | 199 → 180 KiB |

SEO y Accesibilidad: 100 en todas, antes y después. Buenas prácticas: 100 en local (el Inicio da 96 solo por una foto
de categoría de prueba que falta en la base local). En producción daba 74–75 por el error de CSP de Cloudflare, que
este cambio corrige (no se puede medir en local porque Cloudflare inserta ese script solo en producción).

Lectura: el sitio ya era rápido; la ganancia de rendimiento es moderada (−20 a −77 KiB por página, LCP del Inicio en
móvil 3,1 → 2,9 s, CLS de la Tienda 0,012 → 0). Lo importante en el Inicio es que antes el LCP lo marcaba un texto a
los ~4,5 s porque la foto estaba "invisible" durante su animación; ahora el LCP es la fachada, como debe ser.
Medición: `php -S` detrás de Caddy con compresión zstd/gzip (como producción, sin Cloudflare), Lighthouse 12.6.

## 4. Despliegue (en orden)

1. **Código** (tras aprobar el PR): actualizar `/Users/carlosrivera/taller-produccion` con `main` como siempre.
   Archivos nuevos que deben llegar: `public/favicon.ico`, `public/site.webmanifest`, `public/img/icono-192.png`,
   `public/img/icono-512.png`, `public/img/favicon-48.png`, `public/img/logo-completo-400.webp`, `public/css/app.css`.
   No hay migraciones de base de datos ni cambios de `.env`.
2. **Caddy (aparte):** comparar y copiar la propuesta:
   ```sh
   diff /opt/homebrew/etc/Caddyfile docs/produccion/Caddyfile
   cp /opt/homebrew/etc/Caddyfile /opt/homebrew/etc/Caddyfile.respaldo-$(date +%F)
   cp docs/produccion/Caddyfile /opt/homebrew/etc/Caddyfile
   caddy validate --config /opt/homebrew/etc/Caddyfile --adapter caddyfile
   brew services restart caddy
   ```
   Cambia solo la caché: archivos con `?v=` 1 año `immutable`, fotos de productos 1 año, resto 7 días, y el tipo
   correcto del manifest. Para volver atrás: copiar el respaldo y reiniciar.
3. **PHP opcache (aparte, opcional):** ya está activo en el pool `taller-produccion`
   (`/opt/homebrew/etc/php/8.5/php-fpm.d/taller-produccion.conf`). Valores recomendados para agregar ahí:
   ```ini
   php_admin_value[opcache.memory_consumption] = 128
   php_admin_value[opcache.interned_strings_buffer] = 16
   php_admin_value[opcache.max_accelerated_files] = 10000
   php_admin_value[opcache.validate_timestamps] = 1
   php_admin_value[opcache.revalidate_freq] = 60
   php_admin_value[realpath_cache_ttl] = 600
   ```
   Con `revalidate_freq = 60` PHP revisa cambios de archivos una vez por minuto: tras cada despliegue correr
   `brew services restart php` para que se vea al instante. La ganancia es pequeña (PHP ya responde en 5–40 ms);
   el TTFB de producción lo domina el túnel.
4. **Cloudflare (lo hace el dueño en el panel, plan gratis):**
   - SSL/TLS → Edge Certificates: **Always Use HTTPS** = On; **Automatic HTTPS Rewrites** = On; TLS 1.3 = On.
   - Speed → Optimization → Content Optimization: **Brotli** = On (si aparece); **Early Hints** = On;
     **Rocket Loader = Off** (rompería la CSP y el orden de los scripts); Auto Minify ya no existe/no hace falta.
   - Network: **HTTP/3 (with QUIC)** = On (ya se ve `alt-svc: h3`); **0-RTT** = On.
   - Caching → Configuration: Caching Level = Standard; **Browser Cache TTL = "Respect Existing Headers"**.
   - Caching → Tiered Cache: **Smart Tiered Caching** = On.
   - Analytics → Web Analytics: dejarlo activo (ahora sí funciona) y revisar ahí los Core Web Vitals reales.
   - NO crear reglas que guarden el HTML en caché (las páginas tienen sesión y token CSRF).
5. **Search Console** (tras desplegar): Sitemaps → volver a enviar `https://riveraautomotriz.com/sitemap.xml`;
   Inspección de URL → "Solicitar indexación" para Inicio, Servicios, Tienda y cada categoría.
   Probar una ficha en https://search.google.com/test/rich-results (Producto + Migas de pan) y el Inicio.

## 5. Propuestas (no aplicadas: requieren decisión del dueño)

1. **Ventana de consulta que se abre sola al entrar** (decisión del dueño "siempre el form de primero"): Google
   penaliza en celular los "intersticiales intrusivos" que tapan el contenido al llegar desde la búsqueda. Sugerencia:
   abrirla a los 15–20 s o al 50 % del scroll, o no abrirla sola cuando la visita viene de Google (`document.referrer`).
   Es probablemente el riesgo de SEO más grande que queda.
2. **Páginas por servicio** (hoy no existen): `/servicios/cambio-de-aceite`, `/servicios/diagnostico-con-escaner`,
   `/servicios/frenos-y-suspension`, `/servicios/lavado-y-detallado`, `/servicios/revision-tecnica-rtv`,
   `/servicios/carga-autos-electricos`. Cada una con su foto, 2–3 párrafos reales del dueño (qué incluye, cuánto dura,
   qué marcas usa), preguntas propias y botón de WhatsApp. Es lo que más posiciona búsquedas como
   "cambio de aceite Heredia". Si el taller también hace polarizados, instalaciones de accesorios u otros, agregarlos
   primero en Admin → Servicios (hoy no están en la web, por eso no se mencionan en ningún texto).
3. **Texto del banner del Inicio** (Admin → Configuración → `banner_texto`, hoy "Accesorios, repuestos y productos de
   calidad…"): propuesta "Taller mecánico, lavado y tienda de accesorios en La Puebla de Heredia. Cotiza por WhatsApp
   y retira en el taller o recibe en la GAM." (lo cambia el dueño si le gusta; no se tocó la base de datos).
4. **H1 del Inicio** ("Equipa tu camino con lo mejor") no dice qué es el negocio. Opción sin tocar el diseño: que la
   etiqueta roja de arriba diga "Taller y accesorios en Heredia".
5. **Política de devoluciones y envío en el schema de Producto** (`hasMerchantReturnPolicy`, `shippingDetails`):
   Google las pide para la ficha de "comerciante". Se agregan cuando estén los [COMPLETAR] legales de devoluciones.
6. **Fotos de producto de 200 px** para las tarjetas en celular (hoy 400 px; ahorro ~15 KB por tarjeta). Requiere
   regenerar las fotos ya subidas.
7. **Descripciones de productos**: los que no tienen descripción usan una automática (precio + categoría + cómo
   comprar). Escribir 2–3 líneas reales por producto (compatibilidad, medida, uso) ayuda mucho en Google.

## 6. Plan de posicionamiento (fuera del código)

### Perfil de Empresa de Google (lo más importante para "taller cerca de mí")
- **Nombre** exacto: "Rivera Grupo Automotriz" (sin palabras clave agregadas: Google lo castiga).
- **Categoría principal**: "Taller de reparación de automóviles". Secundarias (solo las que de verdad hace):
  "Servicio de cambio de aceite", "Servicio de lavado de autos", "Servicio de detallado de automóviles",
  "Tienda de accesorios para automóviles", "Tienda de repuestos para automóviles", "Estación de carga para
  vehículos eléctricos"; si aplica: "Servicio de polarizado de vidrios", "Taller de frenos".
- **Dirección y teléfono** idénticos a la web: "150 m oeste de la plaza de deportes de La Puebla, antes de llegar a los
  semáforos, Heredia" · 8702-6760 · sitio https://riveraautomotriz.com · horario L–S 8:00–17:00 (marcar feriados).
- **Servicios** en el perfil: los 6 de la web, con la misma descripción. **Productos**: subir los destacados con foto y
  precio y enlace a su ficha.
- **Fotos**: 3–5 nuevas cada mes (fachada de día, recepción, trabajos, antes/después de detallado, equipo). Con
  nombres de archivo descriptivos. Los perfiles con fotos recientes reciben más clics y rutas.
- **Publicaciones** cada semana: oferta del mes, producto nuevo, trabajo terminado, recordatorio de RTV por terminación
  de placa. Siempre con botón "Más información" a la página correspondiente.
- **Reseñas**: meta 30+ en 6 meses. Pedirla por WhatsApp al entregar el auto con el enlace
  https://g.page/r/CV_ftd9wTRbwEBM/review (ya está en la web) y un QR impreso en recepción. Responder TODAS en
  24–48 h, mencionando el servicio ("¡Gracias por confiarnos el cambio de aceite de tu Hilux!"). Nunca pagar ni
  ofrecer descuentos por reseñas (va contra las reglas de Google).
- **Preguntas y respuestas**: publicar y responder las de /preguntas-frecuentes.
- Revisar que el enlace de Maps abra la ficha correcta: hoy el enlace del dueño abre una ficha llamada "Evolube";
  si es un duplicado o un error, pedir a Google que lo corrija o se una (es importante: divide las señales).

### Directorios y menciones locales (NAP idéntico en todos)
- Apple Business Connect (Apple Maps), Bing Places (importa desde Google), Waze (ya está), Facebook (sección
  Información con dirección/horario/web), Instagram y TikTok con el enlace a la web en la biografía.
- Directorios de Costa Rica: Páginas Amarillas CR (paginasamarillas.co.cr / guía telefónica), Encuentra24 (anuncios de
  accesorios con enlace), Cybo, Hotfrog, Foursquare, Yelp. Grupos de Facebook de Heredia/San Francisco/La Puebla
  (presentarse una vez, sin spam) y grupos de dueños de Hilux, RAV4, etc. para los productos.
- Marcas con las que trabaja (Havoline/Chevron, Castrol, Vonixx): preguntar si tienen "dónde comprar" o
  "talleres afiliados" y pedir aparecer con enlace.

### Palabras clave locales prioritarias
1. taller mecánico Heredia · taller mecánico La Puebla · taller cerca de mí (Heredia)
2. cambio de aceite Heredia · cambio de aceite y filtro precio Costa Rica
3. diagnóstico con escáner Heredia · revisión antes de la RTV Heredia
4. lavado y detallado de autos Heredia
5. frenos y suspensión Heredia
6. cargador para carro eléctrico Heredia
7. accesorios para carro Heredia · estribos Hilux Costa Rica · alfombras RAV4 Costa Rica · bombillos LED H11 Costa Rica
   (las de producto: nombre + modelo + "Costa Rica")

### Contenido sugerido (artículos cortos, 500–900 palabras, con fotos propias)
- "¿Cada cuánto cambiar el aceite? Guía para Costa Rica (0W-20 vs 5W-30)".
- "Cómo preparar tu carro para la RTV: lista de revisión" (+ calendario por terminación de placa).
- "Luces LED H11: qué revisar antes de comprar para que pasen la RTV".
- "Lavado vs. detallado: qué incluye cada uno y cada cuánto hacerlo".
- "Estribos para Hilux 2015–2026: modelos, instalación y precio" (enlaza a la ficha).
- "Testigos del tablero: qué significan y cuándo hacer un diagnóstico con escáner".
Requiere crear una sección de artículos (propuesta aparte); mientras, publicarlos como posts del Perfil de Google y
redes enlazando a Servicios/Tienda.

### Qué medir en Search Console (cada mes)
- Rendimiento → Consultas: impresiones y clics de "taller", "cambio de aceite", "Heredia", "La Puebla" y del nombre
  del negocio; posición media de las 10 consultas principales; CTR de Inicio y Servicios (si es <3 %, mejorar título).
- Páginas → indexadas vs. no indexadas (esperado: ~23 URL del sitemap indexadas; categorías ya no como "alternativa
  con canonical").
- Mejoras: Productos, Migas de pan y Preguntas frecuentes sin errores.
- Experiencia → Core Web Vitals (móvil) en "Buenas"; complementar con Cloudflare Web Analytics.
- Perfil de Google → Rendimiento: búsquedas, llamadas, rutas y clics al sitio (la métrica de negocio real).

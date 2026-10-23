# Seguridad de la tienda

Resumen de cómo está protegido el sitio y qué revisar antes de publicarlo y después.

## Lista para revisar antes de publicar

- [ ] En el `.env` del servidor: `APP_ENV=produccion` y `APP_DEBUG=false`.
  (Con `APP_ENV=produccion` los errores nunca se muestran al público, aunque `APP_DEBUG` quede en `true`.)
- [ ] `APP_URL` con `https://` y tu dominio, sin barra al final.
- [ ] Certificado SSL activo en Hostinger (el sitio fuerza HTTPS).
- [ ] La carpeta `public` es la raíz web (lo que se ve en internet). `app/`, `storage/`, `database/` y `.env` quedan **fuera**.
- [ ] Contraseña del dueño de 12 caracteres o más, que no uses en otro sitio.
- [ ] Tarea programada cada hora: `app/tareas/liberar-pedidos-vencidos.php` (ver `docs/instalacion-local.md`).
- [ ] Datos de pago, tarifas de envío y servicios revisados en Admin → Configuración / Servicios.
- [ ] Términos y Política de privacidad completos (sin [COMPLETAR]) y revisados por un abogado. El checkout exige aceptarlos (consentimiento de la Ley 8968).
- [ ] Respaldo automático de la base de datos activado en Hostinger.
- [ ] Correr `tests/flujo-completo.sh` contra el sitio de pruebas (no contra la tienda real con clientes).

## Pagos (la regla más importante)

- **Ningún pago se aprueba solo.** El pedido nace en `pendiente_pago`; subir un comprobante lo pasa a
  `comprobante_enviado`; **solo el dueño** lo pasa a `pagado`.
- **Jamás se aprueba sin comprobante**, y hay que marcar la casilla "Verifiqué en mi cuenta que entró ₡X".
  Un comprobante de SINPE puede ser una imagen editada: siempre revisa tu cuenta bancaria.
- Precios y totales se calculan en el servidor con los precios de la base de datos. Lo que el cliente
  manipule en su navegador se ignora.
- Cada cambio de estado bloquea el pedido mientras se guarda: dos personas no pueden aprobar el mismo pago a la vez.
- Todo queda en el historial del pedido (quién, qué y cuándo).

## Protecciones del sitio

| Riesgo | Protección |
|---|---|
| Robo de contraseñas | Contraseñas cifradas (`password_hash`). Bloqueo de 15 min tras 5 fallos por correo o 10 por conexión. |
| Sesión robada u olvidada abierta | Cookie solo HTTP, segura en HTTPS, SameSite. Nuevo ID al ingresar. Cierre tras 2 h sin uso. |
| Formularios falsos desde otro sitio (CSRF) | Token secreto en todos los formularios, incluido cerrar sesión. |
| Inyección SQL | Todas las consultas usan parámetros preparados (PDO). |
| Código malicioso en textos (XSS) | Todo lo que se muestra pasa por `e()`. Política CSP: el navegador solo ejecuta scripts del propio sitio. |
| Archivos maliciosos subidos | Comprobantes: se revisa el tipo real del archivo (no el nombre), máx. 5 MB, nombre aleatorio, carpeta privada. Fotos: se vuelven a dibujar como WebP. |
| Ejecutar código en la carpeta de fotos | `public/uploads/.htaccess`: nada se ejecuta y solo se sirven fotos con el nombre exacto que genera el sitio. Probado en Apache con `foto.php.jpg`, `.phtml`, `.php5`, etc. |
| Ver comprobantes ajenos | Viven fuera de la web; solo se entregan a usuarios del panel con sesión. |
| Ver pedidos ajenos | Hace falta número de pedido **y** teléfono; bloqueo tras 10 intentos fallidos. |
| Pedidos falsos para apartar stock | Campo trampa para robots, máx. 3 pedidos sin pagar por teléfono y 5 por hora por conexión. Vencen a las 48 h. |
| Empleado con más permisos de los debidos | El empleado solo ve y atiende pedidos; no aprueba, rechaza ni cancela pagos, ni toca productos, precios o usuarios. Se revisa en el servidor, no solo en los botones. |
| Ver archivos internos | `.htaccess` bloquea `.env`, `.git`, `.sql`, `.log`, `.md` y archivos ocultos. Sin listados de carpetas. |
| Página insertada en otro sitio (clickjacking) | `X-Frame-Options` y `frame-ancestors`. |
| Conexión sin cifrar | Redirección a HTTPS y HSTS (el navegador recuerda usar solo HTTPS). |

## Si sospechas un problema

1. Cambia tu contraseña (Admin → Mi cuenta) y desactiva usuarios que no reconozcas (Admin → Usuarios).
2. Revisa el historial de los pedidos afectados.
3. Revisa el archivo `storage/logs/php-errores.log` en el servidor.
4. Restaura el último respaldo si algo fue modificado.

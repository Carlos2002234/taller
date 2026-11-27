# Monitoreo (Admin → Monitoreo)

Página solo para el dueño que muestra el estado **real** de todo lo que sostiene la web.
Si algo no se puede leer, el bloque dice «Sin datos» y explica cómo conectarlo; nunca se inventan datos.

| Bloque | De dónde sale | Se renueva |
|---|---|---|
| Web pública (código y tiempo de respuesta) | Petición desde el servidor a `MONITOREO_URL` (o `APP_URL`) | 2 min |
| Historial de 24 h (gráfico) | `storage/estado/web-historial.json`, lo escribe la tarea de estado cada minuto | 1 min |
| Certificado HTTPS | Conexión TLS al dominio (fecha de vencimiento y emisor) | 6 h |
| Dominio | RDAP (registro oficial de dominios): vencimiento y registrador | 12 h |
| Servicios de la Mac | Puertos locales (en vivo) + `storage/estado/servidor.json` (launchd, energía, arranque) | 1 min |
| Túnel de Cloudflare | `http://CLOUDFLARED_METRICS/ready` y `/metrics` (solo local) | en vivo |
| Disco, PHP, MariaDB | PHP y la base, en vivo | en vivo |
| Respaldos | Carpeta `RESPALDOS_DIR` y última línea de `RESPALDOS_LOG` | en vivo |
| UptimeRobot | API v2 con la clave de **solo lectura** de Configuración → Monitoreo | 5 min |
| Actividad de la tienda | Base de datos | en vivo |
| Clarity | Data Export API con el token de Configuración → Monitoreo (máx. 10 consultas al día) | 4 h |
| Search Console | Solo enlace (su API exige una cuenta de servicio de Google) | — |

## Seguridad
- Solo el dueño (`requerir_admin('duenio')`).
- Las claves de UptimeRobot y Clarity se guardan en `settings` (base de datos), no en git. En Configuración nunca se muestran: solo sus últimos 4 caracteres. UptimeRobot exige la clave de solo lectura (empieza con «ur»).
- Todas las consultas externas se hacen desde el servidor, con tiempo límite corto (2 a 8 s) y caché en `storage/cache/`. Si un servicio de afuera falla, la página igual carga y muestra el motivo.
- PHP-FPM no ejecuta comandos. Lo que requiere comandos del sistema (launchctl, pmset, sysctl) lo hace la tarea CLI `app/tareas/estado-servidor.php`, que corre con launchd y solo usa comandos fijos.
- «Actualizar ahora» ignora la caché, pero nunca más de una vez cada 20 s. Clarity no se fuerza nunca por su límite diario.

## Instalar en la Mac de producción
1. `.env`:
   ```
   MONITOREO_URL=https://riveraautomotriz.com
   RESPALDOS_DIR=/Users/carlosrivera/taller-respaldos
   RESPALDOS_LOG=/Users/carlosrivera/taller-local/produccion/logs/respaldo.log
   CLOUDFLARED_METRICS=127.0.0.1:20241
   ```
2. Tarea de estado cada minuto:
   ```sh
   cp scripts/launchd/com.rivera.taller.estado.plist ~/Library/LaunchAgents/
   launchctl bootstrap gui/$(id -u) ~/Library/LaunchAgents/com.rivera.taller.estado.plist
   ```
3. `brew services restart php` (recarga el `.env`).
4. Opcional: claves de UptimeRobot (solo lectura) y Clarity (Data Export) en Admin → Configuración → Monitoreo.

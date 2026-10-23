#!/usr/bin/env bash
# =====================================================================
# Prueba automática del flujo completo de la tienda (contra un sitio en marcha).
#
#   BASE=http://taller.test ADMIN_EMAIL=tu@correo ADMIN_CLAVE='...' bash tests/flujo-completo.sh
#
# Úsala SOLO en local o en un sitio de pruebas: crea pedidos reales en la base de datos
# (quedan como "Cancelado" al final). Requiere: curl, base64 y un producto visible con stock.
# Opcional: PRODUCTO_ID (por defecto 1) y TELEFONO (por defecto uno al azar 8xxx-xxxx).
# =====================================================================

set -u
BASE="${BASE:-http://taller.test}"
PRODUCTO_ID="${PRODUCTO_ID:-1}"
TELEFONO="${TELEFONO:-8$(printf '%03d' $((RANDOM % 1000)))-$(printf '%04d' $((RANDOM % 10000)))}"
: "${ADMIN_EMAIL:?Falta ADMIN_EMAIL (correo de un usuario dueño)}"
: "${ADMIN_CLAVE:?Falta ADMIN_CLAVE}"

TMP="$(mktemp -d)"; trap 'rm -rf "$TMP"' EXIT
CLIENTE="$TMP/cliente.txt"; DUENIO="$TMP/duenio.txt"
OK=0; FALLOS=0

bien()  { OK=$((OK + 1));         printf '  \033[32m✔\033[0m %s\n' "$1"; }
mal()   { FALLOS=$((FALLOS + 1)); printf '  \033[31m✘ %s\033[0m\n' "$1"; }
igual() { [ "$2" = "$3" ] && bien "$1" || mal "$1 (esperado: $3, obtenido: $2)"; }
contiene() { grep -q -- "$2" "$3" && bien "$1" || mal "$1 (no aparece: $2)"; }

codigo()   { curl -s -o "$TMP/r.html" -w '%{http_code}' "$@"; }
token()    { curl -s -c "$1" -b "$1" "$BASE/$2" | grep -o 'name="csrf_token" value="[^"]*' | head -1 | cut -d'"' -f4; }

# PNG válido de 1x1 píxel (comprobante de prueba) y un PHP disfrazado de imagen
echo 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==' | base64 -d > "$TMP/comprobante.png"
printf '<?php echo 1; ?>' > "$TMP/ataque.jpg"

echo "Probando $BASE (teléfono de prueba $TELEFONO)"

echo "1. Páginas públicas"
for ruta in "" tienda servicios contacto terminos privacidad carrito mi-pedido sitemap.xml robots.txt; do
    igual "/$ruta responde" "$(codigo "$BASE/$ruta")" 200
done
igual "página inexistente da 404" "$(codigo "$BASE/no-existe-$RANDOM")" 404
igual "/admin sin sesión redirige" "$(codigo "$BASE/admin")" 302

echo "2. Archivos protegidos"
for ruta in .env .htaccess uploads/.htaccess; do
    resultado="$(codigo "$BASE/$ruta")"; [ "$resultado" = 403 ] || [ "$resultado" = 404 ] && bien "/$ruta bloqueado ($resultado)" || mal "/$ruta NO está bloqueado ($resultado)"
done

echo "3. Checkout"
T="$(token "$CLIENTE" checkout)"
igual "sin token CSRF se rechaza" "$(codigo -X POST "$BASE/checkout" -d 'nombre_cliente=X')" 403
igual "datos inválidos se rechazan" "$(codigo -b "$CLIENTE" -c "$CLIENTE" -X POST "$BASE/checkout" -d "csrf_token=$T" -d 'nombre_cliente=A' -d 'telefono=1' -d 'carrito=[]')" 422
igual "sin aceptar los términos se rechaza" "$(codigo -b "$CLIENTE" -c "$CLIENTE" -X POST "$BASE/checkout" -d "csrf_token=$T" -d 'nombre_cliente=Sin Aceptar' -d "telefono=$TELEFONO" -d 'tipo_entrega=retiro' --data-urlencode "carrito=[{\"id\":$PRODUCTO_ID,\"cantidad\":1}]")" 422
igual "campo trampa (robot) se descarta" "$(codigo -b "$CLIENTE" -c "$CLIENTE" -X POST "$BASE/checkout" -d "csrf_token=$T" -d 'sitio_web=spam' -d 'nombre_cliente=Robot' -d "telefono=$TELEFONO" -d 'tipo_entrega=retiro' -d 'acepto=on' --data-urlencode "carrito=[{\"id\":$PRODUCTO_ID,\"cantidad\":1}]")" 302
resultado="$(curl -s -o /dev/null -w '%{redirect_url}' -b "$CLIENTE" -c "$CLIENTE" -X POST "$BASE/checkout" -d "csrf_token=$T" \
    -d 'nombre_cliente=Prueba Automatica' -d "telefono=$TELEFONO" -d 'tipo_entrega=retiro' -d 'acepto=on' \
    --data-urlencode "carrito=[{\"id\":$PRODUCTO_ID,\"cantidad\":1,\"precio\":1}]")"
case "$resultado" in */pedido-confirmado) bien "pedido creado";; *) mal "no se creó el pedido ($resultado)";; esac
codigo -b "$CLIENTE" "$BASE/pedido-confirmado" >/dev/null
NUMERO="$(grep -o '[A-Z0-9]\{2,5\}-20[0-9][0-9]-[0-9]\{4,\}' "$TMP/r.html" | head -1)"
[ -n "$NUMERO" ] && bien "número de pedido: $NUMERO" || { mal "no aparece el número de pedido"; exit 1; }
NUM_URL="$(echo "$NUMERO" | tr 'A-Z' 'a-z')"

echo "4. Seguimiento y comprobante"
igual "otra persona sin sesión no ve el pedido" "$(codigo "$BASE/mi-pedido/$NUM_URL")" 302
T="$(token "$CLIENTE" "mi-pedido/$NUM_URL")"
igual "PHP disfrazado de imagen se rechaza" "$(codigo -b "$CLIENTE" -X POST "$BASE/mi-pedido/$NUM_URL" -F "csrf_token=$T" -F "comprobante=@$TMP/ataque.jpg")" 422
igual "comprobante válido se acepta" "$(codigo -b "$CLIENTE" -c "$CLIENTE" -X POST "$BASE/mi-pedido/$NUM_URL" -F "csrf_token=$T" -F "comprobante=@$TMP/comprobante.png")" 302
codigo -b "$CLIENTE" "$BASE/mi-pedido/$NUM_URL" >/dev/null
contiene "el cliente ve 'Recibimos tu comprobante'" "Recibimos tu comprobante" "$TMP/r.html"
igual "no se puede subir un segundo comprobante" "$(codigo -b "$CLIENTE" -X POST "$BASE/mi-pedido/$NUM_URL" -F "csrf_token=$T" -F "comprobante=@$TMP/comprobante.png")" 422

echo "5. Panel: aprobación del pago"
T="$(token "$DUENIO" admin/login)"
igual "login del dueño" "$(codigo -b "$DUENIO" -c "$DUENIO" -X POST "$BASE/admin/login" --data-urlencode "csrf_token=$T" --data-urlencode "email=$ADMIN_EMAIL" --data-urlencode "clave=$ADMIN_CLAVE")" 302
T="$(token "$DUENIO" "admin/pedidos/$NUM_URL")"
accion() { curl -s -o /dev/null -b "$DUENIO" -c "$DUENIO" -X POST "$BASE/admin/pedidos/$NUM_URL" -d "csrf_token=$T" "$@"; codigo -b "$DUENIO" -c "$DUENIO" "$BASE/admin/pedidos/$NUM_URL" >/dev/null; }
accion -d accion=aprobar
contiene "sin la casilla 'verifiqué' NO se aprueba" "Marca la casilla" "$TMP/r.html"
accion -d accion=aprobar -d verifique=on
contiene "con la casilla se aprueba" "Pago aprobado" "$TMP/r.html"
accion -d accion=entregado
contiene "no se puede saltar a 'Entregado'" "No se puede hacer esto" "$TMP/r.html"
accion -d accion=preparacion
contiene "pasa a 'En preparación'" "marcado en preparación" "$TMP/r.html"
accion -d accion=cancelar -d 'motivo=Pedido de prueba automatica'
contiene "se cancela y devuelve el stock" "stock devuelto" "$TMP/r.html"

echo
echo "Resultado: $OK correctas, $FALLOS fallidas"
[ "$FALLOS" -eq 0 ]

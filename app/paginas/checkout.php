<?php
/**
 * Checkout: datos del cliente, tipo de entrega y creación del pedido.
 * GET muestra el formulario; POST valida (con CSRF) y crea el pedido en el servidor.
 * El total SIEMPRE se calcula en el servidor con precios de la base de datos.
 */

require_once RAIZ . '/app/modelos/pedidos.php';
require_once RAIZ . '/app/modelos/envios.php';

$meta['titulo'] = 'Confirmar pedido';
$meta['noindex'] = true;
$meta['fondo_claro'] = true;

$zonas = zonas_envio_activas();
$errores = [];
$campo = [
    'nombre_cliente' => '', 'telefono' => '', 'email' => '', 'tipo_entrega' => 'retiro',
    'zona_envio_id' => '', 'direccion' => '', 'notas' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    // Texto recibido: solo UTF-8 válido (si no, se descarta y la validación pide el dato de nuevo)
    $texto = function (string $clave, int $max): string {
        $valor = is_string($_POST[$clave] ?? null) ? $_POST[$clave] : '';
        return mb_check_encoding($valor, 'UTF-8') ? mb_substr(trim($valor), 0, $max) : '';
    };

    // Campo trampa: las personas no lo ven; los robots lo llenan
    if ($texto('sitio_web', 100) !== '') {
        redirigir('tienda');
    }

    $campo = [
        'nombre_cliente' => $texto('nombre_cliente', 120),
        'telefono'       => $texto('telefono', 20),
        'email'          => $texto('email', 150),
        'tipo_entrega'   => $texto('tipo_entrega', 10),
        'zona_envio_id'  => $texto('zona_envio_id', 10),
        'direccion'      => $texto('direccion', 500),
        'notas'          => $texto('notas', 500),
    ];

    $telefono = normalizar_telefono($campo['telefono']);

    if (mb_strlen($campo['nombre_cliente']) < 3) {
        $errores[] = 'Escribe tu nombre completo.';
    }
    if ($telefono === null) {
        $errores[] = 'Escribe un número de teléfono de 8 dígitos.';
    }
    if ($campo['email'] !== '' && !filter_var($campo['email'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El correo no es válido (o déjalo vacío).';
    }
    if (!in_array($campo['tipo_entrega'], ['retiro', 'envio'], true)) {
        $errores[] = 'Elige cómo quieres recibir tu pedido.';
    }
    if ($campo['tipo_entrega'] === 'envio') {
        if (!ctype_digit($campo['zona_envio_id'])) {
            $errores[] = 'Elige la zona de envío.';
        }
        if (mb_strlen($campo['direccion']) < 10) {
            $errores[] = 'Escribe la dirección exacta de entrega (con señas).';
        }
    }

    $carrito = normalizar_carrito(json_decode(is_string($_POST['carrito'] ?? null) ? $_POST['carrito'] : '[]', true));
    if (!$carrito) {
        $errores[] = 'Tu carrito está vacío.';
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    if (!$errores) {
        liberar_pedidos_vencidos();
        $limite = limite_pedidos_superado($telefono, $ip);
        if ($limite) {
            $errores[] = $limite;
        }
    }

    if (!$errores) {
        $resultado = crear_pedido([
            'nombre_cliente' => $campo['nombre_cliente'],
            'telefono'       => $telefono,
            'email'          => $campo['email'],
            'tipo_entrega'   => $campo['tipo_entrega'],
            'zona_envio_id'  => (int) $campo['zona_envio_id'],
            'direccion'      => $campo['direccion'],
            'notas'          => $campo['notas'],
            'utm_source'     => $texto('utm_source', 100),
            'utm_medium'     => $texto('utm_medium', 100),
            'utm_campaign'   => $texto('utm_campaign', 100),
            'ip'             => $ip,
        ], $carrito);

        if (isset($resultado['pedido'])) {
            iniciar_sesion();
            regenerar_sesion();
            $_SESSION['pedido_confirmado'] = (int) $resultado['pedido']['id'];
            autorizar_pedido((int) $resultado['pedido']['id']);
            redirigir('pedido-confirmado');
        }
        $errores = $resultado['errores'];
    }

    http_response_code(422);
}
?>
<div class="contenedor py-8 sm:py-10" data-checkout data-api="<?= e(url('api/carrito')) ?>">
    <a href="<?= e(url('carrito')) ?>" class="inline-flex items-center gap-1 text-sm font-medium text-neutral-600 hover:text-rivera-negro">
        <span class="rotate-180"><?= icono('flecha', 'h-4 w-4') ?></span> Volver al carrito
    </a>
    <h1 class="mt-3 font-display text-3xl font-extrabold">Confirmar <span class="text-rivera-rojo">pedido</span></h1>

    <noscript>
        <div class="mt-6"><?php componente('aviso', ['tipo' => 'error', 'mensaje' => 'Para hacer tu pedido necesitas activar JavaScript en tu navegador.']); ?></div>
    </noscript>

    <?php if ($errores): ?>
        <div class="aviso-error mt-6" role="alert">
            <span class="shrink-0"><?= icono('alerta', 'h-5 w-5') ?></span>
            <div>
                <p class="font-semibold">Revisa lo siguiente:</p>
                <ul class="mt-1 list-disc pl-5">
                    <?php foreach ($errores as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <!-- Carrito vacío -->
    <div class="tarjeta-clara mt-6 hidden px-6 py-14 text-center" data-checkout-vacio>
        <h2 class="text-xl">Tu carrito está vacío</h2>
        <p class="mt-2 text-sm text-neutral-600">Agrega productos antes de confirmar tu pedido.</p>
        <a href="<?= e(url('tienda')) ?>" class="btn-primario mt-6">Ir a la tienda</a>
    </div>

    <form method="post" action="<?= e(url('checkout')) ?>" class="mt-6 grid gap-8 lg:grid-cols-[1fr_24rem]" data-checkout-formulario novalidate>
        <?= csrf_campo() ?>
        <input type="hidden" name="carrito" value="" data-checkout-carrito>
        <input type="hidden" name="utm_source" data-utm="utm_source">
        <input type="hidden" name="utm_medium" data-utm="utm_medium">
        <input type="hidden" name="utm_campaign" data-utm="utm_campaign">
        <!-- Campo trampa para robots (oculto a las personas) -->
        <div class="absolute -left-[9999px]" aria-hidden="true">
            <label for="sitio_web">No llenar</label>
            <input id="sitio_web" type="text" name="sitio_web" tabindex="-1" autocomplete="off">
        </div>

        <div class="space-y-6">
            <!-- Datos de contacto -->
            <fieldset class="tarjeta-clara space-y-4 p-5 sm:p-6">
                <legend class="sr-only">Tus datos</legend>
                <h2 class="text-lg">1. Tus datos</h2>
                <div>
                    <label for="nombre_cliente" class="etiqueta-campo">Nombre completo</label>
                    <input id="nombre_cliente" name="nombre_cliente" class="campo-claro" required minlength="3" maxlength="120"
                           autocomplete="name" value="<?= e($campo['nombre_cliente']) ?>">
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="telefono" class="etiqueta-campo">Teléfono / WhatsApp</label>
                        <input id="telefono" name="telefono" type="tel" class="campo-claro" required maxlength="20"
                               inputmode="tel" autocomplete="tel" placeholder="8888-8888" value="<?= e($campo['telefono']) ?>">
                    </div>
                    <div>
                        <label for="email" class="etiqueta-campo">Correo <span class="font-normal text-neutral-500">(opcional)</span></label>
                        <input id="email" name="email" type="email" class="campo-claro" maxlength="150"
                               autocomplete="email" value="<?= e($campo['email']) ?>">
                    </div>
                </div>
            </fieldset>

            <!-- Entrega -->
            <fieldset class="tarjeta-clara space-y-4 p-5 sm:p-6">
                <legend class="sr-only">Entrega</legend>
                <h2 class="text-lg">2. ¿Cómo lo recibes?</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="flex cursor-pointer gap-3 rounded-lg border-2 border-neutral-200 p-4 has-[:checked]:border-rivera-rojo has-[:checked]:bg-red-50/40">
                        <input type="radio" name="tipo_entrega" value="retiro" class="mt-0.5 h-4 w-4 accent-rivera-rojo" data-entrega
                            <?= $campo['tipo_entrega'] !== 'envio' ? 'checked' : '' ?>>
                        <span>
                            <span class="block text-sm font-semibold">Retiro en el taller</span>
                            <span class="block text-xs text-neutral-600">Gratis · <?= e(ajuste('horario')) ?></span>
                        </span>
                    </label>
                    <label class="flex cursor-pointer gap-3 rounded-lg border-2 border-neutral-200 p-4 has-[:checked]:border-rivera-rojo has-[:checked]:bg-red-50/40 <?= $zonas ? '' : 'pointer-events-none opacity-50' ?>">
                        <input type="radio" name="tipo_entrega" value="envio" class="mt-0.5 h-4 w-4 accent-rivera-rojo" data-entrega
                            <?= $campo['tipo_entrega'] === 'envio' ? 'checked' : '' ?> <?= $zonas ? '' : 'disabled' ?>>
                        <span>
                            <span class="block text-sm font-semibold">Envío a domicilio</span>
                            <span class="block text-xs text-neutral-600">Solo GAM · entrega en <?= e(ajuste('envio_plazo', '48 horas')) ?></span>
                        </span>
                    </label>
                </div>

                <div class="space-y-4 <?= $campo['tipo_entrega'] === 'envio' ? '' : 'hidden' ?>" data-campos-envio>
                    <div>
                        <label for="zona_envio_id" class="etiqueta-campo">Zona de envío</label>
                        <select id="zona_envio_id" name="zona_envio_id" class="campo-claro" data-zona>
                            <option value="" data-tarifa="0">Elige tu zona…</option>
                            <?php foreach ($zonas as $zona): ?>
                                <option value="<?= (int) $zona['id'] ?>" data-tarifa="<?= (int) round((float) $zona['tarifa']) ?>"
                                    <?= $campo['zona_envio_id'] === (string) $zona['id'] ? 'selected' : '' ?>>
                                    <?= e($zona['nombre']) ?> — <?= colones($zona['tarifa']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="direccion" class="etiqueta-campo">Dirección exacta</label>
                        <textarea id="direccion" name="direccion" rows="3" class="campo-claro" maxlength="500"
                                  placeholder="Provincia, cantón, distrito y señas (ej. 100 m norte de la iglesia, casa blanca portón negro)"><?= e($campo['direccion']) ?></textarea>
                    </div>
                </div>

                <div>
                    <label for="notas" class="etiqueta-campo">Notas <span class="font-normal text-neutral-500">(opcional)</span></label>
                    <textarea id="notas" name="notas" rows="2" class="campo-claro" maxlength="500"
                              placeholder="Ej. modelo y año de tu auto, o algo que debamos saber"><?= e($campo['notas']) ?></textarea>
                </div>
            </fieldset>
        </div>

        <!-- Resumen -->
        <aside>
            <div class="tarjeta-clara p-5 lg:sticky lg:top-24">
                <h2 class="text-lg">3. Resumen</h2>
                <p class="mt-4 text-sm text-neutral-500" data-checkout-cargando>Cargando productos…</p>
                <ul class="mt-4 space-y-3 text-sm" data-checkout-lista></ul>
                <dl class="mt-4 space-y-2 border-t border-neutral-200 pt-4 text-sm">
                    <div class="flex justify-between"><dt class="text-neutral-600">Subtotal</dt><dd data-checkout-subtotal>—</dd></div>
                    <div class="flex justify-between"><dt class="text-neutral-600">Envío</dt><dd data-checkout-envio>Gratis</dd></div>
                    <div class="flex justify-between border-t border-neutral-200 pt-3 text-base font-bold">
                        <dt>Total a pagar</dt><dd class="font-display text-xl" data-checkout-total>—</dd>
                    </div>
                </dl>

                <div class="mt-4 rounded-md bg-neutral-50 p-3 text-xs text-neutral-600">
                    <p class="flex gap-2"><span class="shrink-0 text-rivera-rojo"><?= icono('celular', 'h-4 w-4') ?></span>
                        <span>Al confirmar verás los datos para pagar por <strong>SINPE Móvil o transferencia</strong>.
                        Tu pedido se prepara cuando verificamos el pago.</span></p>
                </div>

                <button type="submit" class="btn-primario mt-5 w-full text-base" data-checkout-enviar disabled>
                    Confirmar pedido
                </button>
                <p class="mt-3 text-center text-xs text-neutral-500">
                    Tienes <?= (int) ajuste('horas_expiracion_pedido', '48') ?> horas para pagar; después el pedido se cancela.
                </p>
            </div>
        </aside>
    </form>
</div>
<script src="<?= e(asset('js/checkout.js')) ?>" defer></script>

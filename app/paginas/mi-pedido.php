<?php
/**
 * Consulta de pedido: el cliente escribe número de pedido + teléfono.
 * Con 10 intentos fallidos desde la misma conexión se bloquea por 15 minutos.
 */

require_once RAIZ . '/app/modelos/pedidos.php';
require_once RAIZ . '/app/modelos/intentos.php';

$meta['titulo'] = 'Mi pedido';
$meta['noindex'] = true;
$meta['fondo_claro'] = true;

$error = null;
$numero = is_string($_GET['numero'] ?? null) ? strtoupper(mb_substr(trim($_GET['numero']), 0, 20)) : '';
$telefono = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    $numero = strtoupper(mb_substr(trim(is_string($_POST['numero'] ?? null) ? $_POST['numero'] : ''), 0, 20));
    $telefono = mb_substr(trim(is_string($_POST['telefono'] ?? null) ? $_POST['telefono'] : ''), 0, 20);
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    if (intentos_bloqueado('seguimiento', $ip)) {
        $error = 'Hiciste demasiados intentos. Espera ' . INTENTOS_MINUTOS_BLOQUEO . ' minutos e intenta de nuevo, o escríbenos por WhatsApp.';
    } else {
        $telefonoNormal = normalizar_telefono($telefono);
        $pedido = $telefonoNormal ? pedido_por_numero_y_telefono($numero, $telefonoNormal) : null;
        registrar_intento('seguimiento', $numero, $ip, $pedido !== null);

        if ($pedido) {
            autorizar_pedido((int) $pedido['id']);
            redirigir('mi-pedido/' . strtolower($pedido['numero_pedido']));
        }
        $error = 'No encontramos un pedido con esos datos. Revisa el número de pedido y el teléfono que usaste al comprar.';
    }

    http_response_code(422);
}
?>
<div class="contenedor max-w-md py-10 sm:py-14">
    <div class="text-center">
        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-white text-rivera-rojo shadow-sm"><?= icono('pedido', 'h-7 w-7') ?></span>
        <h1 class="mt-4 font-display text-3xl font-extrabold">Mi <span class="text-rivera-rojo">pedido</span></h1>
        <p class="mt-2 text-sm text-neutral-600">Consulta el estado de tu pedido y sube tu comprobante de pago.</p>
    </div>

    <?php if ($error): ?>
        <div class="mt-6"><?php componente('aviso', ['tipo' => 'error', 'mensaje' => $error]); ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('mi-pedido')) ?>" class="tarjeta-clara mt-6 space-y-4 p-5 sm:p-6">
        <?= csrf_campo() ?>
        <div>
            <label for="numero" class="etiqueta-campo">Número de pedido</label>
            <input id="numero" name="numero" class="campo-claro uppercase" required maxlength="20"
                   placeholder="RGA-2026-0001" autocomplete="off" value="<?= e($numero) ?>">
        </div>
        <div>
            <label for="telefono" class="etiqueta-campo">Teléfono que usaste al comprar</label>
            <input id="telefono" name="telefono" type="tel" class="campo-claro" required maxlength="20"
                   inputmode="tel" autocomplete="tel" placeholder="8888-8888" value="<?= e($telefono) ?>">
        </div>
        <button type="submit" class="btn-primario w-full text-base">Consultar pedido</button>
    </form>

    <p class="mt-6 text-center text-sm text-neutral-600">
        ¿No encuentras tu número de pedido?
        <a href="<?= e(whatsapp_url('Hola, necesito ayuda para encontrar mi pedido.')) ?>" target="_blank" rel="noopener" class="font-semibold text-rivera-rojo hover:underline">Escríbenos</a>
    </p>
</div>

<?php
/**
 * Ingreso al panel de administración.
 */

$meta['titulo'] = 'Ingresar';
$meta['layout'] = 'admin/layout';
header('Cache-Control: no-store, private');

// A dónde volver después de ingresar (solo rutas del panel)
$volver = is_string($_GET['volver'] ?? null) ? $_GET['volver'] : '';
if (!preg_match('#^admin(/[a-z0-9/-]*)?$#', $volver) || $volver === 'admin/login' || $volver === 'admin/salir') {
    $volver = 'admin';
}

if (usuario_actual()) {
    redirigir($volver);
}

$error = null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $email = mb_substr(is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '', 0, 150);
    $clave = is_string($_POST['clave'] ?? null) ? $_POST['clave'] : '';

    $error = iniciar_sesion_admin($email, $clave, $_SERVER['REMOTE_ADDR'] ?? '');
    if ($error === null) {
        redirigir($volver);
    }
    http_response_code(422);
}

$avisos = tomar_avisos();
?>
<div class="flex min-h-screen items-center justify-center bg-rivera-negro px-4 py-12">
    <div class="w-full max-w-sm">
        <div class="mb-8 flex items-center justify-center gap-3 text-white">
            <img src="<?= e(asset('img/logo.webp')) ?>" alt="" width="48" height="48" class="h-12 w-12">
            <span class="leading-none">
                <img src="<?= e(asset('img/rivera-texto.webp')) ?>" alt="Rivera" width="203" height="22" class="block h-[22px] w-auto">
                <span class="mt-1 block text-[10px] font-semibold uppercase tracking-[0.25em] text-neutral-400">Panel de administración</span>
            </span>
        </div>

        <form method="post" action="<?= e(url('admin/login?volver=' . rawurlencode($volver))) ?>" class="tarjeta-clara space-y-4 p-6">
            <?= csrf_campo() ?>
            <h1 class="text-xl">Ingresar</h1>

            <?php foreach ($avisos as $aviso) { componente('aviso', $aviso); } ?>
            <?php if ($error): ?>
                <?php componente('aviso', ['tipo' => 'error', 'mensaje' => $error]); ?>
            <?php endif; ?>

            <div>
                <label for="email" class="etiqueta-campo">Correo</label>
                <input id="email" name="email" type="email" class="campo-claro" required autocomplete="username" autofocus value="<?= e($email) ?>">
            </div>
            <div>
                <label for="clave" class="etiqueta-campo">Contraseña</label>
                <input id="clave" name="clave" type="password" class="campo-claro" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn-primario w-full text-base">Ingresar</button>
        </form>

        <p class="mt-6 text-center text-sm text-neutral-400">
            <a href="<?= e(url()) ?>" class="hover:text-white">← Volver a la tienda</a>
        </p>
    </div>
</div>

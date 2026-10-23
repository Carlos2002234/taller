<?php
/**
 * Mi cuenta: cambiar la propia contraseña.
 */

$usuario = requerir_admin();
$meta['titulo'] = 'Mi cuenta';
$meta['layout'] = 'admin/layout';

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $actual = is_string($_POST['actual'] ?? null) ? $_POST['actual'] : '';
    $nueva = is_string($_POST['nueva'] ?? null) ? $_POST['nueva'] : '';
    $confirmar = is_string($_POST['confirmar'] ?? null) ? $_POST['confirmar'] : '';

    if (!usuario_verificar_clave((int) $usuario['id'], $actual)) {
        $errores[] = 'La contraseña actual no es correcta.';
    }
    if (mb_strlen($nueva) < CLAVE_MINIMA) {
        $errores[] = 'La nueva contraseña debe tener al menos ' . CLAVE_MINIMA . ' caracteres.';
    } elseif ($nueva !== $confirmar) {
        $errores[] = 'La confirmación no coincide con la nueva contraseña.';
    } elseif ($nueva === $actual) {
        $errores[] = 'La nueva contraseña debe ser distinta de la actual.';
    }

    if (!$errores) {
        usuario_cambiar_clave((int) $usuario['id'], $nueva);
        regenerar_sesion();
        avisar('exito', 'Tu contraseña se cambió.');
        redirigir('admin/cuenta');
    }
    http_response_code(422);
}
?>
<h1 class="font-display text-2xl font-extrabold sm:text-3xl">Mi cuenta</h1>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <div class="tarjeta-clara p-5 sm:p-6">
        <h2 class="text-lg">Tus datos</h2>
        <dl class="mt-4 space-y-2 text-sm">
            <div><dt class="text-neutral-500">Nombre</dt><dd class="font-medium"><?= e($usuario['nombre']) ?></dd></div>
            <div><dt class="text-neutral-500">Correo</dt><dd class="font-medium"><?= e($usuario['email']) ?></dd></div>
            <div><dt class="text-neutral-500">Rol</dt><dd class="font-medium"><?= e(ROLES[$usuario['rol']]) ?></dd></div>
        </dl>
    </div>

    <form method="post" action="<?= e(url('admin/cuenta')) ?>" class="tarjeta-clara space-y-4 p-5 sm:p-6">
        <?= csrf_campo() ?>
        <h2 class="text-lg">Cambiar contraseña</h2>
        <?php if ($errores): ?>
            <?php componente('aviso', ['tipo' => 'error', 'mensaje' => implode(' ', $errores)]); ?>
        <?php endif; ?>
        <div>
            <label for="actual" class="etiqueta-campo">Contraseña actual</label>
            <input id="actual" name="actual" type="password" class="campo-claro" required autocomplete="current-password">
        </div>
        <div>
            <label for="nueva" class="etiqueta-campo">Nueva contraseña <span class="font-normal text-neutral-500">(mínimo <?= CLAVE_MINIMA ?> caracteres)</span></label>
            <input id="nueva" name="nueva" type="password" class="campo-claro" required minlength="<?= CLAVE_MINIMA ?>" autocomplete="new-password">
        </div>
        <div>
            <label for="confirmar" class="etiqueta-campo">Repite la nueva contraseña</label>
            <input id="confirmar" name="confirmar" type="password" class="campo-claro" required minlength="<?= CLAVE_MINIMA ?>" autocomplete="new-password">
        </div>
        <button type="submit" class="btn-primario">Guardar contraseña</button>
    </form>
</div>

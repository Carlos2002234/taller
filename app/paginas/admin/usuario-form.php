<?php
/**
 * Crear o editar un usuario del panel (solo el dueño).
 * Reglas: siempre debe quedar al menos un dueño activo, y nadie puede desactivarse
 * ni quitarse el rol de dueño a sí mismo (para no quedarse fuera del panel).
 */

$yo = requerir_admin('duenio');
$meta['layout'] = 'admin/layout';

$id = null;
if (isset($parametros['id'])) {
    $id = ctype_digit($parametros['id']) ? (int) $parametros['id'] : no_encontrado();
}
$usuario = $id ? (usuario_por_id($id) ?? no_encontrado()) : null;
$esYo = $usuario && (int) $usuario['id'] === (int) $yo['id'];
$meta['titulo'] = $usuario ? $usuario['nombre'] : 'Nuevo usuario';

$campo = [
    'nombre' => $usuario['nombre'] ?? '',
    'email'  => $usuario['email'] ?? '',
    'rol'    => $usuario['rol'] ?? 'empleado',
    'activo' => (bool) ($usuario['activo'] ?? true),
];
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $texto = fn (string $clave, int $max): string =>
        is_string($_POST[$clave] ?? null) && mb_check_encoding($_POST[$clave], 'UTF-8') ? mb_substr(trim($_POST[$clave]), 0, $max) : '';

    $campo = [
        'nombre' => $texto('nombre', 100),
        'email'  => strtolower($texto('email', 150)),
        'rol'    => $texto('rol', 10),
        'activo' => isset($_POST['activo']),
    ];
    $clave = is_string($_POST['clave'] ?? null) ? $_POST['clave'] : '';

    if (mb_strlen($campo['nombre']) < 2) {
        $errores[] = 'Escribe el nombre.';
    }
    if (!filter_var($campo['email'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El correo no es válido.';
    } elseif (usuario_email_existe($campo['email'], $id)) {
        $errores[] = 'Ya existe un usuario con ese correo.';
    }
    if (!isset(ROLES[$campo['rol']])) {
        $errores[] = 'Elige un rol.';
    }
    if (($usuario === null || $clave !== '') && mb_strlen($clave) < CLAVE_MINIMA) {
        $errores[] = 'La contraseña debe tener al menos ' . CLAVE_MINIMA . ' caracteres.';
    }
    if ($esYo && (!$campo['activo'] || $campo['rol'] !== 'duenio')) {
        $errores[] = 'No puedes desactivarte ni quitarte el rol de dueño a ti mismo.';
    }
    if ($usuario && $usuario['rol'] === 'duenio' && ($campo['rol'] !== 'duenio' || !$campo['activo']) && duenios_activos($id) === 0) {
        $errores[] = 'Debe quedar al menos un dueño activo.';
    }

    if (!$errores) {
        $nuevoId = usuario_guardar($id, $campo['nombre'], $campo['email'], $campo['rol'], $campo['activo'], $clave);
        avisar('exito', $id ? 'Cambios guardados.' : 'Usuario creado. Compártele su correo y contraseña de forma privada.');
        redirigir('admin/usuarios/' . $nuevoId);
    }
    http_response_code(422);
}
?>
<a href="<?= e(url('admin/usuarios')) ?>" class="inline-flex items-center gap-1 text-sm font-medium text-neutral-600 hover:text-rivera-negro">
    <span class="rotate-180"><?= icono('flecha', 'h-4 w-4') ?></span> Usuarios
</a>
<h1 class="mt-2 font-display text-2xl font-extrabold sm:text-3xl"><?= e($usuario ? 'Editar usuario' : 'Nuevo usuario') ?></h1>

<?php if ($errores): ?>
    <div class="mt-5"><?php componente('aviso', ['tipo' => 'error', 'mensaje' => implode(' ', $errores)]); ?></div>
<?php endif; ?>

<form method="post" action="<?= e(url($id ? 'admin/usuarios/' . $id : 'admin/usuarios/nuevo')) ?>" class="tarjeta-clara mt-5 max-w-xl space-y-4 p-5 sm:p-6">
    <?= csrf_campo() ?>
    <div>
        <label for="nombre" class="etiqueta-campo">Nombre</label>
        <input id="nombre" name="nombre" class="campo-claro" required maxlength="100" value="<?= e($campo['nombre']) ?>">
    </div>
    <div>
        <label for="email" class="etiqueta-campo">Correo (para ingresar)</label>
        <input id="email" name="email" type="email" class="campo-claro" required maxlength="150" autocomplete="off" value="<?= e($campo['email']) ?>">
    </div>
    <fieldset>
        <legend class="etiqueta-campo">Rol</legend>
        <div class="grid gap-2 sm:grid-cols-2">
            <?php foreach (['empleado' => 'Ve y atiende pedidos', 'duenio' => 'Todo, incluido aprobar pagos'] as $rol => $descripcion): ?>
                <label class="flex cursor-pointer gap-3 rounded-lg border-2 border-neutral-200 p-3 has-[:checked]:border-rivera-rojo <?= $esYo ? 'pointer-events-none opacity-60' : '' ?>">
                    <input type="radio" name="rol" value="<?= $rol ?>" class="mt-0.5 h-4 w-4 accent-rivera-rojo" <?= $campo['rol'] === $rol ? 'checked' : '' ?>>
                    <span><span class="block text-sm font-semibold"><?= e(ROLES[$rol]) ?></span><span class="block text-xs text-neutral-500"><?= e($descripcion) ?></span></span>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>
    <div>
        <label for="clave" class="etiqueta-campo">
            <?= $usuario ? 'Nueva contraseña <span class="font-normal text-neutral-500">(déjala vacía para no cambiarla)</span>' : 'Contraseña' ?>
        </label>
        <input id="clave" name="clave" type="password" class="campo-claro" minlength="<?= CLAVE_MINIMA ?>" autocomplete="new-password" <?= $usuario ? '' : 'required' ?>>
        <p class="mt-1 text-xs text-neutral-500">Mínimo <?= CLAVE_MINIMA ?> caracteres.</p>
    </div>
    <label class="flex cursor-pointer items-center gap-3 text-sm <?= $esYo ? 'pointer-events-none opacity-60' : '' ?>">
        <input type="checkbox" name="activo" class="h-4 w-4 accent-rivera-rojo" <?= $campo['activo'] ? 'checked' : '' ?>>
        <span><span class="font-semibold">Activo</span> <span class="text-neutral-500">— si lo desmarcas, no podrá ingresar.</span></span>
    </label>
    <button type="submit" class="btn-primario"><?= $usuario ? 'Guardar cambios' : 'Crear usuario' ?></button>
</form>

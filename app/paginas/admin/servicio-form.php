<?php
/**
 * Crear, editar o borrar un servicio del taller (solo el dueño).
 */

require_once RAIZ . '/app/modelos/servicios.php';

requerir_admin('duenio');
$meta['layout'] = 'admin/layout';

$id = null;
if (isset($parametros['id'])) {
    $id = ctype_digit($parametros['id']) ? (int) $parametros['id'] : no_encontrado();
}
$servicio = $id ? (servicio_por_id($id) ?? no_encontrado()) : null;
$meta['titulo'] = $servicio ? $servicio['nombre'] : 'Nuevo servicio';

$campo = [
    'nombre'      => $servicio['nombre'] ?? '',
    'descripcion' => $servicio['descripcion'] ?? '',
    'icono'       => $servicio['icono'] ?? 'llave',
    'orden'       => (string) ($servicio['orden'] ?? '0'),
    'activo'      => (bool) ($servicio['activo'] ?? true),
    'en_inicio'   => (bool) ($servicio['en_inicio'] ?? true),
];
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $texto = fn (string $clave, int $max): string =>
        is_string($_POST[$clave] ?? null) && mb_check_encoding($_POST[$clave], 'UTF-8') ? mb_substr(trim($_POST[$clave]), 0, $max) : '';

    if ($id && $texto('accion', 10) === 'borrar') {
        servicio_borrar($id);
        avisar('exito', '«' . $servicio['nombre'] . '» se borró.');
        redirigir('admin/servicios');
    }

    $campo = [
        'nombre'      => $texto('nombre', 100),
        'descripcion' => $texto('descripcion', 300),
        'icono'       => $texto('icono', 30),
        'orden'       => $texto('orden', 4),
        'activo'      => isset($_POST['activo']),
        'en_inicio'   => isset($_POST['en_inicio']),
    ];
    if (mb_strlen($campo['nombre']) < 2) {
        $errores[] = 'Escribe el nombre del servicio.';
    }
    if (!isset(ICONOS_SERVICIO[$campo['icono']])) {
        $errores[] = 'Elige un ícono.';
    }
    if (!preg_match('/^\d{1,4}$/', $campo['orden'])) {
        $errores[] = 'El orden debe ser un número (0 = primero).';
    }

    if (!$errores) {
        $nuevoId = servicio_guardar($id, $campo['nombre'], $campo['descripcion'], $campo['icono'], (int) $campo['orden'], $campo['activo'], $campo['en_inicio']);
        avisar('exito', $id ? 'Cambios guardados.' : 'Servicio creado.');
        redirigir('admin/servicios/' . $nuevoId);
    }
    http_response_code(422);
}
?>
<a href="<?= e(url('admin/servicios')) ?>" class="inline-flex items-center gap-1 text-sm font-medium text-neutral-600 hover:text-rivera-negro">
    <span class="rotate-180"><?= icono('flecha', 'h-4 w-4') ?></span> Servicios
</a>
<h1 class="mt-2 font-display text-2xl font-extrabold sm:text-3xl"><?= e($servicio ? 'Editar servicio' : 'Nuevo servicio') ?></h1>

<?php if ($errores): ?>
    <div class="mt-5"><?php componente('aviso', ['tipo' => 'error', 'mensaje' => implode(' ', $errores)]); ?></div>
<?php endif; ?>

<form method="post" action="<?= e(url($id ? 'admin/servicios/' . $id : 'admin/servicios/nuevo')) ?>" class="tarjeta-clara mt-5 max-w-2xl space-y-4 p-5 sm:p-6">
    <?= csrf_campo() ?>
    <div>
        <label for="nombre" class="etiqueta-campo">Nombre</label>
        <input id="nombre" name="nombre" class="campo-claro" required maxlength="100" value="<?= e($campo['nombre']) ?>" placeholder="Ej. Alineado y balanceo">
    </div>
    <div>
        <label for="descripcion" class="etiqueta-campo">Descripción corta <span class="font-normal text-neutral-500">(máx. 300 caracteres)</span></label>
        <textarea id="descripcion" name="descripcion" rows="3" maxlength="300" class="campo-claro"><?= e($campo['descripcion']) ?></textarea>
    </div>
    <fieldset>
        <legend class="etiqueta-campo">Ícono</legend>
        <div class="grid grid-cols-3 gap-2 sm:grid-cols-5">
            <?php foreach (ICONOS_SERVICIO as $clave => $nombre): ?>
                <label class="flex cursor-pointer flex-col items-center gap-1 rounded-lg border-2 border-neutral-200 p-3 text-center text-xs has-[:checked]:border-rivera-rojo has-[:checked]:bg-red-50/40">
                    <input type="radio" name="icono" value="<?= e($clave) ?>" class="sr-only" <?= $campo['icono'] === $clave ? 'checked' : '' ?>>
                    <span class="text-rivera-rojo"><?= icono($clave, 'h-7 w-7') ?></span>
                    <?= e($nombre) ?>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>
    <div class="grid gap-4 sm:grid-cols-3">
        <div>
            <label for="orden" class="etiqueta-campo">Orden</label>
            <input id="orden" name="orden" type="number" min="0" max="9999" class="campo-claro" value="<?= e($campo['orden']) ?>">
        </div>
        <label class="flex cursor-pointer items-center gap-3 self-end pb-2.5 text-sm">
            <input type="checkbox" name="activo" class="h-4 w-4 accent-rivera-rojo" <?= $campo['activo'] ? 'checked' : '' ?>>
            <span class="font-semibold">Visible</span>
        </label>
        <label class="flex cursor-pointer items-center gap-3 self-end pb-2.5 text-sm">
            <input type="checkbox" name="en_inicio" class="h-4 w-4 accent-rivera-rojo" <?= $campo['en_inicio'] ? 'checked' : '' ?>>
            <span class="font-semibold">Mostrar en inicio</span>
        </label>
    </div>
    <button type="submit" class="btn-primario"><?= $servicio ? 'Guardar cambios' : 'Crear servicio' ?></button>
</form>

<?php if ($servicio): ?>
    <form method="post" action="<?= e(url('admin/servicios/' . $id)) ?>" class="mt-5 max-w-2xl rounded-lg border border-red-200 bg-white p-5" data-confirmar="¿Borrar «<?= e($servicio['nombre']) ?>»?">
        <?= csrf_campo() ?>
        <input type="hidden" name="accion" value="borrar">
        <p class="text-sm text-neutral-600">Si solo quieres dejar de mostrarlo, desmarca "Visible".</p>
        <button type="submit" class="btn btn-pequeno mt-3 border border-rivera-rojo text-rivera-rojo hover:bg-rivera-rojo hover:text-white">Borrar servicio</button>
    </form>
<?php endif; ?>

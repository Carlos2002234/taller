<?php
/**
 * Crear o editar una categoría (nombre, orden, visible y foto de la tarjeta) y borrarla.
 */

require_once RAIZ . '/app/modelos/categorias-admin.php';

requerir_admin('duenio');
$meta['layout'] = 'admin/layout';

$id = null;
if (isset($parametros['id'])) {
    $id = ctype_digit($parametros['id']) ? (int) $parametros['id'] : no_encontrado();
}
$categoria = $id ? (categoria_admin_por_id($id) ?? no_encontrado()) : null;
$meta['titulo'] = $categoria ? $categoria['nombre'] : 'Nueva categoría';

$campo = [
    'nombre' => $categoria['nombre'] ?? '',
    'orden'  => (string) ($categoria['orden'] ?? '0'),
    'activa' => (bool) ($categoria['activa'] ?? true),
];
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $errores[] = 'La foto pesa demasiado.';
    } else {
        verificar_csrf();
        $accion = is_string($_POST['accion'] ?? null) ? $_POST['accion'] : '';

        if ($id && $accion === 'quitar_foto') {
            categoria_quitar_imagen($id);
            avisar('exito', 'Foto quitada.');
            redirigir('admin/categorias/' . $id);
        }
        if ($id && $accion === 'borrar') {
            $error = categoria_borrar($id);
            avisar($error ? 'error' : 'exito', $error ?? '«' . $categoria['nombre'] . '» se borró.');
            redirigir($error ? 'admin/categorias/' . $id : 'admin/categorias');
        }

        if ($accion === 'guardar') {
            $nombre = is_string($_POST['nombre'] ?? null) && mb_check_encoding($_POST['nombre'], 'UTF-8')
                ? mb_substr(trim($_POST['nombre']), 0, 100) : '';
            $campo = [
                'nombre' => $nombre,
                'orden'  => is_string($_POST['orden'] ?? null) ? trim($_POST['orden']) : '0',
                'activa' => isset($_POST['activa']),
            ];

            if (mb_strlen($campo['nombre']) < 2) {
                $errores[] = 'Escribe el nombre de la categoría.';
            }
            if (!preg_match('/^\d{1,4}$/', $campo['orden'])) {
                $errores[] = 'El orden debe ser un número (0 = primero).';
            }

            if (!$errores) {
                $nuevoId = categoria_guardar($id, $campo['nombre'], (int) $campo['orden'], $campo['activa']);
                avisar('exito', $id ? 'Cambios guardados.' : 'Categoría creada.');
                $archivo = $_FILES['imagen'] ?? null;
                if ($archivo && ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    $errorFoto = categoria_cambiar_imagen($nuevoId, $archivo);
                    if ($errorFoto) {
                        avisar('error', $errorFoto);
                    }
                }
                redirigir('admin/categorias/' . $nuevoId);
            }
        }
    }
    http_response_code(422);
}
?>
<a href="<?= e(url('admin/categorias')) ?>" class="inline-flex items-center gap-1 text-sm font-medium text-neutral-600 hover:text-rivera-negro">
    <span class="rotate-180"><?= icono('flecha', 'h-4 w-4') ?></span> Categorías
</a>
<h1 class="mt-2 font-display text-2xl font-extrabold sm:text-3xl"><?= e($categoria ? 'Editar categoría' : 'Nueva categoría') ?></h1>

<?php if ($errores): ?>
    <div class="mt-5"><?php componente('aviso', ['tipo' => 'error', 'mensaje' => implode(' ', $errores)]); ?></div>
<?php endif; ?>

<div class="mt-5 grid gap-5 lg:grid-cols-[1fr_20rem]">
    <form method="post" action="<?= e(url($id ? 'admin/categorias/' . $id : 'admin/categorias/nueva')) ?>" enctype="multipart/form-data"
          class="tarjeta-clara space-y-4 p-5 sm:p-6" data-fotos data-espacio="1">
        <?= csrf_campo() ?>
        <input type="hidden" name="accion" value="guardar">
        <div>
            <label for="nombre" class="etiqueta-campo">Nombre</label>
            <input id="nombre" name="nombre" class="campo-claro" required maxlength="100" value="<?= e($campo['nombre']) ?>">
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="orden" class="etiqueta-campo">Orden <span class="font-normal text-neutral-500">(0 = primero)</span></label>
                <input id="orden" name="orden" type="number" min="0" max="9999" class="campo-claro" value="<?= e($campo['orden']) ?>">
            </div>
            <label class="flex cursor-pointer items-center gap-3 self-end pb-2.5 text-sm">
                <input type="checkbox" name="activa" class="h-4 w-4 accent-rivera-rojo" <?= $campo['activa'] ? 'checked' : '' ?>>
                <span class="font-semibold">Visible en la tienda</span>
            </label>
        </div>
        <div>
            <label for="imagen" class="etiqueta-campo">Foto de la tarjeta <span class="font-normal text-neutral-500">(opcional · JPG, PNG o WebP · hasta 10 MB)</span></label>
            <input id="imagen" name="imagen" type="file" accept="image/jpeg,image/png,image/webp"
                   class="block w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-neutral-100 file:px-3 file:py-2 file:font-semibold">
        </div>
        <button type="submit" class="btn-primario"><?= $categoria ? 'Guardar cambios' : 'Crear categoría' ?></button>
    </form>

    <?php if ($categoria): ?>
        <div class="space-y-5">
            <?php if ($categoria['imagen']): ?>
                <div class="tarjeta-clara p-5">
                    <h2 class="text-base">Foto actual</h2>
                    <img src="<?= e(url_foto($categoria['imagen'], true)) ?>" alt="" class="mt-3 aspect-[4/3] w-full rounded-md object-cover">
                    <form method="post" action="<?= e(url('admin/categorias/' . $id)) ?>" class="mt-3" data-confirmar="¿Quitar la foto?">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="accion" value="quitar_foto">
                        <button type="submit" class="text-sm font-medium text-rivera-rojo hover:underline">Quitar foto</button>
                    </form>
                </div>
            <?php endif; ?>

            <div class="rounded-lg border border-red-200 bg-white p-5">
                <h2 class="text-base text-rivera-rojo">Borrar categoría</h2>
                <p class="mt-1 text-sm text-neutral-600">Solo se puede si no tiene productos.</p>
                <form method="post" action="<?= e(url('admin/categorias/' . $id)) ?>" class="mt-3" data-confirmar="¿Borrar «<?= e($categoria['nombre']) ?>»?">
                    <?= csrf_campo() ?>
                    <input type="hidden" name="accion" value="borrar">
                    <button type="submit" class="btn btn-pequeno border border-rivera-rojo text-rivera-rojo hover:bg-rivera-rojo hover:text-white">Borrar</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

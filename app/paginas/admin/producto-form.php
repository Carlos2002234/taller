<?php
/**
 * Crear o editar un producto, administrar sus fotos (hasta 5) y borrarlo.
 * Recibe $parametros['id'] al editar.
 */

require_once RAIZ . '/app/modelos/productos-admin.php';
require_once RAIZ . '/app/modelos/categorias-admin.php';

requerir_admin('duenio');
$meta['layout'] = 'admin/layout';

$id = null;
if (isset($parametros['id'])) {
    $id = ctype_digit($parametros['id']) ? (int) $parametros['id'] : no_encontrado();
}
$producto = $id ? (producto_admin_por_id($id) ?? no_encontrado()) : null;
$meta['titulo'] = $producto ? $producto['nombre'] : 'Nuevo producto';

/**
 * Monto en colones sin decimales. Acepta "28000", "28.000", "28,000" o "28 000".
 */
function leer_monto(string $texto): ?int
{
    $texto = trim($texto);
    if (preg_match('/^\d+$/', $texto) || preg_match('/^\d{1,3}([.,\s]\d{3})+$/', $texto)) {
        return (int) preg_replace('/\D/', '', $texto);
    }
    return null;
}

$campo = [
    'nombre'          => $producto['nombre'] ?? '',
    'category_id'     => (string) ($producto['category_id'] ?? ''),
    'marca'           => $producto['marca'] ?? '',
    'descripcion'     => $producto['descripcion'] ?? '',
    'precio'          => $producto ? (string) (int) $producto['precio'] : '',
    'precio_anterior' => $producto && $producto['precio_anterior'] !== null ? (string) (int) $producto['precio_anterior'] : '',
    'stock'           => (string) ($producto['stock'] ?? '0'),
    'destacado'       => (bool) ($producto['destacado'] ?? false),
    'activo'          => (bool) ($producto['activo'] ?? true),
];
$errores = [];
$volverAqui = fn (int $productoId) => redirigir('admin/productos/' . $productoId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $errores[] = 'Las fotos pesan demasiado en total. Súbelas de una o dos a la vez.';
    } else {
        verificar_csrf();
        $accion = is_string($_POST['accion'] ?? null) ? $_POST['accion'] : '';
        $imagenId = (int) ($_POST['imagen_id'] ?? 0);

        if ($id && $accion === 'mover_foto') {
            producto_mover_foto($id, $imagenId, ($_POST['direccion'] ?? '') === 'antes' ? -1 : 1);
            $volverAqui($id);
        }
        if ($id && $accion === 'borrar_foto') {
            producto_borrar_foto($id, $imagenId);
            avisar('exito', 'Foto borrada.');
            $volverAqui($id);
        }
        if ($id && $accion === 'borrar') {
            $abiertos = producto_pedidos_abiertos($id);
            if ($abiertos > 0) {
                avisar('error', "No se puede borrar: está en {$abiertos} pedido(s) sin terminar. Ocúltalo para que no se venda más.");
                $volverAqui($id);
            }
            producto_borrar($id);
            avisar('exito', '«' . $producto['nombre'] . '» se borró.');
            redirigir('admin/productos');
        }

        if ($accion === 'guardar') {
            $texto = function (string $clave, int $max): string {
                $valor = is_string($_POST[$clave] ?? null) ? $_POST[$clave] : '';
                return mb_check_encoding($valor, 'UTF-8') ? mb_substr(trim($valor), 0, $max) : '';
            };
            $campo = [
                'nombre'          => $texto('nombre', 150),
                'category_id'     => $texto('category_id', 10),
                'marca'           => $texto('marca', 80),
                'descripcion'     => $texto('descripcion', 5000),
                'precio'          => $texto('precio', 20),
                'precio_anterior' => $texto('precio_anterior', 20),
                'stock'           => $texto('stock', 10),
                'destacado'       => isset($_POST['destacado']),
                'activo'          => isset($_POST['activo']),
            ];

            $precio = leer_monto($campo['precio']);
            $precioAnterior = $campo['precio_anterior'] === '' ? null : leer_monto($campo['precio_anterior']);

            if (mb_strlen($campo['nombre']) < 2) {
                $errores[] = 'Escribe el nombre del producto.';
            }
            if (!ctype_digit($campo['category_id']) || !categoria_admin_por_id((int) $campo['category_id'])) {
                $errores[] = 'Elige una categoría.';
            }
            if ($precio === null || $precio < 1) {
                $errores[] = 'Escribe el precio en colones, sin decimales (ej. 28000).';
            }
            if ($campo['precio_anterior'] !== '' && $precioAnterior === null) {
                $errores[] = 'El precio anterior debe ser un monto en colones, sin decimales.';
            } elseif ($precioAnterior !== null && $precio !== null && $precioAnterior <= $precio) {
                $errores[] = 'El precio anterior (oferta) debe ser mayor que el precio actual. Déjalo vacío si no hay oferta.';
            }
            if (!ctype_digit($campo['stock']) || (int) $campo['stock'] > 99999) {
                $errores[] = 'El stock debe ser un número entero (0 o más).';
            }

            if (!$errores) {
                $nuevoId = producto_guardar($id, [
                    'category_id'     => (int) $campo['category_id'],
                    'nombre'          => $campo['nombre'],
                    'marca'           => $campo['marca'],
                    'descripcion'     => $campo['descripcion'],
                    'precio'          => $precio,
                    'precio_anterior' => $precioAnterior,
                    'stock'           => (int) $campo['stock'],
                    'destacado'       => $campo['destacado'],
                    'activo'          => $campo['activo'],
                ]);
                avisar('exito', $id ? 'Cambios guardados.' : 'Producto creado.');
                foreach (producto_agregar_fotos($nuevoId, archivos_subidos($_FILES['fotos'] ?? [])) as $errorFoto) {
                    avisar('error', $errorFoto);
                }
                $volverAqui($nuevoId);
            }
        }
    }
    http_response_code(422);
}

$categorias = categorias_admin_listar();
$fotos = $id ? producto_admin_imagenes($id) : [];
$marcas = db()->query("SELECT DISTINCT marca FROM products WHERE marca IS NOT NULL AND marca <> '' ORDER BY marca")->fetchAll(PDO::FETCH_COLUMN);
$espacioFotos = PRODUCTO_MAX_FOTOS - count($fotos);
?>
<a href="<?= e(url('admin/productos')) ?>" class="inline-flex items-center gap-1 text-sm font-medium text-neutral-600 hover:text-rivera-negro">
    <span class="rotate-180"><?= icono('flecha', 'h-4 w-4') ?></span> Productos
</a>
<div class="mt-2 flex flex-wrap items-center justify-between gap-3">
    <h1 class="font-display text-2xl font-extrabold sm:text-3xl"><?= e($producto ? 'Editar producto' : 'Nuevo producto') ?></h1>
    <?php if ($producto && $producto['activo']): ?>
        <a href="<?= e(url('producto/' . $producto['slug'])) ?>" target="_blank" rel="noopener" class="text-sm font-medium text-rivera-rojo hover:underline">Ver en la tienda ↗</a>
    <?php endif; ?>
</div>

<?php if ($errores): ?>
    <div class="mt-5"><?php componente('aviso', ['tipo' => 'error', 'mensaje' => implode(' ', $errores)]); ?></div>
<?php endif; ?>

<form method="post" action="<?= e(url($id ? 'admin/productos/' . $id : 'admin/productos/nuevo')) ?>" enctype="multipart/form-data"
      class="mt-5 grid gap-5 lg:grid-cols-[1fr_20rem]">
    <?= csrf_campo() ?>
    <input type="hidden" name="accion" value="guardar">

    <div class="tarjeta-clara space-y-4 p-5 sm:p-6">
        <div>
            <label for="nombre" class="etiqueta-campo">Nombre</label>
            <input id="nombre" name="nombre" class="campo-claro" required maxlength="150" value="<?= e($campo['nombre']) ?>"
                   placeholder="Ej. Aceite Castrol EDGE 5W-30 (1 L)">
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="category_id" class="etiqueta-campo">Categoría</label>
                <select id="category_id" name="category_id" class="campo-claro" required>
                    <option value="">Elige…</option>
                    <?php foreach ($categorias as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= $campo['category_id'] === (string) $c['id'] ? 'selected' : '' ?>>
                            <?= e($c['nombre']) ?><?= $c['activa'] ? '' : ' (oculta)' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="marca" class="etiqueta-campo">Marca <span class="font-normal text-neutral-500">(opcional)</span></label>
                <input id="marca" name="marca" class="campo-claro" maxlength="80" list="marcas" value="<?= e($campo['marca']) ?>">
                <datalist id="marcas">
                    <?php foreach ($marcas as $m): ?><option value="<?= e($m) ?>"><?php endforeach; ?>
                </datalist>
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label for="precio" class="etiqueta-campo">Precio (₡)</label>
                <input id="precio" name="precio" class="campo-claro" required inputmode="numeric" value="<?= e($campo['precio']) ?>" placeholder="28000">
            </div>
            <div>
                <label for="precio_anterior" class="etiqueta-campo">Precio anterior <span class="font-normal text-neutral-500">(oferta)</span></label>
                <input id="precio_anterior" name="precio_anterior" class="campo-claro" inputmode="numeric" value="<?= e($campo['precio_anterior']) ?>" placeholder="Vacío = sin oferta">
            </div>
            <div>
                <label for="stock" class="etiqueta-campo">Stock</label>
                <input id="stock" name="stock" type="number" min="0" max="99999" class="campo-claro" required value="<?= e($campo['stock']) ?>">
            </div>
        </div>
        <div>
            <label for="descripcion" class="etiqueta-campo">Descripción <span class="font-normal text-neutral-500">(opcional)</span></label>
            <textarea id="descripcion" name="descripcion" rows="6" class="campo-claro" maxlength="5000"
                      placeholder="Características, compatibilidad, medidas…"><?= e($campo['descripcion']) ?></textarea>
        </div>
    </div>

    <div class="space-y-5">
        <div class="tarjeta-clara space-y-3 p-5">
            <h2 class="text-base">Publicación</h2>
            <label class="flex cursor-pointer items-start gap-3 text-sm">
                <input type="checkbox" name="activo" class="mt-0.5 h-4 w-4 accent-rivera-rojo" <?= $campo['activo'] ? 'checked' : '' ?>>
                <span><span class="font-semibold">Visible en la tienda</span><span class="block text-xs text-neutral-500">Desmárcalo para ocultarlo sin borrarlo.</span></span>
            </label>
            <label class="flex cursor-pointer items-start gap-3 text-sm">
                <input type="checkbox" name="destacado" class="mt-0.5 h-4 w-4 accent-rivera-rojo" <?= $campo['destacado'] ? 'checked' : '' ?>>
                <span><span class="font-semibold">Destacado</span><span class="block text-xs text-neutral-500">Aparece primero y con etiqueta.</span></span>
            </label>
        </div>

        <div class="tarjeta-clara p-5" data-fotos data-espacio="<?= $espacioFotos ?>">
            <h2 class="text-base">Agregar fotos</h2>
            <?php if ($espacioFotos > 0): ?>
                <label for="fotos" class="mt-3 flex cursor-pointer flex-col items-center rounded-lg border-2 border-dashed border-neutral-300 px-3 py-6 text-center hover:border-rivera-rojo">
                    <span class="text-rivera-rojo"><?= icono('tienda', 'h-8 w-8') ?></span>
                    <span class="mt-2 text-sm font-semibold" data-fotos-texto>Elegir fotos (puedes elegir varias)</span>
                    <span class="mt-1 text-xs text-neutral-500">JPG, PNG o WebP · hasta 10 MB c/u · quedan <?= $espacioFotos ?> de <?= PRODUCTO_MAX_FOTOS ?></span>
                    <input id="fotos" name="fotos[]" type="file" multiple accept="image/jpeg,image/png,image/webp" class="sr-only">
                </label>
            <?php else: ?>
                <p class="mt-2 text-sm text-neutral-600">Ya tiene <?= PRODUCTO_MAX_FOTOS ?> fotos. Borra alguna para agregar otra.</p>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn-primario w-full text-base"><?= $producto ? 'Guardar cambios' : 'Crear producto' ?></button>
    </div>
</form>

<?php if ($fotos): ?>
    <section class="tarjeta-clara mt-5 p-5 sm:p-6">
        <h2 class="text-base">Fotos <span class="font-normal text-neutral-500">(la primera es la principal)</span></h2>
        <ul class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            <?php foreach ($fotos as $i => $foto): ?>
                <li class="overflow-hidden rounded-lg border <?= $i === 0 ? 'border-rivera-rojo' : 'border-neutral-200' ?>">
                    <div class="relative aspect-square bg-neutral-50">
                        <img src="<?= e(url_foto($foto['ruta'], true)) ?>" alt="Foto <?= $i + 1 ?>" class="h-full w-full object-contain">
                        <?php if ($i === 0): ?><span class="insignia-oferta absolute left-1.5 top-1.5">Principal</span><?php endif; ?>
                    </div>
                    <div class="flex divide-x divide-neutral-200 border-t border-neutral-200 text-sm">
                        <?php foreach (['antes' => ['←', 'Mover antes', $i > 0], 'despues' => ['→', 'Mover después', $i < count($fotos) - 1]] as $dir => [$flecha, $etiqueta, $posible]): ?>
                            <form method="post" action="<?= e(url('admin/productos/' . $id)) ?>" class="flex-1">
                                <?= csrf_campo() ?>
                                <input type="hidden" name="accion" value="mover_foto">
                                <input type="hidden" name="imagen_id" value="<?= (int) $foto['id'] ?>">
                                <input type="hidden" name="direccion" value="<?= $dir ?>">
                                <button type="submit" class="w-full py-2 hover:bg-neutral-50 disabled:text-neutral-300" aria-label="<?= $etiqueta ?>" <?= $posible ? '' : 'disabled' ?>><?= $flecha ?></button>
                            </form>
                        <?php endforeach; ?>
                        <form method="post" action="<?= e(url('admin/productos/' . $id)) ?>" class="flex-1" data-confirmar="¿Borrar esta foto?">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="accion" value="borrar_foto">
                            <input type="hidden" name="imagen_id" value="<?= (int) $foto['id'] ?>">
                            <button type="submit" class="w-full py-2 text-rivera-rojo hover:bg-red-50" aria-label="Borrar foto">✕</button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<?php if ($producto): ?>
    <section class="mt-5 rounded-lg border border-red-200 bg-white p-5 sm:p-6">
        <h2 class="text-base text-rivera-rojo">Borrar producto</h2>
        <p class="mt-1 text-sm text-neutral-600">Se borra con sus fotos. Los pedidos anteriores conservan el nombre y el precio. Si solo quieres dejar de venderlo, mejor ocúltalo.</p>
        <form method="post" action="<?= e(url('admin/productos/' . $id)) ?>" class="mt-4" data-confirmar="¿Seguro que quieres borrar «<?= e($producto['nombre']) ?>»? No se puede deshacer.">
            <?= csrf_campo() ?>
            <input type="hidden" name="accion" value="borrar">
            <button type="submit" class="btn border border-rivera-rojo text-rivera-rojo hover:bg-rivera-rojo hover:text-white">Borrar producto</button>
        </form>
    </section>
<?php endif; ?>

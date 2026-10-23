<?php
/**
 * Configuración del negocio (solo el dueño): datos de pago, contacto, pedidos, banner,
 * redes sociales y zonas de envío con sus tarifas.
 */

require_once RAIZ . '/app/modelos/configuracion.php';

requerir_admin('duenio');
$meta['titulo'] = 'Configuración';
$meta['layout'] = 'admin/layout';

$texto = fn (string $clave, int $max = 500): string =>
    is_string($_POST[$clave] ?? null) && mb_check_encoding($_POST[$clave], 'UTF-8') ? mb_substr(trim($_POST[$clave]), 0, $max) : '';

$errores = [];
$valores = [];
foreach (CONFIGURACION_CAMPOS as $campos) {
    foreach ($campos as $clave => $_) {
        $actual = ajuste($clave);
        $valores[$clave] = strtoupper($actual) === 'PENDIENTE' ? '' : $actual;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $accion = $texto('accion', 20);

    if ($accion === 'ajustes') {
        $nuevos = [];
        foreach (CONFIGURACION_CAMPOS as $campos) {
            foreach ($campos as $clave => [$etiqueta, $tipo]) {
                [$valor, $error] = configuracion_validar($tipo, $texto($clave), $etiqueta);
                $valores[$clave] = $valor;
                if ($error) {
                    $errores[] = $error;
                }
                // El WhatsApp se guarda en formato internacional para los enlaces wa.me
                $nuevos[$clave] = $clave === 'whatsapp' && $valor !== '' ? '506' . preg_replace('/\D/', '', $valor) : $valor;
            }
        }
        if (!$errores) {
            configuracion_guardar($nuevos);
            avisar('exito', 'Configuración guardada.');
            redirigir('admin/configuracion');
        }
        http_response_code(422);
    }

    if ($accion === 'zona_guardar' || $accion === 'zona_nueva') {
        $nombre = $texto('nombre', 100);
        $tarifa = preg_replace('/[.,\s]/', '', $texto('tarifa', 10));
        $orden = $texto('orden', 4);
        if (mb_strlen($nombre) < 2 || !ctype_digit($tarifa) || !ctype_digit($orden ?: '0')) {
            avisar('error', 'Revisa la zona: necesita nombre y una tarifa en colones sin decimales.');
        } else {
            $zonaId = $accion === 'zona_guardar' && ctype_digit($texto('id', 10)) ? (int) $texto('id', 10) : null;
            zona_guardar($zonaId, $nombre, $texto('descripcion', 255) ?: null, (int) $tarifa, (int) ($orden ?: 0), isset($_POST['activa']));
            avisar('exito', $zonaId ? 'Zona actualizada.' : 'Zona agregada.');
        }
        redirigir('admin/configuracion#envios');
    }

    if ($accion === 'zona_borrar' && ctype_digit($texto('id', 10))) {
        zona_borrar((int) $texto('id', 10));
        avisar('exito', 'Zona borrada.');
        redirigir('admin/configuracion#envios');
    }
}

$zonas = zonas_admin_listar();
?>
<h1 class="font-display text-2xl font-extrabold sm:text-3xl">Configuración</h1>
<p class="mt-1 text-sm text-neutral-600">Estos datos se muestran en la tienda y en las instrucciones de pago.</p>

<?php if ($errores): ?>
    <div class="mt-5"><?php componente('aviso', ['tipo' => 'error', 'mensaje' => implode(' ', $errores)]); ?></div>
<?php endif; ?>

<form method="post" action="<?= e(url('admin/configuracion')) ?>" class="mt-5 space-y-5">
    <?= csrf_campo() ?>
    <input type="hidden" name="accion" value="ajustes">

    <?php foreach (CONFIGURACION_CAMPOS as $seccion => $campos): ?>
        <fieldset class="tarjeta-clara p-5 sm:p-6">
            <legend class="sr-only"><?= e($seccion) ?></legend>
            <h2 class="text-lg"><?= e($seccion) ?></h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <?php foreach ($campos as $clave => [$etiqueta, $tipo, $ayuda]): ?>
                    <div class="<?= in_array($tipo, ['largo', 'legal'], true) ? 'sm:col-span-2' : '' ?>">
                        <label for="<?= e($clave) ?>" class="etiqueta-campo"><?= e($etiqueta) ?></label>
                        <?php if ($tipo === 'legal'): ?>
                            <textarea id="<?= e($clave) ?>" name="<?= e($clave) ?>" rows="14" maxlength="20000" class="campo-claro font-mono text-xs leading-relaxed"><?= e($valores[$clave]) ?></textarea>
                            <p class="mt-1 text-xs text-neutral-600">
                                <?= e(TEXTOS_LEGALES_AYUDA) ?>
                                <a href="<?= e(url($clave === 'terminos_texto' ? 'terminos' : 'privacidad')) ?>" target="_blank" class="font-semibold text-rivera-rojo hover:underline">Ver cómo se ve ↗</a>
                            </p>
                        <?php elseif ($tipo === 'largo'): ?>
                            <textarea id="<?= e($clave) ?>" name="<?= e($clave) ?>" rows="2" maxlength="500" class="campo-claro"><?= e($valores[$clave]) ?></textarea>
                        <?php else: ?>
                            <input id="<?= e($clave) ?>" name="<?= e($clave) ?>" class="campo-claro"
                                   type="<?= ['url' => 'url', 'correo' => 'email', 'telefono' => 'tel'][$tipo] ?? 'text' ?>"
                                   <?= in_array($tipo, ['telefono', 'entero'], true) ? 'inputmode="numeric"' : '' ?>
                                   value="<?= e($clave === 'whatsapp' && strlen($valores[$clave]) === 11 ? normalizar_telefono($valores[$clave]) : $valores[$clave]) ?>">
                        <?php endif; ?>
                        <?php if ($ayuda): ?><p class="mt-1 text-xs text-neutral-500"><?= e($ayuda) ?></p><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </fieldset>
    <?php endforeach; ?>

    <div class="sticky bottom-0 -mx-4 border-t border-neutral-200 bg-rivera-gris/95 px-4 py-3 backdrop-blur sm:mx-0 sm:rounded-lg sm:border">
        <button type="submit" class="btn-primario w-full sm:w-auto">Guardar configuración</button>
    </div>
</form>

<!-- Zonas de envío -->
<section id="envios" class="tarjeta-clara mt-8 p-5 sm:p-6">
    <h2 class="text-lg">Zonas de envío (GAM)</h2>
    <p class="mt-1 text-sm text-neutral-600">El cliente elige su zona y paga la tarifa junto con el pedido. Las zonas inactivas no se ofrecen.</p>

    <div class="mt-4 space-y-3">
        <?php foreach ($zonas as $zona): ?>
            <div class="rounded-lg border border-neutral-200 p-3">
                <form method="post" action="<?= e(url('admin/configuracion')) ?>" class="grid gap-2 sm:grid-cols-[1fr_1fr_7rem_5rem_auto_auto] sm:items-center">
                    <?= csrf_campo() ?>
                    <input type="hidden" name="accion" value="zona_guardar">
                    <input type="hidden" name="id" value="<?= (int) $zona['id'] ?>">
                    <input name="nombre" class="campo-claro py-2" required maxlength="100" value="<?= e($zona['nombre']) ?>" aria-label="Nombre de la zona">
                    <input name="descripcion" class="campo-claro py-2" maxlength="255" value="<?= e($zona['descripcion'] ?? '') ?>" placeholder="Descripción" aria-label="Descripción">
                    <input name="tarifa" class="campo-claro py-2" required inputmode="numeric" value="<?= (int) $zona['tarifa'] ?>" aria-label="Tarifa en colones">
                    <input name="orden" type="number" min="0" class="campo-claro py-2" value="<?= (int) $zona['orden'] ?>" aria-label="Orden">
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="activa" class="h-4 w-4 accent-rivera-rojo" <?= $zona['activa'] ? 'checked' : '' ?>> Activa</label>
                    <button type="submit" class="btn-claro btn-pequeno bg-white">Guardar</button>
                </form>
                <form method="post" action="<?= e(url('admin/configuracion')) ?>" class="mt-2 text-right" data-confirmar="¿Borrar la zona «<?= e($zona['nombre']) ?>»?">
                    <?= csrf_campo() ?>
                    <input type="hidden" name="accion" value="zona_borrar">
                    <input type="hidden" name="id" value="<?= (int) $zona['id'] ?>">
                    <button type="submit" class="text-xs font-medium text-rivera-rojo hover:underline">Borrar zona</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <form method="post" action="<?= e(url('admin/configuracion')) ?>" class="mt-4 grid gap-2 rounded-lg border-2 border-dashed border-neutral-300 p-3 sm:grid-cols-[1fr_1fr_7rem_5rem_auto] sm:items-center">
        <?= csrf_campo() ?>
        <input type="hidden" name="accion" value="zona_nueva">
        <input type="hidden" name="activa" value="1">
        <input name="nombre" class="campo-claro py-2" required maxlength="100" placeholder="Nueva zona (ej. Escazú)" aria-label="Nombre de la nueva zona">
        <input name="descripcion" class="campo-claro py-2" maxlength="255" placeholder="Descripción (opcional)" aria-label="Descripción">
        <input name="tarifa" class="campo-claro py-2" required inputmode="numeric" placeholder="₡ Tarifa" aria-label="Tarifa en colones">
        <input name="orden" type="number" min="0" class="campo-claro py-2" value="<?= count($zonas) + 1 ?>" aria-label="Orden">
        <button type="submit" class="btn-primario btn-pequeno">+ Agregar</button>
    </form>
</section>

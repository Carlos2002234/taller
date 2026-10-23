<?php
/**
 * Detalle de una consulta: datos del cliente, botones para contactarlo, estado y notas internas.
 * Recibe $parametros['id']. Dueño y empleado la atienden; solo el dueño la borra.
 */

require_once RAIZ . '/app/modelos/consultas.php';
require_once RAIZ . '/app/modelos/reportes.php';

$usuario = requerir_admin();
$meta['layout'] = 'admin/layout';

$id = ctype_digit($parametros['id'] ?? '') ? (int) $parametros['id'] : no_encontrado();
$consulta = consulta_por_id($id) ?? no_encontrado();
$urlConsulta = 'admin/consultas/' . $id;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $accion = is_string($_POST['accion'] ?? null) ? $_POST['accion'] : '';

    if ($accion === 'borrar') {
        if (!es_duenio($usuario)) {
            prohibido();
        }
        borrar_consulta($id);
        avisar('exito', 'Consulta borrada.');
        redirigir('admin/consultas');
    }

    if ($accion === 'guardar') {
        $estado = is_string($_POST['estado'] ?? null) && isset(CONSULTA_ESTADOS[$_POST['estado']]) ? $_POST['estado'] : $consulta['estado'];
        $notas = is_string($_POST['notas_internas'] ?? null) && mb_check_encoding($_POST['notas_internas'], 'UTF-8')
            ? mb_substr(trim($_POST['notas_internas']), 0, 2000) : '';
        actualizar_consulta($id, $estado, $notas, (int) $usuario['id']);
        avisar('exito', 'Consulta actualizada.');
    }
    redirigir($urlConsulta);
}

$meta['titulo'] = 'Consulta de ' . $consulta['nombre'];
$primerNombre = explode(' ', $consulta['nombre'])[0];
$mensajeWhatsapp = 'Hola ' . $primerNombre . ', le saluda ' . ajuste('negocio_nombre', 'Rivera Grupo Automotriz')
    . '. Recibimos su consulta desde la página web' . ($consulta['mensaje'] ? ': "' . mb_strimwidth($consulta['mensaje'], 0, 120, '…') . '"' : '') . '.';
$origen = array_filter([$consulta['utm_source'], $consulta['utm_medium'], $consulta['utm_campaign']]);
?>
<a href="<?= e(url('admin/consultas')) ?>" class="inline-flex items-center gap-1 text-sm font-medium text-neutral-600 hover:text-rivera-negro">
    <span class="rotate-180"><?= icono('flecha', 'h-4 w-4') ?></span> Volver a consultas
</a>
<div class="mt-3 flex flex-wrap items-center gap-3">
    <h1 class="font-display text-2xl font-extrabold sm:text-3xl"><?= e($consulta['nombre']) ?></h1>
    <span class="insignia <?= ['nueva' => 'bg-rivera-rojo text-white', 'contactada' => 'bg-sky-100 text-sky-900', 'cerrada' => 'bg-neutral-200 text-neutral-600'][$consulta['estado']] ?>">
        <?= e(CONSULTA_ESTADOS[$consulta['estado']]) ?>
    </span>
</div>
<p class="mt-1 text-sm text-neutral-600">Recibida el <?= e(date_create($consulta['creado_en'])->format('d/m/Y \a \l\a\s g:i a')) ?></p>

<div class="mt-6 grid gap-5 lg:grid-cols-3">
    <div class="space-y-5 lg:col-span-2">
        <!-- Mensaje -->
        <section class="tarjeta-clara p-5 sm:p-6">
            <h2 class="text-base">Pregunta o comentario</h2>
            <?php if ($consulta['mensaje']): ?>
                <p class="mt-3 whitespace-pre-line text-neutral-800"><?= e($consulta['mensaje']) ?></p>
            <?php else: ?>
                <p class="mt-3 text-sm text-neutral-600">El cliente no escribió un mensaje: solo pidió que lo contacten.</p>
            <?php endif; ?>
        </section>

        <!-- Estado y notas -->
        <form method="post" action="<?= e(url($urlConsulta)) ?>" class="tarjeta-clara space-y-4 p-5 sm:p-6">
            <?= csrf_campo() ?>
            <input type="hidden" name="accion" value="guardar">
            <h2 class="text-base">Seguimiento</h2>
            <fieldset>
                <legend class="etiqueta-campo">Estado</legend>
                <div class="flex flex-wrap gap-2">
                    <?php foreach (CONSULTA_ESTADOS as $clave => $texto): ?>
                        <label class="flex cursor-pointer items-center gap-2 rounded-full border border-neutral-300 bg-white px-3.5 py-1.5 text-sm has-[:checked]:border-rivera-negro has-[:checked]:bg-rivera-negro has-[:checked]:text-white has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-rivera-rojo has-[:focus-visible]:ring-offset-2">
                            <input type="radio" name="estado" value="<?= e($clave) ?>" class="sr-only" <?= $consulta['estado'] === $clave ? 'checked' : '' ?>>
                            <?= e($texto) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>
            <div>
                <label for="notas_internas" class="etiqueta-campo">Notas internas <span class="font-normal text-neutral-500">(el cliente no las ve)</span></label>
                <textarea id="notas_internas" name="notas_internas" rows="4" maxlength="2000" class="campo-claro"
                          placeholder="Ej. Le cotizamos cambio de aceite ₡28.000, viene el sábado."><?= e($consulta['notas_internas'] ?? '') ?></textarea>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <button type="submit" class="btn-primario">Guardar</button>
                <?php if ($consulta['atendido_nombre']): ?>
                    <p class="text-xs text-neutral-500">Último cambio: <?= e($consulta['atendido_nombre']) ?> · <?= e(date_create($consulta['actualizado_en'])->format('d/m/Y g:i a')) ?></p>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <aside class="space-y-5">
        <!-- Contacto -->
        <section class="tarjeta-clara p-5">
            <h2 class="text-base">Contactar</h2>
            <p class="mt-3 font-semibold"><?= e($consulta['nombre']) ?></p>
            <p class="text-sm text-neutral-600"><?= e($consulta['telefono']) ?></p>
            <?php if ($consulta['email']): ?>
                <p class="break-all text-sm text-neutral-600"><?= e($consulta['email']) ?></p>
            <?php endif; ?>
            <div class="mt-3 grid grid-cols-2 gap-2">
                <a href="<?= e(whatsapp_cliente_url($consulta['telefono'], $mensajeWhatsapp)) ?>" target="_blank" rel="noopener"
                   class="btn btn-pequeno bg-green-700 text-white hover:bg-green-800">
                    <?= icono('whatsapp', 'h-4 w-4') ?> WhatsApp
                </a>
                <a href="tel:+506<?= e(preg_replace('/\D/', '', $consulta['telefono'])) ?>" class="btn-claro btn-pequeno bg-white"><?= icono('telefono', 'h-4 w-4') ?> Llamar</a>
                <?php if ($consulta['email']): ?>
                    <a href="mailto:<?= e($consulta['email']) ?>?subject=<?= e(rawurlencode('Su consulta en ' . ajuste('negocio_nombre', 'Rivera Grupo Automotriz'))) ?>"
                       class="btn-claro btn-pequeno col-span-2 bg-white">Escribir correo</a>
                <?php endif; ?>
            </div>
            <p class="mt-3 text-xs text-neutral-500">
                Llegó desde: <?= e($origen ? implode(' / ', $origen) : nombre_origen('directo')) ?>
                <?php if ($consulta['pagina'] !== null): ?>
                    <br>Envió el formulario en: /<?= e($consulta['pagina']) ?>
                <?php endif; ?>
            </p>
        </section>

        <?php if (es_duenio($usuario)): ?>
            <form method="post" action="<?= e(url($urlConsulta)) ?>" class="tarjeta-clara p-5"
                  data-confirmar="¿Borrar esta consulta? No se puede deshacer.">
                <?= csrf_campo() ?>
                <input type="hidden" name="accion" value="borrar">
                <h2 class="text-base">Borrar</h2>
                <p class="mt-1 text-sm text-neutral-600">Por ejemplo, si es spam o el cliente pidió que borren sus datos.</p>
                <button type="submit" class="btn-claro btn-pequeno mt-3 bg-white text-rivera-rojo hover:border-rivera-rojo">Borrar consulta</button>
            </form>
        <?php endif; ?>
    </aside>
</div>

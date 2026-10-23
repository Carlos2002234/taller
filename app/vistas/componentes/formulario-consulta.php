<?php
/**
 * Formulario de consulta (lead): nombre, teléfono, correo y pregunta.
 * Se usa fijo en Contacto y dentro de la ventana "¿Tienes preguntas?". Siempre se envía a /contacto.
 * Datos: ?$prefijo (para ids únicos), ?$campo (valores), ?$errores (por campo), ?$pagina (desde dónde se envía)
 */

$prefijo ??= 'consulta';
$campo ??= ['nombre' => '', 'telefono' => '', 'email' => '', 'mensaje' => ''];
$errores ??= [];
$pagina ??= ruta_actual() !== '' ? ruta_actual() : 'inicio';

// Atributos de accesibilidad de un campo con o sin error
$estadoCampo = fn (string $nombre): string => isset($errores[$nombre])
    ? 'aria-invalid="true" aria-describedby="' . e($prefijo . '-' . $nombre . '-error') . '"'
    : '';
$mensajeError = function (string $nombre) use ($errores, $prefijo): void {
    if (isset($errores[$nombre])) {
        echo '<p id="' . e($prefijo . '-' . $nombre . '-error') . '" class="mt-1.5 text-xs text-rivera-rojo-claro">' . e($errores[$nombre]) . '</p>';
    }
};
?>
<form method="post" action="<?= e(url('contacto')) ?>#consulta" class="relative space-y-4" data-consulta>
    <?= csrf_campo() ?>
    <input type="hidden" name="pagina" value="<?= e($pagina) ?>">
    <input type="hidden" name="utm_source" data-utm="utm_source">
    <input type="hidden" name="utm_medium" data-utm="utm_medium">
    <input type="hidden" name="utm_campaign" data-utm="utm_campaign">
    <!-- Campo trampa para robots (oculto a las personas) -->
    <div class="absolute -left-[9999px]" aria-hidden="true">
        <label for="<?= e($prefijo) ?>-sitio_web">No llenar</label>
        <input id="<?= e($prefijo) ?>-sitio_web" type="text" name="sitio_web" tabindex="-1" autocomplete="off">
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="<?= e($prefijo) ?>-nombre" class="etiqueta-campo">Nombre <span class="text-rivera-rojo-claro">*</span></label>
            <input id="<?= e($prefijo) ?>-nombre" name="nombre" class="campo" required maxlength="120" autocomplete="name"
                   value="<?= e($campo['nombre']) ?>" <?= $estadoCampo('nombre') ?>>
            <?php $mensajeError('nombre'); ?>
        </div>
        <div>
            <label for="<?= e($prefijo) ?>-telefono" class="etiqueta-campo">Teléfono / WhatsApp <span class="text-rivera-rojo-claro">*</span></label>
            <input id="<?= e($prefijo) ?>-telefono" name="telefono" type="tel" class="campo" required maxlength="20" inputmode="tel" autocomplete="tel"
                   placeholder="8888-8888" value="<?= e($campo['telefono']) ?>" <?= $estadoCampo('telefono') ?>>
            <?php $mensajeError('telefono'); ?>
        </div>
    </div>
    <div>
        <label for="<?= e($prefijo) ?>-email" class="etiqueta-campo">Correo <span class="font-normal text-neutral-400">(opcional)</span></label>
        <input id="<?= e($prefijo) ?>-email" name="email" type="email" class="campo" maxlength="150" autocomplete="email"
               value="<?= e($campo['email']) ?>" <?= $estadoCampo('email') ?>>
        <?php $mensajeError('email'); ?>
    </div>
    <div>
        <label for="<?= e($prefijo) ?>-mensaje" class="etiqueta-campo">Tu pregunta o comentario <span class="font-normal text-neutral-400">(opcional)</span></label>
        <textarea id="<?= e($prefijo) ?>-mensaje" name="mensaje" rows="4" class="campo" maxlength="1000"
                  placeholder="Ej. ¿Cuánto cuesta el cambio de aceite para un Hyundai Tucson 2019?"><?= e($campo['mensaje']) ?></textarea>
    </div>

    <div>
        <label class="flex cursor-pointer items-start gap-2.5 text-xs text-neutral-300">
            <input type="checkbox" name="acepto" required class="mt-0.5 h-4 w-4 shrink-0 accent-rivera-rojo"
                   <?= isset($_POST['acepto']) ? 'checked' : '' ?> <?= $estadoCampo('acepto') ?>>
            <span>Autorizo que me contacten con estos datos para responder mi consulta, según la
                <a href="<?= e(url('privacidad')) ?>" target="_blank" class="font-semibold underline hover:text-white">Política de privacidad</a>.</span>
        </label>
        <?php $mensajeError('acepto'); ?>
    </div>

    <button type="submit" class="btn-primario w-full sm:w-auto"><?= icono('chat', 'h-4 w-4') ?> Enviar consulta</button>
</form>

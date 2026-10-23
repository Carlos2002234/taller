<?php
/**
 * Ventana (popup) con el formulario de consulta. La incluye el layout público en casi todas las páginas.
 * public/js/app.js la abre sola al entrar (una vez por visita, decisión del dueño: "el form de primero"),
 * con el botón "Hacer una consulta" o con un enlace ?consulta=abrir.
 */
?>
<dialog id="ventana-consulta" class="ventana-consulta" aria-labelledby="ventana-consulta-titulo">
    <!-- El relleno va aquí (no en el <dialog>) para que solo el fondo oscuro cierre la ventana al hacer clic -->
    <div class="p-6 sm:p-8">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 id="ventana-consulta-titulo" class="titulo-seccion">¿Tienes <span class="text-rivera-rojo">preguntas?</span></h2>
                <p class="mt-1 text-sm text-neutral-300">Te contactamos por WhatsApp o llamada en horario de atención.</p>
            </div>
            <button type="button" class="-mr-2 -mt-1 rounded-md p-2 text-neutral-400 hover:text-white" data-cerrar-consulta aria-label="Cerrar">
                <?= icono('cerrar', 'h-6 w-6') ?>
            </button>
        </div>
        <div class="mt-5">
            <?php componente('formulario-consulta', ['prefijo' => 'ventana-consulta']); ?>
        </div>
    </div>
</dialog>

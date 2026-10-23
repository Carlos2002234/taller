<?php
/**
 * Franja "¿Tienes preguntas?" con un botón que abre el formulario de consulta en una ventana.
 * Sin JavaScript, el botón lleva al formulario de la página Contacto.
 */
?>
<section class="contenedor pt-20 sm:pt-24" aria-labelledby="cta-consulta">
    <div class="aparecer flex flex-col items-start justify-between gap-5 rounded-2xl border border-white/10 bg-gradient-to-br from-rivera-carbon to-black p-6 sm:flex-row sm:items-center sm:p-8">
        <div>
            <h2 id="cta-consulta" class="titulo-seccion">¿Tienes <span class="text-rivera-rojo">preguntas?</span></h2>
            <p class="mt-2 max-w-xl text-neutral-300">Déjanos tus datos y te contactamos para cotizar o resolver tus dudas.</p>
        </div>
        <a href="<?= e(url('contacto')) ?>#consulta" class="btn-primario shrink-0" data-abrir-consulta aria-haspopup="dialog">
            <?= icono('chat', 'h-4 w-4') ?> Hacer una consulta
        </a>
    </div>
</section>

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

<?php
/**
 * Franja "¿Tienes preguntas?" con un botón que abre la ventana de consulta (componente ventana-consulta,
 * que el layout incluye en las páginas públicas). Sin JavaScript, el botón lleva al formulario de Contacto.
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

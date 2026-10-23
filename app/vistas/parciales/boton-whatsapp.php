<?php
/**
 * Botón flotante de WhatsApp. Es solo un enlace con mensaje prellenado;
 * la automatización de mensajes es la Fase 2 (notificarPedido).
 */
?>
<a href="<?= e(whatsapp_url('Hola, les escribo desde la página web.')) ?>"
   target="_blank" rel="noopener"
   class="fixed bottom-20 right-4 z-40 flex h-14 w-14 items-center justify-center rounded-full bg-[#25D366] text-white shadow-lg shadow-black/40 transition-transform hover:scale-105 lg:bottom-6 lg:right-6"
   aria-label="Escríbenos por WhatsApp">
    <?= icono('whatsapp', 'h-7 w-7') ?>
</a>

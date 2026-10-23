<?php
/**
 * Botón para que el cliente avise por WhatsApp que hizo su pedido (Fase 2, opción gratuita:
 * el mensaje lo envía el cliente desde su propio WhatsApp). El comprobante se sigue subiendo en la web.
 * Datos: $pedido
 */

$yaSubio = $pedido['estado'] === 'comprobante_enviado';
$mensaje = 'Hola, hice el pedido ' . $pedido['numero_pedido'] . ' por ' . colones($pedido['total'])
    . ' a nombre de ' . $pedido['nombre_cliente'] . '. '
    . ($yaSubio ? 'Ya subí el comprobante en la página web.' : 'Voy a subir el comprobante en la página web.');
?>
<a href="<?= e(whatsapp_url($mensaje)) ?>" target="_blank" rel="noopener"
   class="btn w-full bg-green-700 text-white hover:bg-green-800 sm:w-auto">
    <?= icono('whatsapp', 'h-5 w-5') ?> Avisar por WhatsApp
</a>

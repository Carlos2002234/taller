<?php
/**
 * Notificaciones al cliente.
 *
 * FASE 2 (NO implementar todavía): enviar por WhatsApp el número de pedido
 * y las instrucciones para mandar el comprobante de SINPE Móvil.
 * El dueño decidirá entre la API oficial de WhatsApp Business o un enlace prellenado.
 */

declare(strict_types=1);

/**
 * Se llama justo después de crear cada pedido (ver crear_pedido()).
 * $pedido trae: id, numero_pedido, nombre_cliente, telefono, email, total, tipo_entrega, expira_en.
 */
function notificarPedido(array $pedido): void
{
    // Intencionalmente vacía hasta la Fase 2.
}

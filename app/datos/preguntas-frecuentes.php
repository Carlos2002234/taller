<?php
/**
 * Preguntas frecuentes (sección del Inicio y página /preguntas-frecuentes).
 * Este es el ÚNICO lugar donde se editan: el HTML y los datos para Google (JSON-LD FAQPage)
 * se arman solos desde esta lista.
 *
 * CÓMO EDITAR
 *   - Cambiar una respuesta: edita el texto entre comillas de 'respuesta'.
 *   - Agregar una pregunta: copia un bloque [ ... ], pégalo donde quieras que aparezca (con su coma)
 *     y cambia 'pregunta' y 'respuesta'. El orden de la lista es el orden en la página.
 *   - Quitar una pregunta: borra su bloque completo.
 *   - Si el texto lleva comillas simples ('), escríbelas como \' (ej. 'el taller\'s').
 *
 * DATOS QUE SE LLENAN SOLOS (se toman de Admin → Configuración, así nunca quedan desactualizados)
 *   {negocio}   nombre del negocio          {telefono}  teléfono / WhatsApp
 *   {direccion} dirección del taller        {horario}   horario (ej. "Lunes a sábado: 8:00 a.m. – 5:00 p.m.")
 *   {horas_pago} horas para pagar un pedido antes de que se cancele
 *
 * ENLACE OPCIONAL al final de la respuesta:
 *   'enlace' => ['texto' => 'Ver el mapa', 'ruta' => 'ubicacion']   → página del sitio
 *   'enlace' => ['texto' => 'Escribir por WhatsApp', 'whatsapp' => 'Hola, tengo una consulta']  → WhatsApp con ese mensaje
 *
 * Reglas del dueño: solo datos reales; nada de precios, garantías ni plazos que el dueño no haya dado.
 */

return [
    [
        'pregunta'  => '¿Puedo pagar con tarjeta?',
        'respuesta' => 'Sí, pero solo en nuestro local físico.',
    ],
    [
        'pregunta'  => '¿Cómo pago una compra en la tienda en línea?',
        'respuesta' => 'En línea se paga por SINPE Móvil o transferencia bancaria. Al hacer el pedido te mostramos el número de pedido, el monto exacto y los datos para pagar; después subes el comprobante en la página de tu pedido. Tienes {horas_pago} horas para pagar; si no, el pedido se cancela.',
    ],
    [
        'pregunta'  => '¿Hacen envíos?',
        'respuesta' => 'Sí, hacemos envíos en la GAM y el envío se cobra aparte. También puedes retirar tu pedido en el taller.',
    ],
    [
        'pregunta'  => '¿Cuánto tarda la entrega?',
        'respuesta' => 'Tenemos un lapso de 48 horas hábiles.',
    ],
    [
        'pregunta'  => '¿Cómo sé en qué va mi pedido?',
        'respuesta' => 'En "Mi pedido" escribes tu número de pedido y tu teléfono, y ves el estado. Ahí mismo subes el comprobante de pago.',
        'enlace'    => ['texto' => 'Consultar mi pedido', 'ruta' => 'mi-pedido'],
    ],
    [
        'pregunta'  => '¿Tengo que sacar cita?',
        'respuesta' => 'Depende del servicio. El detailing, los polarizados y la instalación de productos son con cita. Para el mantenimiento, los cambios de aceite y los demás servicios no necesitas cita.',
        'enlace'    => ['texto' => 'Pedir cita por WhatsApp', 'whatsapp' => 'Hola, quiero sacar una cita.'],
    ],
    [
        'pregunta'  => '¿Me pueden asesorar antes de comprar?',
        'respuesta' => 'Sí, escríbenos por WhatsApp al {telefono} y te ayudamos a elegir.',
        'enlace'    => ['texto' => 'Escribir por WhatsApp', 'whatsapp' => 'Hola, quiero asesoría para elegir un producto.'],
    ],
];

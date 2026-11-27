<?php
/**
 * Página /preguntas-frecuentes: pestaña "Preguntas" del menú (entre Ubicación y Contacto) y enlace del pie.
 * Encabezado propio (h1) dentro del componente y JSON-LD FAQPage con el mismo texto visible.
 * Las preguntas se editan en app/datos/preguntas-frecuentes.php.
 */

require_once RAIZ . '/app/modelos/preguntas.php';

$meta['titulo'] = 'Preguntas frecuentes';
$meta['descripcion'] = 'Preguntas frecuentes de ' . ajuste('negocio_nombre', 'Rivera Grupo Automotriz')
    . ': formas de pago, envíos en la GAM, tiempo de entrega, horario y citas en el taller.';
$meta['datos'][] = preguntas_frecuentes_schema();
?>
<?php componente('preguntas-frecuentes'); ?>

<div class="pb-20 sm:pb-24"></div>

<?php
/**
 * Página /preguntas-frecuentes (enlazada en el pie). Mismo componente que el Inicio, con h1,
 * y el JSON-LD FAQPage (solo aquí: Google pide marcar una sola vez el mismo contenido).
 * Las preguntas se editan en app/datos/preguntas-frecuentes.php.
 */

require_once RAIZ . '/app/modelos/preguntas.php';

$meta['titulo'] = 'Preguntas frecuentes';
$meta['descripcion'] = 'Preguntas frecuentes de ' . ajuste('negocio_nombre', 'Rivera Grupo Automotriz')
    . ': formas de pago, envíos en la GAM, tiempo de entrega, horario y ubicación del taller.';
$meta['datos'][] = preguntas_frecuentes_schema();
?>
<?php componente('preguntas-frecuentes', ['nivel' => 'h1']); ?>

<div class="pb-20 sm:pb-24"></div>

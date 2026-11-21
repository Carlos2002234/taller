<?php
/**
 * Ubicación y horario (pestaña "Ubicación" del menú; pedido del dueño 2026-11-21).
 * Encabezado racing como Servicios (titular por líneas con máscara) + bloque Visítanos:
 * mapa que se carga al tocarlo, tablero Abierto/Cerrado con cuenta regresiva, horario de la semana,
 * copiar dirección y botones a Google Maps, Waze y WhatsApp. Datos: Configuración (una sola fuente).
 */

$estado = horario_estado();
$meta['titulo'] = 'Ubicación y horario';
$meta['descripcion'] = ajuste('negocio_nombre', 'Rivera Grupo Automotriz') . ' en La Puebla: ' . ajuste('direccion')
    . ' Horario: ' . horario_resumen() . '. Cómo llegar con Google Maps o Waze.';
$meta['datos'][] = datos_negocio();
?>
<section class="portada-racing contenedor pb-10 pt-12 sm:pb-14 sm:pt-16 lg:pt-20" aria-labelledby="titulo-ubicacion">
    <p class="etiqueta-seccion etiqueta-dibuja portada-entrar portada-entrar-1">Ubicación y horario · La Puebla</p>
    <h1 id="titulo-ubicacion" class="titulo-lineas mt-3 font-display text-4xl font-extrabold leading-[1.02] tracking-tight sm:text-5xl xl:text-[3.6rem]">
        <span class="linea"><span>Visítanos en</span></span>
        <span class="linea"><span class="text-rivera-rojo">el taller.</span></span>
    </h1>
    <p class="portada-entrar portada-entrar-3 mt-6 max-w-xl text-lg leading-relaxed text-neutral-300">
        <?= e(ajuste('direccion')) ?>
    </p>
</section>

<?php componente('visitanos', ['encabezado' => false]); ?>

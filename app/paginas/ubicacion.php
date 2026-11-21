<?php
/**
 * Ubicación (pestaña "Ubicación" del menú). Decisión del dueño 2026-11-21: lo principal es el MAPA DE GOOGLE
 * grande; nada de tableros ni efectos. Mapa real a todo el ancho (se carga al abrir: es el contenido de la página)
 * y una sola tarjeta sobria con nombre, dirección, horario, estado en texto y botones.
 * Datos de Configuración (una sola fuente): direccion, horario_semana, mapa_url, waze_url, whatsapp.
 * El estado ("Abierto hoy hasta…") lo calcula PHP y app.js lo refresca cada minuto ([data-horario]).
 */

$nombre = ajuste('negocio_nombre', 'Rivera Grupo Automotriz');
$estado = horario_estado();
$embed = mapa_embed_url();

$meta['titulo'] = 'Ubicación y horario';
$meta['descripcion'] = $nombre . ' en La Puebla: ' . ajuste('direccion') . ' Horario: ' . horario_resumen() . '. Cómo llegar con Google Maps o Waze.';
$meta['datos'][] = datos_negocio();
?>
<section class="ubicacion" aria-labelledby="titulo-ubicacion">
    <?php if ($embed): ?>
        <iframe class="ubicacion-mapa" src="<?= e($embed) ?>" title="Mapa de Google con la ubicación de <?= e($nombre) ?>"
                referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
    <?php endif; ?>

    <div class="ubicacion-tarjeta-contenedor">
        <div class="ubicacion-tarjeta" data-horario="<?= e(json_encode(horario_semana())) ?>">
            <h1 id="titulo-ubicacion" class="font-display text-2xl font-bold italic leading-tight">
                <span class="sr-only">Ubicación y horario de </span><?= e($nombre) ?>
            </h1>
            <p class="mt-2 text-[0.95rem] leading-relaxed text-neutral-300"><?= e(ajuste('direccion')) ?></p>

            <p class="mt-4 text-sm leading-relaxed text-neutral-300">
                <?php foreach (horario_lineas() as $linea): ?><?= e($linea) ?><br><?php endforeach; ?>
            </p>
            <p class="mt-2 text-sm font-semibold text-white" data-horario-simple><?= e($estado['simple']) ?></p>

            <div class="mt-5 flex flex-wrap gap-2">
                <a href="<?= e(mapa_url()) ?>" target="_blank" rel="noopener" class="btn-primario">Cómo llegar</a>
                <?php if (ajuste('waze_url') !== ''): ?>
                    <a href="<?= e(ajuste('waze_url')) ?>" target="_blank" rel="noopener" class="btn-secundario">Waze</a>
                <?php endif; ?>
                <a href="<?= e(whatsapp_url('Hola, les escribo desde la página web.')) ?>" target="_blank" rel="noopener" class="btn-secundario">WhatsApp</a>
            </div>
        </div>
    </div>
</section>

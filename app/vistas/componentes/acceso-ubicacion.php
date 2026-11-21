<?php
/**
 * Acceso discreto a la página /ubicacion (Inicio y Servicios): estado "Abierto ahora / Cerrado"
 * (se actualiza solo cada minuto, app.js [data-horario]) + dirección corta + enlace "Ver ubicación y horario".
 */
$estado = horario_estado();
?>
<section class="contenedor pt-5" aria-label="Ubicación y horario">
    <a href="<?= e(url('ubicacion')) ?>" class="acceso-ubicacion aparecer group" data-horario="<?= e(json_encode(horario_semana())) ?>">
        <span class="flex min-w-0 flex-wrap items-center gap-x-4 gap-y-2">
            <span class="estado-horario <?= $estado['abierto'] ? 'abierto' : '' ?>" data-horario-estado>
                <span class="estado-horario-punto" aria-hidden="true"></span>
                <span data-horario-texto><?= e($estado['texto']) ?></span>
            </span>
            <span class="text-sm text-neutral-300"><?= e(ajuste('direccion')) ?></span>
        </span>
        <span class="flex shrink-0 items-center gap-2 font-display text-sm font-bold uppercase italic tracking-wide text-white">
            Ver ubicación y horario
            <span class="text-rivera-rojo-claro transition-transform motion-safe:group-hover:translate-x-1"><?= icono('flecha', 'h-4 w-4') ?></span>
        </span>
    </a>
</section>

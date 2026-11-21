<?php
/**
 * Acceso discreto a /ubicacion (Inicio y Servicios): una línea de texto simple
 * "Cómo llegar · Abierto hoy hasta las 5:00 p.m." (el estado se refresca cada minuto, app.js [data-horario]).
 */
?>
<section class="contenedor pt-8" aria-label="Ubicación">
    <p class="text-sm text-neutral-400" data-horario="<?= e(json_encode(horario_semana())) ?>">
        <a href="<?= e(url('ubicacion')) ?>" class="font-semibold text-white underline decoration-white/30 underline-offset-4 hover:decoration-white">Cómo llegar</a>
        <span aria-hidden="true"> · </span><span data-horario-simple><?= e(horario_estado()['simple']) ?></span>
    </p>
</section>

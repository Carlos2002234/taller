<?php
/**
 * Horario por día con el de hoy resaltado y el indicador "Abierto ahora" / "Cerrado · abre …".
 * Los datos salen de horario_semana() (Admin → Configuración → "Horario por día"); la hora es la de Costa Rica.
 * El estado se calcula en PHP y app.js lo recalcula cada minuto ([data-horario]); sin JavaScript queda el de PHP.
 * Datos opcionales: $titulo (bool, muestra "Horario" arriba; por defecto sí).
 */
$titulo ??= true;
$semana = horario_semana();
$estado = horario_estado();
$paraJs = [];
foreach ($semana as $dia => $horas) {
    $paraJs[$dia] = $horas;
}
?>
<div class="horario" data-horario="<?= e(json_encode($paraJs)) ?>">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <?php if ($titulo): ?>
            <h3 class="font-display text-lg font-bold italic">Horario</h3>
        <?php endif; ?>
        <p class="estado-horario <?= $estado['abierto'] ? 'abierto' : '' ?>" data-horario-estado>
            <span class="estado-horario-punto" aria-hidden="true"></span>
            <span data-horario-texto><?= e($estado['texto']) ?></span>
        </p>
    </div>
    <table class="tabla-horario mt-4 w-full">
        <caption class="sr-only">Horario de atención por día (hora de Costa Rica)</caption>
        <tbody>
            <?php foreach ($semana as $dia => $horas): $esHoy = $dia === $estado['hoy']; ?>
                <tr class="<?= $esHoy ? 'hoy' : '' ?>" data-dia="<?= $dia ?>" <?= $esHoy ? 'aria-current="date"' : '' ?>>
                    <th scope="row">
                        <?= e(mb_strtoupper(mb_substr(DIAS_SEMANA[$dia], 0, 1)) . mb_substr(DIAS_SEMANA[$dia], 1)) ?>
                        <span class="tabla-horario-hoy">Hoy</span>
                    </th>
                    <td><?= $horas ? e(hora_12($horas[0]) . ' – ' . hora_12($horas[1])) : 'Cerrado' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

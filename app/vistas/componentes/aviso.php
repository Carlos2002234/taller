<?php
/**
 * Mensaje de aviso.
 * Datos: $tipo ('exito' | 'error' | 'info'), $mensaje
 */

// Nombres de clase completos para que Tailwind los incluya en el CSS compilado
$estilos = [
    'exito' => ['aviso-exito', 'exito'],
    'error' => ['aviso-error', 'alerta'],
    'info'  => ['aviso-info', 'info'],
];
[$clase, $icono] = $estilos[$tipo] ?? $estilos['info'];
?>
<div class="<?= $clase ?>" role="<?= $tipo === 'error' ? 'alert' : 'status' ?>">
    <span class="shrink-0"><?= icono($icono, 'h-5 w-5') ?></span>
    <p><?= e($mensaje) ?></p>
</div>

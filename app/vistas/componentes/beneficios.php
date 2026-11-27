<?php
/**
 * Franja de beneficios bajo la portada (estilo racing, diseño aprobado 2026-11-21):
 * borde superior blanco, 4 bloques con número grande en contorno rojo y, al pasar el mouse,
 * un relleno rojo que entra de izquierda a derecha (estilos .beneficio en resources/css/app.css).
 * Con teclado (Tab) cada bloque recibe el foco y muestra el mismo relleno (pedido del dueño).
 * Sin íconos. Parámetro opcional: 'animar' => true para que entren escalonados con la portada.
 */

$animar = $animar ?? false;
$beneficios = [
    ['Pago con SINPE Móvil',  'o transferencia bancaria'],
    ['Envíos en la GAM',      'Entrega en ' . ajuste('envio_plazo', '48 horas')],
    ['Productos originales',  'Marcas de confianza'],
    ['Asesoría por WhatsApp', 'Te ayudamos a elegir'],
];
$retrasos = ['racing-entra-1', 'racing-entra-2', 'racing-entra-3', 'racing-entra-4'];
?>
<ul id="beneficios" aria-label="Beneficios" class="grid grid-cols-1 border-t-2 border-white sm:grid-cols-2 lg:grid-cols-4">
    <?php foreach ($beneficios as $i => [$titulo, $texto]): ?>
        <li tabindex="0" class="beneficio <?= $animar ? 'racing-entra ' . $retrasos[$i] : '' ?> flex items-center gap-[18px] border-b border-rivera-borde px-6 py-[26px] sm:border-r lg:border-b-0 lg:last:border-r-0">
            <span class="beneficio-numero font-display text-[3.4rem] font-black italic leading-none" aria-hidden="true"><?= sprintf('%02d', $i + 1) ?></span>
            <span class="flex flex-col gap-1">
                <span class="font-display text-lg font-extrabold uppercase italic tracking-[0.02em]"><?= e($titulo) ?></span>
                <span class="beneficio-texto text-sm text-neutral-400"><?= e($texto) ?></span>
            </span>
        </li>
    <?php endforeach; ?>
</ul>

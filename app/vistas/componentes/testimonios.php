<?php
/**
 * Bloque "Lo que dicen nuestros clientes": capturas reales de WhatsApp.
 * Las imágenes se recortaron a la burbuja del mensaje para quitar los números de teléfono
 * y se taparon las placas de los carros
 * (las capturas originales están en /testimoniales, que NO se sube a git).
 * El texto completo de cada mensaje va en el "alt" para lectores de pantalla y buscadores.
 */

$testimonios = [
    [
        'foto'     => 'testimonio-1.webp',
        'ancho'    => 570,
        'alto'     => 1063,
        'servicio' => 'Mantenimiento de 10.000 km',
        'texto'    => 'Holaa a todos! Hoy llevamos al consentido al mantenimiento de los 10K con don Carlos Rivera y la verdad, 10/10! '
                    . 'Muy buena recomendación de Licda. Nancy Prendas N., y ahora nosotros también lo recomendamos muchísimo. '
                    . 'La atención fue excelente, todos muy atentos y la verdad es que quedamos muy contentos con el servicio.',
    ],
    [
        'foto'     => 'testimonio-2.webp',
        'ancho'    => 545,
        'alto'     => 802,
        'servicio' => 'Mantenimiento de 20.000 km y lavado',
        'texto'    => 'Hola para comentarles que hoy llevé mi carro al mantenimiento de 20mil km a este lugar en Heredia, me pareció muy bueno. '
                    . 'Me cobraron 95mil por todo el mantenimiento y lavado.',
    ],
    [
        'foto'     => 'testimonio-3.webp',
        'ancho'    => 440,
        'alto'     => 595,
        'servicio' => 'Mantenimiento de 30.000 km',
        'texto'    => 'Listo el mantenimiento de los 30 mil km en Rivera Grupo Automotriz, super recomendados, servicio al cliente muy bueno '
                    . 'y excelente trabajo, me voy muy contento y agradecido.',
    ],
];
?>
<section class="contenedor pt-20 sm:pt-24" aria-labelledby="testimonios">
    <div class="aparecer">
        <p class="etiqueta-seccion">Testimonios</p>
        <h2 id="testimonios" class="titulo-seccion">Lo que dicen <span class="text-rivera-rojo">nuestros clientes</span></h2>
    </div>
    <div class="escalonado mt-8 grid items-start gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($testimonios as $t): ?>
            <figure class="tarjeta w-full rounded">
                <img src="<?= e(asset('img/testimonios/' . $t['foto'])) ?>" width="<?= $t['ancho'] ?>" height="<?= $t['alto'] ?>"
                     alt="Mensaje de un cliente: “<?= e($t['texto']) ?>”" loading="lazy" decoding="async" class="h-[22rem] w-full object-cover object-top">
                <figcaption class="border-t border-white/10 px-4 py-3.5">
                    <span class="leading-tight">
                        <span class="block text-sm font-semibold text-white"><?= e($t['servicio']) ?></span>
                        <span class="block text-xs text-neutral-400">Cliente Omoda · vía WhatsApp</span>
                    </span>
                </figcaption>
            </figure>
        <?php endforeach; ?>
    </div>
</section>

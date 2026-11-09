<?php
/**
 * Bloque "Lo que dicen nuestros clientes": mensajes reales de WhatsApp.
 * Diseño (2026-11-08, aprobado por el dueño): el mensaje se lee como texto (frase destacada + resto)
 * y la captura original va al lado como prueba. El primero es el principal; los otros dos, más pequeños.
 * Las capturas se recortaron a la burbuja del mensaje para quitar los números de teléfono
 * y se taparon las placas de los carros
 * (las capturas originales están en /testimoniales, que NO se sube a git).
 * El texto completo de cada mensaje va en el "alt" de la captura para lectores de pantalla y buscadores.
 * Sin estrellas ni íconos: solo comillas tipográficas.
 */

$testimonios = [
    [
        'foto'     => 'testimonio-1.webp',
        'ancho'    => 570,
        'alto'     => 1063,
        'servicio' => 'Mantenimiento de 10.000 km',
        'cita'     => 'La atención fue excelente, todos muy atentos y la verdad es que quedamos muy contentos con el servicio.',
        'resto'    => 'Hoy llevamos al consentido al mantenimiento de los 10K con don Carlos Rivera y la verdad, 10/10. '
                    . 'Muy buena recomendación de Licda. Nancy Prendas N., y ahora nosotros también lo recomendamos muchísimo.',
        'texto'    => 'Holaa a todos! Hoy llevamos al consentido al mantenimiento de los 10K con don Carlos Rivera y la verdad, 10/10! '
                    . 'Muy buena recomendación de Licda. Nancy Prendas N., y ahora nosotros también lo recomendamos muchísimo. '
                    . 'La atención fue excelente, todos muy atentos y la verdad es que quedamos muy contentos con el servicio.',
    ],
    [
        'foto'     => 'testimonio-3.webp',
        'ancho'    => 440,
        'alto'     => 595,
        'servicio' => 'Mantenimiento de 30.000 km',
        'cita'     => 'Súper recomendados, servicio al cliente muy bueno y excelente trabajo.',
        'resto'    => 'Me voy muy contento y agradecido.',
        'texto'    => 'Listo el mantenimiento de los 30 mil km en Rivera Grupo Automotriz, super recomendados, servicio al cliente muy bueno '
                    . 'y excelente trabajo, me voy muy contento y agradecido.',
    ],
    [
        'foto'     => 'testimonio-2.webp',
        'ancho'    => 545,
        'alto'     => 802,
        'servicio' => 'Mantenimiento de 20.000 km y lavado',
        'cita'     => 'Hoy llevé mi carro al mantenimiento de 20 mil km a este lugar en Heredia, me pareció muy bueno.',
        'resto'    => 'Me cobraron 95 mil por todo el mantenimiento y lavado.',
        'texto'    => 'Hola para comentarles que hoy llevé mi carro al mantenimiento de 20mil km a este lugar en Heredia, me pareció muy bueno. '
                    . 'Me cobraron 95mil por todo el mantenimiento y lavado.',
    ],
];
$principal = $testimonios[0];
$otros = array_slice($testimonios, 1);
$origen = 'Cliente Omoda · vía WhatsApp';
?>
<section class="contenedor pt-20 sm:pt-28" aria-labelledby="testimonios">
    <div class="aparecer flex flex-wrap items-end justify-between gap-x-12 gap-y-4 border-b border-white/10 pb-9">
        <div>
            <p class="etiqueta-seccion">Testimonios</p>
            <h2 id="testimonios" class="mt-3 text-4xl font-extrabold leading-none tracking-tight sm:text-5xl">Lo que dicen<br><span class="text-rivera-rojo">nuestros clientes</span></h2>
        </div>
        <p class="max-w-sm text-[0.95rem] leading-relaxed text-neutral-400">
            Mensajes reales que nos enviaron por WhatsApp después de su mantenimiento. Solo ocultamos los números de teléfono y las placas.
        </p>
    </div>

    <div class="escalonado mt-10 grid gap-5 lg:grid-cols-12">
        <!-- Testimonio principal -->
        <figure class="tarjeta flex flex-col gap-8 rounded p-7 sm:flex-row sm:p-10 lg:col-span-7">
            <div class="flex min-w-0 flex-1 flex-col">
                <span class="font-display text-8xl font-extrabold italic leading-[0.7] text-rivera-rojo" aria-hidden="true">“</span>
                <blockquote class="mt-4">
                    <p class="font-display text-2xl font-bold italic leading-snug lg:text-[1.9rem]"><?= e($principal['cita']) ?></p>
                    <p class="mt-5 text-[0.95rem] leading-relaxed text-neutral-300"><?= e($principal['resto']) ?></p>
                </blockquote>
                <figcaption class="mt-auto flex flex-wrap items-center gap-x-4 gap-y-2 pt-7">
                    <span class="rounded-sm border border-neutral-700 px-2.5 py-1.5 text-xs font-semibold uppercase tracking-wide"><?= e($principal['servicio']) ?></span>
                    <span class="text-sm text-neutral-400"><?= e($origen) ?></span>
                </figcaption>
            </div>
            <div class="flex shrink-0 flex-col gap-2.5 sm:w-52">
                <div class="h-80 overflow-hidden rounded border border-rivera-borde bg-[#1B1B1B] sm:h-[25rem]">
                    <img src="<?= e(asset('img/testimonios/' . $principal['foto'])) ?>" width="<?= $principal['ancho'] ?>" height="<?= $principal['alto'] ?>"
                         alt="Captura del mensaje original: “<?= e($principal['texto']) ?>”" loading="lazy" decoding="async" class="w-full">
                </div>
                <span class="text-[11px] uppercase tracking-[0.14em] text-neutral-500">Captura original</span>
            </div>
        </figure>

        <!-- Testimonios secundarios -->
        <div class="flex flex-col gap-5 lg:col-span-5">
            <?php foreach ($otros as $t): ?>
                <figure class="tarjeta flex flex-1 gap-5 rounded p-7">
                    <div class="flex min-w-0 flex-1 flex-col">
                        <span class="font-display text-6xl font-extrabold italic leading-[0.7] text-rivera-rojo" aria-hidden="true">“</span>
                        <blockquote class="mt-2.5">
                            <p class="font-display text-xl font-bold italic leading-snug"><?= e($t['cita']) ?></p>
                            <p class="mt-3 text-sm leading-relaxed text-neutral-300"><?= e($t['resto']) ?></p>
                        </blockquote>
                        <figcaption class="mt-auto flex flex-col gap-1 pt-5">
                            <span class="text-xs font-semibold uppercase tracking-wide"><?= e($t['servicio']) ?></span>
                            <span class="text-sm text-neutral-400"><?= e($origen) ?></span>
                        </figcaption>
                    </div>
                    <div class="hidden h-48 w-28 shrink-0 overflow-hidden rounded border border-rivera-borde bg-[#1B1B1B] sm:block">
                        <img src="<?= e(asset('img/testimonios/' . $t['foto'])) ?>" width="<?= $t['ancho'] ?>" height="<?= $t['alto'] ?>"
                             alt="Captura del mensaje original: “<?= e($t['texto']) ?>”" loading="lazy" decoding="async" class="w-full">
                    </div>
                </figure>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Invitación a dejar su opinión -->
    <div class="fondo-claro aparecer mt-5 flex flex-wrap items-center justify-between gap-x-10 gap-y-5 rounded bg-white px-6 py-7 text-rivera-negro sm:px-10">
        <div>
            <p class="font-display text-2xl font-extrabold italic leading-tight">¿Ya nos visitaste?</p>
            <p class="mt-1.5 text-neutral-600">Cuéntanos cómo te fue. Tu mensaje ayuda a otros clientes a decidir.</p>
        </div>
        <a href="<?= e(whatsapp_url('Hola, les quiero contar cómo me fue en el taller.')) ?>" target="_blank" rel="noopener" class="btn-primario">Escribir por WhatsApp</a>
    </div>
</section>

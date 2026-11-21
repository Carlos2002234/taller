<?php
/**
 * Bloque "Lo que dicen nuestros clientes": mensajes reales de WhatsApp.
 * Diseño (2026-11-21, aprobado por el dueño): una tarjeta grande con UN testimonio a la vez (frase destacada,
 * resto del mensaje y la captura original en un marco de teléfono) y al lado la lista de los 3 para elegir.
 * Cambia solo cada 8 s (también con "reducir movimiento", con fundido). Se pausa con el mouse sobre la tarjeta,
 * con el foco del teclado o con el botón "Pausar"; se cambia con la lista, las flechas, ← → o deslizando el dedo;
 * la lógica está en public/js/app.js ([data-testimonios]) y los estilos en resources/css/app.css.
 * Mejora progresiva: sin JavaScript se ven los 3 testimonios completos, uno debajo del otro.
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
        'km'       => '10.000 km',
        'extracto' => 'La atención fue excelente, todos muy atentos.',
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
        'km'       => '30.000 km',
        'extracto' => 'Súper recomendados, excelente trabajo.',
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
        'km'       => '20.000 km',
        'extracto' => 'Me pareció muy bueno.',
        'cita'     => 'Hoy llevé mi carro al mantenimiento de 20 mil km a este lugar en Heredia, me pareció muy bueno.',
        'resto'    => 'Me cobraron 95 mil por todo el mantenimiento y lavado.',
        'texto'    => 'Hola para comentarles que hoy llevé mi carro al mantenimiento de 20mil km a este lugar en Heredia, me pareció muy bueno. '
                    . 'Me cobraron 95mil por todo el mantenimiento y lavado.',
    ],
];
$total = count($testimonios);
$autor = 'Cliente Omoda';
$inicial = 'O'; // del modelo del carro (no se publica el nombre del cliente)
$segundos = 8;
$dos_digitos = fn (int $n): string => str_pad((string) $n, 2, '0', STR_PAD_LEFT);
?>
<section class="contenedor pt-20 sm:pt-28" aria-labelledby="testimonios">
    <!-- Encabezado (sin el párrafo ni las píldoras, quitados por el dueño 2026-11-21) -->
    <div class="aparecer border-b border-white/10 pb-9">
        <p class="etiqueta-seccion">Testimonios</p>
        <h2 id="testimonios" class="mt-3 text-4xl font-extrabold leading-none tracking-tight sm:text-5xl">Lo que dicen<br><span class="text-rivera-rojo">nuestros clientes</span></h2>
    </div>

    <div class="testimonios-rejilla aparecer desde-izq mt-12 grid gap-5" data-testimonios data-segundos="<?= $segundos ?>">
        <!-- Testimonios: sin JavaScript se ven los 3; con JavaScript, uno a la vez (el resto queda oculto) -->
        <div id="testimonios-paneles" class="testimonios-paneles flex min-w-0 flex-col gap-5" aria-live="polite" tabindex="0"
             role="group" aria-roledescription="carrusel" aria-label="Testimonios (flechas ← → para cambiar)" data-testimonios-paneles>
            <?php foreach ($testimonios as $i => $t): ?>
                <figure id="testimonio-<?= $i + 1 ?>" class="testimonio <?= $i === 0 ? 'activo' : '' ?> flex min-w-0 flex-col gap-10 rounded-xl border border-rivera-borde bg-[#141414] p-7 sm:flex-row sm:p-12" data-testimonio>
                    <div class="flex min-w-0 flex-1 flex-col">
                        <div class="testimonio-entra flex items-center justify-between gap-4">
                            <span class="block h-[52px] font-display text-[7rem] font-extrabold italic leading-[0.62] text-rivera-rojo" aria-hidden="true">“</span>
                            <span class="font-display text-[0.8rem] font-semibold tracking-[0.14em] text-neutral-400">
                                <span class="sr-only">Testimonio </span><?= $dos_digitos($i + 1) ?> <span aria-hidden="true">/</span><span class="sr-only">de</span> <?= $dos_digitos($total) ?>
                            </span>
                        </div>
                        <blockquote class="mt-7">
                            <p class="testimonio-entra testimonio-retraso-1 font-display text-2xl font-bold italic leading-[1.22] tracking-[-0.01em] text-white lg:text-[2rem]"><?= e($t['cita']) ?></p>
                            <p class="testimonio-entra testimonio-retraso-2 mt-5 leading-[1.7] text-[#bdbdbd]"><?= e($t['resto']) ?></p>
                        </blockquote>
                        <figcaption class="testimonio-entra testimonio-retraso-3 mt-auto flex flex-wrap items-center gap-x-4 gap-y-3 pt-9">
                            <span class="flex h-11 w-11 items-center justify-center rounded-full bg-neutral-800 font-display text-[0.95rem] font-extrabold text-white" aria-hidden="true"><?= e($inicial) ?></span>
                            <span class="flex flex-col gap-0.5">
                                <span class="text-[0.95rem] font-semibold text-white"><?= e($autor) ?></span>
                                <span class="text-[0.8rem] text-[#8a8a8a]"><?= e($t['servicio']) ?> · vía WhatsApp</span>
                            </span>
                        </figcaption>
                    </div>
                    <div class="testimonio-foto mx-auto flex w-[220px] shrink-0 flex-col gap-2.5 sm:mx-0">
                        <!-- Marco tipo teléfono -->
                        <div class="h-[25rem] rounded-[22px] border border-[#333] bg-[#1f1f1f] p-2 shadow-[0_30px_60px_-30px_rgba(0,0,0,0.9)]">
                            <div class="h-full overflow-hidden rounded-[15px] bg-[#0f0f0f]">
                                <img src="<?= e(asset('img/testimonios/' . $t['foto'])) ?>" width="<?= $t['ancho'] ?>" height="<?= $t['alto'] ?>"
                                     alt="Captura del mensaje original: “<?= e($t['texto']) ?>”" loading="lazy" decoding="async" class="block h-auto w-full">
                            </div>
                        </div>
                        <span class="text-center text-[11px] uppercase tracking-[0.14em] text-[#8a8a8a]">Captura original</span>
                    </div>
                </figure>
            <?php endforeach; ?>
        </div>

        <!-- Lista para elegir (solo con JavaScript; sin él ya se ven los 3 completos) -->
        <nav class="testimonios-controles min-w-0 flex-col gap-3" aria-label="Elegir testimonio">
            <?php foreach ($testimonios as $i => $t): ?>
                <button type="button" class="group w-full rounded-[10px] border border-[#222] bg-transparent px-[22px] py-5 text-left transition duration-media hover:border-[#3a3a3a] motion-safe:hover:-translate-y-0.5 aria-pressed:border-[#3a3a3a] aria-pressed:bg-rivera-carbon"
                        aria-pressed="<?= $i === 0 ? 'true' : 'false' ?>" aria-controls="testimonio-<?= $i + 1 ?>" data-testimonio-opcion>
                    <span class="flex items-baseline justify-between gap-3">
                        <span class="font-display text-[0.95rem] font-extrabold italic text-[#8a8a8a] transition-colors group-aria-pressed:text-rivera-rojo-claro"><?= $dos_digitos($i + 1) ?></span>
                        <span class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[#8a8a8a]"><?= e($t['km']) ?></span>
                    </span>
                    <span class="mt-2.5 block font-display text-lg font-bold italic leading-[1.3] text-neutral-400 transition-colors group-aria-pressed:text-white">“<?= e($t['extracto']) ?>”</span>
                    <span class="mt-4 block h-0.5 overflow-hidden rounded-sm bg-neutral-800" aria-hidden="true">
                        <span class="testimonio-barra block h-full bg-rivera-rojo group-aria-[pressed=false]:invisible" data-testimonio-barra></span>
                    </span>
                </button>
            <?php endforeach; ?>
            <div class="mt-auto flex items-center justify-between gap-3 pt-2">
                <button type="button" class="min-h-11 text-[0.8rem] font-semibold text-neutral-300 underline decoration-neutral-600 underline-offset-4 transition-colors hover:text-white hover:decoration-white"
                        aria-label="Pausar: los testimonios cambian solos cada <?= $segundos ?> segundos" data-testimonios-pausa>Pausar</button>
                <span class="flex gap-2">
                    <button type="button" class="flex h-11 w-11 items-center justify-center rounded-full border border-[#333] text-white transition hover:border-white hover:bg-white hover:text-rivera-negro" aria-label="Testimonio anterior" data-testimonio-anterior>
                        <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
                    </button>
                    <button type="button" class="flex h-11 w-11 items-center justify-center rounded-full border border-[#333] text-white transition hover:border-white hover:bg-white hover:text-rivera-negro" aria-label="Siguiente testimonio" data-testimonio-siguiente>
                        <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
                    </button>
                </span>
            </div>
        </nav>
    </div>

    <!-- Invitación a dejar su opinión -->
    <div class="fondo-claro aparecer mt-5 flex flex-wrap items-center justify-between gap-x-10 gap-y-5 rounded-xl bg-white px-6 py-8 text-rivera-negro sm:px-10">
        <div>
            <p class="font-display text-2xl font-extrabold italic leading-tight sm:text-[1.6rem]">¿Ya nos visitaste?</p>
            <p class="mt-1.5 text-neutral-600">Cuéntanos cómo te fue. Tu mensaje ayuda a otros clientes a decidir.</p>
        </div>
        <a href="<?= e(whatsapp_url('Hola, les quiero contar cómo me fue en el taller.')) ?>" target="_blank" rel="noopener" class="btn-primario min-h-12">
            Escribir por WhatsApp
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </a>
    </div>
</section>

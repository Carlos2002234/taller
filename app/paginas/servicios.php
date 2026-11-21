<?php
/**
 * Servicios del taller. Sin precios: cada servicio se cotiza por WhatsApp.
 * Diseño: portada en dos columnas, franja de datos, lista numerada sobre fondo claro,
 * pasos de trabajo, marcas y galería del taller.
 */

require_once RAIZ . '/app/modelos/servicios.php';

$servicios = servicios_activos();
$meta['titulo'] = 'Servicios del taller';
$meta['descripcion'] = 'Servicios de taller mecánico en ' . ajuste('negocio_nombre', 'Rivera Grupo Automotriz') . ': '
    . implode(', ', array_map(fn ($s) => mb_strtolower($s['nombre']), array_slice($servicios, 0, 5))) . '. Cotiza por WhatsApp.';

$horario = ajuste('horario');

// Cómo trabajamos (el último paso va resaltado en rojo)
$pasos = [
    ['Escríbenos', 'Cuéntanos por WhatsApp qué necesita tu auto: marca, modelo y año.'],
    ['Te cotizamos', 'Te enviamos el precio del trabajo antes de que traigas el vehículo.'],
    ['Traes tu auto', $horario !== '' ? $horario . ', en La Puebla.' : 'Te esperamos en el taller, en La Puebla.'],
    ['Lo recoges listo', 'Te avisamos por WhatsApp apenas el trabajo esté terminado.'],
];

// Marcas de los productos que se usan en el taller (texto, sin logos)
$marcas = ['Havoline', 'Castrol', 'Delo', 'Chevron', 'Vonixx', 'Malco'];

// Galería: foto => texto (la sala de espera ya sale en "Visítanos")
$galeria = [
    'rivera-07' => ['Auto cubierto en el área de detallado', 'Área de detallado', '960', '1920'],
    'rivera-06' => ['Pared del taller con publicidad de lubricantes Chevron', 'Lubricantes Chevron', '800', '1600'],
    'rivera-18' => ['Entrada del taller', 'Entrada del taller', '800', '1600'],
];
?>
<!-- Portada: texto y foto lado a lado -->
<section class="contenedor grid items-stretch gap-10 py-12 sm:py-16 lg:grid-cols-[minmax(0,7fr)_minmax(0,5fr)] lg:gap-14 lg:py-20">
    <div class="flex flex-col justify-center">
        <p class="etiqueta-seccion portada-entrar portada-entrar-1">Centro de servicio automotriz · La Puebla</p>
        <h1 class="portada-entrar portada-entrar-2 mt-3 font-display text-4xl font-extrabold leading-[1.02] tracking-tight sm:text-5xl xl:text-[3.6rem]">
            Servicio para tu auto,<br><span class="text-rivera-rojo">sin vueltas.</span>
        </h1>
        <p class="portada-entrar portada-entrar-3 mt-6 max-w-lg text-lg leading-relaxed text-neutral-300">
            Cambio de aceite, diagnóstico, frenos, lavado y preparación para la RTV. Cada trabajo se cotiza
            por WhatsApp antes de empezar, para que sepas el precio desde el inicio.
        </p>
        <div class="portada-entrar portada-entrar-4 mt-8 flex flex-wrap gap-3">
            <a href="<?= e(whatsapp_url('Hola, quiero cotizar un servicio para mi auto.')) ?>" target="_blank" rel="noopener" class="btn-primario">
                <?= icono('whatsapp', 'h-4 w-4') ?> Cotizar por WhatsApp
            </a>
            <a href="#lista-servicios" class="btn-secundario">Ver servicios</a>
        </div>
    </div>
    <figure class="portada-entrar portada-entrar-2 relative min-h-[18rem] overflow-hidden rounded-lg bg-rivera-carbon sm:min-h-[26rem]">
        <img src="<?= e(url('img/fotos/rivera-01-1600.webp')) ?>"
             srcset="<?= e(url('img/fotos/rivera-01-800.webp')) ?> 800w, <?= e(url('img/fotos/rivera-01-1600.webp')) ?> 1600w"
             sizes="(min-width: 1024px) 50vw, 100vw"
             alt="Rótulo del Centro de Servicio Automotriz Rivera" fetchpriority="high"
             class="portada-foto absolute inset-0 h-full w-full object-cover object-[30%_50%]">
        <?php if ($horario !== ''): ?>
            <figcaption class="absolute bottom-4 left-4 right-4 flex w-fit items-center gap-2.5 rounded-md border border-rivera-borde bg-rivera-negro px-4 py-3 text-sm text-neutral-200">
                <span class="shrink-0 text-rivera-rojo-claro"><?= icono('reloj', 'h-4 w-4') ?></span><?= e($horario) ?>
            </figcaption>
        <?php endif; ?>
    </figure>
</section>

<!-- Franja de datos del taller -->
<section aria-label="Datos del taller" class="border-y border-rivera-borde">
    <dl class="contenedor grid grid-cols-1 divide-y divide-rivera-borde sm:grid-cols-2 sm:divide-y-0 lg:grid-cols-4 lg:divide-x">
        <?php foreach ([
            'Horario'             => $horario,
            'Ubicación'           => '150 m oeste de la plaza de La Puebla',
            'WhatsApp y teléfono' => ajuste('telefono'),
            'Cotización'          => 'Antes de cada trabajo',
        ] as $titulo => $valor): ?>
            <div class="py-5 lg:px-6 lg:py-7 lg:first:pl-0 lg:last:pr-0">
                <dt class="text-[11px] font-semibold uppercase tracking-[0.16em] text-neutral-400"><?= e($titulo) ?></dt>
                <dd class="mt-1.5 font-medium"><?= e($valor) ?></dd>
            </div>
        <?php endforeach; ?>
    </dl>
</section>

<!-- Lista de servicios (fondo claro) -->
<section id="lista-servicios" class="fondo-claro scroll-mt-28 bg-white text-rivera-negro">
    <div class="contenedor py-16 sm:py-24">
        <div class="aparecer flex flex-wrap items-end justify-between gap-x-16 gap-y-6 pb-10">
            <div class="flex-[1_1_26rem]">
                <p class="etiqueta-seccion">01 — Servicios</p>
                <h2 class="mt-3 text-4xl font-extrabold leading-[1.02] tracking-tight sm:text-5xl">Lo que hacemos<br>en el taller</h2>
            </div>
            <p class="flex-[0_1_25rem] leading-relaxed text-neutral-600">
                No publicamos precios fijos: cada auto es distinto. Toca <strong class="text-rivera-negro">Cotizar</strong> en el
                servicio que necesitas y te respondemos por WhatsApp con el precio para tu vehículo.
            </p>
        </div>

        <?php if ($servicios): ?>
            <ol class="escalonado border-t-2 border-rivera-negro">
                <?php foreach ($servicios as $i => $servicio) {
                    componente('fila-servicio', ['servicio' => $servicio, 'numero' => $i + 1]);
                } ?>
            </ol>
        <?php else: ?>
            <?php componente('aviso', ['tipo' => 'info', 'mensaje' => 'Escríbenos por WhatsApp para conocer nuestros servicios.']); ?>
        <?php endif; ?>

        <div class="escalonado mt-14 grid gap-5 sm:mt-20 lg:grid-cols-5">
            <figure class="lg:col-span-3">
                <img src="<?= e(url('img/fotos/rivera-08-1600.webp')) ?>"
                     srcset="<?= e(url('img/fotos/rivera-08-800.webp')) ?> 800w, <?= e(url('img/fotos/rivera-08-1600.webp')) ?> 1600w"
                     sizes="(min-width: 1024px) 60vw, 100vw" loading="lazy"
                     alt="Zona de lavado del taller con un sedán gris" class="aspect-[4/3] w-full rounded-lg object-cover lg:aspect-auto lg:h-[28rem]">
                <figcaption class="mt-3 flex justify-between gap-4 text-sm text-neutral-600">
                    <span class="font-semibold text-rivera-negro">Zona de lavado y detallado</span><span>Productos Vonixx y Malco</span>
                </figcaption>
            </figure>
            <figure class="lg:col-span-2">
                <img src="<?= e(url('img/fotos/rivera-25-1600.webp')) ?>"
                     srcset="<?= e(url('img/fotos/rivera-25-800.webp')) ?> 800w, <?= e(url('img/fotos/rivera-25-1600.webp')) ?> 1600w"
                     sizes="(min-width: 1024px) 40vw, 100vw" loading="lazy"
                     alt="Aceites Havoline y Castrol en exhibición" class="aspect-[4/3] w-full rounded-lg object-cover lg:aspect-auto lg:h-[28rem]">
                <figcaption class="mt-3 flex justify-between gap-4 text-sm text-neutral-600">
                    <span class="font-semibold text-rivera-negro">Cambio de aceite</span><span>Havoline · Castrol · Delo</span>
                </figcaption>
            </figure>
        </div>
    </div>
</section>

<!-- Cómo trabajamos -->
<section class="contenedor py-16 sm:py-24" aria-labelledby="como-trabajamos">
    <div class="aparecer">
        <p class="etiqueta-seccion">02 — Cómo trabajamos</p>
        <h2 id="como-trabajamos" class="mt-3 text-4xl font-extrabold leading-[1.02] tracking-tight sm:text-5xl">Cuatro pasos,<br><span class="text-rivera-rojo">cero sorpresas.</span></h2>
    </div>
    <ol class="escalonado mt-12 grid gap-x-8 gap-y-10 sm:grid-cols-2 lg:mt-14 lg:grid-cols-4">
        <?php foreach ($pasos as $i => [$titulo, $texto]): $ultimo = $i === count($pasos) - 1; ?>
            <li class="flex flex-col gap-3 border-t pt-6 <?= $ultimo ? 'border-t-2 border-rivera-rojo' : 'border-neutral-700' ?>">
                <span class="font-display text-5xl font-extrabold italic leading-none <?= $ultimo ? 'text-rivera-rojo' : 'text-neutral-600' ?>" aria-hidden="true"><?= sprintf('%02d', $i + 1) ?></span>
                <h3 class="text-xl"><?= e($titulo) ?></h3>
                <p class="text-[0.95rem] leading-relaxed text-neutral-300"><?= e($texto) ?></p>
            </li>
        <?php endforeach; ?>
    </ol>
</section>

<!-- Marcas -->
<section aria-label="Marcas de los productos que usamos" class="fondo-claro bg-rivera-gris text-rivera-negro">
    <div class="contenedor flex flex-wrap items-center justify-between gap-x-10 gap-y-5 py-10">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-neutral-600">Productos que usamos</p>
        <ul class="flex flex-wrap gap-x-11 gap-y-3 font-display text-xl font-bold uppercase italic tracking-wide text-neutral-700 sm:text-2xl">
            <?php foreach ($marcas as $marca): ?><li><?= e($marca) ?></li><?php endforeach; ?>
        </ul>
    </div>
</section>

<!-- El taller -->
<section class="contenedor py-16 sm:py-24" aria-labelledby="el-taller">
    <div class="aparecer mb-10 flex flex-wrap items-end justify-between gap-x-10 gap-y-4">
        <div>
            <p class="etiqueta-seccion">03 — El taller</p>
            <h2 id="el-taller" class="mt-3 text-4xl font-extrabold leading-[1.02] tracking-tight sm:text-5xl">Así nos encuentras</h2>
        </div>
    </div>
    <div class="escalonado grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($galeria as $foto => [$alt, $texto, $chico, $grande]): ?>
            <figure class="group">
                <div class="overflow-hidden rounded-lg">
                    <img src="<?= e(url("img/fotos/{$foto}-{$chico}.webp")) ?>"
                         srcset="<?= e(url("img/fotos/{$foto}-{$chico}.webp")) ?> <?= $chico ?>w, <?= e(url("img/fotos/{$foto}-{$grande}.webp")) ?> <?= $grande ?>w"
                         sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw" loading="lazy" alt="<?= e($alt) ?>"
                         class="aspect-[4/3] w-full object-cover transition-transform duration-lenta ease-suave motion-safe:group-hover:scale-105">
                </div>
                <figcaption class="mt-3 text-sm font-medium text-neutral-200"><?= e($texto) ?></figcaption>
            </figure>
        <?php endforeach; ?>
    </div>
</section>

<?php componente('video-taller'); ?>

<?php componente('cta-consulta'); ?>

<?php componente('visitanos'); ?>

<?php
/**
 * Contacto y ubicación. El botón "Cómo llegar" usa el enlace de Google Maps de Configuración
 * (o busca el negocio si todavía no se configuró).
 */

$meta['titulo'] = 'Contacto y ubicación';
$meta['descripcion'] = 'Visítanos: ' . ajuste('direccion') . ' Horario: ' . ajuste('horario') . '. Teléfono y WhatsApp ' . ajuste('telefono') . '.';

$meta['datos'][] = datos_negocio();

$telefono = preg_replace('/\D/', '', ajuste('telefono'));
$redes = array_filter(['Facebook' => ajuste('facebook_url'), 'Instagram' => ajuste('instagram_url'), 'TikTok' => ajuste('tiktok_url')]);
?>
<section class="contenedor py-12 sm:py-16">
    <p class="etiqueta-seccion portada-entrar portada-entrar-1">Estamos para ayudarte</p>
    <h1 class="portada-entrar portada-entrar-2 font-display text-4xl font-extrabold sm:text-5xl">Contacto y <span class="text-rivera-rojo">ubicación</span></h1>

    <div class="mt-10 grid gap-8 lg:grid-cols-2">
        <div class="escalonado space-y-4">
            <div class="tarjeta flex gap-4 p-5">
                <span class="text-rivera-rojo"><?= icono('ubicacion', 'h-7 w-7') ?></span>
                <div>
                    <h2 class="font-sans text-base font-semibold">Dirección</h2>
                    <p class="mt-1 text-neutral-300"><?= e(ajuste('direccion')) ?></p>
                    <a href="<?= e(mapa_url()) ?>" target="_blank" rel="noopener" class="mt-2 inline-flex items-center gap-1 text-sm font-semibold text-rivera-rojo-claro hover:underline">
                        Cómo llegar (Google Maps) <?= icono('flecha', 'h-4 w-4') ?>
                    </a>
                </div>
            </div>
            <div class="tarjeta flex gap-4 p-5">
                <span class="text-rivera-rojo"><?= icono('reloj', 'h-7 w-7') ?></span>
                <div>
                    <h2 class="font-sans text-base font-semibold">Horario</h2>
                    <p class="mt-1 text-neutral-300"><?= e(ajuste('horario')) ?></p>
                </div>
            </div>
            <div class="tarjeta flex gap-4 p-5">
                <span class="text-rivera-rojo"><?= icono('telefono', 'h-7 w-7') ?></span>
                <div class="flex-1">
                    <h2 class="font-sans text-base font-semibold">Teléfono y WhatsApp</h2>
                    <p class="mt-1 text-neutral-300"><?= e(ajuste('telefono')) ?></p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="<?= e(whatsapp_url('Hola, les escribo desde la página web.')) ?>" target="_blank" rel="noopener" class="btn-primario btn-pequeno">
                            <?= icono('whatsapp', 'h-4 w-4') ?> WhatsApp
                        </a>
                        <a href="tel:+506<?= e($telefono) ?>" class="btn-secundario btn-pequeno"><?= icono('telefono', 'h-4 w-4') ?> Llamar</a>
                    </div>
                </div>
            </div>
            <?php if ($redes): ?>
                <div class="tarjeta p-5">
                    <h2 class="font-sans text-base font-semibold">Síguenos</h2>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <?php foreach ($redes as $red => $enlace): ?>
                            <a href="<?= e($enlace) ?>" target="_blank" rel="noopener" class="btn-secundario btn-pequeno">
                                <?= icono(strtolower($red), 'h-4 w-4') ?> <?= e($red) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="aparecer space-y-3">
            <p class="text-sm text-neutral-400">Así nos encuentras:</p>
            <img src="<?= e(url('img/fotos/rivera-14-1600.webp')) ?>" alt="Fachada de <?= e(ajuste('negocio_nombre', 'Rivera Grupo Automotriz')) ?>" class="aspect-[16/9] w-full rounded-lg object-cover">
            <img src="<?= e(url('img/fotos/rivera-18-800.webp')) ?>" alt="Entrada al taller con los rótulos de servicios" loading="lazy" class="aspect-[16/9] w-full rounded-lg object-cover">
        </div>
    </div>
</section>

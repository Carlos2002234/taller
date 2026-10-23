<?php
/**
 * Servicios del taller. Sin precios: cada servicio se cotiza por WhatsApp.
 */

require_once RAIZ . '/app/modelos/servicios.php';

$servicios = servicios_activos();
$meta['titulo'] = 'Servicios del taller';
$meta['descripcion'] = 'Servicios de taller mecánico en ' . ajuste('negocio_nombre', 'Rivera Grupo Automotriz') . ': '
    . implode(', ', array_map(fn ($s) => mb_strtolower($s['nombre']), array_slice($servicios, 0, 5))) . '. Cotiza por WhatsApp.';
?>
<section class="relative isolate overflow-hidden">
    <img src="<?= e(url('img/fotos/rivera-01-1600.webp')) ?>" alt="" class="portada-foto absolute inset-0 -z-20 h-full w-full object-cover" fetchpriority="high">
    <div class="absolute inset-0 -z-10 bg-gradient-to-r from-rivera-negro via-rivera-negro/85 to-rivera-negro/40"></div>
    <div class="contenedor py-16 sm:py-24">
        <p class="etiqueta-seccion portada-entrar portada-entrar-1">Centro de servicio automotriz</p>
        <h1 class="portada-entrar portada-entrar-2 max-w-2xl font-display text-4xl font-extrabold leading-tight sm:text-5xl">
            Servicios <span class="text-rivera-rojo">del taller</span>
        </h1>
        <p class="portada-entrar portada-entrar-3 mt-4 max-w-lg text-neutral-300">Escríbenos y te damos una cotización para tu vehículo. Atendemos <?= e(rtrim(mb_strtolower(ajuste('horario')), '.')) ?>.</p>
        <a href="<?= e(whatsapp_url('Hola, quiero cotizar un servicio para mi auto.')) ?>" target="_blank" rel="noopener" class="btn-primario portada-entrar portada-entrar-4 mt-7">
            <?= icono('whatsapp', 'h-4 w-4') ?> Cotizar por WhatsApp
        </a>
    </div>
</section>

<section class="contenedor py-16 sm:py-20">
    <?php if ($servicios): ?>
        <div class="escalonado grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($servicios as $servicio) { componente('tarjeta-servicio', ['servicio' => $servicio]); } ?>
        </div>
    <?php else: ?>
        <?php componente('aviso', ['tipo' => 'info', 'mensaje' => 'Escríbenos por WhatsApp para conocer nuestros servicios.']); ?>
    <?php endif; ?>
</section>

<section class="contenedor pb-14">
    <div class="escalonado grid gap-3 sm:grid-cols-3">
        <?php foreach (['rivera-08' => 'Zona de lavado', 'rivera-25' => 'Aceites de marcas reconocidas', 'rivera-18' => 'Nuestras instalaciones'] as $foto => $texto): ?>
            <figure class="tarjeta group">
                <img src="<?= e(url('img/fotos/' . $foto . '-800.webp')) ?>" alt="<?= e($texto) ?>" loading="lazy" class="aspect-[4/3] w-full object-cover transition-transform duration-700 ease-out group-hover:scale-105">
                <figcaption class="px-4 py-3 text-sm text-neutral-300"><?= e($texto) ?></figcaption>
            </figure>
        <?php endforeach; ?>
    </div>
</section>

<?php componente('visitanos'); ?>

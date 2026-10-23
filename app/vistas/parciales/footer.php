<?php
/**
 * Pie de página: datos del negocio, enlaces y redes sociales (solo las configuradas).
 */

$redes = array_filter([
    'Facebook'  => ajuste('facebook_url'),
    'Instagram' => ajuste('instagram_url'),
    'TikTok'    => ajuste('tiktok_url'),
]);
?>
<footer class="border-t border-white/10 bg-black pb-16 lg:pb-0">
    <div class="contenedor grid gap-10 py-12 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <div class="flex items-center gap-2.5">
                <img src="<?= e(asset('img/logo.webp')) ?>" alt="" width="40" height="40" class="h-10 w-10" loading="lazy">
                <span class="leading-none">
                    <img src="<?= e(asset('img/rivera-texto.webp')) ?>" alt="Rivera" width="166" height="18" class="block h-[18px] w-auto" loading="lazy">
                    <span class="mt-1.5 block font-display text-[9px] font-semibold uppercase italic tracking-[0.32em] text-neutral-300">Grupo Automotriz</span>
                </span>
            </div>
            <p class="mt-4 text-sm text-neutral-400">Taller mecánico y tienda de accesorios. Tu auto, nuestra pasión.</p>
            <?php if ($redes): ?>
                <div class="mt-5 flex gap-2.5">
                    <?php foreach ($redes as $red => $enlace): ?>
                        <a href="<?= e($enlace) ?>" target="_blank" rel="noopener" aria-label="<?= e($red) ?> de <?= e(ajuste('negocio_nombre', 'Rivera Grupo Automotriz')) ?>"
                           class="flex h-10 w-10 items-center justify-center rounded-full border border-white/15 text-neutral-300 transition duration-300 hover:-translate-y-0.5 hover:border-rivera-rojo hover:bg-rivera-rojo hover:text-white">
                            <?= icono(strtolower($red), 'h-[18px] w-[18px]') ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div>
            <h2 class="text-sm font-semibold uppercase tracking-wider">Visítanos</h2>
            <ul class="mt-4 space-y-3 text-sm text-neutral-300">
                <li class="flex gap-2.5"><span class="mt-0.5 shrink-0 text-rivera-rojo"><?= icono('ubicacion', 'h-4 w-4') ?></span><?= e(ajuste('direccion')) ?></li>
                <li class="flex gap-2.5"><span class="mt-0.5 shrink-0 text-rivera-rojo"><?= icono('reloj', 'h-4 w-4') ?></span><?= e(ajuste('horario')) ?></li>
            </ul>
        </div>

        <div>
            <h2 class="text-sm font-semibold uppercase tracking-wider">Contacto</h2>
            <ul class="mt-4 space-y-3 text-sm text-neutral-300">
                <li>
                    <a href="tel:+506<?= e(preg_replace('/\D/', '', ajuste('telefono'))) ?>" class="flex gap-2.5 hover:text-white">
                        <span class="mt-0.5 shrink-0 text-rivera-rojo"><?= icono('telefono', 'h-4 w-4') ?></span><?= e(ajuste('telefono')) ?>
                    </a>
                </li>
                <li>
                    <a href="<?= e(whatsapp_url('Hola, les escribo desde la página web.')) ?>" class="flex gap-2.5 hover:text-white" target="_blank" rel="noopener">
                        <span class="mt-0.5 shrink-0 text-rivera-rojo"><?= icono('whatsapp', 'h-4 w-4') ?></span>WhatsApp
                    </a>
                </li>
            </ul>
        </div>

        <div>
            <h2 class="text-sm font-semibold uppercase tracking-wider">Formas de pago</h2>
            <ul class="mt-4 space-y-3 text-sm text-neutral-300">
                <li class="flex gap-2.5"><span class="mt-0.5 shrink-0 text-rivera-rojo"><?= icono('celular', 'h-4 w-4') ?></span>SINPE Móvil</li>
                <li class="flex gap-2.5"><span class="mt-0.5 shrink-0 text-rivera-rojo"><?= icono('escudo', 'h-4 w-4') ?></span>Transferencia bancaria</li>
            </ul>
            <a href="<?= e(url('mi-pedido')) ?>" class="mt-5 inline-flex items-center gap-1 text-sm font-medium text-white hover:text-rivera-rojo">
                Consultar mi pedido <?= icono('flecha', 'h-4 w-4') ?>
            </a>
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="contenedor flex flex-wrap items-center justify-between gap-x-6 gap-y-2 py-5 text-xs text-neutral-400">
            <p>© <?= date('Y') ?> <?= e(ajuste('negocio_nombre', 'Rivera Grupo Automotriz')) ?>. Costa Rica.</p>
            <nav class="flex gap-5" aria-label="Legal">
                <a href="<?= e(url('terminos')) ?>" class="hover:text-white">Términos y condiciones</a>
                <a href="<?= e(url('privacidad')) ?>" class="hover:text-white">Política de privacidad</a>
            </nav>
        </div>
    </div>
</footer>

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
                    <span class="block font-display text-xl font-extrabold italic tracking-wide"><span class="text-rivera-rojo">R</span>IVERA</span>
                    <span class="mt-0.5 block text-[9px] font-semibold uppercase tracking-[0.28em] text-neutral-400">Grupo Automotriz</span>
                </span>
            </div>
            <p class="mt-4 text-sm text-neutral-400">Taller mecánico y tienda de accesorios. Tu auto, nuestra pasión.</p>
            <?php if ($redes): ?>
                <div class="mt-4 flex gap-4 text-sm">
                    <?php foreach ($redes as $red => $enlace): ?>
                        <a href="<?= e($enlace) ?>" class="text-neutral-300 hover:text-white" target="_blank" rel="noopener"><?= e($red) ?></a>
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
        <p class="contenedor py-5 text-xs text-neutral-500">© <?= date('Y') ?> <?= e(ajuste('negocio_nombre', 'Rivera Grupo Automotriz')) ?>. Costa Rica.</p>
    </div>
</footer>

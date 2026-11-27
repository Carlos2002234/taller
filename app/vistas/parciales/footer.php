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
<footer class="border-t-[3px] border-rivera-rojo bg-black pb-16 lg:pb-0">
    <div class="contenedor grid gap-10 py-12 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <img src="<?= e(asset('img/logo-completo.webp')) ?>" alt="<?= e(ajuste('negocio_nombre', 'Rivera Grupo Automotriz')) ?>" width="816" height="480" class="h-auto w-44" loading="lazy">
            <p class="mt-4 text-sm text-neutral-400">Taller mecánico y tienda de accesorios. Tu auto, nuestra pasión.</p>
            <?php if ($redes): ?>
                <div class="mt-5 flex gap-2.5">
                    <?php foreach ($redes as $red => $enlace): ?>
                        <a href="<?= e($enlace) ?>" target="_blank" rel="noopener" aria-label="<?= e($red) ?> de <?= e(ajuste('negocio_nombre', 'Rivera Grupo Automotriz')) ?>"
                           class="flex h-10 w-10 items-center justify-center rounded-full border border-white/15 text-neutral-300 transition motion-safe:hover:-translate-y-0.5 hover:border-rivera-rojo hover:bg-rivera-rojo hover:text-white">
                            <?= icono(strtolower($red), 'h-[18px] w-[18px]') ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div>
            <h2 class="text-sm font-semibold uppercase tracking-wider">Visítanos</h2>
            <ul class="mt-4 space-y-3 text-sm text-neutral-300">
                <li class="flex gap-2.5"><?= e(ajuste('direccion')) ?></li>
                <li class="flex gap-2.5"><?= e(horario_resumen()) ?></li>
                <li><a href="<?= e(mapa_url()) ?>" target="_blank" rel="noopener" class="font-semibold text-white hover:text-rivera-rojo-claro">Cómo llegar (Google Maps)</a></li>
            </ul>
        </div>

        <div>
            <h2 class="text-sm font-semibold uppercase tracking-wider">Contacto</h2>
            <ul class="mt-4 space-y-3 text-sm text-neutral-300">
                <li>
                    <a href="tel:+506<?= e(preg_replace('/\D/', '', ajuste('telefono'))) ?>" class="flex gap-2.5 hover:text-white">
                        <?= e(ajuste('telefono')) ?>
                    </a>
                </li>
                <li>
                    <a href="<?= e(whatsapp_url('Hola, les escribo desde la página web.')) ?>" class="flex gap-2.5 hover:text-white" target="_blank" rel="noopener">
                        WhatsApp
                    </a>
                </li>
            </ul>
        </div>

        <div>
            <h2 class="text-sm font-semibold uppercase tracking-wider">Formas de pago</h2>
            <ul class="mt-4 space-y-3 text-sm text-neutral-300">
                <li class="flex gap-2.5">SINPE Móvil</li>
                <li class="flex gap-2.5">Transferencia bancaria</li>
            </ul>
            <a href="<?= e(url('mi-pedido')) ?>" class="mt-5 inline-flex items-center gap-1 text-sm font-medium text-white hover:text-rivera-rojo">
                Consultar mi pedido
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

<?php
/**
 * Carrito. El contenido (IDs y cantidades) vive en el navegador; esta página
 * lo dibuja con public/js/carrito.js usando precios y stock reales de /api/carrito.
 */

$meta['titulo'] = 'Tu carrito';
$meta['noindex'] = true;
$meta['fondo_claro'] = true;
?>
<div class="contenedor py-8 sm:py-10" data-carrito-pagina data-api="<?= e(url('api/carrito')) ?>">
    <h1 class="font-display text-3xl font-extrabold">Tu <span class="text-rivera-rojo">carrito</span></h1>

    <noscript>
        <div class="mt-6"><?php componente('aviso', ['tipo' => 'error', 'mensaje' => 'Para usar el carrito necesitas activar JavaScript en tu navegador.']); ?></div>
    </noscript>

    <p class="mt-6 text-sm text-neutral-500" data-carrito-cargando>Cargando tu carrito…</p>

    <div class="mt-6 space-y-2" data-carrito-avisos></div>

    <!-- Carrito vacío -->
    <div class="tarjeta-clara mt-6 hidden px-6 py-14 text-center" data-carrito-vacio>
        <span class="mx-auto block w-fit text-neutral-300"><?= icono('carrito', 'h-14 w-14') ?></span>
        <h2 class="mt-4 text-xl">Tu carrito está vacío</h2>
        <p class="mt-2 text-sm text-neutral-600">Explora la tienda y agrega lo que necesitas para tu auto.</p>
        <a href="<?= e(url('tienda')) ?>" class="btn-primario mt-6">Ir a la tienda <?= icono('flecha', 'h-4 w-4') ?></a>
    </div>

    <!-- Error al cargar -->
    <div class="mt-6 hidden" data-carrito-error>
        <?php componente('aviso', ['tipo' => 'error', 'mensaje' => 'No pudimos cargar tu carrito. Revisa tu conexión y recarga la página.']); ?>
    </div>

    <!-- Contenido -->
    <div class="mt-6 hidden gap-8 lg:grid lg:grid-cols-[1fr_22rem]" data-carrito-contenido>
        <ul class="space-y-3" data-carrito-lista aria-label="Productos en el carrito"></ul>

        <aside class="mt-6 lg:mt-0">
            <div class="tarjeta-clara p-5 lg:sticky lg:top-24">
                <h2 class="text-lg">Resumen</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-neutral-600">Subtotal</dt>
                        <dd class="font-semibold" data-carrito-subtotal>₡0</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-neutral-600">Envío</dt>
                        <dd class="text-right text-neutral-600">Se calcula al confirmar el pedido</dd>
                    </div>
                </dl>
                <a href="<?= e(url('checkout')) ?>" class="btn-primario mt-5 w-full text-base">
                    Continuar con el pedido <?= icono('flecha', 'h-4 w-4') ?>
                </a>
                <a href="<?= e(url('tienda')) ?>" class="mt-3 block text-center text-sm font-medium text-neutral-600 hover:text-rivera-negro">Seguir comprando</a>
                <p class="mt-5 flex gap-2 border-t border-neutral-200 pt-4 text-xs text-neutral-500">
                    <span class="shrink-0 text-rivera-rojo"><?= icono('celular', 'h-4 w-4') ?></span>
                    Pagas por SINPE Móvil o transferencia. Tu pedido se confirma cuando verificamos el pago.
                </p>
            </div>
        </aside>
    </div>

    <!-- Plantilla de cada producto (la usa carrito.js; los textos se insertan con textContent) -->
    <template data-carrito-plantilla>
        <li class="tarjeta-clara flex gap-3 p-3 sm:gap-4 sm:p-4" data-carrito-item>
            <a data-campo="enlace" tabindex="-1" aria-hidden="true" class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-md bg-neutral-50 text-neutral-300 sm:h-24 sm:w-24">
                <img data-campo="imagen" alt="" class="hidden h-full w-full object-contain p-1">
                <span data-campo="sin-imagen"><?= icono('llave', 'h-8 w-8') ?></span>
            </a>
            <div class="flex min-w-0 flex-1 flex-col">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p data-campo="marca" class="text-[11px] font-semibold uppercase tracking-wider text-neutral-500"></p>
                        <a data-campo="nombre" class="line-clamp-2 text-sm font-semibold hover:text-rivera-rojo"></a>
                        <p data-campo="precio" class="mt-0.5 text-xs text-neutral-500"></p>
                    </div>
                    <button type="button" class="-m-1 shrink-0 p-1 text-neutral-400 hover:text-rivera-rojo" data-carrito-quitar aria-label="Quitar del carrito">
                        <?= icono('cerrar', 'h-5 w-5') ?>
                    </button>
                </div>
                <div class="mt-auto flex items-end justify-between gap-2 pt-2">
                    <div class="flex h-9 items-center rounded-md border border-neutral-300" data-cantidad>
                        <button type="button" class="h-full w-9 text-lg text-neutral-600" data-cantidad-menos aria-label="Restar uno">−</button>
                        <input type="number" min="1" inputmode="numeric" aria-label="Cantidad"
                               class="h-full w-11 border-x border-neutral-300 text-center text-sm font-semibold [appearance:textfield] focus:outline-none [&::-webkit-inner-spin-button]:appearance-none"
                               data-cantidad-campo>
                        <button type="button" class="h-full w-9 text-lg text-neutral-600" data-cantidad-mas aria-label="Sumar uno">+</button>
                    </div>
                    <p data-campo="total" class="font-display text-base font-bold"></p>
                </div>
            </div>
        </li>
    </template>
</div>

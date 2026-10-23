<?php
/**
 * Detalle de producto: galería, precio, disponibilidad, cantidad y agregar al carrito.
 * Recibe $parametros['slug'] desde el enrutador.
 */

require_once RAIZ . '/app/modelos/productos.php';

$producto = producto_por_slug($parametros['slug']);
if (!$producto) {
    no_encontrado();
}

$imagenes = producto_imagenes((int) $producto['id']);
$relacionados = productos_relacionados((int) $producto['id'], (int) $producto['category_id']);
[$textoStock, $tipoStock] = estado_stock((int) $producto['stock']);
$oferta = $producto['precio_anterior'] !== null && (float) $producto['precio_anterior'] > (float) $producto['precio'];
$agotado = $tipoStock === 'agotado';

$meta['titulo'] = $producto['nombre'];
$meta['descripcion'] = $producto['descripcion']
    ? mb_strimwidth(preg_replace('/\s+/', ' ', $producto['descripcion']), 0, 155, '…')
    : $producto['nombre'] . ' — ' . colones($producto['precio']) . '. Paga por SINPE Móvil o transferencia.';
if ($imagenes) {
    $meta['imagen'] = url_foto($imagenes[0]);
}
$meta['fondo_claro'] = true;

$coloresStock = [
    'disponible' => 'bg-emerald-500',
    'ultimas'    => 'bg-amber-500',
    'agotado'    => 'bg-neutral-400',
];
?>
<div class="contenedor py-6 sm:py-8">
    <nav class="mb-5 text-xs text-neutral-500" aria-label="Ruta">
        <a href="<?= e(url('tienda')) ?>" class="hover:text-rivera-negro">Tienda</a>
        <span class="mx-1.5">/</span>
        <a href="<?= e(url('tienda?categoria=' . rawurlencode($producto['categoria_slug']))) ?>" class="hover:text-rivera-negro"><?= e($producto['categoria_nombre']) ?></a>
        <span class="mx-1.5">/</span>
        <span class="text-neutral-700"><?= e($producto['nombre']) ?></span>
    </nav>

    <div class="grid gap-8 lg:grid-cols-2 lg:gap-12">
        <?php componente('galeria-producto', ['imagenes' => $imagenes, 'nombre' => $producto['nombre']]); ?>

        <div data-producto>
            <?php if ($producto['marca']): ?>
                <p class="text-xs font-semibold uppercase tracking-wider text-neutral-500"><?= e($producto['marca']) ?></p>
            <?php endif; ?>
            <h1 class="mt-1 font-display text-2xl font-extrabold leading-tight sm:text-3xl"><?= e($producto['nombre']) ?></h1>

            <div class="mt-4 flex flex-wrap items-center gap-3">
                <span class="font-display text-3xl font-extrabold"><?= colones($producto['precio']) ?></span>
                <?php if ($oferta): ?>
                    <span class="text-lg text-neutral-500 line-through"><?= colones($producto['precio_anterior']) ?></span>
                    <span class="insignia-oferta">Oferta</span>
                <?php endif; ?>
            </div>

            <p class="mt-3 flex items-center gap-2 text-sm font-medium">
                <span class="h-2.5 w-2.5 rounded-full <?= $coloresStock[$tipoStock] ?>"></span>
                <?= e($textoStock) ?>
            </p>

            <?php if (!$agotado): ?>
                <div class="mt-6 flex flex-wrap items-end gap-3">
                    <div>
                        <label for="cantidad" class="etiqueta-campo">Cantidad</label>
                        <div class="flex h-12 items-center rounded-md border border-neutral-300 bg-white" data-cantidad>
                            <button type="button" class="h-full w-11 text-xl text-neutral-600 hover:text-rivera-negro" data-cantidad-menos aria-label="Restar uno">−</button>
                            <input id="cantidad" type="number" value="1" min="1" max="<?= (int) $producto['stock'] ?>" inputmode="numeric"
                                   class="h-full w-14 border-x border-neutral-300 text-center text-base font-semibold [appearance:textfield] focus:outline-none [&::-webkit-inner-spin-button]:appearance-none"
                                   data-cantidad-campo>
                            <button type="button" class="h-full w-11 text-xl text-neutral-600 hover:text-rivera-negro" data-cantidad-mas aria-label="Sumar uno">+</button>
                        </div>
                    </div>
                    <button type="button" class="btn-primario h-12 flex-1 whitespace-nowrap px-4 sm:text-base"
                            data-agregar-carrito="<?= (int) $producto['id'] ?>" data-stock="<?= (int) $producto['stock'] ?>">
                        <?= icono('carrito', 'h-5 w-5') ?> Agregar al carrito
                    </button>
                </div>
            <?php endif; ?>

            <a href="<?= e(whatsapp_url('Hola, me interesa este producto: ' . $producto['nombre'] . ' — ' . url('producto/' . $producto['slug']))) ?>"
               target="_blank" rel="noopener"
               class="btn-claro w-full bg-white <?= $agotado ? 'mt-6' : 'mt-3' ?>">
                <?= icono('whatsapp', 'h-4 w-4') ?> <?= $agotado ? 'Preguntar cuándo vuelve' : 'Consultar por WhatsApp' ?>
            </a>

            <ul class="mt-6 space-y-2.5 border-t border-neutral-200 pt-6 text-sm text-neutral-700">
                <li class="flex items-center gap-2.5"><span class="text-rivera-rojo"><?= icono('celular', 'h-5 w-5') ?></span>Paga con SINPE Móvil o transferencia</li>
                <li class="flex items-center gap-2.5"><span class="text-rivera-rojo"><?= icono('camion', 'h-5 w-5') ?></span>Envío en la GAM en <?= e(ajuste('envio_plazo', '48 horas')) ?> o retiro en el taller</li>
                <li class="flex items-center gap-2.5"><span class="text-rivera-rojo"><?= icono('escudo', 'h-5 w-5') ?></span>Tu pedido se confirma cuando verificamos el pago</li>
            </ul>

            <?php if ($producto['descripcion']): ?>
                <div class="mt-8">
                    <h2 class="text-lg">Descripción</h2>
                    <div class="mt-3 text-sm leading-relaxed text-neutral-700"><?= nl2br(e($producto['descripcion'])) ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($relacionados): ?>
        <section class="mt-14">
            <?php componente('titulo-seccion', ['titulo' => 'También te puede interesar', 'enlace' => url('tienda?categoria=' . rawurlencode($producto['categoria_slug'])), 'enlace_texto' => 'Ver categoría']); ?>
            <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                <?php foreach ($relacionados as $relacionado): ?>
                    <?php componente('tarjeta-producto', ['producto' => $relacionado, 'tono' => 'claro']); ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<?php
/**
 * Guía de estilos (solo en desarrollo): todos los componentes con datos de ejemplo,
 * para revisar y aprobar el diseño.
 */

$meta['titulo'] = 'Guía de estilos';
$meta['noindex'] = true;

$colores = [
    ['Rojo Rivera', '#D11A21', 'bg-rivera-rojo'],
    ['Rojo oscuro (hover)', '#A8141A', 'bg-rivera-rojo-oscuro'],
    ['Negro', '#0B0B0B', 'bg-rivera-negro'],
    ['Carbón', '#1A1A1A', 'bg-rivera-carbon'],
    ['Blanco', '#FFFFFF', 'bg-white'],
    ['Gris apoyo', '#F2F2F2', 'bg-rivera-gris'],
];

$productos = [
    ['id' => 1, 'nombre' => 'Aceite Havoline Full Synthetic 0W-20 (1/4 gal)', 'slug' => 'ejemplo-1', 'marca' => 'Havoline',
     'precio' => 9500, 'precio_anterior' => null, 'stock' => 24, 'destacado' => 1],
    ['id' => 2, 'nombre' => 'Aceite Castrol EDGE 5W-30 (1 L)', 'slug' => 'ejemplo-2', 'marca' => 'Castrol',
     'precio' => 11500, 'precio_anterior' => 12900, 'stock' => 12, 'destacado' => 0],
    ['id' => 3, 'nombre' => 'Bombillos LED H11 (par)', 'slug' => 'ejemplo-3', 'marca' => null,
     'precio' => 18000, 'precio_anterior' => null, 'stock' => 10, 'destacado' => 0],
    ['id' => 4, 'nombre' => 'Tapetes de hule (juego de 4)', 'slug' => 'ejemplo-4', 'marca' => null,
     'precio' => 15000, 'precio_anterior' => null, 'stock' => 0, 'destacado' => 0],
];

$categorias = [
    ['nombre' => 'Aceites y Filtros', 'slug' => 'aceites-y-filtros'],
    ['nombre' => 'Rines y Llantas', 'slug' => 'rines-y-llantas'],
    ['nombre' => 'Luces LED', 'slug' => 'luces-led'],
    ['nombre' => 'Frenos', 'slug' => 'frenos'],
];
?>
<div class="contenedor space-y-16 py-12">
    <?php componente('titulo-seccion', ['nivel' => 'h1', 'etiqueta' => 'Solo en desarrollo', 'titulo' => 'Guía de estilos', 'titulo_rojo' => 'Rivera Grupo Automotriz']); ?>

    <section>
        <h2 class="mb-4 text-lg">Colores</h2>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            <?php foreach ($colores as [$nombre, $hex, $clase]): ?>
                <div class="tarjeta">
                    <div class="<?= $clase ?> h-16 border-b border-rivera-borde"></div>
                    <div class="p-3 text-xs"><p class="font-semibold"><?= e($nombre) ?></p><p class="text-neutral-400"><?= e($hex) ?></p></div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section>
        <h2 class="mb-4 text-lg">Tipografía</h2>
        <p class="font-display text-4xl font-extrabold">Saira — Títulos (como el logo)</p>
        <p class="mt-2 text-base text-neutral-300">Inter — Textos. Accesorios, repuestos y productos de calidad para tu auto. ₡28.000</p>
    </section>

    <section>
        <h2 class="mb-4 text-lg">Botones</h2>
        <div class="flex flex-wrap gap-3">
            <button class="btn-primario">Botón principal <?= icono('flecha', 'h-4 w-4') ?></button>
            <button class="btn-secundario">Botón secundario</button>
            <button class="btn-primario" disabled>Deshabilitado</button>
            <button class="btn-primario btn-pequeno">Pequeño</button>
        </div>
    </section>

    <section>
        <?php componente('titulo-seccion', ['etiqueta' => 'Categorías', 'titulo' => 'Explora por categoría', 'enlace' => url('tienda'), 'enlace_texto' => 'Ver todas']); ?>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <?php foreach ($categorias as $categoria) { componente('tarjeta-categoria', ['categoria' => $categoria]); } ?>
        </div>
    </section>

    <section>
        <?php componente('titulo-seccion', ['titulo' => 'Productos destacados', 'enlace' => url('tienda')]); ?>
        <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
            <?php foreach ($productos as $producto) { componente('tarjeta-producto', ['producto' => $producto]); } ?>
        </div>
    </section>

    <section class="-mx-4 bg-rivera-gris px-4 py-10 text-rivera-negro sm:-mx-6 sm:px-6 lg:-mx-8 lg:rounded-lg lg:px-8">
        <h2 class="mb-4 text-lg">Catálogo (fondo claro)</h2>
        <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
            <?php foreach ($productos as $producto) { componente('tarjeta-producto', ['producto' => $producto, 'tono' => 'claro']); } ?>
        </div>
    </section>

    <section class="-mx-4 sm:-mx-6 lg:-mx-8">
        <h2 class="contenedor mb-4 text-lg">Franja de beneficios</h2>
        <?php componente('beneficios'); ?>
    </section>

    <section class="grid gap-8 lg:grid-cols-2">
        <div class="space-y-3">
            <h2 class="mb-4 text-lg">Avisos</h2>
            <?php componente('aviso', ['tipo' => 'exito', 'mensaje' => 'Pedido TAL-2026-0001 creado. Te enviamos las instrucciones de pago.']); ?>
            <?php componente('aviso', ['tipo' => 'error', 'mensaje' => 'El comprobante debe ser JPG, PNG o PDF de máximo 5 MB.']); ?>
            <?php componente('aviso', ['tipo' => 'info', 'mensaje' => 'Tu pago está en revisión. Te avisaremos cuando sea aprobado.']); ?>
        </div>
        <div>
            <h2 class="mb-4 text-lg">Formulario</h2>
            <div class="space-y-4">
                <div>
                    <label for="ej-nombre" class="etiqueta-campo">Nombre completo</label>
                    <input id="ej-nombre" class="campo" placeholder="Ej. María Rodríguez">
                </div>
                <div>
                    <label for="ej-entrega" class="etiqueta-campo">Tipo de entrega</label>
                    <select id="ej-entrega" class="campo"><option>Retiro en el taller</option><option>Envío en la GAM</option></select>
                </div>
            </div>
        </div>
    </section>
</div>

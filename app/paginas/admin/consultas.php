<?php
/**
 * Consultas (leads) recibidas desde el formulario "¿Tienes preguntas?".
 * Dueño y empleado las ven y atienden (decisión del dueño); solo el dueño las borra.
 */

require_once RAIZ . '/app/modelos/consultas.php';
require_once RAIZ . '/app/modelos/reportes.php';

requerir_admin();
$meta['titulo'] = 'Consultas';
$meta['layout'] = 'admin/layout';

$conteos = contar_consultas_por_estado();
$pestanas = ['nueva' => 'Nuevas', 'contactada' => 'Contactadas', 'cerrada' => 'Cerradas', 'todas' => 'Todas'];
$pestana = is_string($_GET['ver'] ?? null) && isset($pestanas[$_GET['ver']]) ? $_GET['ver'] : null;
// Por defecto se abren las nuevas si hay; si no, todas
$pestana ??= $conteos['nueva'] > 0 ? 'nueva' : 'todas';
$q = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 60) : '';

$consultas = listar_consultas($pestana === 'todas' ? null : $pestana, $q);

$colores = [
    'nueva'      => 'bg-rivera-rojo text-white',
    'contactada' => 'bg-sky-100 text-sky-900',
    'cerrada'    => 'bg-neutral-200 text-neutral-600',
];
?>
<h1 class="font-display text-2xl font-extrabold sm:text-3xl">Consultas</h1>
<p class="mt-1 text-sm text-neutral-600">Personas que dejaron sus datos en la página para que las contacten.</p>

<!-- Pestañas -->
<nav class="-mx-4 mt-5 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:px-0" aria-label="Estados">
    <?php foreach ($pestanas as $clave => $texto): ?>
        <?php
        $cantidad = $clave === 'todas' ? array_sum($conteos) : $conteos[$clave];
        $activa = $clave === $pestana;
        ?>
        <a href="<?= e(url('admin/consultas?' . http_build_query(['ver' => $clave] + ($q !== '' ? ['q' => $q] : [])))) ?>"
           class="flex shrink-0 items-center gap-2 rounded-full border px-3.5 py-1.5 text-sm font-medium
                  <?= $activa ? 'border-rivera-negro bg-rivera-negro text-white' : 'border-neutral-300 bg-white hover:border-rivera-negro' ?>"
           <?= $activa ? 'aria-current="page"' : '' ?>>
            <?= e($texto) ?>
            <span class="rounded-full px-1.5 text-xs <?= $clave === 'nueva' && $cantidad > 0 ? 'bg-rivera-rojo text-white' : ($activa ? 'bg-white/20' : 'bg-neutral-100') ?>"><?= $cantidad ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<form method="get" action="<?= e(url('admin/consultas')) ?>" class="mt-4 flex gap-2">
    <input type="hidden" name="ver" value="<?= e($pestana) ?>">
    <label for="q" class="sr-only">Buscar</label>
    <input id="q" name="q" type="search" class="campo-claro" placeholder="Buscar por nombre, teléfono o correo…" value="<?= e($q) ?>">
    <button type="submit" class="btn-claro bg-white">Buscar</button>
</form>

<?php if ($consultas): ?>
    <ul class="mt-4 space-y-2">
        <?php foreach ($consultas as $c): ?>
            <li>
                <a href="<?= e(url('admin/consultas/' . $c['id'])) ?>"
                   class="tarjeta-clara flex flex-wrap items-start gap-x-4 gap-y-1 p-4 transition-colors hover:border-rivera-rojo">
                    <span class="min-w-0 flex-1">
                        <span class="flex flex-wrap items-center gap-2">
                            <span class="font-semibold"><?= e($c['nombre']) ?></span>
                            <span class="insignia <?= $colores[$c['estado']] ?>"><?= e(CONSULTA_ESTADOS[$c['estado']]) ?></span>
                        </span>
                        <span class="mt-0.5 block truncate text-sm text-neutral-700">
                            <?= e($c['telefono']) ?><?= $c['email'] ? ' · ' . e($c['email']) : '' ?>
                        </span>
                        <?php if ($c['mensaje']): ?>
                            <span class="mt-1 block truncate text-sm text-neutral-600">“<?= e($c['mensaje']) ?>”</span>
                        <?php endif; ?>
                    </span>
                    <span class="text-right text-xs text-neutral-500">
                        <span class="block"><?= e(date_create($c['creado_en'])->format('d/m/Y g:i a')) ?></span>
                        <span class="block">Llegó de: <?= e(nombre_origen($c['utm_source'] ?: 'directo')) ?></span>
                    </span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <div class="tarjeta-clara mt-4 px-6 py-12 text-center text-sm text-neutral-600">
        <?= $q !== '' ? 'No hay consultas que coincidan con la búsqueda.' : 'No hay consultas en esta sección.' ?>
    </div>
<?php endif; ?>

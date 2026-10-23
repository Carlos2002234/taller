<?php
/**
 * Consultas (leads) recibidas desde el formulario "¿Tienes preguntas?".
 * Dueño y empleado las ven y atienden (decisión del dueño); solo el dueño las borra.
 */

require_once RAIZ . '/app/modelos/consultas.php';
require_once RAIZ . '/app/modelos/reportes.php';
require_once RAIZ . '/app/servicios/notificaciones.php';

$usuario = requerir_admin();

// Mensaje de prueba por Telegram (solo el dueño)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    if (!es_duenio($usuario)) {
        prohibido();
    }
    if (($_POST['accion'] ?? '') === 'probar_telegram') {
        $error = enviar_telegram("✅ <b>Prueba de alertas</b>
Así te llegará el aviso cada vez que alguien deje una consulta en la página.");
        avisar($error === null ? 'exito' : 'error', $error === null
            ? 'Mensaje de prueba enviado. Revisa Telegram.'
            : 'No se pudo enviar por Telegram: ' . $error);
    }
    redirigir('admin/consultas');
}
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

<!-- Alertas cuando entra una consulta -->
<section class="tarjeta-clara mt-5 grid gap-4 p-4 sm:grid-cols-2 sm:p-5" aria-labelledby="titulo-alertas">
    <h2 id="titulo-alertas" class="sr-only">Alertas</h2>
    <div>
        <p class="flex items-center gap-2 text-sm font-semibold"><?= icono('alerta', 'h-4 w-4 text-rivera-rojo') ?> Aviso en este dispositivo</p>
        <p class="mt-1 text-xs text-neutral-600">Con el panel abierto, suena y aparece un aviso cuando entra una consulta.
            <span data-estado-notificaciones></span></p>
        <button type="button" class="btn-claro btn-pequeno mt-2 hidden bg-white" data-activar-notificaciones>Activar notificaciones</button>
    </div>
    <div>
        <p class="flex items-center gap-2 text-sm font-semibold"><?= icono('celular', 'h-4 w-4 text-rivera-rojo') ?> Aviso al celular (Telegram)</p>
        <?php if (telegram_configurado()): ?>
            <p class="mt-1 text-xs text-emerald-800">Activo: cada consulta nueva llega al instante a Telegram.</p>
            <?php if (es_duenio($usuario)): ?>
                <form method="post" action="<?= e(url('admin/consultas')) ?>" class="mt-2">
                    <?= csrf_campo() ?>
                    <input type="hidden" name="accion" value="probar_telegram">
                    <button type="submit" class="btn-claro btn-pequeno bg-white">Enviar mensaje de prueba</button>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <p class="mt-1 text-xs text-neutral-600">Todavía no está configurado. Sigue la guía <code>docs/alertas-telegram.md</code>
                (5 minutos) para recibir cada consulta al instante en tu celular.</p>
        <?php endif; ?>
    </div>
</section>

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

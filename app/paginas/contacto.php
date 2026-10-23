<?php
/**
 * Contacto y ubicación. El botón "Cómo llegar" usa el enlace de Google Maps de Configuración
 * (o busca el negocio si todavía no se configuró).
 * También recibe el formulario de consulta (POST), venga de esta página o de la ventana "¿Tienes preguntas?".
 */

require_once RAIZ . '/app/modelos/consultas.php';

$meta['titulo'] = 'Contacto y ubicación';
$meta['descripcion'] = 'Visítanos: ' . ajuste('direccion') . ' Horario: ' . ajuste('horario') . '. Teléfono y WhatsApp ' . ajuste('telefono') . '.';

$meta['datos'][] = datos_negocio();

$telefono = preg_replace('/\D/', '', ajuste('telefono'));
$redes = array_filter(['Facebook' => ajuste('facebook_url'), 'Instagram' => ajuste('instagram_url'), 'TikTok' => ajuste('tiktok_url')]);

// Formulario de consulta (lead)
$campo = ['nombre' => '', 'telefono' => '', 'email' => '', 'mensaje' => ''];
$errores = [];
$errorGeneral = null;
$enviada = ($_GET['consulta'] ?? null) === 'enviada';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    // Campo trampa: las personas no lo ven; los robots lo llenan
    if (is_string($_POST['sitio_web'] ?? null) && $_POST['sitio_web'] !== '') {
        redirigir('contacto?consulta=enviada#consulta');
    }

    [$campo, $errores] = validar_consulta($_POST);
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    if (!$errores) {
        $errorGeneral = limite_consultas_superado((string) normalizar_telefono($campo['telefono']), $ip);
    }
    if (!$errores && $errorGeneral === null) {
        crear_consulta($campo, $ip);
        redirigir('contacto?consulta=enviada#consulta');
    }

    http_response_code(422);
}
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
                    <?php if (ajuste('waze_url') !== ''): ?>
                        <a href="<?= e(ajuste('waze_url')) ?>" target="_blank" rel="noopener" class="mt-2 ml-4 inline-flex items-center gap-1 text-sm font-semibold text-rivera-rojo-claro hover:underline">
                            Abrir en Waze <?= icono('flecha', 'h-4 w-4') ?>
                        </a>
                    <?php endif; ?>
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

    <!-- Formulario de consulta -->
    <div id="consulta" class="tarjeta mt-12 scroll-mt-24 p-6 sm:p-8">
        <h2 class="titulo-seccion">¿Tienes <span class="text-rivera-rojo">preguntas?</span></h2>
        <p class="mt-2 max-w-2xl text-neutral-300">Déjanos tus datos y tu consulta; te contactamos por WhatsApp o llamada en horario de atención.</p>
        <div class="mt-6">
            <?php if ($enviada): ?>
                <div class="aviso-exito" role="status">
                    <span class="shrink-0"><?= icono('exito', 'h-5 w-5') ?></span>
                    <div>
                        <p class="font-semibold">¡Gracias! Recibimos tu consulta.</p>
                        <p class="mt-1">Te contactaremos pronto. Si es urgente, escríbenos por
                            <a href="<?= e(whatsapp_url('Hola, acabo de enviar una consulta desde la página web.')) ?>" target="_blank" rel="noopener" class="font-semibold underline">WhatsApp</a>.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php if ($errores || $errorGeneral): ?>
                    <div class="aviso-error mb-5" role="alert">
                        <span class="shrink-0"><?= icono('alerta', 'h-5 w-5') ?></span>
                        <p><?= e($errorGeneral ?? 'Revisa los datos marcados en rojo.') ?></p>
                    </div>
                <?php endif; ?>
                <?php componente('formulario-consulta', ['prefijo' => 'consulta', 'campo' => $campo, 'errores' => $errores,
                    'pagina' => is_string($_POST['pagina'] ?? null) ? mb_substr($_POST['pagina'], 0, 190) : 'contacto']); ?>
            <?php endif; ?>
        </div>
    </div>
</section>

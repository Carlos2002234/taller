<?php
/**
 * Términos y condiciones / Política de privacidad. El texto se edita en Admin → Configuración.
 * La ruta indica cuál mostrar: /terminos o /privacidad.
 */

$paginas = [
    'terminos'   => ['Términos y condiciones', 'terminos_texto'],
    'privacidad' => ['Política de privacidad', 'privacidad_texto'],
];
[$titulo, $clave] = $paginas[ruta_actual()] ?? no_encontrado();

$meta['titulo'] = $titulo;
$meta['descripcion'] = $titulo . ' de la tienda en línea de ' . ajuste('negocio_nombre', 'Rivera Grupo Automotriz') . '.';
$meta['fondo_claro'] = true;
?>
<article class="contenedor max-w-3xl py-10 sm:py-14">
    <h1 class="font-display text-3xl font-extrabold sm:text-4xl"><?= e($titulo) ?></h1>
    <div class="texto-legal tarjeta-clara mt-6 p-6 sm:p-8">
        <?= texto_legal_html(ajuste($clave)) ?: '<p>Pronto publicaremos este texto. Si tienes dudas, escríbenos por WhatsApp.</p>' ?>
        <?php if ($clave === 'privacidad_texto' && config('clarity.id') !== ''): ?>
            <h2>Análisis de uso del sitio (Microsoft Clarity)</h2>
            <p>
                Usamos Microsoft Clarity para entender cómo se usa este sitio y mejorarlo: registra clics,
                desplazamiento y movimientos dentro de las páginas, y graba sesiones de navegación de forma anónima.
                Clarity oculta lo que escribes en los formularios y usa cookies propias para agrupar las visitas.
                Microsoft trata estos datos según su
                <a href="https://privacy.microsoft.com/es-es/privacystatement" rel="noopener" target="_blank">declaración de privacidad</a>.
            </p>
        <?php endif; ?>
    </div>
</article>

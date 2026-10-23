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
    </div>
</article>

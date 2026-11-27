<?php
/**
 * Preguntas frecuentes (Inicio y página /preguntas-frecuentes). Pedido del dueño 2026-11-27.
 *
 * - Textos: app/datos/preguntas-frecuentes.php (este HTML no se toca para editarlas).
 * - Acordeón con <details>/<summary>: funciona sin JavaScript (se abre y cierra al instante).
 *   Con JavaScript (app.js [data-faq]) la respuesta se despliega con grid-template-rows 0fr → 1fr
 *   y el "+" gira a "×". Con "reducir movimiento" cambia sin animación.
 * - Encabezado igual al resto de secciones: etiqueta roja pequeña + título grande en dos líneas.
 * - Debajo, un llamado corto a WhatsApp para las dudas que no estén.
 * - El JSON-LD FAQPage lo agrega SOLO la página /preguntas-frecuentes (Google pide marcar una sola vez
 *   el mismo contenido); lo arma preguntas_frecuentes_schema() con el mismo texto que se ve aquí.
 *
 * Datos opcionales: 'nivel' => 'h1' (en la página propia) | 'h2' (por defecto, en el Inicio).
 */

require_once RAIZ . '/app/modelos/preguntas.php';

$preguntas = preguntas_frecuentes();
if (!$preguntas) {
    return;
}
$nivel = ($nivel ?? 'h2') === 'h1' ? 'h1' : 'h2';
?>
<section id="preguntas-frecuentes" class="contenedor pt-20 sm:pt-28" aria-labelledby="titulo-preguntas" data-faq>
    <div class="grid gap-10 lg:grid-cols-12 lg:gap-14">
        <div class="aparecer lg:col-span-5">
            <div class="lg:sticky lg:top-32">
                <p class="etiqueta-seccion">Preguntas frecuentes</p>
                <<?= $nivel ?> id="titulo-preguntas" class="mt-3 text-4xl font-extrabold leading-none tracking-tight sm:text-5xl">Resolvemos<br><span class="text-rivera-rojo">tus dudas</span></<?= $nivel ?>>
                <p class="mt-5 max-w-sm text-neutral-300">Pagos, envíos, horario y ubicación: lo que más nos preguntan, en pocas palabras.</p>
            </div>
        </div>

        <div class="lg:col-span-7">
            <div class="escalonado border-t border-white/10">
                <?php foreach ($preguntas as $p): ?>
                    <details class="faq-item border-b border-white/10" data-faq-item>
                        <summary class="faq-pregunta flex min-h-[4.25rem] cursor-pointer items-center justify-between gap-5 py-4 text-left">
                            <span class="text-base font-semibold leading-snug text-white sm:text-lg"><?= e($p['pregunta']) ?></span>
                            <span class="faq-icono flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-white/15 text-white" aria-hidden="true">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                            </span>
                        </summary>
                        <div class="faq-respuesta">
                            <div class="faq-respuesta-interior">
                                <p class="max-w-2xl pb-6 leading-relaxed sm:pr-12 text-neutral-300">
                                    <?= e($p['respuesta']) ?>
                                    <?php if ($p['enlace']): ?>
                                        <a href="<?= e($p['enlace']['url']) ?>"<?= $p['enlace']['externo'] ? ' target="_blank" rel="noopener"' : '' ?>
                                           class="font-semibold text-white underline decoration-rivera-rojo decoration-2 underline-offset-4 hover:text-rivera-rojo-claro"><?= e($p['enlace']['texto']) ?></a>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>

            <div class="aparecer mt-8 flex flex-wrap items-center justify-between gap-x-6 gap-y-4">
                <p class="text-neutral-300">¿No encontraste tu pregunta? <span class="text-white">Escríbenos y te respondemos.</span></p>
                <a href="<?= e(whatsapp_url('Hola, tengo una pregunta que no encontré en la página.')) ?>" target="_blank" rel="noopener" class="btn-primario shrink-0">
                    <?= icono('chat', 'h-4 w-4') ?> Preguntar por WhatsApp
                </a>
            </div>
        </div>
    </div>
</section>

<?php
/**
 * Bloque "Visítanos" de la página /ubicacion (pestaña "Ubicación" del menú), rediseño pedido por el dueño 2026-11-21:
 * "más profesional e interactivo, con transiciones". Estilo racing.
 *
 * - Izquierda (≈60%): mapa con esquinas recortadas en diagonal. Al principio es la foto de la entrada
 *   en tonos oscuros con un "mapa" dibujado encima y un pin rojo que pulsa; el mapa interactivo de Google
 *   se carga SOLO si la persona toca "Ver mapa interactivo" (más rápido y más privado; sin JavaScript ese
 *   botón abre Google Maps). El mapa usa las coordenadas exactas del enlace de Waze.
 * - Derecha (≈40%): tablero con el estado grande ("ABIERTO AHORA" / "CERRADO"), cuenta regresiva,
 *   barra de cuánto va la jornada de hoy y el horario de la semana con hoy en rojo.
 * - Dirección con "Copiar dirección", botones inclinados "Cómo llegar", "Waze" y WhatsApp.
 *
 * Datos: horario_semana() / horario_estado() (Configuración → "Horario por día"), direccion, telefono,
 * waze_url y mapa_url. app.js recalcula el estado cada minuto ([data-horario]) y carga el mapa ([data-cargar-mapa]).
 * Estilos: bloque "Visítanos" en resources/css/app.css.
 */
// $encabezado (bool, por defecto sí): muestra el título propio; la página /ubicacion lo apaga porque ya tiene su h1.
$encabezado ??= true;
require_once RAIZ . '/app/modelos/reportes.php'; // clase_ancho_barra(): ancho inicial de la barra sin estilos en línea (CSP)

$semana = horario_semana();
$estado = horario_estado();
$embed = mapa_embed_url();
$nombre = ajuste('negocio_nombre', 'Rivera Grupo Automotriz');
$direccion = ajuste('direccion');
?>
<section class="visitanos relative overflow-hidden <?= $encabezado ? 'py-20 sm:py-28' : 'pb-20 sm:pb-28' ?>" <?= $encabezado ? 'aria-labelledby="visitanos"' : 'aria-label="Mapa, horario y cómo llegar"' ?>>
    <div class="contenedor">
        <?php if ($encabezado): ?>
        <div class="aparecer mb-10 flex flex-wrap items-end justify-between gap-x-10 gap-y-4 sm:mb-14">
            <div>
                <p class="etiqueta-seccion">Ubicación y horario</p>
                <h2 id="visitanos" class="mt-3 text-4xl font-extrabold leading-[1.02] tracking-tight sm:text-5xl">Visítanos en<br><span class="text-rivera-rojo">el taller</span></h2>
            </div>
            <p class="font-display text-sm font-semibold uppercase italic tracking-[0.18em] text-neutral-500">La Puebla · Costa Rica</p>
        </div>
        <?php endif; ?>

        <div class="grid gap-5 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)] lg:gap-6">
            <!-- Mapa (en celular va debajo del tablero) -->
            <div class="order-2 lg:order-1">
                <div class="mapa-recorte relative h-full min-h-[22rem] overflow-hidden bg-rivera-carbon sm:min-h-[28rem]" data-mapa>
                    <img src="<?= e(url('img/fotos/rivera-18-1600.webp')) ?>"
                         srcset="<?= e(url('img/fotos/rivera-18-800.webp')) ?> 800w, <?= e(url('img/fotos/rivera-18-1600.webp')) ?> 1600w"
                         sizes="(min-width: 1024px) 60vw, 100vw" alt="Entrada de <?= e($nombre) ?> en La Puebla" loading="lazy"
                         class="mapa-foto absolute inset-0 h-full w-full object-cover">
                    <span class="mapa-trama" aria-hidden="true"></span>

                    <!-- Pin del taller -->
                    <span class="mapa-pin" aria-hidden="true"><span class="mapa-pin-pulso"></span><span class="mapa-pin-gota"></span></span>

                    <!-- Etiqueta del lugar -->
                    <div class="mapa-etiqueta absolute left-5 top-5 sm:left-7 sm:top-7">
                        <p class="font-display text-lg font-extrabold italic leading-tight text-white"><?= e($nombre) ?></p>
                        <p class="mt-0.5 text-xs font-semibold uppercase tracking-[0.16em] text-neutral-300">Centro de servicio · La Puebla</p>
                    </div>

                    <!-- Cargar el mapa interactivo (sin JS: abre Google Maps) -->
                    <a href="<?= e(mapa_url()) ?>" target="_blank" rel="noopener"
                       class="mapa-cargar absolute bottom-5 right-5 sm:bottom-7 sm:right-7"
                       <?php if ($embed): ?>data-cargar-mapa="<?= e($embed) ?>" data-mapa-titulo="Mapa de ubicación de <?= e($nombre) ?>"<?php endif; ?>>
                        <?= icono('ubicacion', 'h-4 w-4') ?> Ver mapa interactivo
                    </a>
                </div>
            </div>

            <!-- Tablero: estado, cuenta regresiva, jornada y horario -->
            <div class="tablero order-1 flex flex-col lg:order-2" data-horario="<?= e(json_encode($semana)) ?>">
                <div class="aparecer desde-der tarjeta tablero-estado p-6 sm:p-7">
                    <div class="flex items-center justify-between gap-4">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-neutral-400">Hoy en el taller</p>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-neutral-500">Hora de Costa Rica</p>
                    </div>
                    <p class="estado-grande <?= $estado['abierto'] ? 'abierto' : '' ?> mt-4" data-horario-estado>
                        <span class="estado-horario-punto" aria-hidden="true"></span>
                        <span data-horario-titulo><?= e($estado['titulo']) ?></span>
                    </p>
                    <p class="mt-2 text-neutral-300" data-horario-cuenta><?= e($estado['cuenta']) ?></p>
                    <span class="sr-only" data-horario-texto><?= e($estado['texto']) ?></span>

                    <!-- Jornada de hoy: de la apertura al cierre -->
                    <div class="mt-5" aria-hidden="true">
                        <div class="jornada-pista">
                            <span class="jornada-relleno <?= clase_ancho_barra((float) ($estado['progreso'] ?? 0), 100) ?>" data-horario-progreso></span>
                        </div>
                        <?php if ($semana[$estado['hoy']]): ?>
                            <div class="mt-2 flex justify-between text-[11px] font-semibold uppercase tracking-wider text-neutral-500">
                                <span>Abre <?= e(hora_12($semana[$estado['hoy']][0])) ?></span><span>Cierra <?= e(hora_12($semana[$estado['hoy']][1])) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="aparecer desde-der tarjeta mt-5 p-6 sm:p-7">
                    <h3 class="font-display text-lg font-bold italic">Horario de la semana</h3>
                    <table class="tabla-horario tabla-tablero mt-3 w-full">
                        <caption class="sr-only">Horario de atención por día (hora de Costa Rica)</caption>
                        <tbody class="escalonado">
                            <?php foreach ($semana as $dia => $horas): $esHoy = $dia === $estado['hoy']; ?>
                                <tr class="<?= $esHoy ? 'hoy' : '' ?>" data-dia="<?= $dia ?>" <?= $esHoy ? 'aria-current="date"' : '' ?>>
                                    <th scope="row">
                                        <?= e(mb_strtoupper(mb_substr(DIAS_SEMANA[$dia], 0, 1)) . mb_substr(DIAS_SEMANA[$dia], 1)) ?>
                                        <span class="tabla-horario-hoy">Hoy</span>
                                    </th>
                                    <td><?= $horas ? e(hora_12($horas[0]) . ' – ' . hora_12($horas[1])) : 'Cerrado' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Dirección y cómo llegar -->
        <div class="aparecer tarjeta mt-5 flex flex-col gap-6 p-6 sm:p-7 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex gap-4">
                <span class="mt-1 shrink-0 text-rivera-rojo"><?= icono('ubicacion', 'h-6 w-6') ?></span>
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-neutral-400">Dirección</p>
                    <p class="mt-1.5 max-w-md text-lg font-medium leading-snug text-white"><?= e($direccion) ?></p>
                    <button type="button" class="copiar-direccion mt-2" data-copiar="<?= e($nombre . ', ' . $direccion) ?>" data-copiar-texto="¡Dirección copiada!">
                        <span data-copiar-etiqueta>Copiar dirección</span>
                    </button>
                </div>
            </div>
            <div class="flex flex-wrap gap-3 pl-2 lg:shrink-0 xl:flex-nowrap">
                <a href="<?= e(mapa_url()) ?>" target="_blank" rel="noopener" class="btn-sesgo btn-sesgo-rojo"><span><?= icono('ubicacion', 'h-4 w-4') ?> Cómo llegar</span></a>
                <?php if (ajuste('waze_url') !== ''): ?>
                    <a href="<?= e(ajuste('waze_url')) ?>" target="_blank" rel="noopener" class="btn-sesgo btn-sesgo-linea"><span>Waze</span></a>
                <?php endif; ?>
                <a href="<?= e(whatsapp_url('Hola, les escribo desde la página web.')) ?>" target="_blank" rel="noopener" class="btn-sesgo btn-sesgo-linea"><span><?= icono('whatsapp', 'h-4 w-4') ?> WhatsApp</span></a>
            </div>
        </div>
    </div>
</section>

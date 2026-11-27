<?php
/**
 * Reseñas reales de Google del taller (las muestra el componente "testimonios" del inicio).
 * Textos copiados TAL CUAL de Google: no corregir ortografía ni cambiar palabras.
 *
 * CÓMO AGREGAR UNA RESEÑA NUEVA
 *   1. Copia un bloque de 'resenas' y pégalo al final de la lista (con su coma).
 *   2. Cambia 'nombre' y 'texto' por los de Google. 'estrellas' = las que dio (1 a 5).
 *   3. Actualiza 'resumen' con lo que dice Google en la ficha del negocio:
 *      'calificacion' (ej. 4.9) y 'total' (número de reseñas, CON y SIN texto).
 *   No hace falta tocar el HTML: el carrusel, los puntos, el "N reseñas" y los datos
 *   para Google (JSON-LD aggregateRating) se calculan desde este archivo.
 *
 * POR QUÉ HAY UN 'resumen' APARTE DE LA LISTA
 *   Google cuenta 11 reseñas pero solo 10 tienen texto (la otra es solo de estrellas y no se
 *   puede mostrar como tarjeta). Si el total se contara solo con la lista, diría "10 reseñas"
 *   y no coincidiría con Google. Por eso:
 *     - "N reseñas en Google" y reviewCount usan resumen.total;
 *     - si un día la lista tiene MÁS reseñas que resumen.total (se agregó una y se olvidó
 *       actualizar el resumen), se usa el número mayor (ver resenas_google() en app/modelos/resenas.php);
 *     - la calificación es resumen.calificacion (el promedio de Google, que incluye las de solo estrellas);
 *       en ese mismo caso de lista más larga, se usa el promedio de la lista.
 */

return [
    'resumen' => [
        'calificacion' => 5.0,
        'total'        => 11,
        'url'          => 'https://g.page/r/CV_ftd9wTRbwEBM/review', // ver y dejar reseñas
    ],
    'resenas' => [
        [
            'nombre'    => 'Ian Chacón',
            'estrellas' => 5,
            'texto'     => 'Llevé mi carro para mantenimiento y tuve un excelente servicio. Me solucionaron las dudas y fueron transparentes con todo el proceso, estoy muy contento con el recibí y lo recomiendo por completo!!',
        ],
        [
            'nombre'    => 'Josue Fernández',
            'estrellas' => 5,
            'texto'     => 'Excelente servicio, desde el inicio me dieron claridad y transparencia con la situación de mi carro, el trato por parte de todo el equipo, 100% recomendado',
        ],
        [
            'nombre'    => 'kendall Ocampo Castillo',
            'estrellas' => 5,
            'texto'     => 'Excelente servicio, el lugar tiene una sala de espera súper cómoda y el servicio del personal es impecable, precios justos, súper recomendado!',
        ],
        [
            'nombre'    => 'Kattya Rivera solorzano',
            'estrellas' => 5,
            'texto'     => 'Excelente servicio!! Detallado y cuidadoso. Un muy buen trato por parte de los funcionarios',
        ],
        [
            'nombre'    => 'Julián Cerdas Paniagua',
            'estrellas' => 5,
            'texto'     => 'Fui a hacer el cambio de aceite y mantenimiento de mi vehículo y volvería muchas veces más, increíble trato y un lugar muy agradable',
        ],
        [
            'nombre'    => 'Ariel',
            'estrellas' => 5,
            'texto'     => 'Excelente trato y precio de cara al cliente. Tienen excelentes valores ya que olvidé una vez pertenencias de valor y me las guardaron hasta que fui por ellas.',
        ],
        [
            'nombre'    => 'Glenn Campos',
            'estrellas' => 5,
            'texto'     => 'Fui por un lavado detallado y mi carro quedó impecable, excelente servicio los recomiendo bastante!!',
        ],
        [
            'nombre'    => 'Fabiana Arceyut',
            'estrellas' => 5,
            'texto'     => 'Fui por mantenimiento del carro, calidad, muy buen precio, y un muy buen servicio, súper recomendado',
        ],
        [
            'nombre'    => 'Cluis 2002',
            'estrellas' => 5,
            'texto'     => '100% recomendados, llevé mi vehículo y el servicio fue excelente!',
        ],
        [
            'nombre'    => 'María Celeste',
            'estrellas' => 5,
            'texto'     => 'Excelente servicio, recomendados 100%!',
        ],
    ],
];

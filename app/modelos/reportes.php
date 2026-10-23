<?php
/**
 * Reportes para el dueño: ventas por origen (anuncios) y productos más vendidos.
 * Una venta cuenta en el mes en que se APROBÓ el pago y si no se canceló después
 * (misma regla que ventas_del_mes()).
 */

declare(strict_types=1);

// Nombres para mostrar de los orígenes más comunes
const ORIGENES_NOMBRES = [
    'facebook'  => 'Facebook',
    'instagram' => 'Instagram',
    'google'    => 'Google',
    'tiktok'    => 'TikTok',
    'whatsapp'  => 'WhatsApp',
    'bing'      => 'Bing',
    'directo'   => 'Directo / sin anuncio',
];

function nombre_origen(string $origen): string
{
    return ORIGENES_NOMBRES[$origen] ?? ucfirst($origen);
}

/**
 * Pedidos creados y ventas pagadas del mes, por origen y campaña.
 * Devuelve filas: origen, campana, creados, pagados, total (monto pagado).
 */
function reporte_origenes(string $inicioMes): array
{
    $origen = "COALESCE(NULLIF(LOWER(TRIM(o.utm_source)), ''), 'directo')";
    $campana = "COALESCE(NULLIF(LOWER(TRIM(o.utm_campaign)), ''), '')";

    // Pedidos creados en el mes (pagados o no): muestra cuántos intentan comprar desde cada origen
    $stmt = db()->prepare(
        "SELECT {$origen} AS origen, {$campana} AS campana, COUNT(*) AS creados
           FROM orders o
          WHERE o.creado_en >= ? AND o.creado_en < (? + INTERVAL 1 MONTH)
          GROUP BY origen, campana"
    );
    $stmt->execute([$inicioMes, $inicioMes]);
    $filas = [];
    foreach ($stmt as $f) {
        $filas[$f['origen'] . '|' . $f['campana']] = ['origen' => $f['origen'], 'campana' => $f['campana'], 'creados' => (int) $f['creados'], 'pagados' => 0, 'total' => 0.0];
    }

    // Ventas pagadas en el mes
    $stmt = db()->prepare(
        "SELECT {$origen} AS origen, {$campana} AS campana, COUNT(*) AS pagados, SUM(o.total) AS total
           FROM orders o
           JOIN order_status_log l ON l.order_id = o.id AND l.estado_nuevo = 'pagado'
          WHERE o.estado IN ('pagado', 'en_preparacion', 'entregado')
            AND l.fecha >= ? AND l.fecha < (? + INTERVAL 1 MONTH)
          GROUP BY origen, campana"
    );
    $stmt->execute([$inicioMes, $inicioMes]);
    foreach ($stmt as $f) {
        $clave = $f['origen'] . '|' . $f['campana'];
        $filas[$clave] ??= ['origen' => $f['origen'], 'campana' => $f['campana'], 'creados' => 0, 'pagados' => 0, 'total' => 0.0];
        $filas[$clave]['pagados'] = (int) $f['pagados'];
        $filas[$clave]['total'] = (float) $f['total'];
    }

    usort($filas, fn ($a, $b) => [$b['total'], $b['creados']] <=> [$a['total'], $a['creados']]);
    return $filas;
}

/**
 * Agrupa las filas por origen (suma todas sus campañas), ordenado por monto vendido.
 */
function reporte_por_origen(array $filas): array
{
    $origenes = [];
    foreach ($filas as $f) {
        $o = &$origenes[$f['origen']];
        $o ??= ['origen' => $f['origen'], 'creados' => 0, 'pagados' => 0, 'total' => 0.0];
        $o['creados'] += $f['creados'];
        $o['pagados'] += $f['pagados'];
        $o['total'] += $f['total'];
        unset($o);
    }
    usort($origenes, fn ($a, $b) => [$b['total'], $b['creados']] <=> [$a['total'], $a['creados']]);
    return $origenes;
}

/**
 * Productos más vendidos del mes (por unidades), con el monto.
 * Se agrupa por el nombre guardado en el pedido: sirve aunque el producto ya se haya borrado.
 */
function productos_mas_vendidos(string $inicioMes, int $limite = 10): array
{
    $stmt = db()->prepare(
        "SELECT i.nombre_snapshot AS nombre, SUM(i.cantidad) AS unidades, SUM(i.cantidad * i.precio_snapshot) AS total
           FROM order_items i
           JOIN orders o ON o.id = i.order_id
           JOIN order_status_log l ON l.order_id = o.id AND l.estado_nuevo = 'pagado'
          WHERE o.estado IN ('pagado', 'en_preparacion', 'entregado')
            AND l.fecha >= ? AND l.fecha < (? + INTERVAL 1 MONTH)
          GROUP BY i.nombre_snapshot
          ORDER BY unidades DESC, total DESC
          LIMIT ?"
    );
    $stmt->bindValue(1, $inicioMes);
    $stmt->bindValue(2, $inicioMes);
    $stmt->bindValue(3, $limite, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Clase de ancho de barra en pasos de 5% (la política de seguridad no permite estilos en línea).
 * Los nombres van completos para que Tailwind los incluya en el CSS.
 */
function clase_ancho_barra(float $valor, float $maximo): string
{
    static $clases = [
        'w-0', 'w-[5%]', 'w-[10%]', 'w-[15%]', 'w-[20%]', 'w-[25%]', 'w-[30%]', 'w-[35%]', 'w-[40%]', 'w-[45%]', 'w-[50%]',
        'w-[55%]', 'w-[60%]', 'w-[65%]', 'w-[70%]', 'w-[75%]', 'w-[80%]', 'w-[85%]', 'w-[90%]', 'w-[95%]', 'w-full',
    ];
    if ($maximo <= 0 || $valor <= 0) {
        return 'w-0';
    }
    // Mínimo un paso visible para valores pequeños pero mayores que cero
    return $clases[max(1, (int) round($valor / $maximo * 20))];
}

/**
 * Igual que clase_ancho_barra() pero para barras verticales (alto en pasos de 5%).
 */
function clase_alto_barra(float $valor, float $maximo): string
{
    static $clases = [
        'h-0', 'h-[5%]', 'h-[10%]', 'h-[15%]', 'h-[20%]', 'h-[25%]', 'h-[30%]', 'h-[35%]', 'h-[40%]', 'h-[45%]', 'h-[50%]',
        'h-[55%]', 'h-[60%]', 'h-[65%]', 'h-[70%]', 'h-[75%]', 'h-[80%]', 'h-[85%]', 'h-[90%]', 'h-[95%]', 'h-full',
    ];
    if ($maximo <= 0 || $valor <= 0) {
        return 'h-0';
    }
    return $clases[max(1, (int) round($valor / $maximo * 20))];
}

<?php
/**
 * Gestión de productos desde el admin (incluye ocultos). Solo para el dueño.
 */

declare(strict_types=1);

require_once RAIZ . '/app/servicios/imagenes.php';

const PRODUCTO_MAX_FOTOS = 5;
const STOCK_BAJO = 3;

/**
 * Lista para el admin. $estado: '' | 'visibles' | 'ocultos' | 'stock-bajo'
 */
function productos_admin_listar(string $q, ?int $categoriaId, string $estado): array
{
    $where = ['1 = 1'];
    $params = [];

    if ($q !== '') {
        $like = '%' . addcslashes($q, '%_\\') . '%';
        $where[] = '(p.nombre LIKE ? OR p.marca LIKE ?)';
        array_push($params, $like, $like);
    }
    if ($categoriaId) {
        $where[] = 'p.category_id = ?';
        $params[] = $categoriaId;
    }
    $where[] = match ($estado) {
        'visibles'   => 'p.activo = 1',
        'ocultos'    => 'p.activo = 0',
        'stock-bajo' => 'p.stock <= ' . STOCK_BAJO,
        default      => '1 = 1',
    };

    $stmt = db()->prepare(
        'SELECT p.id, p.nombre, p.marca, p.precio, p.precio_anterior, p.stock, p.destacado, p.activo,
                c.nombre AS categoria,
                (SELECT pi.ruta FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.orden, pi.id LIMIT 1) AS imagen
           FROM products p JOIN categories c ON c.id = p.category_id
          WHERE ' . implode(' AND ', $where) . '
          ORDER BY p.activo DESC, p.nombre'
    );
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function producto_admin_por_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function producto_admin_imagenes(int $productoId): array
{
    $stmt = db()->prepare('SELECT id, ruta, orden FROM product_images WHERE product_id = ? ORDER BY orden, id');
    $stmt->execute([$productoId]);
    return $stmt->fetchAll();
}

/**
 * Slug único a partir del nombre ("Aceite 5W-30" → "aceite-5w-30", y "-2", "-3"... si ya existe).
 */
function slug_unico(string $tabla, string $nombre, ?int $exceptoId = null): string
{
    $tablas = ['products' => 'products', 'categories' => 'categories']; // lista blanca
    $base = slug($nombre) ?: 'producto';
    $candidato = $base;
    $stmt = db()->prepare('SELECT COUNT(*) FROM ' . $tablas[$tabla] . ' WHERE slug = ? AND id <> ?');

    for ($n = 2; ; $n++) {
        $stmt->execute([$candidato, $exceptoId ?? 0]);
        if ((int) $stmt->fetchColumn() === 0) {
            return $candidato;
        }
        $candidato = $base . '-' . $n;
    }
}

/**
 * Crea o actualiza un producto. El slug (URL) se crea una sola vez y no cambia al editar,
 * para no romper enlaces ya compartidos.
 */
function producto_guardar(?int $id, array $d): int
{
    $valores = [
        $d['category_id'], $d['nombre'], $d['marca'] ?: null, $d['descripcion'] ?: null,
        $d['precio'], $d['precio_anterior'], $d['stock'], $d['destacado'] ? 1 : 0, $d['activo'] ? 1 : 0,
    ];

    if ($id === null) {
        db()->prepare(
            'INSERT INTO products (category_id, nombre, marca, descripcion, precio, precio_anterior, stock, destacado, activo, slug)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([...$valores, slug_unico('products', $d['nombre'])]);
        return (int) db()->lastInsertId();
    }

    db()->prepare(
        'UPDATE products SET category_id = ?, nombre = ?, marca = ?, descripcion = ?, precio = ?,
                precio_anterior = ?, stock = ?, destacado = ?, activo = ? WHERE id = ?'
    )->execute([...$valores, $id]);
    return $id;
}

/**
 * Guarda fotos nuevas respetando el máximo de 5. Devuelve los mensajes de error (si hubo).
 */
function producto_agregar_fotos(int $productoId, array $archivos): array
{
    $errores = [];
    // Convertir varias fotos de cámara puede tardar: se da más tiempo que el normal (30 s)
    set_time_limit(180);
    $stmt = db()->prepare('SELECT COUNT(*), COALESCE(MAX(orden), -1) FROM product_images WHERE product_id = ?');
    $stmt->execute([$productoId]);
    [$total, $orden] = array_map('intval', $stmt->fetch(PDO::FETCH_NUM));

    foreach ($archivos as $archivo) {
        if ($total >= PRODUCTO_MAX_FOTOS) {
            $errores[] = 'Cada producto puede tener máximo ' . PRODUCTO_MAX_FOTOS . ' fotos; las demás no se guardaron.';
            break;
        }
        $resultado = guardar_imagen($archivo, 'productos');
        if (is_array($resultado)) {
            $errores[] = $resultado['error'];
            continue;
        }
        db()->prepare('INSERT INTO product_images (product_id, ruta, orden) VALUES (?, ?, ?)')
            ->execute([$productoId, $resultado, ++$orden]);
        $total++;
    }

    return $errores;
}

/**
 * Mueve una foto una posición (-1 = antes, +1 = después). La primera es la principal.
 */
function producto_mover_foto(int $productoId, int $imagenId, int $direccion): void
{
    $fotos = producto_admin_imagenes($productoId);
    $ids = array_column($fotos, 'id');
    $i = array_search($imagenId, array_map('intval', $ids), true);
    $j = $i === false ? false : $i + $direccion;
    if ($i === false || $j < 0 || $j >= count($ids)) {
        return;
    }

    [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
    $stmt = db()->prepare('UPDATE product_images SET orden = ? WHERE id = ? AND product_id = ?');
    foreach ($ids as $orden => $id) {
        $stmt->execute([$orden, $id, $productoId]);
    }
}

function producto_borrar_foto(int $productoId, int $imagenId): void
{
    $stmt = db()->prepare('SELECT ruta FROM product_images WHERE id = ? AND product_id = ?');
    $stmt->execute([$imagenId, $productoId]);
    $ruta = $stmt->fetchColumn();
    if ($ruta) {
        db()->prepare('DELETE FROM product_images WHERE id = ?')->execute([$imagenId]);
        borrar_imagen($ruta);
    }
}

/**
 * Borra el producto y sus fotos. Los pedidos anteriores conservan nombre y precio (copias).
 */
function producto_borrar(int $id): void
{
    $rutas = array_column(producto_admin_imagenes($id), 'ruta');
    db()->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
    foreach ($rutas as $ruta) {
        borrar_imagen($ruta);
    }
}

/**
 * Pedidos sin terminar que incluyen el producto (para avisar antes de borrarlo).
 */
function producto_pedidos_abiertos(int $id): int
{
    $stmt = db()->prepare(
        "SELECT COUNT(DISTINCT o.id) FROM order_items i JOIN orders o ON o.id = i.order_id
          WHERE i.product_id = ? AND o.estado IN ('pendiente_pago','comprobante_enviado','pagado','en_preparacion')"
    );
    $stmt->execute([$id]);
    return (int) $stmt->fetchColumn();
}

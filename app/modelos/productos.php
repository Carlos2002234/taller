<?php
/**
 * Consultas de productos para la tienda pública.
 * Solo se muestran productos activos de categorías activas.
 */

declare(strict_types=1);

// Columnas comunes + foto principal (la de menor orden)
const PRODUCTO_COLUMNAS = 'p.id, p.category_id, p.nombre, p.slug, p.marca, p.descripcion, p.precio,
    p.precio_anterior, p.stock, p.destacado, p.creado_en,
    (SELECT pi.ruta FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.orden, pi.id LIMIT 1) AS imagen';

// Órdenes permitidos en el catálogo (nunca se usa texto del usuario en el ORDER BY)
const PRODUCTO_ORDENES = [
    'destacados'  => ['Destacados',             'p.destacado DESC, p.creado_en DESC, p.id DESC'],
    'precio-asc'  => ['Precio: menor a mayor',  'p.precio ASC, p.id ASC'],
    'precio-desc' => ['Precio: mayor a menor',  'p.precio DESC, p.id DESC'],
    'nuevos'      => ['Más nuevos',             'p.creado_en DESC, p.id DESC'],
];

/**
 * Catálogo con filtros y paginación.
 *
 * $filtros: categoria_id (?int), marcas (string[]), min (?int), max (?int), q (string), orden (clave de PRODUCTO_ORDENES)
 * Devuelve ['productos' => [...], 'total' => int]
 */
function productos_catalogo(array $filtros, int $pagina, int $porPagina): array
{
    $where = ['p.activo = 1', 'c.activa = 1'];
    $params = [];

    if (!empty($filtros['categoria_id'])) {
        $where[] = 'p.category_id = ?';
        $params[] = (int) $filtros['categoria_id'];
    }
    if (!empty($filtros['marcas'])) {
        $where[] = 'p.marca IN (' . implode(',', array_fill(0, count($filtros['marcas']), '?')) . ')';
        array_push($params, ...$filtros['marcas']);
    }
    if (isset($filtros['min'])) {
        $where[] = 'p.precio >= ?';
        $params[] = $filtros['min'];
    }
    if (isset($filtros['max'])) {
        $where[] = 'p.precio <= ?';
        $params[] = $filtros['max'];
    }
    if (($filtros['q'] ?? '') !== '') {
        $like = '%' . addcslashes($filtros['q'], '%_\\') . '%';
        $where[] = '(p.nombre LIKE ? OR p.marca LIKE ? OR p.descripcion LIKE ?)';
        array_push($params, $like, $like, $like);
    }

    $sqlWhere = 'FROM products p JOIN categories c ON c.id = p.category_id WHERE ' . implode(' AND ', $where);
    $orden = PRODUCTO_ORDENES[$filtros['orden'] ?? ''][1] ?? PRODUCTO_ORDENES['destacados'][1];

    $stmt = db()->prepare('SELECT COUNT(*) ' . $sqlWhere);
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();

    $stmt = db()->prepare('SELECT ' . PRODUCTO_COLUMNAS . ' ' . $sqlWhere . ' ORDER BY ' . $orden . ' LIMIT ? OFFSET ?');
    foreach ($params as $i => $valor) {
        $stmt->bindValue($i + 1, $valor);
    }
    $stmt->bindValue(count($params) + 1, $porPagina, PDO::PARAM_INT);
    $stmt->bindValue(count($params) + 2, ($pagina - 1) * $porPagina, PDO::PARAM_INT);
    $stmt->execute();

    return ['productos' => $stmt->fetchAll(), 'total' => $total];
}

/**
 * Productos destacados para el inicio (con stock primero).
 */
function productos_destacados(int $limite = 8): array
{
    $stmt = db()->prepare(
        'SELECT ' . PRODUCTO_COLUMNAS . '
           FROM products p JOIN categories c ON c.id = p.category_id
          WHERE p.activo = 1 AND c.activa = 1 AND p.destacado = 1
          ORDER BY (p.stock > 0) DESC, p.creado_en DESC
          LIMIT ?'
    );
    $stmt->bindValue(1, $limite, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Marcas disponibles (para el filtro), opcionalmente dentro de una categoría.
 */
function productos_marcas(?int $categoriaId = null): array
{
    $sql = 'SELECT DISTINCT p.marca FROM products p JOIN categories c ON c.id = p.category_id
             WHERE p.activo = 1 AND c.activa = 1 AND p.marca IS NOT NULL AND p.marca <> \'\'';
    $params = [];
    if ($categoriaId) {
        $sql .= ' AND p.category_id = ?';
        $params[] = $categoriaId;
    }
    $stmt = db()->prepare($sql . ' ORDER BY p.marca');
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function producto_por_slug(string $slug): ?array
{
    $stmt = db()->prepare(
        'SELECT ' . PRODUCTO_COLUMNAS . ', c.nombre AS categoria_nombre, c.slug AS categoria_slug
           FROM products p JOIN categories c ON c.id = p.category_id
          WHERE p.slug = ? AND p.activo = 1 AND c.activa = 1'
    );
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

function producto_imagenes(int $productoId): array
{
    $stmt = db()->prepare('SELECT ruta FROM product_images WHERE product_id = ? ORDER BY orden, id LIMIT 5');
    $stmt->execute([$productoId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Otros productos de la misma categoría (con stock primero).
 */
function productos_relacionados(int $productoId, int $categoriaId, int $limite = 4): array
{
    $stmt = db()->prepare(
        'SELECT ' . PRODUCTO_COLUMNAS . '
           FROM products p
          WHERE p.category_id = ? AND p.id <> ? AND p.activo = 1
          ORDER BY (p.stock > 0) DESC, p.destacado DESC, p.creado_en DESC
          LIMIT ?'
    );
    $stmt->bindValue(1, $categoriaId, PDO::PARAM_INT);
    $stmt->bindValue(2, $productoId, PDO::PARAM_INT);
    $stmt->bindValue(3, $limite, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Productos por ID (para validar el carrito con precios y stock reales).
 */
function productos_por_ids(array $ids): array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn ($id) => $id > 0)));
    if (!$ids) {
        return [];
    }

    $stmt = db()->prepare(
        'SELECT ' . PRODUCTO_COLUMNAS . '
           FROM products p JOIN categories c ON c.id = p.category_id
          WHERE p.activo = 1 AND c.activa = 1 AND p.id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')'
    );
    $stmt->execute($ids);
    return $stmt->fetchAll();
}

/**
 * Texto de disponibilidad sin revelar el inventario exacto.
 * Devuelve [texto, tipo] donde tipo es 'disponible' | 'ultimas' | 'agotado'.
 */
function estado_stock(int $stock): array
{
    return match (true) {
        $stock <= 0 => ['Agotado', 'agotado'],
        $stock <= 3 => [$stock === 1 ? '¡Última unidad!' : "¡Últimas {$stock} unidades!", 'ultimas'],
        default     => ['Disponible', 'disponible'],
    };
}

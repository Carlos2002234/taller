<?php
/**
 * Consultas de categorías.
 */

declare(strict_types=1);

/**
 * Categorías activas con la cantidad de productos visibles en cada una.
 */
function categorias_activas(): array
{
    return db()->query(
        'SELECT c.id, c.nombre, c.slug, c.imagen, COUNT(p.id) AS total_productos
           FROM categories c
           LEFT JOIN products p ON p.category_id = c.id AND p.activo = 1
          WHERE c.activa = 1
          GROUP BY c.id, c.nombre, c.slug, c.imagen, c.orden
          ORDER BY c.orden, c.nombre'
    )->fetchAll();
}

function categoria_por_slug(string $slug): ?array
{
    $stmt = db()->prepare('SELECT id, nombre, slug, imagen FROM categories WHERE slug = ? AND activa = 1');
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

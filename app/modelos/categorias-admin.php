<?php
/**
 * Gestión de categorías desde el admin. Solo para el dueño.
 */

declare(strict_types=1);

require_once RAIZ . '/app/servicios/imagenes.php';
require_once RAIZ . '/app/modelos/productos-admin.php';

function categorias_admin_listar(): array
{
    return db()->query(
        'SELECT c.id, c.nombre, c.slug, c.imagen, c.orden, c.activa, COUNT(p.id) AS total_productos
           FROM categories c LEFT JOIN products p ON p.category_id = c.id
          GROUP BY c.id, c.nombre, c.slug, c.imagen, c.orden, c.activa
          ORDER BY c.orden, c.nombre'
    )->fetchAll();
}

function categoria_admin_por_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function categoria_guardar(?int $id, string $nombre, int $orden, bool $activa): int
{
    if ($id === null) {
        db()->prepare('INSERT INTO categories (nombre, slug, orden, activa) VALUES (?, ?, ?, ?)')
            ->execute([$nombre, slug_unico('categories', $nombre), $orden, $activa ? 1 : 0]);
        return (int) db()->lastInsertId();
    }

    db()->prepare('UPDATE categories SET nombre = ?, orden = ?, activa = ? WHERE id = ?')
        ->execute([$nombre, $orden, $activa ? 1 : 0, $id]);
    return $id;
}

/**
 * Reemplaza la foto de la tarjeta de categoría. Devuelve un error o null.
 */
function categoria_cambiar_imagen(int $id, array $archivo): ?string
{
    $resultado = guardar_imagen($archivo, 'categorias');
    if (is_array($resultado)) {
        return $resultado['error'];
    }

    $anterior = categoria_admin_por_id($id)['imagen'] ?? null;
    db()->prepare('UPDATE categories SET imagen = ? WHERE id = ?')->execute([$resultado, $id]);
    if ($anterior) {
        borrar_imagen($anterior);
    }
    return null;
}

function categoria_quitar_imagen(int $id): void
{
    $anterior = categoria_admin_por_id($id)['imagen'] ?? null;
    db()->prepare('UPDATE categories SET imagen = NULL WHERE id = ?')->execute([$id]);
    if ($anterior) {
        borrar_imagen($anterior);
    }
}

/**
 * Borra la categoría solo si no tiene productos. Devuelve un error o null.
 */
function categoria_borrar(int $id): ?string
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
    $stmt->execute([$id]);
    $total = (int) $stmt->fetchColumn();
    if ($total > 0) {
        return "No se puede borrar: tiene {$total} producto(s). Muévelos a otra categoría u ocúltala.";
    }

    $imagen = categoria_admin_por_id($id)['imagen'] ?? null;
    db()->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
    if ($imagen) {
        borrar_imagen($imagen);
    }
    return null;
}

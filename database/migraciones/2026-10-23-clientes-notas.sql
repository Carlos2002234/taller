-- Migración: agrega la tabla de notas de clientes.
-- Solo para bases creadas ANTES de este cambio (las nuevas ya la traen en schema.sql).
-- Se puede importar varias veces sin problema (IF NOT EXISTS).

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- Clientes: NO hay una tabla de clientes; la lista se arma sola con las consultas y los pedidos
-- (unidos por teléfono). Aquí solo se guardan las notas internas de cada cliente.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS clientes_notas (
    telefono        VARCHAR(20) NOT NULL,             -- mismo formato que consultas/orders: "8888-8888"
    notas           TEXT NOT NULL,
    actualizado_por INT UNSIGNED NULL,
    actualizado_en  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (telefono),
    CONSTRAINT fk_clientes_notas_usuario FOREIGN KEY (actualizado_por) REFERENCES users (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


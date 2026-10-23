-- =====================================================================
-- Rivera Grupo Automotriz — Esquema de base de datos
-- Compatible con MySQL 8+ y MariaDB 10.6+ (Hostinger).
-- Importar sobre una base de datos VACÍA ya creada (utf8mb4).
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Usuarios del panel de administración
-- rol 'duenio': todo, y es el ÚNICO que puede aprobar pagos.
-- rol 'empleado': ve pedidos y los marca en preparación / entregado.
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre          VARCHAR(100) NOT NULL,
    email           VARCHAR(150) NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,
    rol             ENUM('duenio','empleado') NOT NULL DEFAULT 'empleado',
    activo          TINYINT(1) NOT NULL DEFAULT 1,
    ultimo_acceso   DATETIME NULL,
    creado_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Intentos de inicio de sesión (para bloquear ataques de fuerza bruta)
CREATE TABLE login_attempts (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email           VARCHAR(150) NOT NULL,
    ip              VARCHAR(45) NOT NULL,
    exitoso         TINYINT(1) NOT NULL DEFAULT 0,
    fecha           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_login_email_fecha (email, fecha),
    KEY idx_login_ip_fecha (ip, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Catálogo
-- ---------------------------------------------------------------------
CREATE TABLE categories (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre          VARCHAR(100) NOT NULL,
    slug            VARCHAR(120) NOT NULL,
    imagen          VARCHAR(255) NULL,              -- foto de la tarjeta de categoría
    orden           SMALLINT NOT NULL DEFAULT 0,
    activa          TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE products (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id     INT UNSIGNED NOT NULL,
    nombre          VARCHAR(150) NOT NULL,
    slug            VARCHAR(170) NOT NULL,
    marca           VARCHAR(80) NULL,
    descripcion     TEXT NULL,
    precio          DECIMAL(12,2) NOT NULL,           -- en colones
    precio_anterior DECIMAL(12,2) NULL,               -- si tiene valor, se muestra como "Oferta"
    stock           INT NOT NULL DEFAULT 0,           -- se descuenta al crear el pedido (reserva)
    destacado       TINYINT(1) NOT NULL DEFAULT 0,
    activo          TINYINT(1) NOT NULL DEFAULT 1,    -- 0 = oculto en la tienda
    creado_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_products_slug (slug),
    KEY idx_products_categoria (activo, category_id),
    KEY idx_products_marca (marca),
    KEY idx_products_destacado (activo, destacado),
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Hasta 5 fotos por producto (límite controlado en el admin)
CREATE TABLE product_images (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id      INT UNSIGNED NOT NULL,
    ruta            VARCHAR(255) NOT NULL,            -- relativa a /public/uploads
    orden           TINYINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_product_images_producto (product_id, orden),
    CONSTRAINT fk_product_images_product FOREIGN KEY (product_id) REFERENCES products (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Envíos (solo GAM, mensajero propio). Tarifas editables desde el admin.
-- ---------------------------------------------------------------------
CREATE TABLE shipping_zones (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre          VARCHAR(100) NOT NULL,
    descripcion     VARCHAR(255) NULL,
    tarifa          DECIMAL(12,2) NOT NULL DEFAULT 0,
    activa          TINYINT(1) NOT NULL DEFAULT 1,
    orden           SMALLINT NOT NULL DEFAULT 0,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Pedidos
-- Flujo: pendiente_pago → comprobante_enviado → pagado → en_preparacion → entregado
--        (además: cancelado, rechazado)
-- NINGÚN pedido pasa a 'pagado' sin aprobación manual del dueño.
-- ---------------------------------------------------------------------
CREATE TABLE orders (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    numero_pedido       VARCHAR(20) NOT NULL,         -- ej. RGA-2026-0001
    nombre_cliente      VARCHAR(120) NOT NULL,
    telefono            VARCHAR(20) NOT NULL,
    email               VARCHAR(150) NULL,
    tipo_entrega        ENUM('retiro','envio') NOT NULL,
    zona_envio_id       INT UNSIGNED NULL,
    zona_envio_nombre   VARCHAR(100) NULL,            -- copia del nombre al momento del pedido
    direccion           TEXT NULL,
    notas               TEXT NULL,                    -- notas del cliente
    notas_internas      TEXT NULL,                    -- notas del admin (no las ve el cliente)
    subtotal            DECIMAL(12,2) NOT NULL,
    costo_envio         DECIMAL(12,2) NOT NULL DEFAULT 0,
    total               DECIMAL(12,2) NOT NULL,       -- monto exacto a pagar por SINPE/transferencia
    estado              ENUM('pendiente_pago','comprobante_enviado','pagado','en_preparacion',
                             'entregado','cancelado','rechazado') NOT NULL DEFAULT 'pendiente_pago',
    expira_en           DATETIME NOT NULL,            -- si no se paga antes, se cancela y libera stock
    utm_source          VARCHAR(100) NULL,
    utm_medium          VARCHAR(100) NULL,
    utm_campaign        VARCHAR(100) NULL,
    ip                  VARCHAR(45) NULL,             -- para limitar pedidos falsos por conexión
    creado_en           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_orders_numero (numero_pedido),
    KEY idx_orders_estado (estado, creado_en),
    KEY idx_orders_telefono (telefono),
    KEY idx_orders_expira (estado, expira_en),
    KEY idx_orders_ip (ip, creado_en),
    CONSTRAINT fk_orders_zona FOREIGN KEY (zona_envio_id) REFERENCES shipping_zones (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contador por año para generar números de pedido únicos sin choques
-- (se usa con SELECT ... FOR UPDATE dentro de una transacción).
CREATE TABLE order_sequences (
    anio            SMALLINT UNSIGNED NOT NULL,
    ultimo          INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (anio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_items (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id        INT UNSIGNED NOT NULL,
    product_id      INT UNSIGNED NULL,                -- NULL si el producto se borró después
    nombre_snapshot VARCHAR(150) NOT NULL,            -- nombre y precio al momento de la compra
    precio_snapshot DECIMAL(12,2) NOT NULL,
    cantidad        INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    KEY idx_order_items_pedido (order_id),
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comprobantes de pago. Los archivos viven en /storage/comprobantes (privado).
CREATE TABLE payment_proofs (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id        INT UNSIGNED NOT NULL,
    ruta_archivo    VARCHAR(255) NOT NULL,            -- nombre aleatorio, no adivinable
    nombre_original VARCHAR(255) NULL,
    mime            VARCHAR(50) NOT NULL,
    tamano          INT UNSIGNED NOT NULL,            -- bytes (máx. 5 MB)
    subido_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado_revision ENUM('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
    revisado_por    INT UNSIGNED NULL,
    revisado_en     DATETIME NULL,
    nota_revision   VARCHAR(255) NULL,
    PRIMARY KEY (id),
    KEY idx_payment_proofs_pedido (order_id),
    CONSTRAINT fk_payment_proofs_order FOREIGN KEY (order_id) REFERENCES orders (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_payment_proofs_user FOREIGN KEY (revisado_por) REFERENCES users (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Historial de cambios de estado (quién, cuándo y desde dónde)
CREATE TABLE order_status_log (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id        INT UNSIGNED NOT NULL,
    estado_anterior VARCHAR(30) NULL,
    estado_nuevo    VARCHAR(30) NOT NULL,
    usuario_id      INT UNSIGNED NULL,                -- NULL si lo hizo el cliente o el sistema
    origen          ENUM('admin','cliente','sistema') NOT NULL,
    nota            VARCHAR(255) NULL,
    fecha           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_status_log_pedido (order_id, fecha),
    CONSTRAINT fk_status_log_order FOREIGN KEY (order_id) REFERENCES orders (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_status_log_user FOREIGN KEY (usuario_id) REFERENCES users (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Configuración editable desde el admin (datos de pago, contacto, banner...)
-- ---------------------------------------------------------------------
CREATE TABLE settings (
    clave           VARCHAR(100) NOT NULL,
    valor           TEXT NOT NULL,
    actualizado_en  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

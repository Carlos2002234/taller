-- Migración: agrega las tablas de visitas y consultas (leads).
-- Solo para bases creadas ANTES del 2026-10-23. Las nuevas ya las traen en schema.sql.
-- Se puede importar varias veces sin problema (IF NOT EXISTS).

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- Visitas a la tienda (solo para el reporte del dueño).
-- Privacidad: NO se guarda la IP ni datos personales, y no se usan cookies.
-- "visitante" es un código anónimo que cambia cada día (hash con una sal diaria
-- que se descarta), así que no se puede seguir a una persona entre días.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS visitas (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    fecha           DATE NOT NULL,
    ruta            VARCHAR(190) NOT NULL,            -- ej. "" (inicio), "tienda", "producto/aceite-5w30"
    visitante       CHAR(16) NOT NULL,                -- código anónimo del día
    entrada         TINYINT(1) NOT NULL DEFAULT 0,    -- 1 = llegó desde fuera del sitio (inicio de la visita)
    origen          VARCHAR(40) NULL,                 -- solo en entradas: facebook, instagram, google, directo...
    dispositivo     ENUM('celular','tablet','computadora') NOT NULL,
    creado_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_visitas_fecha (fecha, visitante),
    KEY idx_visitas_ruta (fecha, ruta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Consultas (leads) del formulario "¿Tienes preguntas?"
-- Flujo: nueva → contactada → cerrada. El empleado puede atenderlas; solo el dueño las borra.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS consultas (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre          VARCHAR(120) NOT NULL,
    telefono        VARCHAR(20) NOT NULL,
    email           VARCHAR(150) NULL,
    mensaje         TEXT NULL,                        -- pregunta o comentario del cliente
    pagina          VARCHAR(190) NULL,                -- desde dónde la envió (ej. "servicios")
    estado          ENUM('nueva','contactada','cerrada') NOT NULL DEFAULT 'nueva',
    notas_internas  TEXT NULL,                        -- solo las ve el equipo
    atendido_por    INT UNSIGNED NULL,
    utm_source      VARCHAR(100) NULL,
    utm_medium      VARCHAR(100) NULL,
    utm_campaign    VARCHAR(100) NULL,
    ip              VARCHAR(45) NULL,                 -- para limitar envíos masivos (robots)
    creado_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_consultas_estado (estado, creado_en),
    KEY idx_consultas_ip (ip, creado_en),
    CONSTRAINT fk_consultas_usuario FOREIGN KEY (atendido_por) REFERENCES users (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


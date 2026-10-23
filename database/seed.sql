-- =====================================================================
-- Datos iniciales. Importar DESPUÉS de schema.sql.
-- Lo marcado como PENDIENTE se completa desde el panel de administración.
-- Los productos y las tarifas de envío son de EJEMPLO.
-- El usuario administrador NO se crea aquí: usar database/crear-admin.php
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- Configuración del negocio
-- ---------------------------------------------------------------------
INSERT INTO settings (clave, valor) VALUES
    ('negocio_nombre',          'Rivera Grupo Automotriz'),
    ('telefono',                '8702-6760'),
    ('whatsapp',                '50687026760'),   -- formato internacional para enlaces wa.me
    ('correo_avisos',           ''),              -- PENDIENTE
    ('direccion',               '150 m oeste de la plaza de deportes de La Puebla, antes de llegar a los semáforos.'),
    ('mapa_url',                ''),              -- PENDIENTE: enlace de Google Maps
    ('horario',                 'Lunes a sábado, 8:00 a.m. – 5:00 p.m.'),

    -- Pagos (nunca se aprueban automáticamente)
    ('sinpe_numero',            '8702-6760'),
    ('sinpe_titular',           'PENDIENTE'),
    ('banco_nombre',            'PENDIENTE'),
    ('banco_iban',              'PENDIENTE'),
    ('banco_titular',           'PENDIENTE'),
    ('banco_cedula',            'PENDIENTE'),

    -- Pedidos y entregas
    ('prefijo_pedido',          'RGA'),           -- Rivera Grupo Automotriz → RGA-2026-0001
    ('horas_expiracion_pedido', '48'),
    ('envio_plazo',             '48 horas'),
    ('envio_cobertura',         'Gran Área Metropolitana (GAM)'),

    -- Banner principal (texto de ejemplo tomado del prototipo)
    ('banner_etiqueta',         'Accesorios y repuestos para tu auto'),
    ('banner_titulo',           'Equipa tu camino'),
    ('banner_titulo_rojo',      'con lo mejor'),
    ('banner_texto',            'Accesorios, repuestos y productos de calidad para mantener tu auto en perfectas condiciones.'),

    -- Redes sociales (PENDIENTE)
    ('facebook_url',            ''),
    ('instagram_url',           ''),
    ('tiktok_url',              '');

-- ---------------------------------------------------------------------
-- Zonas de envío (EJEMPLO: tarifas a confirmar por el dueño)
-- ---------------------------------------------------------------------
INSERT INTO shipping_zones (nombre, descripcion, tarifa, activa, orden) VALUES
    ('Heredia',  'Cantones de Heredia dentro de la GAM',  2000, 1, 1),
    ('San José', 'Cantones de San José dentro de la GAM', 2500, 1, 2),
    ('Alajuela', 'Cantones de Alajuela dentro de la GAM', 3000, 1, 3),
    ('Cartago',  'Cantones de Cartago dentro de la GAM',  3500, 1, 4);

-- ---------------------------------------------------------------------
-- Categorías (basadas en el prototipo)
-- ---------------------------------------------------------------------
INSERT INTO categories (id, nombre, slug, orden) VALUES
    (1, 'Aceites y Filtros',       'aceites-y-filtros',       1),
    (2, 'Rines y Llantas',         'rines-y-llantas',         2),
    (3, 'Luces LED',               'luces-led',               3),
    (4, 'Frenos',                  'frenos',                  4),
    (5, 'Accesorios Exteriores',   'accesorios-exteriores',   5),
    (6, 'Accesorios Interiores',   'accesorios-interiores',   6),
    (7, 'Limpieza y Detallado',    'limpieza-y-detallado',    7);

-- ---------------------------------------------------------------------
-- Productos de EJEMPLO (borrar o editar desde el admin)
-- ---------------------------------------------------------------------
INSERT INTO products (category_id, nombre, slug, marca, descripcion, precio, precio_anterior, stock, destacado) VALUES
    (1, 'Aceite Havoline Full Synthetic 0W-20 (1/4 gal)', 'aceite-havoline-full-synthetic-0w-20',
        'Havoline', 'Aceite sintético para motores a gasolina e híbridos. Producto de ejemplo.', 9500, NULL, 24, 1),
    (1, 'Aceite Castrol EDGE 5W-30 (1 L)', 'aceite-castrol-edge-5w-30',
        'Castrol', 'Aceite sintético de alto rendimiento. Producto de ejemplo.', 11500, 12900, 12, 1),
    (1, 'Filtro de aceite (universal)', 'filtro-de-aceite-universal',
        NULL, 'Producto de ejemplo.', 4500, NULL, 30, 0),
    (3, 'Bombillos LED H11 (par)', 'bombillos-led-h11',
        NULL, 'Luz blanca de alta intensidad. Producto de ejemplo.', 18000, NULL, 10, 1),
    (6, 'Tapetes de hule (juego de 4)', 'tapetes-de-hule-juego-4',
        NULL, 'Producto de ejemplo.', 15000, NULL, 8, 0),
    (7, 'Shampoo para auto Vonixx (500 ml)', 'shampoo-vonixx-500ml',
        'Vonixx', 'Producto de ejemplo.', 6500, NULL, 15, 0);

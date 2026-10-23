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

    -- Redes sociales (confirmadas por el dueño; editables en Admin → Configuración)
    ('facebook_url',            'https://www.facebook.com/profile.php?id=61592820894749'),
    ('instagram_url',           'https://www.instagram.com/riveragrupoautomotriz/'),
    ('tiktok_url',              'https://www.tiktok.com/@riveragrupoautomotriz'),

    -- Textos legales BASE: el dueño completa lo marcado [COMPLETAR] y un abogado los revisa.
    -- {negocio}, {telefono}, {direccion}, {horario}, {horas_pago} y {envio_plazo} se reemplazan solos.
    ('terminos_texto', '## Sobre esta tienda\nEsta tienda en línea pertenece a {negocio} [COMPLETAR: razón social y número de cédula]. Al hacer un pedido aceptas estas condiciones.\n\n## Precios\nLos precios están en colones costarricenses (₡). [COMPLETAR: indicar si los precios incluyen IVA]. El precio válido es el que aparece al confirmar el pedido.\n\n## Pedidos y disponibilidad\nCada pedido recibe un número único. Los productos quedan apartados mientras el pedido está pendiente de pago. Si un producto se agota antes de que confirmes, la tienda te avisa y no se incluye en el pedido.\n\n## Pagos\n- Solo aceptamos SINPE Móvil y transferencia bancaria, por el monto exacto y con el número de pedido en el detalle del pago.\n- Debes subir el comprobante en la sección Mi pedido.\n- Verificamos cada pago manualmente en nuestra cuenta. El pedido se prepara únicamente después de esa verificación.\n- Si no recibimos el pago en {horas_pago} horas, el pedido se cancela automáticamente.\n- Si un comprobante no corresponde a un pago recibido, el pedido puede ser rechazado.\n\n## Entregas\n- Retiro en el taller ({direccion}), en horario {horario}, presentando el número de pedido.\n- Envío a domicilio dentro de la Gran Área Metropolitana con la tarifa de la zona elegida. El plazo estimado es de {envio_plazo} a partir de la verificación del pago.\n\n## Cambios, devoluciones y garantía\n[COMPLETAR: plazo para cambios y devoluciones, estado en que debe estar el producto, quién paga el envío, garantía de los productos y cómo se devuelve el dinero cuando corresponde.]\n\n## Servicios del taller\nLos servicios del taller se cotizan por WhatsApp y no se venden por medio de esta tienda.\n\n## Contacto\nWhatsApp y teléfono: {telefono}. Dirección: {direccion}.'),
    ('privacidad_texto', '## Responsable\n{negocio} [COMPLETAR: razón social y número de cédula], con dirección en {direccion}, es responsable de los datos personales que nos das al usar esta tienda. Puedes contactarnos al {telefono}, también por WhatsApp.\n\n## Qué datos recopilamos\n- Nombre, teléfono y, si lo das, correo electrónico.\n- Dirección de entrega, cuando eliges envío a domicilio.\n- El comprobante de pago que subes (imagen o PDF).\n- Datos técnicos mínimos para el funcionamiento y la seguridad del sitio, como la dirección IP.\n\n## Para qué los usamos\n- Procesar y entregar tu pedido y verificar tu pago.\n- Comunicarnos contigo sobre tu pedido.\n- Prevenir fraudes y pedidos falsos.\nNo vendemos tus datos ni los compartimos con terceros, salvo cuando la ley lo exija. No los usamos para publicidad sin tu consentimiento.\n\n## Cookies y almacenamiento en tu navegador\nUsamos una cookie de sesión necesaria para el funcionamiento y la seguridad del sitio, y guardamos tu carrito en tu propio navegador. No usamos cookies de publicidad.\n\n## Quién puede ver tus datos\nSolo el personal autorizado del negocio. Los comprobantes de pago se guardan en un área privada a la que solo ese personal tiene acceso.\n\n## Cuánto tiempo los guardamos\nConservamos los datos de los pedidos mientras sean necesarios para atenderlos y para cumplir obligaciones legales y contables. [COMPLETAR: plazo de conservación]\n\n## Tus derechos\nDe acuerdo con la Ley N.° 8968 de Protección de la Persona frente al Tratamiento de sus Datos Personales, puedes pedir acceso, corrección o eliminación de tus datos, o retirar tu consentimiento. Escríbenos por WhatsApp al {telefono} indicando tu número de pedido.\n\n## Cambios a esta política\nSi la modificamos, publicaremos la nueva versión en esta página.');

-- ---------------------------------------------------------------------
-- Zonas de envío (EJEMPLO: tarifas a confirmar por el dueño)
-- ---------------------------------------------------------------------
INSERT INTO shipping_zones (nombre, descripcion, tarifa, activa, orden) VALUES
    ('Heredia',  'Cantones de Heredia dentro de la GAM',  2000, 1, 1),
    ('San José', 'Cantones de San José dentro de la GAM', 2500, 1, 2),
    ('Alajuela', 'Cantones de Alajuela dentro de la GAM', 3000, 1, 3),
    ('Cartago',  'Cantones de Cartago dentro de la GAM',  3500, 1, 4);

-- ---------------------------------------------------------------------
-- Servicios del taller (tomados de los rótulos de las fotos).
-- REVISAR: el dueño corrige nombres y descripciones desde Admin → Servicios.
-- ---------------------------------------------------------------------
INSERT INTO services (nombre, descripcion, icono, orden, en_inicio) VALUES
    ('Cambio de aceite',            'Cambio de aceite y filtro con marcas como Havoline, Castrol y Delo.',     'aceite',    1, 1),
    ('Lavado y detallado',          'Lavado exterior e interior y detallado con productos profesionales.',    'lavado',    2, 1),
    ('Diagnóstico con escáner',     'Lectura de códigos de falla y diagnóstico electrónico de tu vehículo.',  'escaner',   3, 1),
    ('Revisión técnica vehicular',  'Revisamos tu vehículo para que llegue preparado a la RTV.',              'revision',  4, 1),
    ('Frenos y suspensión',         'Revisión y reparación de frenos y suspensión.',                          'engranaje', 5, 1),
    ('Carga para autos eléctricos', 'Contamos con cargador para vehículos eléctricos.',                       'electrico', 6, 1);

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

<?php
/**
 * Configuración editable desde el admin (tabla settings) y zonas de envío. Solo el dueño.
 */

declare(strict_types=1);

/**
 * Campos editables agrupados por sección: clave => [etiqueta, tipo, ayuda]
 * tipo: texto | telefono | iban | url | entero | prefijo | correo | largo
 */
const CONFIGURACION_CAMPOS = [
    'Pagos' => [
        'sinpe_numero'   => ['Número de SINPE Móvil', 'telefono', ''],
        'sinpe_titular'  => ['Titular del SINPE', 'texto', 'Nombre que ve el cliente al hacer el SINPE.'],
        'banco_nombre'   => ['Banco', 'texto', 'Ej. Banco Nacional, BCR, BAC…'],
        'banco_iban'     => ['Cuenta IBAN', 'iban', 'Formato CR + 20 números. Déjalo vacío para no ofrecer transferencia.'],
        'banco_titular'  => ['Titular de la cuenta', 'texto', ''],
        'banco_cedula'   => ['Cédula del titular', 'texto', 'Física o jurídica.'],
    ],
    'Contacto' => [
        'telefono'       => ['Teléfono', 'telefono', ''],
        'whatsapp'       => ['WhatsApp', 'telefono', 'Número al que escriben los clientes desde la web.'],
        'correo_avisos'  => ['Correo del negocio', 'correo', 'Opcional.'],
        'direccion'      => ['Dirección', 'largo', ''],
        'mapa_url'       => ['Enlace de Google Maps', 'url', 'En Google Maps: Compartir → Copiar enlace.'],
        'horario'        => ['Horario', 'texto', ''],
    ],
    'Pedidos y envíos' => [
        'prefijo_pedido'          => ['Prefijo del número de pedido', 'prefijo', 'Letras al inicio del número (ej. RGA → RGA-2026-0001).'],
        'horas_expiracion_pedido' => ['Horas para pagar', 'entero', 'Si no paga en este plazo, el pedido se cancela solo.'],
        'envio_plazo'             => ['Plazo de entrega', 'texto', 'Ej. 48 horas.'],
    ],
    'Banner de inicio' => [
        'banner_etiqueta'    => ['Texto pequeño (rojo)', 'texto', ''],
        'banner_titulo'      => ['Título (blanco)', 'texto', ''],
        'banner_titulo_rojo' => ['Título (segunda línea, roja)', 'texto', ''],
        'banner_texto'       => ['Descripción', 'largo', ''],
    ],
    'Redes sociales' => [
        'facebook_url'  => ['Facebook', 'url', 'Enlace completo. Vacío = no se muestra.'],
        'instagram_url' => ['Instagram', 'url', ''],
        'tiktok_url'    => ['TikTok', 'url', ''],
    ],
    'Textos legales' => [
        'terminos_texto'   => ['Términos y condiciones', 'legal', ''],
        'privacidad_texto' => ['Política de privacidad', 'legal', ''],
    ],
];

// Ayuda que se muestra junto a los textos legales
const TEXTOS_LEGALES_AYUDA = 'Formato: "## " al inicio de una línea crea un título; "- " crea una viñeta; deja una línea vacía entre párrafos. '
    . 'Puedes usar {negocio}, {telefono}, {direccion}, {horario}, {horas_pago} y {envio_plazo}: se reemplazan solos con tus datos. '
    . 'Reemplaza todo lo que dice [COMPLETAR …] antes de publicar y pide a un abogado que revise el texto.';

/**
 * Valida y normaliza un valor según su tipo. Devuelve [valor, error|null].
 */
function configuracion_validar(string $tipo, string $valor, string $etiqueta): array
{
    $valor = trim($valor);
    if ($valor === '') {
        return in_array($tipo, ['telefono', 'entero', 'prefijo'], true)
            ? ['', "«{$etiqueta}» es obligatorio."]
            : ['', null];
    }

    return match ($tipo) {
        'telefono' => ($t = normalizar_telefono($valor)) ? [$t, null] : [$valor, "«{$etiqueta}» debe ser un teléfono de 8 dígitos."],
        'iban'     => preg_match('/^CR\d{20}$/', $iban = strtoupper(preg_replace('/\s/', '', $valor)))
                        ? [trim(chunk_split($iban, 4, ' ')), null]
                        : [$valor, '«Cuenta IBAN» debe tener el formato CR seguido de 20 números.'],
        'url'      => filter_var($valor, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $valor)
                        ? [$valor, null] : [$valor, "«{$etiqueta}» debe ser un enlace completo (https://…)."],
        'correo'   => filter_var($valor, FILTER_VALIDATE_EMAIL) ? [$valor, null] : [$valor, "«{$etiqueta}» no es un correo válido."],
        'entero'   => preg_match('/^\d{1,3}$/', $valor) && (int) $valor >= 1 && (int) $valor <= 168
                        ? [$valor, null] : [$valor, "«{$etiqueta}» debe ser un número entre 1 y 168."],
        'prefijo'  => preg_match('/^[A-Z0-9]{2,5}$/', $p = strtoupper($valor)) ? [$p, null] : [$valor, "«{$etiqueta}» debe tener de 2 a 5 letras o números."],
        'legal'    => [mb_substr(str_replace("\r\n", "\n", $valor), 0, 20000), null],
        default    => [mb_substr($valor, 0, $tipo === 'largo' ? 500 : 150), null],
    };
}

function configuracion_guardar(array $valores): void
{
    $stmt = db()->prepare('INSERT INTO settings (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)');
    foreach ($valores as $clave => $valor) {
        $stmt->execute([$clave, $valor]);
    }
}

function zonas_admin_listar(): array
{
    return db()->query('SELECT * FROM shipping_zones ORDER BY orden, nombre')->fetchAll();
}

function zona_guardar(?int $id, string $nombre, ?string $descripcion, int $tarifa, int $orden, bool $activa): void
{
    if ($id === null) {
        db()->prepare('INSERT INTO shipping_zones (nombre, descripcion, tarifa, orden, activa) VALUES (?, ?, ?, ?, ?)')
            ->execute([$nombre, $descripcion, $tarifa, $orden, $activa ? 1 : 0]);
        return;
    }
    db()->prepare('UPDATE shipping_zones SET nombre = ?, descripcion = ?, tarifa = ?, orden = ?, activa = ? WHERE id = ?')
        ->execute([$nombre, $descripcion, $tarifa, $orden, $activa ? 1 : 0, $id]);
}

/**
 * Los pedidos guardan el nombre de la zona, así que borrarla no afecta pedidos anteriores.
 */
function zona_borrar(int $id): void
{
    db()->prepare('DELETE FROM shipping_zones WHERE id = ?')->execute([$id]);
}

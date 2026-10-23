<?php
/**
 * Consultas (leads) del formulario "¿Tienes preguntas?".
 * Flujo: nueva → contactada → cerrada. El empleado las atiende; solo el dueño las borra.
 */

declare(strict_types=1);

const CONSULTA_ESTADOS = [
    'nueva'      => 'Nueva',
    'contactada' => 'Contactada',
    'cerrada'    => 'Cerrada',
];

// Anti-abuso (robots que llenan formularios)
const CONSULTAS_POR_HORA_POR_IP = 5;
const CONSULTAS_NUEVAS_POR_TELEFONO = 3;

/**
 * Valida los datos del formulario. Devuelve [datos limpios, errores].
 * Obligatorios: nombre y teléfono (decisión del dueño). Correo y mensaje son opcionales.
 */
function validar_consulta(array $entrada): array
{
    $texto = function (string $clave, int $max) use ($entrada): string {
        $valor = is_string($entrada[$clave] ?? null) ? $entrada[$clave] : '';
        return mb_check_encoding($valor, 'UTF-8') ? mb_substr(trim($valor), 0, $max) : '';
    };

    $datos = [
        'nombre'   => $texto('nombre', 120),
        'telefono' => $texto('telefono', 20),
        'email'    => $texto('email', 150),
        'mensaje'  => $texto('mensaje', 1000),
    ];
    $errores = [];

    if (mb_strlen($datos['nombre']) < 2) {
        $errores['nombre'] = 'Escribe tu nombre.';
    }
    if (normalizar_telefono($datos['telefono']) === null) {
        $errores['telefono'] = 'Escribe un número de teléfono de 8 dígitos.';
    }
    if ($datos['email'] !== '' && !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
        $errores['email'] = 'El correo no es válido (o déjalo vacío).';
    }
    if (!isset($entrada['acepto'])) {
        $errores['acepto'] = 'Debes aceptar la Política de privacidad para que te contactemos.';
    }

    $datos['utm_source'] = $texto('utm_source', 100);
    $datos['utm_medium'] = $texto('utm_medium', 100);
    $datos['utm_campaign'] = $texto('utm_campaign', 100);
    $datos['pagina'] = $texto('pagina', 190);

    return [$datos, $errores];
}

/**
 * Mensaje de error si se superó el límite de envíos, o null.
 */
function limite_consultas_superado(string $telefono, string $ip): ?string
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM consultas WHERE ip = ? AND creado_en > (NOW() - INTERVAL 1 HOUR)');
    $stmt->execute([$ip]);
    if ((int) $stmt->fetchColumn() >= CONSULTAS_POR_HORA_POR_IP) {
        return 'Se enviaron demasiadas consultas desde esta conexión. Intenta de nuevo en una hora o escríbenos por WhatsApp.';
    }

    $stmt = db()->prepare("SELECT COUNT(*) FROM consultas WHERE telefono = ? AND estado = 'nueva'");
    $stmt->execute([$telefono]);
    if ((int) $stmt->fetchColumn() >= CONSULTAS_NUEVAS_POR_TELEFONO) {
        return 'Ya recibimos tus consultas y te contactaremos pronto. Si es urgente, escríbenos por WhatsApp.';
    }

    return null;
}

function crear_consulta(array $datos, string $ip): int
{
    db()->prepare(
        'INSERT INTO consultas (nombre, telefono, email, mensaje, pagina, utm_source, utm_medium, utm_campaign, ip)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $datos['nombre'],
        normalizar_telefono($datos['telefono']),
        $datos['email'] !== '' ? $datos['email'] : null,
        $datos['mensaje'] !== '' ? $datos['mensaje'] : null,
        $datos['pagina'] !== '' ? $datos['pagina'] : null,
        $datos['utm_source'] !== '' ? strtolower($datos['utm_source']) : null,
        $datos['utm_medium'] !== '' ? $datos['utm_medium'] : null,
        $datos['utm_campaign'] !== '' ? $datos['utm_campaign'] : null,
        $ip,
    ]);
    return (int) db()->lastInsertId();
}

function contar_consultas_nuevas(): int
{
    return (int) db()->query("SELECT COUNT(*) FROM consultas WHERE estado = 'nueva'")->fetchColumn();
}

/**
 * Cantidad por estado: ['nueva' => 3, 'contactada' => 1, 'cerrada' => 0]
 */
function contar_consultas_por_estado(): array
{
    $conteo = array_fill_keys(array_keys(CONSULTA_ESTADOS), 0);
    foreach (db()->query('SELECT estado, COUNT(*) AS n FROM consultas GROUP BY estado') as $f) {
        $conteo[$f['estado']] = (int) $f['n'];
    }
    return $conteo;
}

/**
 * Lista de consultas (las más recientes primero). $estado null = todas; $q busca por nombre, teléfono o correo.
 */
function listar_consultas(?string $estado, string $q = '', int $limite = 200): array
{
    $sql = 'SELECT c.*, u.nombre AS atendido_nombre FROM consultas c LEFT JOIN users u ON u.id = c.atendido_por';
    $condiciones = [];
    $parametros = [];
    if ($estado !== null) {
        $condiciones[] = 'c.estado = ?';
        $parametros[] = $estado;
    }
    if ($q !== '') {
        $like = '%' . addcslashes($q, '%_\\') . '%';
        $digitos = preg_replace('/\D/', '', $q);
        $condiciones[] = '(c.nombre LIKE ? OR c.email LIKE ?' . ($digitos !== '' ? ' OR REPLACE(c.telefono, \'-\', \'\') LIKE ?' : '') . ')';
        array_push($parametros, $like, $like);
        if ($digitos !== '') {
            $parametros[] = '%' . $digitos . '%';
        }
    }
    if ($condiciones) {
        $sql .= ' WHERE ' . implode(' AND ', $condiciones);
    }
    $stmt = db()->prepare($sql . ' ORDER BY c.creado_en DESC, c.id DESC LIMIT ' . $limite);
    $stmt->execute($parametros);
    return $stmt->fetchAll();
}

function consulta_por_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT c.*, u.nombre AS atendido_nombre FROM consultas c LEFT JOIN users u ON u.id = c.atendido_por WHERE c.id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Cambia el estado y las notas internas. Queda registrado quién la atendió.
 */
function actualizar_consulta(int $id, string $estado, string $notas, int $usuarioId): void
{
    db()->prepare('UPDATE consultas SET estado = ?, notas_internas = ?, atendido_por = ? WHERE id = ?')
        ->execute([$estado, $notas !== '' ? $notas : null, $usuarioId, $id]);
}

function borrar_consulta(int $id): void
{
    db()->prepare('DELETE FROM consultas WHERE id = ?')->execute([$id]);
}

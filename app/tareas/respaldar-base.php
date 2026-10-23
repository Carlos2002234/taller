<?php
/**
 * Respaldo de la base de datos: genera storage/respaldos/taller-AAAAMMDD-HHMMSS.sql.gz
 * y conserva solo los 14 más recientes. No necesita mysqldump (usa la misma conexión del sitio).
 *
 * Programar en Hostinger (hPanel → Avanzado → Cron Jobs) una vez al día:
 *   php /home/USUARIO/domains/tudominio.com/taller/app/tareas/respaldar-base.php
 *
 * Restaurar: descomprimir el .gz e importar el .sql con phpMyAdmin en una base vacía.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/config/config.php';

const RESPALDOS_A_CONSERVAR = 14;

$carpeta = RAIZ . '/storage/respaldos';
if (!is_dir($carpeta) && !mkdir($carpeta, 0700, true)) {
    fwrite(STDERR, "No se pudo crear la carpeta {$carpeta}\n");
    exit(1);
}

$db = db();
$sql = "-- Respaldo de " . ajuste('negocio_nombre', 'la tienda') . ' — ' . date('Y-m-d H:i:s') . "\n"
     . "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n";

$tablas = $db->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tablas as $tabla) {
    $nombre = '`' . str_replace('`', '``', $tabla) . '`';
    $crear = $db->query('SHOW CREATE TABLE ' . $nombre)->fetch(PDO::FETCH_NUM)[1];
    $sql .= "DROP TABLE IF EXISTS {$nombre};\n{$crear};\n";

    $filas = $db->query('SELECT * FROM ' . $nombre, PDO::FETCH_ASSOC);
    $lote = [];
    $columnas = null;
    foreach ($filas as $fila) {
        $columnas ??= implode(', ', array_map(fn ($c) => '`' . str_replace('`', '``', $c) . '`', array_keys($fila)));
        $lote[] = '(' . implode(', ', array_map(fn ($v) => $v === null ? 'NULL' : $db->quote((string) $v), $fila)) . ')';
        if (count($lote) === 200) {
            $sql .= "INSERT INTO {$nombre} ({$columnas}) VALUES\n" . implode(",\n", $lote) . ";\n";
            $lote = [];
        }
    }
    if ($lote) {
        $sql .= "INSERT INTO {$nombre} ({$columnas}) VALUES\n" . implode(",\n", $lote) . ";\n";
    }
    $sql .= "\n";
}
$sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";

$archivo = $carpeta . '/taller-' . date('Ymd-His') . '.sql.gz';
if (file_put_contents($archivo, gzencode($sql, 9)) === false) {
    fwrite(STDERR, "No se pudo escribir {$archivo}\n");
    exit(1);
}
chmod($archivo, 0600);

// Borrar los más antiguos
$respaldos = glob($carpeta . '/taller-*.sql.gz') ?: [];
rsort($respaldos);
foreach (array_slice($respaldos, RESPALDOS_A_CONSERVAR) as $viejo) {
    unlink($viejo);
}

printf("%s — respaldo creado: %s (%s KB, %d tablas)\n", date('Y-m-d H:i:s'), basename($archivo), number_format(filesize($archivo) / 1024, 1), count($tablas));

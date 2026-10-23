<?php
/**
 * Punto de entrada único del sitio: todas las páginas pasan por aquí.
 */

declare(strict_types=1);

require dirname(__DIR__) . '/app/config/config.php';
require RAIZ . '/app/core/router.php';

despachar();

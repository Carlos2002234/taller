<?php
/**
 * Cerrar sesión del panel (solo por POST con CSRF, para que un enlace externo no pueda cerrarla).
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('admin');
}

verificar_csrf();
cerrar_sesion_admin();
avisar('exito', 'Cerraste sesión.');
redirigir('admin/login');

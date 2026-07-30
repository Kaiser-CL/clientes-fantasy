<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['id_usuario'])) {
    header("Location: login.php?error=sesion_requerida");
    exit();
}

function esSuperAdmin() {
    return isset($_SESSION['id_rol']) && (int)$_SESSION['id_rol'] === 3;
}


function obtenerEmpleadoFiltro() {
    if (esSuperAdmin()) {
        return null;
    }
    return $_SESSION['id_usuario'] ?? null;
}
?>
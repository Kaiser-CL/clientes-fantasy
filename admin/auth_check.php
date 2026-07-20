<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si hay una sesión activa
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php?error=sesion_requerida");
    exit();
}

// Funciones helper para control de roles en las vistas
function esSuperAdmin() {
    return isset($_SESSION['id_rol']) && $_SESSION['id_rol'] === 3;
}

function obtenerEmpleadoFiltro() {
    // Si es SuperAdmin retorna NULL (ve todo), de lo contrario retorna su propio ID de empleado
    if (esSuperAdmin()) {
        return null;
    }
    return $_SESSION['usuario_id'];
}
?>
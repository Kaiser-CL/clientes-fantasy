<?php
// Archivo central de conexión para el Panel Administrativo y scripts PDO

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . '/'); 
}

$path_database = ROOT_PATH . 'config/database.php';

if (!file_exists($path_database)) {
    $path_database = dirname(__DIR__) . '/config/database.php';
}

if (!file_exists($path_database)) {
    die("Error crítico: No se encontró el archivo de configuración en: " . $path_database);
}

require_once $path_database;

try {
    $db = new Database();
    $pdo = $db->conectar();
} catch (Exception $e) {
    die("Error crítico de conexión a la base de datos: " . $e->getMessage());
}

/**
 * FUNCIÓN GLOBAL DE AUDITORÍA (BITÁCORA)
 * Registra cualquier acción en la tabla historial_cambios
 */
function registrarBitacora($pdo, $accion, $tabla_afectada, $id_registro = 0, $datos_anteriores = null, $datos_nuevos = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $id_usuario = $_SESSION['id_usuario'] ?? null;

    // Convertir arrays o datos a JSON legibles si vienen como array
    $ant_json = is_array($datos_anteriores) ? json_encode($datos_anteriores, JSON_UNESCAPED_UNICODE) : $datos_anteriores;
    $nuev_json = is_array($datos_nuevos) ? json_encode($datos_nuevos, JSON_UNESCAPED_UNICODE) : $datos_nuevos;

    try {
        $sql = "INSERT INTO historial_cambios (id_usuario, accion, tabla_afectada, id_registro, fecha_cambio, datos_anteriores, datos_nuevos) 
                VALUES (?, ?, ?, ?, NOW(), ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $id_usuario,
            strtoupper($accion), // 'AGREGAR', 'ACTUALIZAR', 'ELIMINAR'
            strtolower($tabla_afectada),
            $id_registro,
            $ant_json,
            $nuev_json
        ]);
    } catch (PDOException $e) {
        error_log("Error al escribir en la bitácora: " . $e->getMessage());
    }
}

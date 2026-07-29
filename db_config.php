<?php
// Archivo central de conexión para el Panel Administrativo y scripts PDO

// Cargar la clase central de la base de datos
$path_database = __DIR__ . '/config/database.php';

if (!file_exists($path_database)) {
    // Respaldo si el archivo se incluye desde la carpeta /admin/
    $path_database = dirname(__DIR__) . '/config/database.php';
}

require_once $path_database;

try {
    $db = new Database();
    // Reutilizamos la conexión PDO que genera la clase Database
    $pdo = $db->conectar();
} catch (Exception $e) {
    die("Error crítico de conexión a la base de datos: " . $e->getMessage());
}
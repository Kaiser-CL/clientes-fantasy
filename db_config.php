<?php
// Archivo central de conexión para el Panel Administrativo y scripts PDO

// 1. Definimos la ruta raíz absoluta de la aplicación.
// Si este archivo está dentro de una carpeta (como /admin/ o similar),
// dirname(__DIR__) retrocede automáticamente a la raíz de tu proyecto.
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . '/'); 
}

// 2. Apuntamos a la base de datos de la carpeta config usando la ruta absoluta
$path_database = ROOT_PATH . 'config/database.php';

// Si este archivo está dentro de la carpeta /admin/, usa la siguiente línea en su lugar:
// $path_database = dirname(__DIR__) . '/config/database.php';

if (!file_exists($path_database)) {
    // Si no lo encuentra en la ruta directa, intenta subir un nivel
    $path_database = dirname(__DIR__) . '/config/database.php';
}

if (!file_exists($path_database)) {
    die("Error crítico: No se encontró el archivo de configuración en: " . $path_database);
}

require_once $path_database;

try {
    $db = new Database();
    // Reutilizamos la conexión PDO que genera la clase Database
    $pdo = $db->conectar();
} catch (Exception $e) {
    die("Error crítico de conexión a la base de datos: " . $e->getMessage());
}

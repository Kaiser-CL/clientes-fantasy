<?php

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

require_once '../config/database.php';

$db = new Database();
$conn = $db->conectar();

$data = json_decode(file_get_contents("php://input"), true);

$nombre    = $data['nombre']    ?? '';
$apellidos = $data['apellidos'] ?? '';
$correo    = $data['correo']    ?? '';
$telefono  = $data['telefono']  ?? '';
$contrasena = $data['contrasena'] ?? '';

if (empty($nombre) || empty($apellidos) || empty($correo) || empty($contrasena)) {
    echo json_encode([
        "success" => false,
        "mensaje" => "Faltan datos"
    ]);
    exit;
}

// Verificar si el correo ya existe (columna corregida para TiDB)
$sql = "SELECT id_usuario FROM usuarios WHERE correo_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$correo]);

if ($stmt->rowCount() > 0) {
    echo json_encode([
        "success" => false,
        "mensaje" => "El correo ya existe"
    ]);
    exit;
}

$passwordHash = password_hash($contrasena, PASSWORD_DEFAULT);

// Columnas corregidas para coincidir con el esquema de TiDB
$sql = "INSERT INTO usuarios (nombre_usuario, apellidos_usuario, correo_usuario, telefono_usuario, contrasena_usuario, id_rol)
VALUES (?, ?, ?, ?, ?, 2)";

$stmt = $conn->prepare($sql);

$resultado = $stmt->execute([
    $nombre,
    $apellidos,
    $correo,
    $telefono,
    $passwordHash
]);

echo json_encode([
    "success" => $resultado,
    "mensaje" => $resultado ? "Usuario registrado correctamente" : "Error al registrar"
]);
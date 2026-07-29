<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$data = json_decode(file_get_contents("php://input"), true);

$nombre = $data['nombre'] ?? '';
$apellidos = $data['apellidos'] ?? '';
$correo = $data['correo'] ?? '';
$telefono = $data['telefono'] ?? '';
$contrasena = $data['contrasena'] ?? '';
$id_rol = $data['id_rol'] ?? 2;

if(
    empty($nombre) ||
    empty($apellidos) ||
    empty($correo) ||
    empty($contrasena)
){
    echo json_encode([
        "success" => false,
        "message" => "Faltan datos"
    ]);
    exit;
}

$passwordHash = password_hash($contrasena, PASSWORD_DEFAULT);

$sql = "
INSERT INTO usuarios
(
    nombre,
    apellidos,
    correo,
    telefono,
    contrasena,
    id_rol
)
VALUES
(
    ?, ?, ?, ?, ?, ?
)
";

$stmt = $conn->prepare($sql);

$resultado = $stmt->execute([
    $nombre,
    $apellidos,
    $correo,
    $telefono,
    $passwordHash,
    $id_rol
]);

echo json_encode([
    "success" => $resultado
]);
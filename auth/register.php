<?php

header('Content-Type: application/json');

require_once '../config/database.php';

$db = new Database();
$conn = $db->conectar();

$data = json_decode(file_get_contents("php://input"), true);

$nombre = $data['nombre'] ?? '';
$apellidos = $data['apellidos'] ?? '';
$correo = $data['correo'] ?? '';
$telefono = $data['telefono'] ?? '';
$contrasena = $data['contrasena'] ?? '';

if(
    empty($nombre) ||
    empty($apellidos) ||
    empty($correo) ||
    empty($contrasena)
){
    echo json_encode([
        "success" => false,
        "mensaje" => "Faltan datos"
    ]);
    exit;
}

$sql = "SELECT id_usuario FROM usuarios WHERE correo = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$correo]);

if($stmt->rowCount() > 0){

    echo json_encode([
        "success" => false,
        "mensaje" => "El correo ya existe"
    ]);
    exit;
}

$passwordHash = password_hash(
    $contrasena,
    PASSWORD_DEFAULT
);

$sql = "INSERT INTO usuarios
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
?,
?,
?,
?,
?,
2
)";

$stmt = $conn->prepare($sql);

$resultado = $stmt->execute([
    $nombre,
    $apellidos,
    $correo,
    $telefono,
    $passwordHash
]);

echo json_encode([
    "success" => $resultado
]);
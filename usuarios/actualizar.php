<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$data = json_decode(file_get_contents("php://input"), true);

$id_usuario = $data['id_usuario'] ?? 0;
$nombre = $data['nombre'] ?? '';
$apellidos = $data['apellidos'] ?? '';
$correo = $data['correo'] ?? '';
$telefono = $data['telefono'] ?? '';

$sql = "
UPDATE usuarios
SET
    nombre = ?,
    apellidos = ?,
    correo = ?,
    telefono = ?
WHERE id_usuario = ?
";

$stmt = $conn->prepare($sql);

$resultado = $stmt->execute([
    $nombre,
    $apellidos,
    $correo,
    $telefono,
    $id_usuario
]);

echo json_encode([
    "success" => $resultado
]);
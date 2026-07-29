<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$data = json_decode(file_get_contents("php://input"), true);

$sql = "
UPDATE servicios
SET
    nombre=?,
    descripcion_servicio=?,
    precio_servicio=?,
    imagen_servicio=?,
    id_categoria=?,
    disponible_servicio=?
WHERE id_servicio=?
";

$stmt = $conn->prepare($sql);

$resultado = $stmt->execute([
    $data['nombre'],
    $data['descripcion'],
    $data['precio'],
    $data['imagen'],
    $data['id_categoria'],
    $data['disponible'],
    $data['id_servicio']
]);

echo json_encode([
    "success" => $resultado
]);
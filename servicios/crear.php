<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$data = json_decode(file_get_contents("php://input"), true);

$nombre = $data['nombre'] ?? '';
$descripcion = $data['descripcion'] ?? '';
$precio = $data['precio'] ?? 0;
$id_categoria = $data['id_categoria'] ?? null;
$imagen = $data['imagen'] ?? null;

$sql = "
INSERT INTO servicios
(
    nombre,
    descripcion,
    precio,
    imagen,
    id_categoria
)
VALUES
(
    ?, ?, ?, ?, ?
)
";

$stmt = $conn->prepare($sql);

$resultado = $stmt->execute([
    $nombre,
    $descripcion,
    $precio,
    $imagen,
    $id_categoria
]);

echo json_encode([
    "success" => $resultado
]);
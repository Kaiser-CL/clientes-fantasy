<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$data = json_decode(file_get_contents("php://input"), true);

$id_usuario = $data['id_usuario'];
$id_evento = $data['id_evento'];
$id_servicio = $data['id_servicio'];
$comentario = $data['comentario'] ?? '';

$sql = "
INSERT INTO solicitudes_servicio
(
    id_usuario,
    id_evento,
    id_servicio,
    comentario
)
VALUES
(
    ?, ?, ?, ?
)
";

$stmt = $conn->prepare($sql);

$resultado = $stmt->execute([
    $id_usuario,
    $id_evento,
    $id_servicio,
    $comentario
]);

echo json_encode([
    "success" => $resultado
]);
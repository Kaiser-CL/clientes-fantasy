<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$data = json_decode(file_get_contents("php://input"), true);

$sql = "
INSERT INTO notificaciones
(
    id_usuario,
    titulo,
    mensaje
)
VALUES
(
    ?, ?, ?
)
";

$stmt = $conn->prepare($sql);

$resultado = $stmt->execute([
    $data['id_usuario'],
    $data['titulo'],
    $data['mensaje']
]);

echo json_encode([
    "success" => $resultado
]);
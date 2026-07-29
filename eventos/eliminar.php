<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$id_evento = $_GET['id_evento'] ?? 0;

$sql = "
UPDATE eventos
SET estado_usuario='cancelado'
WHERE id_evento=?
";

$stmt = $conn->prepare($sql);

$resultado = $stmt->execute([$id_evento]);

echo json_encode([
    "success" => $resultado
]);
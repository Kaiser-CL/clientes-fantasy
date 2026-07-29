<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$id_usuario = $_GET['id_usuario'] ?? 0;

$sql = "
UPDATE usuarios
SET estado = 0
WHERE id_usuario = ?
";

$stmt = $conn->prepare($sql);

$resultado = $stmt->execute([$id_usuario]);

echo json_encode([
    "success" => $resultado
]);
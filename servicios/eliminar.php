<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$id_servicio = $_GET['id_servicio'] ?? 0;

$sql = "
UPDATE servicios
SET disponible = 0
WHERE id_servicio = ?
";

$stmt = $conn->prepare($sql);

$resultado = $stmt->execute([$id_servicio]);

echo json_encode([
    "success" => $resultado
]);
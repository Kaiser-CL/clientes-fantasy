<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$id_servicio = $_GET['id_servicio'] ?? 0;

$sql = "
SELECT
    s.*,
    c.nombre_categoria AS categoria
FROM servicios s
LEFT JOIN categorias_servicio c
ON s.id_categoria = c.id_categoria
WHERE s.id_servicio = ?
";

$stmt = $conn->prepare($sql);
$stmt->execute([$id_servicio]);

$servicio = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "servicio" => $servicio
]);
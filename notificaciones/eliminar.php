<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$id_notificacion = $_GET['id_notificacion'] ?? 0;

$sql = "
DELETE FROM notificaciones
WHERE id_notificacion = ?
";

$stmt = $conn->prepare($sql);

$resultado = $stmt->execute([$id_notificacion]);

echo json_encode([
    "success" => $resultado
]);
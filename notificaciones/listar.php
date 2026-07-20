<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$id_usuario = $_GET['id_usuario'] ?? 0;

$sql = "
SELECT *
FROM notificaciones
WHERE id_usuario = ?
ORDER BY fecha_envio DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute([$id_usuario]);

$notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "notificaciones" => $notificaciones
]);
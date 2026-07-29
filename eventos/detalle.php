<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$id_evento = $_GET['id_evento'] ?? 0;

$sql = "
SELECT
    e.*,
    CONCAT(u.nombre_usuario,' ',u.apellidos_usuario) AS cliente,
    te.nombre_tipo
FROM eventos e
INNER JOIN usuarios u
ON e.id_cliente = u.id_usuario
LEFT JOIN tipos_evento te
ON e.id_clasificacion_evento = te.id_clasificacion_evento
WHERE e.id_evento = ?
";

$stmt = $conn->prepare($sql);
$stmt->execute([$id_evento]);

$evento = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "evento" => $evento
]);
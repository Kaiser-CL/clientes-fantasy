<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$sql = "
SELECT
    e.id_evento,
    e.id_cliente,
    e.nombre_evento,
    e.fecha_evento,
    e.hora_evento,
    e.estado AS estado,
    e.costo_total AS costo_total,
    e.id_sucursal,
    e.ubicacion,
    CONCAT(u.nombre_usuario,' ',u.apellidos_usuario) AS cliente
FROM eventos e
INNER JOIN usuarios u
ON e.id_cliente = u.id_usuario
ORDER BY e.fecha_evento DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute();

$eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "eventos" => $eventos
]);
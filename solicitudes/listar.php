<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$sql = "
SELECT
    ss.id_solicitud,
    ss.fecha_solicitud,
    ss.estado,
    ss.comentario,

    CONCAT(u.nombre,' ',u.apellidos) AS cliente,
    e.nombre_evento,
    s.nombre AS servicio

FROM solicitudes_servicio ss

INNER JOIN usuarios u
ON ss.id_usuario = u.id_usuario

INNER JOIN eventos e
ON ss.id_evento = e.id_evento

INNER JOIN servicios s
ON ss.id_servicio = s.id_servicio

ORDER BY ss.fecha_solicitud DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute();

$solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "solicitudes" => $solicitudes
]);
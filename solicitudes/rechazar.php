<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$id_solicitud = $_GET['id_solicitud'];

$sql = "
UPDATE solicitudes_servicio
SET estado='rechazada'
WHERE id_solicitud=?
";

$stmt = $conn->prepare($sql);

$resultado = $stmt->execute([$id_solicitud]);

echo json_encode([
    "success"=>$resultado
]);
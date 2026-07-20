<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$sql = "
SELECT
    p.id_pago,
    p.monto,
    p.metodo_pago,
    p.fecha_pago,
    p.estado,
    e.nombre_evento
FROM pagos p
INNER JOIN eventos e
ON p.id_evento = e.id_evento
ORDER BY p.fecha_pago DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute();

$pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "pagos" => $pagos
]);
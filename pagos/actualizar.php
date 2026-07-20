<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$data = json_decode(file_get_contents("php://input"), true);

$id_pago = $data['id_pago'] ?? 0;
$id_evento = $data['id_evento'] ?? 0;
$monto = $data['monto'] ?? 0;
$metodo_pago = $data['metodo_pago'] ?? '';
$referencia = $data['referencia'] ?? '';
$estado = $data['estado'] ?? '';

$sql = "
UPDATE pagos
SET
    id_evento = ?,
    monto = ?,
    metodo_pago = ?,
    referencia = ?,
    estado = ?
WHERE id_pago = ?
";

$stmt = $conn->prepare($sql);

$resultado = $stmt->execute([
    $id_evento,
    $monto,
    $metodo_pago,
    $referencia,
    $estado,
    $id_pago
]);

echo json_encode([
    "success" => $resultado
]);
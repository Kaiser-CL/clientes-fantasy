<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$data = json_decode(file_get_contents("php://input"), true);

$sql = "
INSERT INTO pagos
(
    id_evento,
    monto,
    metodo_pago,
    referencia,
    estado
)
VALUES
(
    ?, ?, ?, ?, ?
)
";

$stmt = $conn->prepare($sql);

$resultado = $stmt->execute([
    $data['id_evento'],
    $data['monto'],
    $data['metodo_pago'],
    $data['referencia'],
    $data['estado']
]);

echo json_encode([
    "success" => $resultado
]);
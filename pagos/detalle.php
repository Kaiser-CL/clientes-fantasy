<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$id_pago = $_GET['id_pago'] ?? 0;

$sql = "
SELECT *
FROM pagos
WHERE id_pago = ?
";

$stmt = $conn->prepare($sql);
$stmt->execute([$id_pago]);

$pago = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "pago" => $pago
]);
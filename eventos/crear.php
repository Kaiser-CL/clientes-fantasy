<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$data = json_decode(file_get_contents("php://input"), true);

$id_cliente = $data['id_cliente'] ?? 0;
$nombre_evento = $data['nombre_evento'] ?? '';
$id_clasificacion_evento = $data['id_clasificacion_evento'] ?? null;
$fecha_evento = $data['fecha_evento'] ?? '';
$hora_evento = $data['hora_evento'] ?? '';
$ubicacion = $data['ubicacion'] ?? '';
$numero_invitados = $data['numero_invitados'] ?? 0;
$costo_total = $data['costo_total'] ?? 0;
$saldo_pendiente = $data['saldo_pendiente'] ?? 0;
$fecha_limite_pago = $data['fecha_limite_pago'] ?? null;

$sql = "
INSERT INTO eventos
(
    id_cliente,
    nombre_evento,
    id_clasificacion_evento,
    fecha_evento,
    hora_evento,
    ubicacion,
    numero_invitados,
    costo_total,
    saldo_pendiente,
    fecha_limite_pago
)
VALUES
(
    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
)
";

$stmt = $conn->prepare($sql);

$resultado = $stmt->execute([
    $id_cliente,
    $nombre_evento,
    $id_clasificacion_evento,
    $fecha_evento,
    $hora_evento,
    $ubicacion,
    $numero_invitados,
    $costo_total,
    $saldo_pendiente,
    $fecha_limite_pago
]);

echo json_encode([
    "success" => $resultado
]);
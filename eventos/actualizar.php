<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$data = json_decode(file_get_contents("php://input"), true);

$id_evento = $data['id_evento'];

$sql = "
UPDATE eventos
SET
    nombre_evento=?,
    fecha_evento=?,
    hora_evento=?,
    ubicacion=?,
    numero_invitados=?,
    costo_total=?,
    saldo_pendiente=?,
    estado_usuario=?
WHERE id_evento=?
";

$stmt = $conn->prepare($sql);

$resultado = $stmt->execute([
    $data['nombre_evento'],
    $data['fecha_evento'],
    $data['hora_evento'],
    $data['ubicacion'],
    $data['numero_invitados'],
    $data['costo_total'],
    $data['saldo_pendiente'],
    $data['estado'],
    $id_evento
]);

echo json_encode([
    "success" => $resultado
]);
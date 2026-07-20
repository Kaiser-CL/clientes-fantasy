<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$data = json_decode(file_get_contents("php://input"), true);

$id_evento = $data['id_evento'] ?? 0;

if (!$id_evento) {
    echo json_encode([
        "success" => false,
        "mensaje" => "Se requiere el id_evento para actualizar"
    ]);
    exit;
}

$id_sucursal = $data['id_sucursal'] ?? null;
$nombre_evento = $data['nombre_evento'] ?? '';
$clasificacion_evento = $data['clasificacion_evento'] ?? 'infantil';
$fecha_evento = $data['fecha_evento'] ?? '';
$hora_evento = $data['hora_evento'] ?? '';
$ubicacion = $data['ubicacion'] ?? '';
$numero_invitados = $data['numero_invitados'] ?? 0;
$costo_total = $data['costo_total'] ?? 0;
$saldo_pendiente = $data['saldo_pendiente'] ?? 0;
$fecha_limite_pago = $data['fecha_limite_pago'] ?? null;
$estado = $data['estado'] ?? 'pendiente';

$sql = "
UPDATE eventos
SET
id_sucursal = ?,
nombre_evento = ?,
clasificacion_evento = ?,
fecha_evento = ?,
hora_evento = ?,
ubicacion = ?,
numero_invitados = ?,
costo_total = ?,
saldo_pendiente = ?,
fecha_limite_pago = ?,
estado = ?
WHERE id_evento = ?
";

try {
    $stmt = $conn->prepare($sql);

    $resultado = $stmt->execute([
        $id_sucursal,
        $nombre_evento,
        $clasificacion_evento,
        $fecha_evento,
        $hora_evento,
        $ubicacion,
        $numero_invitados,
        $costo_total,
        $saldo_pendiente,
        $fecha_limite_pago,
        $estado,
        $id_evento
    ]);

    echo json_encode([
        "success" => $resultado,
        "mensaje" => $resultado ? "Evento actualizado correctamente" : "No se realizaron cambios"
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "mensaje" => "Error al actualizar evento: " . $e->getMessage()
    ]);
}

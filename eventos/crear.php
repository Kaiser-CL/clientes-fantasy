<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$data = json_decode(file_get_contents("php://input"), true);

$id_cliente = $data['id_cliente'] ?? 0;
$id_sucursal = $data['id_sucursal'] ?? null;
$nombre_evento = $data['nombre_evento'] ?? '';
$clasificacion_evento = $data['clasificacion_evento'] ?? 'infantil'; // 'infantil' o 'social'
$fecha_evento = $data['fecha_evento'] ?? '';
$hora_evento = $data['hora_evento'] ?? '';
$ubicacion = $data['ubicacion'] ?? '';
$numero_invitados = $data['numero_invitados'] ?? 0;
$costo_total = $data['costo_total'] ?? 0;
$saldo_pendiente = $data['saldo_pendiente'] ?? $costo_total;
$fecha_limite_pago = $data['fecha_limite_pago'] ?? null;
$estado = $data['estado'] ?? 'pendiente';

if (!$id_cliente || empty($fecha_evento)) {
    echo json_encode([
        "success" => false,
        "mensaje" => "El id_cliente y la fecha_evento son obligatorios"
    ]);
    exit;
}

$sql = "
INSERT INTO eventos
(
    id_cliente,
id_sucursal,
nombre_evento,
clasificacion_evento,
fecha_evento,
hora_evento,
ubicacion,
numero_invitados,
costo_total,
saldo_pendiente,
fecha_limite_pago,
estado
)
VALUES
(
    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
    )
    ";

    try {
        $stmt = $conn->prepare($sql);

        $resultado = $stmt->execute([
            $id_cliente,
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
            $estado
        ]);

        $id_insertado = $conn->lastInsertId();

        echo json_encode([
            "success" => $resultado,
            "id_evento" => $id_insertado,
            "mensaje" => $resultado ? "Evento creado exitosamente" : "No se pudo registrar el evento"
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            "success" => false,
            "mensaje" => "Error al crear evento: " . $e->getMessage()
        ]);
    }

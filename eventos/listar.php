<?php

header("Content-Type: application/json; charset=utf-8");

require_once "../config/database.php";

try {
    $db = new Database();
    $conn = $db->conectar();

    // Consulta basada estrictamente en tu imagen de 14 columnas
    $sql = "
        SELECT
            e.id_evento,
            e.id_cliente,
            e.id_sucursal,
            e.nombre_evento,
            e.clasificacion_evento,
            e.fecha_evento,
            e.hora_evento,
            e.ubicacion,
            e.numero_invitados,
            e.costo_total,
            e.saldo_pendiente,
            e.fecha_limite_pago,
            e.estado,
            e.num_personas,
            CONCAT(u.nombre_usuario, ' ', u.apellidos_usuario) AS cliente
        FROM eventos e
        INNER JOIN usuarios u ON e.id_cliente = u.id_usuario
        ORDER BY e.fecha_evento DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "eventos" => $eventos
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error de SQL: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
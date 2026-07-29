<?php

header("Content-Type: application/json; charset=utf-8");

require_once "../config/database.php";

try {
    $db = new Database();
    $conn = $db->conectar();

    // Consulta con JOIN a la tabla de servicios para traer las imágenes y galerías
    $sql = "
        SELECT
            e.id_evento,
            e.id_cliente,
            e.nombre_evento,
            e.fecha_evento,
            e.hora_evento,
            e.estado,
            e.costo_total,
            e.id_sucursal,
            e.ubicacion,
            e.tipo_registro,
            CONCAT(u.nombre_usuario, ' ', u.apellidos_usuario) AS cliente,
            s.foto_servicio,
            s.galeria_urls
        FROM eventos e
        INNER JOIN usuarios u ON e.id_cliente = u.id_usuario
        LEFT JOIN servicios s ON e.id_servicio = s.id_servicio
        ORDER BY e.fecha_evento DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Decodificar la columna galeria_urls de String JSON a un Array nativo de PHP/JSON
    foreach ($eventos as &$evento) {
        if (!empty($evento['galeria_urls'])) {
            $evento['galeria_urls'] = json_decode($evento['galeria_urls'], true);
        } else {
            $evento['galeria_urls'] = [];
        }
    }

    echo json_encode([
        "success" => true,
        "eventos" => $eventos
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error al obtener los eventos: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

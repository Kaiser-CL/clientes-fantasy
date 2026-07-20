<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$id_evento = $_GET['id_evento'] ?? 0;

if (!$id_evento) {
    echo json_encode([
        "success" => false,
        "mensaje" => "Se requiere el id_evento para cancelar"
    ]);
    exit;
}

$sql = "
UPDATE eventos
SET estado = 'cancelado'
WHERE id_evento = ?
";

try {
    $stmt = $conn->prepare($sql);

    $resultado = $stmt->execute([$id_evento]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "success" => true,
            "mensaje" => "Evento cancelado exitosamente"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "mensaje" => "No se encontró el evento o ya estaba cancelado"
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "mensaje" => "Error al cancelar evento: " . $e->getMessage()
    ]);
}

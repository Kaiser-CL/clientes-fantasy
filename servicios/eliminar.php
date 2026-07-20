<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$id_servicio = $_GET['id_servicio'] ?? 0;

if (!$id_servicio) {
    echo json_encode([
        "success" => false,
        "mensaje" => "Se requiere el id_servicio para eliminar"
    ]);
    exit;
}

// Actualizamos 'estado' a 'inactivo' en lugar de usar 'disponible'
$sql = "
UPDATE servicios
SET estado = 'inactivo'
WHERE id_servicio = ?
";

try {
    $stmt = $conn->prepare($sql);

    $resultado = $stmt->execute([$id_servicio]);

    // Opcional: Verificar si realmente se afectó alguna fila
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "success" => true,
            "mensaje" => "Servicio eliminado (baja lógica) correctamente"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "mensaje" => "No se encontró el servicio o ya estaba eliminado"
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "mensaje" => "Error al intentar eliminar el servicio: " . $e->getMessage()
    ]);
}

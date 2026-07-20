<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$id_usuario = $_GET['id_usuario'] ?? 0;

if (!$id_usuario) {
    echo json_encode([
        "success" => false,
        "mensaje" => "Se requiere el id_usuario para eliminar"
    ]);
    exit;
}

$sql = "
UPDATE usuarios
SET estado = 'inactivo'
WHERE id_usuario = ?
";

try {
    $stmt = $conn->prepare($sql);

    $resultado = $stmt->execute([$id_usuario]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "success" => true,
            "mensaje" => "Usuario desactivado (baja lógica) correctamente"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "mensaje" => "No se encontró el usuario o ya estaba inactivo"
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "mensaje" => "Error al desactivar el usuario: " . $e->getMessage()
    ]);
}]);

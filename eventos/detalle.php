<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$id_evento = $_GET['id_evento'] ?? 0;

if (!$id_evento) {
    echo json_encode([
        "success" => false,
        "mensaje" => "Se requiere un id_evento válido"
    ]);
    exit;
}

$sql = "
SELECT
e.*,
CONCAT(u.nombre, ' ', u.apellidos) AS cliente,
u.correo AS correo_cliente,
u.telefono AS telefono_cliente,
s.nombre_sucursal
FROM eventos e
INNER JOIN usuarios u ON e.id_cliente = u.id_usuario
LEFT JOIN sucursales s ON e.id_sucursal = s.id_sucursal
WHERE e.id_evento = ?
";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_evento]);

    $evento = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($evento) {
        echo json_encode([
            "success" => true,
            "evento" => $evento
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "mensaje" => "Evento no encontrado"
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "mensaje" => "Error al obtener detalle del evento: " . $e->getMessage()
    ]);
}

<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$id_sucursal = $_GET['id_sucursal'] ?? null;
$clasificacion = $_GET['clasificacion_evento'] ?? null;

$sql = "
SELECT
e.id_evento,
e.id_sucursal,
e.id_cliente,
e.nombre_evento,
e.clasificacion_evento,
e.fecha_evento,
e.hora_evento,
e.ubicacion,
e.numero_invitados,
e.costo_total,
e.saldo_pendiente,
e.estado,
e.fecha_limite_pago,
CONCAT(u.nombre, ' ', u.apellidos) AS cliente,
u.telefono AS telefono_cliente,
s.nombre_sucursal
FROM eventos e
INNER JOIN usuarios u ON e.id_cliente = u.id_usuario
LEFT JOIN sucursales s ON e.id_sucursal = s.id_sucursal
WHERE 1=1
";

$params = [];

if ($id_sucursal) {
    $sql .= " AND e.id_sucursal = ?";
    $params[] = $id_sucursal;
}

if ($clasificacion) {
    $sql .= " AND e.clasificacion_evento = ?";
    $params[] = $clasificacion;
}

$sql .= " ORDER BY e.fecha_evento DESC, e.hora_evento ASC";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "eventos" => $eventos
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "mensaje" => "Error al listar eventos: " . $e->getMessage()
    ]);
}

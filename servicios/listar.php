<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

// Parámetros opcionales de filtro por categoría o clasificación (infantil/social)
$id_categoria = $_GET['id_categoria'] ?? null;
$clasificacion = $_GET['clasificacion_evento'] ?? null;

$sql = "
SELECT
s.id_servicio,
s.nombre_servicio,
s.descripcion,
s.precio_base,
s.tipo_cobro,
s.clasificacion_evento,
s.estado,
c.id_categoria,
c.nombre_categoria AS categoria
FROM servicios s
LEFT JOIN categorias c
ON s.id_categoria = c.id_categoria
WHERE s.estado = 'activo'
";

$params = [];

if ($id_categoria) {
    $sql .= " AND s.id_categoria = ?";
    $params[] = $id_categoria;
}

if ($clasificacion) {
    $sql .= " AND (s.clasificacion_evento = ? OR s.clasificacion_evento = 'ambos')";
    $params[] = $clasificacion;
}

$sql .= " ORDER BY s.nombre_servicio ASC";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "total" => count($servicios),
                     "servicios" => $servicios
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "mensaje" => "Error al obtener los servicios: " . $e->getMessage()
    ]);
}]);

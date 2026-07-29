<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$sql = "
SELECT
    s.id_servicio,
    s.nombre_servicio AS nombre,
    s.descripcion_servicio AS descripcion,
    s.precio_servicio AS precio,
    s.imagen_servicio AS imagen,
    s.disponible_servicio AS disponible,
    s.ubicacion,
    c.nombre_categoria AS categoria
FROM servicios s
LEFT JOIN categorias_servicio c
ON s.id_categoria = c.id_categoria
ORDER BY s.nombre_servicio
";

$stmt = $conn->prepare($sql);
$stmt->execute();

$servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "servicios" => $servicios
]);
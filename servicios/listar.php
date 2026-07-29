<?php

header("Content-Type: application/json; charset=utf-8");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$sql = "
SELECT
    s.id_servicio,
    s.nombre_servicio AS nombre,
    s.descripcion_servicio AS descripcion,
    s.precio_servicio AS precio,
    s.foto_servicio AS imagen,
    s.galeria_urls,
    s.disponible_servicio AS disponible,
    s.es_por_persona,
    s.tipo_cobro,
    s.clasificacion_evento,
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

// Decodificar galeria_urls de JSON string a array
foreach ($servicios as &$servicio) {
    if (!empty($servicio['galeria_urls'])) {
        $servicio['galeria_urls'] = json_decode($servicio['galeria_urls'], true);
    } else {
        $servicio['galeria_urls'] = [];
    }
}

echo json_encode([
    "success" => true,
    "servicios" => $servicios
], JSON_UNESCAPED_UNICODE);
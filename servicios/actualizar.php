<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$data = json_decode(file_get_contents("php://input"), true);

// Obtener datos recibidos o asignar valores por defecto
$id_servicio = $data['id_servicio'] ?? null;
$id_categoria = $data['id_categoria'] ?? null;
$nombre_servicio = $data['nombre_servicio'] ?? $data['nombre'] ?? '';
$descripcion = $data['descripcion'] ?? '';
$precio_base = $data['precio_base'] ?? $data['precio'] ?? 0;
$tipo_cobro = $data['tipo_cobro'] ?? 'fijo'; // 'fijo' o 'por_persona'
$clasificacion_evento = $data['clasificacion_evento'] ?? 'infantil'; // 'infantil', 'social' o 'ambos'
$estado = $data['estado'] ?? ($data['disponible'] ?? 'activo');

// Validar campo primario obligatorio
if (!$id_servicio) {
    echo json_encode([
        "success" => false,
        "mensaje" => "Se requiere el id_servicio para actualizar"
    ]);
    exit;
}

$sql = "
UPDATE servicios
SET
id_categoria = ?,
nombre_servicio = ?,
descripcion = ?,
precio_base = ?,
tipo_cobro = ?,
clasificacion_evento = ?,
estado = ?
WHERE id_servicio = ?
";

try {
    $stmt = $conn->prepare($sql);

    $resultado = $stmt->execute([
        $id_categoria,
        $nombre_servicio,
        $descripcion,
        $precio_base,
        $tipo_cobro,
        $clasificacion_evento,
        $estado,
        $id_servicio
    ]);

    echo json_encode([
        "success" => $resultado,
        "mensaje" => $resultado ? "Servicio actualizado correctamente" : "No se pudo actualizar el servicio"
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "mensaje" => "Error al actualizar: " . $e->getMessage()
    ]);
}

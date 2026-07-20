<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$data = json_decode(file_get_contents("php://input"), true);

// Mapeo y asignación de valores por defecto
$id_categoria = $data['id_categoria'] ?? null;
$nombre_servicio = $data['nombre_servicio'] ?? $data['nombre'] ?? '';
$descripcion = $data['descripcion'] ?? '';
$precio_base = $data['precio_base'] ?? $data['precio'] ?? 0;
$tipo_cobro = $data['tipo_cobro'] ?? 'fijo'; // 'fijo' o 'por_persona'
$clasificacion_evento = $data['clasificacion_evento'] ?? 'infantil'; // 'infantil', 'social' o 'ambos'
$estado = $data['estado'] ?? 'activo';

// Validación básica de nombre obligatorio
if (empty($nombre_servicio)) {
    echo json_encode([
        "success" => false,
        "mensaje" => "El nombre del servicio es obligatorio"
    ]);
    exit;
}

$sql = "
INSERT INTO servicios
(
    id_categoria,
nombre_servicio,
descripcion,
precio_base,
tipo_cobro,
clasificacion_evento,
estado
)
VALUES
(
    ?, ?, ?, ?, ?, ?, ?
    )
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
            $estado
        ]);

        $id_insertado = $conn->lastInsertId();

        echo json_encode([
            "success" => $resultado,
            "id_servicio" => $id_insertado,
            "mensaje" => $resultado ? "Servicio creado exitosamente" : "No se pudo crear el servicio"
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            "success" => false,
            "mensaje" => "Error al crear servicio: " . $e->getMessage()
        ]);
    }

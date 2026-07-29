<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

// Recibimos el id_cliente por la URL (ejemplo: perfil_cliente.php?id_cliente=150001)
$id_cliente = $_GET['id_cliente'] ?? 0;

if (!$id_cliente) {
    echo json_encode([
        "success" => false,
        "message" => "El ID de cliente es obligatorio."
    ]);
    exit;
}

try {
    // Unimos al cliente (c) con el empleado que lo registró (e)
    $sql = "
    SELECT
        c.id_usuario AS id_cliente,
        c.nombre_usuario AS cliente_nombre,
        c.apellidos_usuario AS cliente_apellidos,
        c.correo_usuario AS cliente_correo,
        c.telefono_usuario AS cliente_telefono,
        
        -- Datos del empleado que realizó el registro
        e.nombre_usuario AS empleado_nombre,
        e.apellidos_usuario AS empleado_apellidos,
        e.correo_usuario AS empleado_correo,
        e.telefono_usuario AS empleado_telefono
    FROM usuarios c
    LEFT JOIN usuarios e ON c.id_empleado_registro = e.id_usuario
    WHERE c.id_usuario = ? AND c.id_rol = 2
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_cliente]);

    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        echo json_encode([
            "success" => true,
            "cliente" => [
                "id" => $data['id_cliente'],
                "nombre_completo" => trim($data['cliente_nombre'] . " " . $data['cliente_apellidos']),
                "correo" => $data['cliente_correo'],
                "telefono" => $data['cliente_telefono']
            ],
            "atendido_por" => [
                "nombre_completo" => $data['empleado_nombre'] ? trim($data['empleado_nombre'] . " " . $data['empleado_apellidos']) : "Atención General",
                "correo" => $data['empleado_correo'] ?? "contacto@myfantasy.com",
                "telefono" => $data['empleado_telefono'] ?? "N/A"
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Cliente no encontrado."
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error de base de datos: " . $e->getMessage()
    ]);
}
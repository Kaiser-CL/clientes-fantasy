<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$id_usuario = $_GET['id_usuario'] ?? 0;

if (!$id_usuario) {
    echo json_encode([
        "success" => false,
        "mensaje" => "Se requiere un id_usuario válido"
    ]);
    exit;
}

$sql = "
SELECT
u.id_usuario,
u.id_sucursal,
u.id_rol,
u.nombre,
u.apellidos,
u.correo,
u.telefono,
u.es_superadmin,
u.fecha_registro,
u.estado,
r.nombre_rol,
s.nombre_sucursal
FROM usuarios u
LEFT JOIN roles r ON u.id_rol = r.id_rol
LEFT JOIN sucursales s ON u.id_sucursal = s.id_sucursal
WHERE u.id_usuario = ?
";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_usuario]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        echo json_encode([
            "success" => true,
            "usuario" => $usuario
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "mensaje" => "Usuario no encontrado"
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "mensaje" => "Error al obtener detalle del usuario: " . $e->getMessage()
    ]);
}

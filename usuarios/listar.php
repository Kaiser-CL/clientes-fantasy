<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$id_sucursal = $_GET['id_sucursal'] ?? null;
$id_rol = $_GET['id_rol'] ?? null;

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
WHERE 1=1
";

$params = [];

if ($id_sucursal) {
    $sql .= " AND u.id_sucursal = ?";
    $params[] = $id_sucursal;
}

if ($id_rol) {
    $sql .= " AND u.id_rol = ?";
    $params[] = $id_rol;
}

$sql .= " ORDER BY u.id_usuario DESC";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "usuarios" => $usuarios
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "mensaje" => "Error al listar usuarios: " . $e->getMessage()
    ]);
}

<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$sql = "
SELECT
    u.id_usuario,
    u.nombre,
    u.apellidos AS apellidos,
    u.correo AS correo,
    u.telefono AS telefono,
    u.fecha_registro_usuario AS fecha_registro,
    u.estado_usuario AS estado,
    r.nombre_rol
FROM usuarios u
LEFT JOIN roles r
ON u.id_rol = r.id_rol
ORDER BY u.id_usuario DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute();

$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "usuarios" => $usuarios
]);
<?php

// En produccion los errores NO deben mostrarse en HTML (rompen el JSON)
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

require_once '../config/database.php';

$db = new Database();
$conn = $db->conectar();

$data = json_decode(
    file_get_contents("php://input"),
    true
);

$correo    = $data['correo']    ?? '';
$contrasena = $data['contrasena'] ?? '';

// Columnas corregidas para coincidir con el esquema de TiDB y agregar el telefono del asesor
$sql = "
SELECT
    u.id_usuario,
    u.nombre_usuario AS nombre,
    u.apellidos_usuario AS apellidos,
    u.correo_usuario AS correo,
    u.telefono_usuario AS telefono,
    u.contrasena_usuario AS contrasena,
    r.nombre_rol,
    u.id_empleado_registro,
    e.telefono_usuario AS telefono_asesor
FROM usuarios u
INNER JOIN roles r
    ON u.id_rol = r.id_rol
LEFT JOIN usuarios e
    ON u.id_empleado_registro = e.id_usuario
WHERE u.correo_usuario = ?
";

$stmt = $conn->prepare($sql);
$stmt->execute([$correo]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    echo json_encode([
        "success" => false,
        "mensaje" => "Usuario no encontrado"
    ]);
    exit;
}

if (!password_verify($contrasena, $usuario['contrasena'])) {
    echo json_encode([
        "success" => false,
        "mensaje" => "Contraseña incorrecta"
    ]);
    exit;
}

unset($usuario['contrasena']);

echo json_encode([
    "success" => true,
    "usuario" => $usuario
]);
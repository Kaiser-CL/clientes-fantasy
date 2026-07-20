<?php

header('Content-Type: application/json');

require_once '../config/database.php';

$db = new Database();
$conn = $db->conectar();

$data = json_decode(
    file_get_contents("php://input"),
    true
);

$correo = $data['correo'] ?? '';
$contrasena = $data['contrasena'] ?? '';

$sql = "
SELECT
u.id_usuario,
u.nombre,
u.apellidos,
u.correo,
u.telefono,
u.contrasena,
r.nombre_rol
FROM usuarios u
INNER JOIN roles r
ON u.id_rol = r.id_rol
WHERE u.correo = ?
";

$stmt = $conn->prepare($sql);
$stmt->execute([$correo]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$usuario){

    echo json_encode([
        "success" => false,
        "mensaje" => "Usuario no encontrado"
    ]);
    exit;
}

if(!password_verify(
    $contrasena,
    $usuario['contrasena']
)){
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
<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$data = json_decode(file_get_contents("php://input"), true);

$id_usuario = $data['id_usuario'] ?? 0;

if (!$id_usuario) {
    echo json_encode([
        "success" => false,
        "mensaje" => "Se requiere el id_usuario para actualizar"
    ]);
    exit;
}

$nombre = $data['nombre'] ?? '';
$apellidos = $data['apellidos'] ?? '';
$correo = $data['correo'] ?? '';
$telefono = $data['telefono'] ?? '';
$id_rol = $data['id_rol'] ?? null;
$id_sucursal = $data['id_sucursal'] ?? null;
$es_superadmin = $data['es_superadmin'] ?? 0;
$estado = $data['estado'] ?? 'activo';
$contrasena = $data['contrasena'] ?? '';

try {
    if (!empty($contrasena)) {
        // Si mandan contraseña nueva, se actualiza encriptada
        $passwordHash = password_hash($contrasena, PASSWORD_DEFAULT);
        $sql = "
        UPDATE usuarios
        SET
        nombre = ?,
        apellidos = ?,
        correo = ?,
        telefono = ?,
        id_rol = ?,
        id_sucursal = ?,
        es_superadmin = ?,
        estado = ?,
        contrasena = ?
        WHERE id_usuario = ?
        ";
$params = [$nombre, $apellidos, $correo, $telefono, $id_rol, $id_sucursal, $es_superadmin, $estado, $passwordHash, $id_usuario];
    } else {
        // Sin modificar contraseña
        $sql = "
        UPDATE usuarios
        SET
        nombre = ?,
        apellidos = ?,
        correo = ?,
        telefono = ?,
        id_rol = ?,
        id_sucursal = ?,
        es_superadmin = ?,
        estado = ?
        WHERE id_usuario = ?
        ";
$params = [$nombre, $apellidos, $correo, $telefono, $id_rol, $id_sucursal, $es_superadmin, $estado, $id_usuario];
    }

    $stmt = $conn->prepare($sql);
    $resultado = $stmt->execute($params);

    echo json_encode([
        "success" => $resultado,
        "mensaje" => $resultado ? "Usuario actualizado correctamente" : "No se realizaron cambios"
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "mensaje" => "Error al actualizar usuario: " . $e->getMessage()
    ]);
}

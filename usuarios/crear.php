<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$data = json_decode(file_get_contents("php://input"), true);

$nombre = $data['nombre'] ?? '';
$apellidos = $data['apellidos'] ?? '';
$correo = $data['correo'] ?? '';
$telefono = $data['telefono'] ?? '';
$contrasena = $data['contrasena'] ?? '';
$id_rol = $data['id_rol'] ?? 2; // Rol por defecto (ej. Cliente o Staff)
$id_sucursal = $data['id_sucursal'] ?? null;
$es_superadmin = $data['es_superadmin'] ?? 0;

if (empty($nombre) || empty($apellidos) || empty($correo) || empty($contrasena)) {
    echo json_encode([
        "success" => false,
        "mensaje" => "Faltan datos obligatorios (nombre, apellidos, correo, contraseña)"
    ]);
    exit;
}

$passwordHash = password_hash($contrasena, PASSWORD_DEFAULT);

$sql = "
INSERT INTO usuarios
(
    nombre,
apellidos,
correo,
telefono,
contrasena,
id_rol,
id_sucursal,
es_superadmin,
estado
)
VALUES
(
    ?, ?, ?, ?, ?, ?, ?, ?, 'activo'
    )
    ";

    try {
        $stmt = $conn->prepare($sql);

        $resultado = $stmt->execute([
            $nombre,
            $apellidos,
            $correo,
            $telefono,
            $passwordHash,
            $id_rol,
            $id_sucursal,
            $es_superadmin
        ]);

        $id_insertado = $conn->lastInsertId();

        echo json_encode([
            "success" => $resultado,
            "id_usuario" => $id_insertado,
            "mensaje" => $resultado ? "Usuario creado exitosamente" : "No se pudo registrar el usuario"
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            "success" => false,
            "mensaje" => "Error al registrar usuario: " . $e->getMessage()
        ]);
    }

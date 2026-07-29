<?php
// En producción los errores no deben mostrarse para no romper el formato JSON
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// RUTA ABSOLUTA INTELIGENTE: Busca database.php de forma segura sin importar el entorno
$path_db = __DIR__ . '/../config/database.php';

if (!file_exists($path_db)) {
    // Si la estructura cambió de nivel o la carpeta raíz difiere
    $path_db = dirname(__DIR__) . '/config/database.php';
}

if (!file_exists($path_db)) {
    echo json_encode([
        "success" => false,
        "mensaje" => "Error de servidor: No se encontró la configuración de base de datos."
    ]);
    exit;
}

require_once $path_db;

try {
    $db = new Database();
    $conn = $db->conectar();
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "mensaje" => "Error al conectar con la base de datos."
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$correo     = $data['correo']     ?? '';
$contrasena = $data['contrasena'] ?? '';

if (empty($correo) || empty($contrasena)) {
    echo json_encode([
        "success" => false,
        "mensaje" => "Por favor ingresa correo y contraseña."
    ]);
    exit;
}

// Consulta optimizada para la estructura de TiDB
$sql = "
SELECT
    u.id_usuario,
    u.nombre_usuario AS nombre,
    u.apellidos_usuario AS apellidos,
    u.email AS correo,
    u.telefono_usuario AS telefono,
    u.contrasena_usuario AS contrasena,
    u.id_rol,
    r.nombre_rol,
    u.id_empleado_registro,
    e.telefono_usuario AS telefono_asesor
FROM usuarios u
INNER JOIN roles r
    ON u.id_rol = r.id_rol
LEFT JOIN usuarios e
    ON u.id_empleado_registro = e.id_usuario
WHERE u.email = ?
";

try {
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

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "mensaje" => "Error en la consulta de inicio de sesión.",
        "debug"   => $e->getMessage()
    ]);
}

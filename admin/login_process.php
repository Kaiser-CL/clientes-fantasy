<?php
session_start();

// Inclusión directa de tu archivo de conexión estandarizado
require_once __DIR__ . '/../db_config.php';

if (!isset($pdo) || !$pdo) {
    header("Location: login.php?error=credenciales_invalidas");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit();
}

$input = trim($_POST['correo_usuario'] ?? '');
$pass  = trim($_POST['contrasena_usuario'] ?? '');

if (empty($input) || empty($pass)) {
    header("Location: login.php?error=credenciales_invalidas");
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo_usuario = ? OR nombre_usuario = ? LIMIT 1");
    $stmt->execute([$input, $input]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || (int)$user['estado_usuario'] !== 1 || (int)$user['id_rol'] === 2) {
        header("Location: login.php?error=credenciales_invalidas");
        exit();
    }

    $pass_valida = password_verify($pass, $user['contrasena_usuario']);

    if (!$pass_valida) {
        header("Location: login.php?error=credenciales_invalidas");
        exit();
    }

    // Guardado unificado de variables de sesión
    $nombreCompleto = trim(($user['nombre_usuario'] ?? '') . ' ' . ($user['apellidos_usuario'] ?? ''));

    $_SESSION['id_usuario']      = $user['id_usuario'];
    $_SESSION['usuario_id']      = $user['id_usuario'];
    $_SESSION['nombre_usuario']  = $user['nombre_usuario'];
    $_SESSION['nombre_completo'] = !empty($nombreCompleto) ? $nombreCompleto : $user['nombre_usuario'];
    $_SESSION['correo_usuario']  = $user['correo_usuario'];
    $_SESSION['id_rol']          = (int)$user['id_rol'];
    $_SESSION['es_superadmin']   = ((int)$user['id_rol'] === 3) ? 1 : 0;
    $_SESSION['logged_in']       = true;

    header("Location: index.php");
    exit();

} catch (Exception $e) {
    header("Location: login.php?error=credenciales_invalidas");
    exit();
}
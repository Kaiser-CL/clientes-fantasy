<?php
session_start();
require_once '../db_config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $usuario_ingresado = trim($_POST['correo_usuario'] ?? '');
    $contrasena_ingresada = trim($_POST['contrasena_usuario'] ?? '');

    if (empty($usuario_ingresado) || empty($contrasena_ingresada)) {
        header("Location: login.php?error=campos_vacios");
        exit();
    }

    try {
        // Busca si coincide con correo O usuario
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE TRIM(correo_usuario) = ? OR TRIM(nombre_usuario) = ?");
        $stmt->execute([$usuario_ingresado, $usuario_ingresado]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifica la contraseña
        if ($user && password_verify($contrasena_ingresada, $user['contrasena_usuario'])) {
            
            $_SESSION['logged_in'] = true;
            $_SESSION['id_usuario'] = $user['id_usuario'];
            $_SESSION['nombre_usuario'] = $user['nombre_usuario'];
            $_SESSION['id_rol'] = $user['id_rol'];

            header("Location: index.php");
            exit();

        } else {
            header("Location: login.php?error=credenciales_invalidas");
            exit();
        }

    } catch (PDOException $e) {
        error_log("Error en Login: " . $e->getMessage());
        header("Location: login.php?error=error_servidor");
        exit();
    }

} else {
    header("Location: login.php");
    exit();
}
?>

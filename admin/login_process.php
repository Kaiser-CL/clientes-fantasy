<?php
session_start();
require_once __DIR__ . '/../db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $correo_o_usuario = trim($_POST['correo_usuario'] ?? '');
    $contrasena       = trim($_POST['contrasena_usuario'] ?? '');

    if (empty($correo_o_usuario) || empty($contrasena)) {
        header("Location: login.php?error=campos_vacios");
        exit();
    }

    try {
        $stmt = $pdo->prepare("
            SELECT id_usuario, nombre_usuario, apellidos_usuario, correo_usuario, contrasena_usuario, id_rol 
            FROM usuarios 
            WHERE correo_usuario = :identificador OR nombre_usuario = :identificador
            LIMIT 1
        ");
        
        $stmt->execute([':identificador' => $correo_o_usuario]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            $hash_bd = trim($usuario['contrasena_usuario'] ?? '');

            if (password_verify($contrasena, $hash_bd)) {
                // Login Exitoso
                $_SESSION['id_usuario']     = $usuario['id_usuario'];
                $_SESSION['nombre_usuario'] = $usuario['nombre_usuario'];
                $_SESSION['id_rol']          = $usuario['id_rol'];

                header("Location: index.php");
                exit();
            }
        }

        // Si el usuario no existe o la contraseña falla
        header("Location: login.php?error=credenciales_invalidas");
        exit();

    } catch (PDOException $e) {
        error_log("Error de login: " . $e->getMessage());
        header("Location: login.php?error=error_sistema");
        exit();
    }

} else {
    header("Location: login.php");
    exit();
}

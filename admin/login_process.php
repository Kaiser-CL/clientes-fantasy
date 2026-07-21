<?php
session_start();

$raiz = dirname(__DIR__);
$conexion_path = file_exists($raiz . '/db_config.php') ? $raiz . '/db_config.php' : (file_exists(__DIR__ . '/db_config.php') ? __DIR__ . '/db_config.php' : null);

if (!$conexion_path) {
    header("Location: login.php?error=error_servidor");
    exit();
}

require_once $conexion_path;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $input_usuario = trim($_POST['correo_usuario'] ?? '');
    $contrasena_ingresada = trim($_POST['contrasena_usuario'] ?? '');

    if (empty($input_usuario) || empty($contrasena_ingresada)) {
        header("Location: login.php?error=campos_vacios");
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo_usuario = ? OR nombre_usuario = ? LIMIT 1");
        $stmt->execute([$input_usuario, $input_usuario]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if (isset($user['estado_usuario']) && (int)$user['estado_usuario'] !== 1) {
                header("Location: login.php?error=credenciales_invalidas");
                exit();
            }

            $password_correcta = false;

            if (password_verify($contrasena_ingresada, $user['contrasena_usuario'])) {
                $password_correcta = true;
            } elseif ($contrasena_ingresada === $user['contrasena_usuario']) {
                $password_correcta = true;
                $new_hash = password_hash($contrasena_ingresada, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE usuarios SET contrasena_usuario = ? WHERE id_usuario = ?");
                $upd->execute([$new_hash, $user['id_usuario']]);
            }

            if ($password_correcta) {
                $_SESSION['logged_in']      = true;
                $_SESSION['id_usuario']     = $user['id_usuario'];
                $_SESSION['nombre_usuario'] = $user['nombre_usuario'];
                $_SESSION['correo_usuario'] = $user['correo_usuario'];
                $_SESSION['id_rol']          = (int)$user['id_rol'];

                header("Location: index.php");
                exit();
            }
        }

        header("Location: login.php?error=credenciales_invalidas");
        exit();

    } catch (PDOException $e) {
        error_log("Error en Login: " . $e->getMessage());
        header("Location: login.php?error=error_servidor");
        exit();
    }

} else {
    header("Location: login.php");
    exit();
}

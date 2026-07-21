<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../db_config.php'; // o la ruta correcta a tu db_config

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario_ingresado = trim($_POST['usuario'] ?? $_POST['correo'] ?? ''); 
    $contrasena_ingresada = trim($_POST['contrasena'] ?? '');

    echo "<h3>1. Datos recibidos del formulario:</h3>";
    echo "<b>Usuario/Correo:</b> [" . htmlspecialchars($usuario_ingresado) . "]<br>";
    echo "<b>Contraseña:</b> [" . htmlspecialchars($contrasena_ingresada) . "]<br><hr>";

    // Buscamos si existe en correo OR nombre_usuario
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo_usuario = ? OR nombre_usuario = ?");
    $stmt->execute([$usuario_ingresado, $usuario_ingresado]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "<h3>2. Resultado de la base de datos:</h3>";
    if ($user) {
        echo "<pre>";
        print_r($user);
        echo "</pre>";

        echo "<h3>3. Prueba de password_verify:</h3>";
        $verificado = password_verify($contrasena_ingresada, $user['contrasena_usuario']);
        echo "<b>¿Coincide la contraseña?:</b> " . ($verificado ? "<b style='color:green'>SÍ (TRUE)</b>" : "<b style='color:red'>NO (FALSE)</b>");
    } else {
        echo "<b style='color:red'>No se encontró ningún usuario con ese correo/nombre en la BD.</b>";
    }
    exit;
}
?>

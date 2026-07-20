<?php
// Activar el reporte de errores completo para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Cargar la configuración de la base de datos (un nivel arriba en htdocs)
require_once __DIR__ . '/../db_config.php';

// Verificar que los datos vengan por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $correo_o_usuario = trim($_POST['correo_usuario'] ?? '');
    $contrasena       = trim($_POST['contrasena_usuario'] ?? '');

    if (empty($correo_o_usuario) || empty($contrasena)) {
        header("Location: login.php?error=campos_vacios");
        exit();
    }

    try {
        // Buscamos por correo_usuario o por nombre_usuario según lo ingresado
        $stmt = $pdo->prepare("
            SELECT id_usuario, nombre_usuario, apellidos_usuario, correo_usuario, contrasena_usuario, id_rol 
            FROM usuarios 
            WHERE correo_usuario = :identificador OR nombre_usuario = :identificador
            LIMIT 1
        ");
        
        $stmt->execute([':identificador' => $correo_o_usuario]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            // Verificación de contraseña en texto plano
            if ($contrasena === $usuario['contrasena_usuario']) {
                
                // Regenerar ID de sesión por seguridad
                session_regenerate_id(true);

                // Guardar variables de sesión clave
                $_SESSION['usuario_id']        = $usuario['id_usuario'];
                $_SESSION['nombre_usuario']    = $usuario['nombre_usuario'];
                $_SESSION['apellidos_usuario'] = $usuario['apellidos_usuario'];
                $_SESSION['id_rol']            = (int)$usuario['id_rol']; // 3 = SuperAdmin
                $_SESSION['logged_in']         = true;

                // Redirigir al panel principal
                header("Location: index.php");
                exit();

            } else {
                // Contraseña incorrecta
                header("Location: login.php?error=credenciales_invalidas");
                exit();
            }
        } else {
            // Usuario no encontrado
            header("Location: login.php?error=credenciales_invalidas");
            exit();
        }

    } catch (PDOException $e) {
        // Muestra el error exacto de SQL / TiDB en pantalla para identificar la falla
        echo "<h2>Error de Base de Datos / SQL:</h2>";
        echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
        exit();
    } catch (Exception $e) {
        // Captura cualquier otro tipo de error general de PHP
        echo "<h2>Error General:</h2>";
        echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
        exit();
    }

} else {
    header("Location: login.php");
    exit();
}
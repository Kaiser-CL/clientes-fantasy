<?php
// Activar el reporte de errores completo para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Cargar la configuración de la base de datos
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

            // --- BLOQUE DE DEPURACIÓN TEMPORAL ---
            echo "<style>body { background: #111; color: #0f0; font-family: monospace; padding: 20px; font-size: 16px; }</style>";
            echo "<h2>--- PRUEBA DE DEPURACIÓN LOGIN ---</h2>";
            
            $hash_bd = trim($usuario['contrasena_usuario'] ?? '');
            
            echo "<b>1. Contraseña tipeada:</b> [" . htmlspecialchars($contrasena) . "]<br>";
            echo "<b>2. Longitud tipeada:</b> " . strlen($contrasena) . " caracteres<br><br>";
            
            echo "<b>3. Hash en BD:</b> [" . htmlspecialchars($hash_bd) . "]<br>";
            echo "<b>4. Longitud Hash en BD:</b> " . strlen($hash_bd) . " caracteres (debe ser 60)<br><br>";
            
            $es_valida = password_verify($contrasena, $hash_bd);
            echo "<b>5. Resultado de password_verify():</b> ";
            var_dump($es_valida);
            
            echo "<br><hr><b>Array devuelto por TiDB:</b><br>";
            echo "<pre>";
            print_r($usuario);
            echo "</pre>";
            exit();
            // --- FIN BLOQUE DE DEPURACIÓN ---

            /* 
            // Código normal (se reactivará una vez veamos la salida del debug)
            if (password_verify($contrasena, $usuario['contrasena_usuario'])) {                
                session_regenerate_id(true);

                $_SESSION['usuario_id']        = $usuario['id_usuario'];
                $_SESSION['nombre_usuario']    = $usuario['nombre_usuario'];
                $_SESSION['apellidos_usuario'] = $usuario['apellidos_usuario'];
                $_SESSION['id_rol']            = (int)$usuario['id_rol'];
                $_SESSION['logged_in']         = true;

                header("Location: index.php");
                exit();
            } else {
                header("Location: login.php?error=credenciales_invalidas");
                exit();
            }
            */

        } else {
            // Usuario no encontrado
            header("Location: login.php?error=credenciales_invalidas");
            exit();
        }

    } catch (PDOException $e) {
        echo "<h2>Error de Base de Datos / SQL:</h2>";
        echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
        exit();
    } catch (Exception $e) {
        echo "<h2>Error General:</h2>";
        echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
        exit();
    }

} else {
    header("Location: login.php");
    exit();
}

<?php
// Activar reporte de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Cargar la configuración de la base de datos desde la raíz
require_once __DIR__ . '/../db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $correo_o_usuario = trim($_POST['correo_usuario'] ?? '');
    $contrasena       = trim($_POST['contrasena_usuario'] ?? '');

    if (empty($correo_o_usuario) || empty($contrasena)) {
        header("Location: login.php?error=campos_vacios");
        exit();
    }

    try {
        // Buscamos por correo_usuario o por nombre_usuario
        $stmt = $pdo->prepare("
            SELECT id_usuario, nombre_usuario, apellidos_usuario, correo_usuario, contrasena_usuario, id_rol 
            FROM usuarios 
            WHERE correo_usuario = :identificador OR nombre_usuario = :identificador
            LIMIT 1
        ");
        
        $stmt->execute([':identificador' => $correo_o_usuario]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // --- BLOQUE DE DEPURACIÓN TOTAL ---
        echo "<style>body { background: #0d1117; color: #58a6ff; font-family: monospace; padding: 25px; font-size: 15px; line-height: 1.6; }</style>";
        echo "<h2>--- PRUEBA DE DEPURACIÓN LOGIN (ADMIN) ---</h2>";
        
        echo "<b>1. Valor ingresado en el campo 'correo/usuario':</b> <span style='color:#fff;'>[" . htmlspecialchars($correo_o_usuario) . "]</span><br>";
        echo "<b>2. Contraseña ingresada:</b> <span style='color:#fff;'>[" . htmlspecialchars($contrasena) . "]</span><br><br>";
        
        if (!$usuario) {
            echo "<h3 style='color:#f85149;'>❌ RESULTADO: El usuario NO fue encontrado en TiDB Cloud.</h3>";
            echo "<p style='color:#8b949e;'>Verifica si el correo o usuario ingresado coincide exactamente con la base de datos.</p>";
        } else {
            echo "<h3 style='color:#3fb950;'>✅ RESULTADO: Usuario encontrado en TiDB Cloud.</h3>";
            
            $hash_bd = trim($usuario['contrasena_usuario'] ?? '');
            
            echo "<b>3. Hash recuperado de la BD:</b> <span style='color:#fff;'>" . htmlspecialchars($hash_bd) . "</span><br>";
            echo "<b>4. Longitud del Hash:</b> " . strlen($hash_bd) . " caracteres (debe ser 60)<br><br>";
            
            $es_valida = password_verify($contrasena, $hash_bd);
            
            echo "<b>5. Evaluando password_verify():</b> ";
            if ($es_valida) {
                echo "<span style='color:#3fb950; font-weight:bold;'>TRUE (¡Contraseña correcta!)</span><br>";
            } else {
                echo "<span style='color:#f85149; font-weight:bold;'>FALSE (La contraseña no coincide con el hash)</span><br>";
            }
            
            echo "<br><hr style='border-color:#30363d;'><br><b>Datos completos devueltos por TiDB:</b><br>";
            echo "<pre style='background:#161b22; padding:15px; border-radius:6px; color:#c9d1d9;'>";
            print_r($usuario);
            echo "</pre>";
        }
        exit();
        // --- FIN BLOQUE DE DEPURACIÓN ---

    } catch (PDOException $e) {
        echo "<h2 style='color:#f85149;'>Error de Base de Datos / SQL:</h2>";
        echo "<pre style='background:#161b22; padding:15px; color:#f85149;'>" . htmlspecialchars($e->getMessage()) . "</pre>";
        exit();
    } catch (Exception $e) {
        echo "<h2 style='color:#f85149;'>Error General:</h2>";
        echo "<pre style='background:#161b22; padding:15px; color:#f85149;'>" . htmlspecialchars($e->getMessage()) . "</pre>";
        exit();
    }

} else {
    header("Location: login.php");
    exit();
}

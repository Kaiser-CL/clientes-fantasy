<?php
session_start();

// Si el usuario ya está autenticado, redirigir directamente al index
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center vh-100">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <h3 class="card-title text-center mb-4">Acceso al Sistema</h3>

                        <!-- Notificaciones de Error -->
                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger py-2 text-center" role="alert">
                                <?php
                                    switch ($_GET['error']) {
                                        case 'campos_vacios':
                                            echo "Por favor llena todos los campos.";
                                            break;
                                        case 'credenciales_invalidas':
                                            echo "Usuario o contraseña incorrectos.";
                                            break;
                                        case 'sesion_requerida':
                                            echo "Debes iniciar sesión primero.";
                                            break;
                                        case 'error_servidor':
                                            echo "Ocurrió un error en el servidor.";
                                            break;
                                        default:
                                            echo "Error de autenticación.";
                                    }
                                ?>
                            </div>
                        <?php endif; ?>

                        <form action="login_process.php" method="POST">
                            
                            <!-- Cambiado a type="text" para aceptar "admin" o "correo@dominio.com" -->
                            <div class="mb-3">
                                <label for="correo_usuario" class="form-label">Usuario o Correo</label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="correo_usuario" 
                                    name="correo_usuario" 
                                    placeholder="Ingresa tu usuario o correo" 
                                    required
                                >
                            </div>

                            <div class="mb-3">
                                <label for="contrasena_usuario" class="form-label">Contraseña</label>
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="contrasena_usuario" 
                                    name="contrasena_usuario" 
                                    placeholder="Ingresa tu contraseña" 
                                    required
                                >
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Ingresar</button>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
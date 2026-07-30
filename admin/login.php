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
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome para íconos en los campos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-purple: #7a1ca6;
            --purple-hover: #5d1282;
            --bg-light: #f4effa;
            --text-dark: #2d2b30;
            --border-color: #e2d8ee;
        }

        body {
            background-color: var(--bg-light);
            background-image: radial-gradient(circle at 50% 20%, #ede3f7 0%, var(--bg-light) 80%);
            color: var(--text-dark);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-card {
            border: 1px solid var(--border-color);
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(122, 28, 166, 0.08) !important;
            background-color: #ffffff;
        }

        .logo-img {
            max-width: 140px;
            height: auto;
            object-fit: contain;
        }

        .input-group-text {
            background-color: #ffffff;
            border-right: none;
            color: #9b8ab0;
        }

        .form-control {
            border-left: none;
            padding-top: 0.65rem;
            padding-bottom: 0.65rem;
        }

        .form-control:focus {
            border-color: var(--primary-purple);
            box-shadow: 0 0 0 0.25rem rgba(122, 28, 166, 0.15);
        }

        .input-group:focus-within .input-group-text {
            border-color: var(--primary-purple);
            color: var(--primary-purple);
        }

        .btn-primary-purple {
            background-color: var(--primary-purple);
            border-color: var(--primary-purple);
            color: #ffffff;
            font-weight: 600;
            padding-top: 0.65rem;
            padding-bottom: 0.65rem;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .btn-primary-purple:hover {
            background-color: var(--purple-hover);
            border-color: var(--purple-hover);
            color: #ffffff;
        }

        .btn-primary-purple:active {
            transform: scale(0.99);
        }
    </style>
</head>
<body class="d-flex align-items-center vh-100">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-9 col-md-6 col-lg-4">
                <div class="card login-card p-2 p-md-3">
                    <div class="card-body p-4">
                        
                        <!-- Logo de la empresa -->
                        <div class="text-center mb-3">
                            <img src="../Images/logo_salon1.png" alt="Logo Salón Fantasy" class="logo-img">
                        </div>

                        <h4 class="card-title text-center fw-bold mb-4">Clientes-Fantasy movil</h4>

                        <!-- Notificaciones de Error -->
                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger py-2 text-center small rounded-3" role="alert">
                                <?php
                                    switch ($_GET['error']) {
                                        case 'campos_vacios':
                                            echo "Por favor llena todos los campos.";
                                            break;
                                        case 'credenciales_invalidas':
                                            echo "Usuario o contraseña incorrectos.";
                                            break;
                                        case 'acceso_denegado':
                                            echo "Esta área es exclusiva del personal del salón. Los clientes acceden desde la App.";
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
                            
                            <div class="mb-3">
                                <label for="correo_usuario" class="form-label fw-semibold small">Usuario o Correo</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="correo_usuario" 
                                        name="correo_usuario" 
                                        placeholder="Ingresa tu usuario o correo" 
                                        required
                                        maxlength="100"
                                        pattern="[A-Za-z0-9._@áéíóúÁÉÍÓÚñÑ\s-]+"
                                        autocomplete="username"
                                        spellcheck="false"
                                        title="Ingresa un usuario o correo válido"
                                    >
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="contrasena_usuario" class="form-label fw-semibold small">Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                                    <input 
                                        type="password" 
                                        class="form-control" 
                                        id="contrasena_usuario" 
                                        name="contrasena_usuario" 
                                        placeholder="Ingresa tu contraseña" 
                                        required
                                        maxlength="72"
                                        autocomplete="current-password"
                                    >
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary-purple w-100">Ingresar</button>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>

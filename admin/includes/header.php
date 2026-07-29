<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['id_usuario'])) {
    header("Location: login.php?error=sesion_requerida");
    exit;
}

$esSuperadmin  = $_SESSION['es_superadmin'] ?? ((isset($_SESSION['id_rol']) && $_SESSION['id_rol'] == 3) ? 1 : 0);
$nombreUsuario = $_SESSION['nombre_completo'] ?? $_SESSION['nombre_usuario'] ?? 'Usuario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrativo - Fantasy</title>
    <!-- Bootstrap 5 & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- Google Fonts (Poppins) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --color-morado: #5E239D;
            --color-rosa: #EC268F;
            --color-azul: #2175EC;
            --color-amarillo: #FFC107;
            --color-verde: #38A13B;
            --sidebar-bg: #1e293b;
            --font-principal: 'Poppins', sans-serif;
        }

        body, button, input, select, textarea, .nav-link, .table {
            font-family: var(--font-principal) !important;
        }

        body {
            background-color: #f1f5f9;
        }

        /* Estilos Sidebar */
        .sidebar {
            background-color: var(--sidebar-bg) !important;
        }

        /* Botones estilo Brandbook */
        .btn-primary {
            background-color: var(--color-morado) !important;
            border-color: var(--color-morado) !important;
            border-radius: 20px !important;
            font-weight: 600 !important;
        }
        .btn-primary:hover {
            background-color: #4a1b7d !important;
            border-color: #4a1b7d !important;
        }

        .btn-secondary, .btn-info {
            background-color: var(--color-rosa) !important;
            border-color: var(--color-rosa) !important;
            color: #ffffff !important;
            border-radius: 20px !important;
            font-weight: 600 !important;
        }

        .btn-outline-danger {
            border-radius: 20px !important;
            font-weight: 600 !important;
        }

        /* Badges */
        .badge-jardin { background-color: var(--color-morado) !important; color: #fff; }
        .badge-rosa { background-color: var(--color-rosa) !important; color: #fff; }
        .badge-amarillo { background-color: var(--color-amarillo) !important; color: #000; }
        .badge-verde { background-color: var(--color-verde) !important; color: #fff; }
    </style>
</head>
<body>

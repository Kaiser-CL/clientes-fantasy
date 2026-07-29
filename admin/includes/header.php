<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar sesión activa
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
    
    <!-- FUENTES GOOGLE FONTS (Poppins & Cormorant Garamond) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@1,600&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- BRANDBOOK FANTASY STYLES -->
    <style>
        :root {
            --color-morado: #5E239D;
            --color-rosa: #EC268F;
            --color-azul: #2175EC;
            --color-amarillo: #FFC107;
            --color-verde: #38A13B;
            --font-principal: 'Poppins', sans-serif;
            --font-secundaria: 'Cormorant Garamond', serif;
        }

        body, button, input, select, textarea, .nav-link, .table {
            font-family: var(--font-principal) !important;
        }

        .brand-title-italic {
            font-family: var(--font-secundaria) !important;
            font-style: italic;
        }

        /* Navbar personalizado */
        .navbar-fantasy {
            background-color: var(--color-morado) !important;
        }

        /* Botones estilo Brandbook */
        .btn-primary {
            background-color: var(--color-morado) !important;
            border-color: var(--color-morado) !important;
            border-radius: 20px !important;
            font-weight: 600 !important;
            padding: 0.4rem 1.2rem;
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
            padding: 0.4rem 1.2rem;
        }
        .btn-secondary:hover, .btn-info:hover {
            background-color: #c71e77 !important;
            border-color: #c71e77 !important;
            color: #ffffff !important;
        }

        .btn-outline-primary {
            color: var(--color-morado) !important;
            border-color: var(--color-morado) !important;
            border-radius: 20px !important;
            font-weight: 600 !important;
        }
        .btn-outline-primary:hover {
            background-color: var(--color-morado) !important;
            color: #ffffff !important;
        }

        /* Badges de Salones y Categorías */
        .badge-jardin { background-color: var(--color-morado) !important; color: #fff; }
        .badge-infantil { background-color: var(--color-azul) !important; color: #fff; }
        .badge-rosa { background-color: var(--color-rosa) !important; color: #fff; }
        .badge-amarillo { background-color: var(--color-amarillo) !important; color: #000; }
        .badge-verde { background-color: var(--color-verde) !important; color: #fff; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark navbar-fantasy sticky-top shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-white d-flex align-items-center" href="index.php">
            <i class="fa-solid fa-wand-magic-sparkles me-2 text-warning"></i> Fantasy Admin
        </a>
        <div class="d-flex align-items-center text-white me-3">
            <span class="me-3 small"><i class="fa-solid fa-circle-user me-1 text-warning"></i> <?php echo htmlspecialchars($nombreUsuario); ?></span>
            <a href="logout.php" class="btn btn-sm btn-outline-light rounded-pill"><i class="fa-solid fa-power-off"></i> Salir</a>
        </div>
    </div>
</nav>

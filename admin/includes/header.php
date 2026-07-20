<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si no hay sesión iniciada, lo mandamos al Login
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit;
}

$esSuperadmin = $_SESSION['es_superadmin'] ?? 0;
$nombreUsuario = $_SESSION['nombre_completo'] ?? 'Usuario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrativo - Fantasy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-primary" href="index.php">
            <i class="fa-solid fa-wand-magic-sparkles me-2"></i>Fantasy Admin
        </a>
        <div class="d-flex align-items-center text-white me-3">
            <span class="me-3 small"><i class="fa-solid fa-circle-user me-1 text-primary"></i> <?php echo htmlspecialchars($nombreUsuario); ?></span>
            <a href="logout.php" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-power-off"></i> Salir</a>
        </div>
    </div>
</nav>
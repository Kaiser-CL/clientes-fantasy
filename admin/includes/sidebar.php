<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pagina_actual = basename($_SERVER['PHP_SELF']);
?>

<div class="col-md-3 col-lg-2 bg-dark sidebar p-3 min-vh-100 shadow-sm">
    <div class="text-center my-3">
        <img src="../Images/logo_salon1.png" alt="Fantasy Admin" style="height: 80px; max-width: 100%; object-fit: contain; margin-bottom: 10px; border-radius: 8px;">
        <h5 class="text-white mb-0 fw-bold">Fantasy Admin</h5>
    </div>
    <hr class="text-secondary">
    
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item mb-1">
            <a href="generar_evento.php" class="nav-link text-white <?= ($pagina_actual == 'generar_evento.php') ? 'active bg-primary' : '' ?>">
                <i class="fa-solid fa-calendar-plus me-2"></i> Generar Evento
            </a>
        </li>
        <li class="nav-item mb-1">
            <a href="historial_eventos.php" class="nav-link text-white <?= ($pagina_actual == 'historial_eventos.php') ? 'active bg-primary' : '' ?>">
                <i class="fa-solid fa-calendar-check me-2"></i> Historial Eventos
            </a>
        </li>
        <li class="nav-item mb-1">
            <a href="catalogo.php" class="nav-link text-white <?= ($pagina_actual == 'catalogo.php') ? 'active bg-primary' : '' ?>">
                <i class="fa-solid fa-concierge-bell me-2"></i> Catálogo
            </a>
        </li>
        <li class="nav-item mb-1">
            <a href="clientes.php" class="nav-link text-white <?= ($pagina_actual == 'clientes.php') ? 'active bg-primary' : '' ?>">
                <i class="fa-solid fa-users me-2"></i> Clientes
            </a>
        </li>
        <?php if (isset($_SESSION['id_rol']) && $_SESSION['id_rol'] == 3): ?>
        <li class="nav-item mb-1">
            <a href="empleados.php" class="nav-link text-white <?= ($pagina_actual == 'empleados.php') ? 'active bg-primary' : '' ?>">
                <i class="fa-solid fa-user-gear me-2"></i> Empleados
            </a>
        </li>
        <?php endif; ?>
        <?php if (isset($_SESSION['id_rol']) && $_SESSION['id_rol'] == 3): ?>
        <li class="nav-item mb-1">
            <a href="bitacora.php" class="nav-link text-white <?= ($pagina_actual == 'bitacora.php') ? 'active bg-primary' : '' ?>">
                <i class="fa-solid fa-clipboard-list me-2"></i> Bitácora
            </a>
        </li>
        <?php endif; ?>
    </ul>

    <hr class="text-secondary">
    <div class="px-2 mt-auto">
        <div class="small text-white-50 mb-1">Sesión activa:</div>
        <div class="fw-bold text-white mb-2 text-truncate">
            <i class="fa-solid fa-circle-user me-1 text-primary"></i>
            <?= htmlspecialchars($_SESSION['nombre_usuario'] ?? $_SESSION['usuario'] ?? 'Maestra'); ?>
        </div>
        <a href="logout.php" class="btn btn-outline-danger btn-sm w-100">
            <i class="fa-solid fa-right-from-bracket me-2"></i> Cerrar Sesión
        </a>
    </div>
</div>
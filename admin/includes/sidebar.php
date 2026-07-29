<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pagina_actual = basename($_SERVER['PHP_SELF']);
$es_superadmin = isset($_SESSION['id_rol']) && (int)$_SESSION['id_rol'] === 3;
?>

<div class="col-md-3 col-lg-2 bg-dark sidebar p-3 min-vh-100 shadow-sm">
    <div class="text-center my-3">
        <img src="../Images/logo_salon1.png" alt="Fantasy Admin" style="height: 80px; max-width: 100%; object-fit: contain; margin-bottom: 10px; border-radius: 8px;">
        <h5 class="text-white mb-0 fw-bold">Fantasy Admin</h5>
    </div>
    <hr class="text-secondary">
    
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item mb-1">
            <a href="generar_evento.php" class="nav-link text-white rounded-3 <?= ($pagina_actual == 'generar_evento.php') ? 'active' : '' ?>" style="<?= ($pagina_actual == 'generar_evento.php') ? 'background-color: var(--color-morado) !important;' : '' ?>">
                <i class="fa-regular fa-calendar-plus me-2"></i> Generar Evento
            </a>
        </li>
        <li class="nav-item mb-1">
            <a href="historial_eventos.php" class="nav-link text-white rounded-3 <?= ($pagina_actual == 'historial_eventos.php') ? 'active' : '' ?>" style="<?= ($pagina_actual == 'historial_eventos.php') ? 'background-color: var(--color-morado) !important;' : '' ?>">
                <i class="fa-regular fa-calendar-check me-2"></i> Historial Eventos
            </a>
        </li>
        <li class="nav-item mb-1">
            <a href="catalogo.php" class="nav-link text-white rounded-3 <?= ($pagina_actual == 'catalogo.php') ? 'active' : '' ?>" style="<?= ($pagina_actual == 'catalogo.php') ? 'background-color: var(--color-morado) !important;' : '' ?>">
                <i class="fa-solid fa-layer-group me-2"></i> Catálogo
            </a>
        </li>
        <li class="nav-item mb-1">
            <a href="clientes.php" class="nav-link text-white rounded-3 <?= ($pagina_actual == 'clientes.php') ? 'active' : '' ?>" style="<?= ($pagina_actual == 'clientes.php') ? 'background-color: var(--color-morado) !important;' : '' ?>">
                <i class="fa-solid fa-users me-2"></i> Clientes
            </a>
        </li>
        <?php if ($es_superadmin): ?>
        <li class="nav-item mb-1">
            <a href="empleados.php" class="nav-link text-white rounded-3 <?= ($pagina_actual == 'empleados.php') ? 'active' : '' ?>" style="<?= ($pagina_actual == 'empleados.php') ? 'background-color: var(--color-morado) !important;' : '' ?>">
                <i class="fa-solid fa-user-gear me-2"></i> Empleados
            </a>
        </li>
        <li class="nav-item mb-1">
            <a href="bitacora.php" class="nav-link text-white rounded-3 <?= ($pagina_actual == 'bitacora.php') ? 'active' : '' ?>" style="<?= ($pagina_actual == 'bitacora.php') ? 'background-color: var(--color-rosa) !important;' : '' ?>">
                <i class="fa-solid fa-clock-rotate-left me-2"></i> Bitácora
            </a>
        </li>
        <?php endif; ?>
    </ul>

    <hr class="text-secondary">
    <div class="px-2 mt-auto">
        <div class="small text-white-50 mb-1">Sesión activa:</div>
        <div class="fw-bold text-white mb-2 text-truncate">
            <i class="fa-solid fa-circle-user me-1 text-warning"></i>
            <?= htmlspecialchars($_SESSION['nombre_completo'] ?? $_SESSION['nombre_usuario'] ?? 'Administrador'); ?>
        </div>
        <a href="logout.php" class="btn btn-outline-danger btn-sm w-100 rounded-pill">
            <i class="fa-solid fa-right-from-bracket me-2"></i> Cerrar Sesión
        </a>
    </div>
</div>

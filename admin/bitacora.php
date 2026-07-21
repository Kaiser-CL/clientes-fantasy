<?php
// bitacora.php
session_start();
$raiz = dirname(__DIR__);
$conexion_path = file_exists($raiz . '/db_config.php') ? $raiz . '/db_config.php' : (file_exists(__DIR__ . '/db_config.php') ? __DIR__ . '/db_config.php' : null);

if ($conexion_path) {
    require_once $conexion_path;
} else if (file_exists('conexion.php')) {
    require_once 'conexion.php';
}

if (!isset($pdo)) {
    die("Error: No se pudo conectar a la base de datos.");
}

try {
    // Consultamos los registros de la bitácora usando la nueva tabla historial_cambios
    $sql = "SELECT h.id_historial, h.accion, h.tabla_afectada, h.id_registro, h.fecha_cambio, h.datos_anteriores, h.datos_nuevos,
                   u.nombre_usuario, u.apellidos_usuario
            FROM historial_cambios h
            LEFT JOIN usuarios u ON h.id_usuario = u.id_usuario
            ORDER BY h.fecha_cambio DESC
            LIMIT 100";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al consultar la bitácora: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bitácora de Actividades - Admin Fantasy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { min-height: 100vh; background-color: #e2e8f0; font-family: system-ui, -apple-system, sans-serif; }
        .card-custom { background: #ffffff; border-radius: 12px; border: 2px solid #cbd5e1; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .table-responsive { border-radius: 8px; overflow: hidden; }
        .fecha-log { font-family: monospace; color: #475569; font-size: 0.9em; }
        .datos-json { font-family: monospace; font-size: 0.75em; max-height: 120px; overflow-y: auto; background-color: #f1f5f9; padding: 8px; border-radius: 6px; border: 1px solid #cbd5e1; margin-top: 4px; white-space: pre-wrap; word-break: break-all; }
        .badge-agregar { background-color: #10b981; color: white; } /* Emerald */
        .badge-actualizar { background-color: #0ea5e9; color: white; } /* Sky */
        .badge-eliminar { background-color: #ef4444; color: white; } /* Red */
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <?php 
        $sidebar_path = file_exists($raiz . '/includes/sidebar.php') ? $raiz . '/includes/sidebar.php' : (file_exists(__DIR__ . '/includes/sidebar.php') ? __DIR__ . '/includes/sidebar.php' : null);
        if ($sidebar_path) { include $sidebar_path; }
        ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
                <h2 class="h3 mb-0"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i>Bitácora del Sistema</h2>
            </div>

            <div class="card-custom p-0 overflow-hidden">
                <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                    <h4 class="fw-bold m-0 text-dark"><i class="fa-solid fa-list text-primary me-2"></i>Últimos 100 Cambios</h4>
                    <span class="badge bg-dark fs-6"><?= count($logs) ?> Registros</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="ps-3">ID</th>
                                <th>Usuario</th>
                                <th>Acción</th>
                                <th>Tabla Afectada</th>
                                <th style="width: 35%;">Detalle de Cambios</th>
                                <th>Fecha y Hora</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted fw-bold">No hay registros en el historial.</td></tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <?php 
                                        $clase_badge = 'bg-secondary';
                                        if ($log['accion'] === 'AGREGAR') $clase_badge = 'badge-agregar';
                                        if ($log['accion'] === 'ACTUALIZAR') $clase_badge = 'badge-actualizar';
                                        if ($log['accion'] === 'ELIMINAR') $clase_badge = 'badge-eliminar';
                                    ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-muted">#<?= htmlspecialchars($log['id_historial']) ?></td>
                                        <td>
                                            <span class="fw-bold text-dark">
                                                <?= $log['nombre_usuario'] ? htmlspecialchars($log['nombre_usuario'] . ' ' . $log['apellidos_usuario']) : '<em>Sistema</em>' ?>
                                            </span>
                                        </td>
                                        <td><span class="badge <?= $clase_badge ?> fs-6"><i class="fa-solid <?= $log['accion'] === 'AGREGAR' ? 'fa-plus' : ($log['accion'] === 'ACTUALIZAR' ? 'fa-pen' : 'fa-trash') ?> me-1"></i><?= htmlspecialchars($log['accion'] ?? 'N/A') ?></span></td>
                                        <td>
                                            <span class="fw-bold text-primary"><?= htmlspecialchars(strtoupper($log['tabla_afectada'])) ?></span><br>
                                            <span class="badge bg-light text-dark border">Registro ID: <?= htmlspecialchars($log['id_registro']) ?></span>
                                        </td>
                                        <td>
                                            <?php if ($log['accion'] === 'ACTUALIZAR'): ?>
                                                <div class="row g-2">
                                                    <div class="col-6">
                                                        <small class="fw-bold text-muted">Datos Anteriores:</small>
                                                        <div class="datos-json"><?= htmlspecialchars($log['datos_anteriores'] ?? 'N/A') ?></div>
                                                    </div>
                                                    <div class="col-6">
                                                        <small class="fw-bold text-muted">Datos Nuevos:</small>
                                                        <div class="datos-json"><?= htmlspecialchars($log['datos_nuevos'] ?? 'N/A') ?></div>
                                                    </div>
                                                </div>
                                            <?php elseif ($log['accion'] === 'AGREGAR'): ?>
                                                <small class="fw-bold text-success">Datos Guardados:</small>
                                                <div class="datos-json"><?= htmlspecialchars($log['datos_nuevos'] ?? 'N/A') ?></div>
                                            <?php elseif ($log['accion'] === 'ELIMINAR'): ?>
                                                <small class="fw-bold text-danger">Datos Eliminados:</small>
                                                <div class="datos-json"><?= htmlspecialchars($log['datos_anteriores'] ?? 'N/A') ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fecha-log text-nowrap"><i class="fa-regular fa-calendar me-1"></i><?= htmlspecialchars($log['fecha_cambio']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
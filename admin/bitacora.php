<?php
// bitacora.php
session_start();
require_once 'auth_check.php';

if (!esSuperAdmin()) {
    header("Location: catalogo.php");
    exit();
}

require_once __DIR__ . '/../db_config.php';

if (!isset($pdo)) {
    die("Error: No se pudo conectar a la base de datos.");
}

try {
    // Consultamos los registros de la bitácora usando la tabla historial_cambios
    $sql = "SELECT h.id_historial, h.accion, h.tabla_afectada, h.id_registro, h.fecha_cambio, h.datos_anteriores, h.datos_nuevos,
                   u.nombre_usuario, u.apellidos_usuario, u.email
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

include __DIR__ . '/includes/header.php';
?>

<style>
    .card-custom { background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
    .fecha-log { font-family: monospace; color: #475569; font-size: 0.85em; }
    .datos-json { font-family: monospace; font-size: 0.75em; max-height: 120px; overflow-y: auto; background-color: #f8fafc; padding: 8px; border-radius: 6px; border: 1px solid #cbd5e1; margin-top: 4px; white-space: pre-wrap; word-break: break-all; }
    .badge-agregar { background-color: var(--color-verde); color: white; }
    .badge-actualizar { background-color: var(--color-azul); color: white; }
    .badge-eliminar { background-color: #dc3545; color: white; }
</style>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
                <h2 class="h3 mb-0 fw-bold" style="color: var(--color-morado);">
                    <i class="fa-solid fa-clock-rotate-left me-2" style="color: var(--color-rosa);"></i>Bitácora del Sistema
                </h2>
            </div>

            <div class="card-custom p-0 overflow-hidden">
                <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold m-0 text-dark"><i class="fa-solid fa-list me-2" style="color: var(--color-morado);"></i>Últimos 100 Cambios</h5>
                    <span class="badge rounded-pill bg-dark fs-6"><?= count($logs) ?> Registros</span>
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
                                        if (strtoupper($log['accion']) === 'AGREGAR') $clase_badge = 'badge-agregar';
                                        if (strtoupper($log['accion']) === 'ACTUALIZAR') $clase_badge = 'badge-actualizar';
                                        if (strtoupper($log['accion']) === 'ELIMINAR') $clase_badge = 'badge-eliminar';

                                        $anterior_fmt = 'N/A';
                                        if (!empty($log['datos_anteriores'])) {
                                            $json_decoded = json_decode($log['datos_anteriores']);
                                            $anterior_fmt = $json_decoded ? json_encode($json_decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $log['datos_anteriores'];
                                        }

                                        $nuevo_fmt = 'N/A';
                                        if (!empty($log['datos_nuevos'])) {
                                            $json_decoded = json_decode($log['datos_nuevos']);
                                            $nuevo_fmt = $json_decoded ? json_encode($json_decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $log['datos_nuevos'];
                                        }
                                    ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-muted">#<?= htmlspecialchars($log['id_historial']) ?></td>
                                        <td>
                                            <span class="fw-bold text-dark">
                                                <?= $log['nombre_usuario'] ? htmlspecialchars($log['nombre_usuario'] . ' ' . ($log['apellidos_usuario'] ?? '')) : '<em>Sistema</em>' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?= $clase_badge ?> fs-6 rounded-pill">
                                                <i class="fa-solid <?= strtoupper($log['accion']) === 'AGREGAR' ? 'fa-plus' : (strtoupper($log['accion']) === 'ACTUALIZAR' ? 'fa-pen' : 'fa-trash') ?> me-1"></i>
                                                <?= htmlspecialchars(strtoupper($log['accion'] ?? 'N/A')) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fw-bold" style="color: var(--color-morado);"><?= htmlspecialchars(strtoupper($log['tabla_afectada'])) ?></span><br>
                                            <span class="badge bg-light text-dark border">ID Registro: <?= htmlspecialchars($log['id_registro']) ?></span>
                                        </td>
                                        <td>
                                            <?php if (strtoupper($log['accion']) === 'ACTUALIZAR'): ?>
                                                <div class="row g-2">
                                                    <div class="col-6">
                                                        <small class="fw-bold text-muted">Anterior:</small>
                                                        <div class="datos-json"><?= htmlspecialchars($anterior_fmt) ?></div>
                                                    </div>
                                                    <div class="col-6">
                                                        <small class="fw-bold text-muted">Nuevo:</small>
                                                        <div class="datos-json"><?= htmlspecialchars($nuevo_fmt) ?></div>
                                                    </div>
                                                </div>
                                            <?php elseif (strtoupper($log['accion']) === 'AGREGAR'): ?>
                                                <small class="fw-bold text-success">Guardado:</small>
                                                <div class="datos-json"><?= htmlspecialchars($nuevo_fmt) ?></div>
                                            <?php elseif (strtoupper($log['accion']) === 'ELIMINAR'): ?>
                                                <small class="fw-bold text-danger">Eliminado:</small>
                                                <div class="datos-json"><?= htmlspecialchars($anterior_fmt) ?></div>
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

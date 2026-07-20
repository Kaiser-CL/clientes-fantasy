<?php
// bitacora.php
require_once 'conexion.php';

try {
    // Consultamos los registros de la bitácora uniendo con la tabla de usuarios
    $sql = "SELECT b.id_bitacora, b.accion, b.descripcion, b.fecha_registro,
                   u.nombre_usuario, u.apellidos_usuario
            FROM bitacora b
            LEFT JOIN usuarios u ON b.id_usuario = u.id_usuario
            ORDER BY b.fecha_registro DESC
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
    <title>Bitácora de Actividades - Admin Fantasy</title>
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        .tabla-bitacora { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 0.9em; }
        .tabla-bitacora th, .tabla-bitacora td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        .tabla-bitacora th { background-color: #f4f4f4; }
        .fecha-log { font-family: monospace; color: #555; }
        .badge-accion { background-color: #e2e3e5; color: #383d41; padding: 3px 6px; border-radius: 3px; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <h2>Bitácora del Sistema (Últimos 100 registros)</h2>
    
    <table class="tabla-bitacora">
        <thead>
            <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Acción</th>
                <th>Descripción</th>
                <th>Fecha y Hora</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
                <tr><td colspan="5" style="text-align:center;">No hay registros en la bitácora.</td></tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td>#<?= htmlspecialchars($log['id_bitacora']) ?></td>
                        <td>
                            <?= $log['nombre_usuario'] ? htmlspecialchars($log['nombre_usuario'] . ' ' . $log['apellidos_usuario']) : '<em>Sistema / Desconocido</em>' ?>
                        </td>
                        <td><span class="badge-accion"><?= htmlspecialchars($log['accion'] ?? 'GENERAL') ?></span></td>
                        <td><?= htmlspecialchars($log['descripcion']) ?></td>
                        <td class="fecha-log"><?= htmlspecialchars($log['fecha_registro']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
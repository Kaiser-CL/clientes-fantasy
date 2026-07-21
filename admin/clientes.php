<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'auth_check.php';

$raiz = dirname(__DIR__);
$conexion_path = file_exists($raiz . '/db_config.php') ? $raiz . '/db_config.php' : (file_exists(__DIR__ . '/db_config.php') ? __DIR__ . '/db_config.php' : null);

if ($conexion_path) {
    require_once $conexion_path;
}

$mensaje = '';
$error_db = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($pdo)) {
    // Procesar Registro de Nuevo Cliente
    if (isset($_POST['accion']) && $_POST['accion'] === 'crear') {
        $nombre = trim($_POST['nombre_usuario']);
        $apellidos = trim($_POST['apellidos_usuario']);
        $correo = trim($_POST['correo_usuario']);
        $telefono = trim($_POST['telefono_usuario']);
        $password = password_hash($_POST['password_usuario'] ?? '123456', PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre_usuario, apellidos_usuario, correo_usuario, telefono_usuario, contrasena_usuario, id_rol, estado_usuario) VALUES (?, ?, ?, ?, ?, 2, 1)");
            $stmt->execute([$nombre, $apellidos, $correo, $telefono, $password]);
            $mensaje = "Cliente agregado correctamente.";
        } catch (PDOException $e) {
            $error_db = "Error al guardar cliente: " . $e->getMessage();
        }
    }

    // Procesar Edición de Cliente
    if (isset($_POST['accion']) && $_POST['accion'] === 'editar') {
        $id_usuario = $_POST['id_usuario'];
        $nombre = trim($_POST['nombre_usuario']);
        $apellidos = trim($_POST['apellidos_usuario']);
        $correo = trim($_POST['correo_usuario']);
        $telefono = trim($_POST['telefono_usuario']);
        $estado = $_POST['estado_usuario'];

        try {
            $stmt = $pdo->prepare("UPDATE usuarios SET nombre_usuario = ?, apellidos_usuario = ?, correo_usuario = ?, telefono_usuario = ?, estado_usuario = ? WHERE id_usuario = ? AND id_rol = 2");
            $stmt->execute([$nombre, $apellidos, $correo, $telefono, $estado, $id_usuario]);
            $mensaje = "Cliente actualizado correctamente.";
        } catch (PDOException $e) {
            $error_db = "Error al actualizar cliente: " . $e->getMessage();
        }
    }
}

// Consultar clientes
$clientes = [];
if (isset($pdo)) {
    try {
        $sql = "SELECT u.id_usuario, u.nombre_usuario, u.apellidos_usuario, 
                       u.correo_usuario, u.telefono_usuario, u.estado_usuario,
                       (SELECT COUNT(*) FROM eventos e WHERE e.id_cliente = u.id_usuario) AS total_eventos
                FROM usuarios u
                WHERE u.id_rol = 2
                ORDER BY u.id_usuario DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_db = "Error SQL: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Clientes - Admin Fantasy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body class="bg-light">

<div class="container-fluid">
    <div class="row">
        <?php 
        $sidebar_path = file_exists($raiz . '/includes/sidebar.php') ? $raiz . '/includes/sidebar.php' : (file_exists(__DIR__ . '/includes/sidebar.php') ? __DIR__ . '/includes/sidebar.php' : null);
        if ($sidebar_path) { include $sidebar_path; }
        ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
                <h2 class="h3"><i class="fa-solid fa-users text-primary me-2"></i>Control de Clientes</h2>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAgregarCliente">
                    <i class="fa-solid fa-user-plus me-1"></i>Agregar Cliente
                </button>
            </div>

            <?php if ($mensaje): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($mensaje) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error_db): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error_db) ?></div>
            <?php endif; ?>

            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th class="ps-3">ID</th>
                                    <th>Nombre Completo</th>
                                    <th>Correo Electrónico</th>
                                    <th>Teléfono</th>
                                    <th>Eventos</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($clientes)): ?>
                                    <tr><td colspan="7" class="text-center py-4 text-muted">No hay clientes registrados.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($clientes as $c): ?>
                                        <tr>
                                            <td class="ps-3 fw-bold">#<?= htmlspecialchars($c['id_usuario']) ?></td>
                                            <td><?= htmlspecialchars($c['nombre_usuario'] . ' ' . $c['apellidos_usuario']) ?></td>
                                            <td><?= htmlspecialchars($c['correo_usuario']) ?></td>
                                            <td><?= htmlspecialchars($c['telefono_usuario'] ?? 'N/A') ?></td>
                                            <td><span class="badge bg-info text-dark"><?= htmlspecialchars($c['total_eventos']) ?> evento(s)</span></td>
                                            <td>
                                                <span class="badge <?= $c['estado_usuario'] == 1 ? 'bg-success' : 'bg-danger' ?>">
                                                    <?= $c['estado_usuario'] == 1 ? 'Activo' : 'Inactivo' ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalEditarCliente<?= $c['id_usuario'] ?>">
                                                    <i class="fa-solid fa-pen-to-square"></i> Editar
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- Modal Editar Cliente -->
                                        <div class="modal fade" id="modalEditarCliente<?= $c['id_usuario'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST">
                                                        <input type="hidden" name="accion" value="editar">
                                                        <input type="hidden" name="id_usuario" value="<?= $c['id_usuario'] ?>">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Editar Cliente #<?= $c['id_usuario'] ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">Nombre</label>
                                                                <input type="text" name="nombre_usuario" class="form-control" value="<?= htmlspecialchars($c['nombre_usuario']) ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Apellidos</label>
                                                                <input type="text" name="apellidos_usuario" class="form-control" value="<?= htmlspecialchars($c['apellidos_usuario']) ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Correo Electrónico</label>
                                                                <input type="email" name="correo_usuario" class="form-control" value="<?= htmlspecialchars($c['correo_usuario']) ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Teléfono</label>
                                                                <input type="text" name="telefono_usuario" class="form-control" value="<?= htmlspecialchars($c['telefono_usuario'] ?? '') ?>">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Estado</label>
                                                                <select name="estado_usuario" class="form-select">
                                                                    <option value="1" <?= $c['estado_usuario'] == 1 ? 'selected' : '' ?>>Activo</option>
                                                                    <option value="0" <?= $c['estado_usuario'] == 0 ? 'selected' : '' ?>>Inactivo</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar Cambios</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal Agregar Cliente -->
<div class="modal fade" id="modalAgregarCliente" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="accion" value="crear">
                <div class="modal-header">
                    <h5 class="modal-title">Agregar Nuevo Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre_usuario" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Apellidos</label>
                        <input type="text" name="apellidos_usuario" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Correo Electrónico</label>
                        <input type="email" name="correo_usuario" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono_usuario" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <input type="password" name="password_usuario" class="form-control" placeholder="Por defecto: 123456">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-user-plus me-1"></i> Guardar Cliente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
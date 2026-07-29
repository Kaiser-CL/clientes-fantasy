<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'auth_check.php';
require_once __DIR__ . '/../db_config.php';

$mensaje = '';
$error_db = null;

if (isset($_SESSION['mensaje_exito'])) {
    $mensaje = $_SESSION['mensaje_exito'];
    unset($_SESSION['mensaje_exito']);
}
if (isset($_SESSION['error_db'])) {
    $error_db = $_SESSION['error_db'];
    unset($_SESSION['error_db']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($pdo)) {
    // -------------------------------------------------------------
    // 1. REGISTRO DE NUEVO CLIENTE (Rol 2)
    // -------------------------------------------------------------
    if (isset($_POST['accion']) && $_POST['accion'] === 'crear') {
        $nombre = trim($_POST['nombre_usuario']);
        $apellidos = trim($_POST['apellidos_usuario']);
        $correo = trim($_POST['correo_usuario']);
        $telefono = trim($_POST['telefono_usuario']);
        $password = password_hash($_POST['password_usuario'] ?? '123456', PASSWORD_DEFAULT);

        try {
            $id_empleado = $_SESSION['id_usuario'] ?? null;
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre_usuario, apellidos_usuario, email, telefono_usuario, contrasena_usuario, id_rol, estado_usuario, id_empleado_registro) VALUES (?, ?, ?, ?, ?, 2, 1, ?)");
            $stmt->execute([$nombre, $apellidos, $correo, $telefono, $password, $id_empleado]);
            $nuevoId = $pdo->lastInsertId();

            // BITÁCORA
            if (function_exists('registrarBitacora')) {
                registrarBitacora($pdo, 'AGREGAR', 'usuarios', $nuevoId, null, [
                    'nombre' => $nombre . ' ' . $apellidos,
                    'email' => $correo,
                    'rol' => 'Cliente'
                ]);
            }

            $_SESSION['mensaje_exito'] = "Cliente agregado correctamente.";
        } catch (PDOException $e) {
            $_SESSION['error_db'] = "Error al guardar cliente: " . $e->getMessage();
        }
        header("Location: clientes.php");
        exit;
    }

    // -------------------------------------------------------------
    // 2. EDICIÓN DE CLIENTE
    // -------------------------------------------------------------
    if (isset($_POST['accion']) && $_POST['accion'] === 'editar') {
        $id_usuario = $_POST['id_usuario'];
        $nombre = trim($_POST['nombre_usuario']);
        $apellidos = trim($_POST['apellidos_usuario']);
        $correo = trim($_POST['correo_usuario']);
        $telefono = trim($_POST['telefono_usuario']);
        $estado = $_POST['estado_usuario'];
        $nueva_pass = trim($_POST['contrasena'] ?? '');

        try {
            // Datos viejos para auditoría
            $stmtOld = $pdo->prepare("SELECT nombre_usuario, apellidos_usuario, email, telefono_usuario, estado_usuario FROM usuarios WHERE id_usuario = ?");
            $stmtOld->execute([$id_usuario]);
            $datosViejos = $stmtOld->fetch(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("UPDATE usuarios SET nombre_usuario = ?, apellidos_usuario = ?, email = ?, telefono_usuario = ?, estado_usuario = ? WHERE id_usuario = ? AND id_rol = 2");
            $stmt->execute([$nombre, $apellidos, $correo, $telefono, $estado, $id_usuario]);

            if (esSuperAdmin() && !empty($nueva_pass)) {
                $pass_hash = password_hash($nueva_pass, PASSWORD_DEFAULT);
                $stmtPass = $pdo->prepare("UPDATE usuarios SET contrasena_usuario = ? WHERE id_usuario = ? AND id_rol = 2");
                $stmtPass->execute([$pass_hash, $id_usuario]);
            }

            // BITÁCORA
            if (function_exists('registrarBitacora')) {
                registrarBitacora($pdo, 'ACTUALIZAR', 'usuarios', $id_usuario, $datosViejos, [
                    'nombre' => $nombre . ' ' . $apellidos,
                    'email' => $correo,
                    'estado' => $estado
                ]);
            }

            $_SESSION['mensaje_exito'] = "Cliente actualizado correctamente.";
        } catch (PDOException $e) {
            $_SESSION['error_db'] = "Error al actualizar cliente: " . $e->getMessage();
        }
        header("Location: clientes.php");
        exit;
    }
}

// Consultar clientes (Rol 2)
$clientes = [];
if (isset($pdo)) {
    try {
        $sql = "SELECT u.id_usuario, u.nombre_usuario, u.apellidos_usuario, 
                       u.email, u.telefono_usuario, u.estado_usuario,
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

include __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
                <h2 class="h3 fw-bold" style="color: var(--color-morado);"><i class="fa-solid fa-users me-2" style="color: var(--color-rosa);"></i>Control de Clientes</h2>
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

            <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
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
                                    <tr><td colspan="7" class="text-center py-4 text-muted fw-bold">No hay clientes registrados.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($clientes as $c): ?>
                                        <tr>
                                            <td class="ps-3 fw-bold text-muted">#<?= htmlspecialchars($c['id_usuario']) ?></td>
                                            <td><strong class="text-dark"><?= htmlspecialchars($c['nombre_usuario'] . ' ' . $c['apellidos_usuario']) ?></strong></td>
                                            <td><?= htmlspecialchars($c['email'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($c['telefono_usuario'] ?? 'N/A') ?></td>
                                            <td><span class="badge badge-rosa rounded-pill"><?= htmlspecialchars($c['total_eventos']) ?> evento(s)</span></td>
                                            <td>
                                                <span class="badge rounded-pill <?= $c['estado_usuario'] == 1 ? 'bg-success' : 'bg-danger' ?>">
                                                    <?= $c['estado_usuario'] == 1 ? 'Activo' : 'Inactivo' ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-warning rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#modalEditarCliente<?= $c['id_usuario'] ?>">
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
                                                        <div class="modal-header bg-dark text-white">
                                                            <h5 class="modal-title fw-bold">Editar Cliente #<?= $c['id_usuario'] ?></h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Nombre</label>
                                                                <input type="text" name="nombre_usuario" class="form-control" value="<?= htmlspecialchars($c['nombre_usuario']) ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Apellidos</label>
                                                                <input type="text" name="apellidos_usuario" class="form-control" value="<?= htmlspecialchars($c['apellidos_usuario']) ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Correo Electrónico</label>
                                                                <input type="email" name="correo_usuario" class="form-control" value="<?= htmlspecialchars($c['email'] ?? '') ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Teléfono</label>
                                                                <input type="text" name="telefono_usuario" class="form-control" value="<?= htmlspecialchars($c['telefono_usuario'] ?? '') ?>">
                                                            </div>

                                                            <?php if (esSuperAdmin()): ?>
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-bold text-danger">Nueva Contraseña</label>
                                                                    <input type="password" name="contrasena" class="form-control" placeholder="Dejar en blanco para mantener la actual">
                                                                    <small class="form-text text-muted">Solo tú como Superadministrador puedes redefinir contraseñas.</small>
                                                                </div>
                                                            <?php endif; ?>

                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Estado</label>
                                                                <select name="estado_usuario" class="form-select">
                                                                    <option value="1" <?= $c['estado_usuario'] == 1 ? 'selected' : '' ?>>Activo</option>
                                                                    <option value="0" <?= $c['estado_usuario'] == 0 ? 'selected' : '' ?>>Inactivo</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
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
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold">Agregar Nuevo Cliente</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre</label>
                        <input type="text" name="nombre_usuario" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Apellidos</label>
                        <input type="text" name="apellidos_usuario" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Correo Electrónico</label>
                        <input type="email" name="correo_usuario" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Teléfono</label>
                        <input type="text" name="telefono_usuario" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Contraseña</label>
                        <input type="password" name="password_usuario" class="form-control" placeholder="Por defecto: 123456">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-user-plus me-1"></i> Guardar Cliente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

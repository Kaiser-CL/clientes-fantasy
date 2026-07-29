<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'auth_check.php';

if (!esSuperAdmin()) {
    header("Location: catalogo.php");
    exit();
}

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
    // 1. CREAR EMPLEADO
    if (isset($_POST['accion']) && $_POST['accion'] === 'crear') {
        $nombre = trim($_POST['nombre_usuario']);
        $apellidos = trim($_POST['apellidos_usuario']);
        $correo = trim($_POST['correo_usuario']);
        $telefono = trim($_POST['telefono_usuario']);
        $pass = password_hash($_POST['password_usuario'] ?? '123456', PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre_usuario, apellidos_usuario, email, telefono_usuario, contrasena_usuario, id_rol, estado_usuario) VALUES (?, ?, ?, ?, ?, 4, 1)");
            $stmt->execute([$nombre, $apellidos, $correo, $telefono, $pass]);
            $nuevoId = $pdo->lastInsertId();

            if (function_exists('registrarBitacora')) {
                registrarBitacora($pdo, 'AGREGAR', 'usuarios', $nuevoId, null, [
                    'nombre' => $nombre . ' ' . $apellidos,
                    'email' => $correo,
                    'rol' => 'Empleado'
                ]);
            }

            $_SESSION['mensaje_exito'] = "Empleado guardado correctamente.";
        } catch (PDOException $e) {
            $_SESSION['error_db'] = "Error al guardar empleado: " . $e->getMessage();
        }
        header("Location: empleados.php");
        exit;
    }

    // 2. EDITAR EMPLEADO
    if (isset($_POST['accion']) && $_POST['accion'] === 'editar') {
        $id_u = $_POST['id_usuario'];
        $nombre = trim($_POST['nombre_usuario']);
        $apellidos = trim($_POST['apellidos_usuario']);
        $correo = trim($_POST['correo_usuario']);
        $telefono = trim($_POST['telefono_usuario']);
        $estado = $_POST['estado_usuario'];
        $nueva_pass = trim($_POST['contrasena'] ?? '');

        try {
            $stmtOld = $pdo->prepare("SELECT nombre_usuario, apellidos_usuario, email, telefono_usuario, estado_usuario FROM usuarios WHERE id_usuario = ?");
            $stmtOld->execute([$id_u]);
            $datosViejos = $stmtOld->fetch(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("UPDATE usuarios SET nombre_usuario = ?, apellidos_usuario = ?, email = ?, telefono_usuario = ?, estado_usuario = ? WHERE id_usuario = ? AND id_rol = 4");
            $stmt->execute([$nombre, $apellidos, $correo, $telefono, $estado, $id_u]);

            if (esSuperAdmin() && !empty($nueva_pass)) {
                $pass_hash = password_hash($nueva_pass, PASSWORD_DEFAULT);
                $stmtPass = $pdo->prepare("UPDATE usuarios SET contrasena_usuario = ? WHERE id_usuario = ?");
                $stmtPass->execute([$pass_hash, $id_u]);
            }

            if (function_exists('registrarBitacora')) {
                registrarBitacora($pdo, 'ACTUALIZAR', 'usuarios', $id_u, $datosViejos, [
                    'nombre' => $nombre . ' ' . $apellidos,
                    'email' => $correo,
                    'estado' => $estado
                ]);
            }

            $_SESSION['mensaje_exito'] = "Empleado actualizado correctamente.";
        } catch (PDOException $e) {
            $_SESSION['error_db'] = "Error al actualizar empleado: " . $e->getMessage();
        }
        header("Location: empleados.php");
        exit;
    }

    // 3. ELIMINAR EMPLEADO
    if (isset($_POST['accion']) && $_POST['accion'] === 'eliminar') {
        $id_u = $_POST['id_usuario'];
        try {
            $stmtOld = $pdo->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
            $stmtOld->execute([$id_u]);
            $datosBorrados = $stmtOld->fetch(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id_usuario = ? AND id_rol = 4");
            $stmt->execute([$id_u]);

            if (function_exists('registrarBitacora')) {
                registrarBitacora($pdo, 'ELIMINAR', 'usuarios', $id_u, $datosBorrados, null);
            }

            $_SESSION['mensaje_exito'] = "Empleado eliminado correctamente.";
        } catch (PDOException $e) {
            $_SESSION['error_db'] = "Error al eliminar empleado: " . $e->getMessage();
        }
        header("Location: empleados.php");
        exit;
    }
}

// Consultar SOLO empleados (Rol 4)
$empleados = [];
if (isset($pdo)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id_rol = 4 ORDER BY id_usuario DESC");
        $stmt->execute();
        $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                <h2 class="h3 fw-bold" style="color: var(--color-morado);"><i class="fa-solid fa-user-gear me-2" style="color: var(--color-rosa);"></i>Control de Personal y Empleados</h2>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAgregarEmpleado">
                    <i class="fa-solid fa-user-plus me-1"></i>Agregar Empleado
                </button>
            </div>

            <?php if ($mensaje): ?>
                <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($mensaje) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
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
                                    <th>Rol</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($empleados)): ?>
                                    <tr><td colspan="7" class="text-center py-4 text-muted fw-bold">No hay empleados registrados.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($empleados as $emp): ?>
                                        <tr>
                                            <td class="ps-3 fw-bold text-muted">#<?= htmlspecialchars($emp['id_usuario']) ?></td>
                                            <td><strong class="text-dark"><?= htmlspecialchars($emp['nombre_usuario'] . ' ' . $emp['apellidos_usuario']) ?></strong></td>
                                            <td><?= htmlspecialchars($emp['email'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($emp['telefono_usuario'] ?? 'N/A') ?></td>
                                            <td><span class="badge bg-secondary rounded-pill">Empleado</span></td>
                                            <td>
                                                <span class="badge rounded-pill <?= ($emp['estado_usuario'] == 1) ? 'bg-success' : 'bg-danger' ?>">
                                                    <?= ($emp['estado_usuario'] == 1) ? 'Activo' : 'Inactivo' ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-warning rounded-pill fw-bold me-1" data-bs-toggle="modal" data-bs-target="#modalEditarEmp<?= $emp['id_usuario'] ?>">
                                                    <i class="fa-solid fa-pen-to-square"></i> Editar
                                                </button>
                                                <button class="btn btn-sm btn-danger rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#modalEliminarEmp<?= $emp['id_usuario'] ?>">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- Modal Editar Empleado -->
                                        <div class="modal fade" id="modalEditarEmp<?= $emp['id_usuario'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST">
                                                        <input type="hidden" name="accion" value="editar">
                                                        <input type="hidden" name="id_usuario" value="<?= $emp['id_usuario'] ?>">
                                                        <div class="modal-header bg-dark text-white">
                                                            <h5 class="modal-title fw-bold">Editar Empleado #<?= $emp['id_usuario'] ?></h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Nombre</label>
                                                                <input type="text" name="nombre_usuario" class="form-control" value="<?= htmlspecialchars($emp['nombre_usuario']) ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Apellidos</label>
                                                                <input type="text" name="apellidos_usuario" class="form-control" value="<?= htmlspecialchars($emp['apellidos_usuario']) ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Correo Electrónico</label>
                                                                <input type="email" name="correo_usuario" class="form-control" value="<?= htmlspecialchars($emp['email'] ?? '') ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Teléfono</label>
                                                                <input type="text" name="telefono_usuario" class="form-control" value="<?= htmlspecialchars($emp['telefono_usuario'] ?? '') ?>">
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
                                                                    <option value="1" <?= $emp['estado_usuario'] == 1 ? 'selected' : '' ?>>Activo</option>
                                                                    <option value="0" <?= $emp['estado_usuario'] == 0 ? 'selected' : '' ?>>Inactivo</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
                                                            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Modal Eliminar Empleado -->
                                        <div class="modal fade" id="modalEliminarEmp<?= $emp['id_usuario'] ?>" tabindex="-1">
                                            <div class="modal-dialog modal-sm">
                                                <div class="modal-content">
                                                    <form method="POST">
                                                        <input type="hidden" name="accion" value="eliminar">
                                                        <input type="hidden" name="id_usuario" value="<?= $emp['id_usuario'] ?>">
                                                        <div class="modal-header bg-danger text-white">
                                                            <h5 class="modal-title h6 fw-bold">Eliminar Empleado</h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body text-center">
                                                            ¿Seguro que deseas eliminar a <strong><?= htmlspecialchars($emp['nombre_usuario']) ?></strong>?
                                                        </div>
                                                        <div class="modal-footer justify-content-center">
                                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                                            <button type="submit" class="btn btn-danger btn-sm">Sí, Eliminar</button>
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

<!-- Modal Agregar Empleado -->
<div class="modal fade" id="modalAgregarEmpleado" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="accion" value="crear">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold">Agregar Nuevo Empleado</h5>
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
                        <input type="password" name="password_usuario" class="form-control" placeholder="Clave inicial">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Empleado</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

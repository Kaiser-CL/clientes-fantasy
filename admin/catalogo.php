<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$raiz = dirname(__DIR__);
$conexion_path = file_exists($raiz . '/db_config.php') ? $raiz . '/db_config.php' : (file_exists(__DIR__ . '/db_config.php') ? __DIR__ . '/db_config.php' : null);

if ($conexion_path) {
    require_once $conexion_path;
}

$mensaje = '';
$error_db = null;

// --- PROCESAR POST (GUARDAR / EDITAR / ELIMINAR) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($pdo)) {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'eliminar') {
        $id_eliminar = $_POST['id_servicio'] ?? null;
        if ($id_eliminar) {
            try {
                $stmt = $pdo->prepare("DELETE FROM servicios WHERE id_servicio = ?");
                $stmt->execute([$id_eliminar]);
                $mensaje = "Registro eliminado correctamente del catálogo.";
            } catch (PDOException $e) {
                $error_db = "No se puede eliminar: " . $e->getMessage();
            }
        }
    } 
    else {
        $id_servicio = !empty($_POST['id_servicio']) ? $_POST['id_servicio'] : null;
        $nombre = trim($_POST['nombre_servicio'] ?? '');
        $descripcion = trim($_POST['descripcion_servicio'] ?? '');
        $precio = $_POST['precio_servicio'] ?? 0;
        $tipo_registro = $_POST['tipo_registro'] ?? 'paquete'; 
        $id_categoria = ($tipo_registro === 'paquete') ? 1 : 2;
        $categoria = $_POST['categoria'] ?? 'infantil';
        $ubicacion = $_POST['ubicacion'] ?? 'jardin';
        $es_por_persona = isset($_POST['es_por_persona']) ? 1 : 0;
        $disponible = isset($_POST['disponible_servicio']) ? 1 : 0;
        $foto = trim($_POST['foto_servicio'] ?? '');
        if (empty($foto)) $foto = 'default.png';

        // Intento 1: Actualizar incluyendo 'ubicacion', 'tipo_registro' e 'id_categoria'
        try {
            if ($id_servicio) {
                $stmt = $pdo->prepare("UPDATE servicios 
                    SET nombre_servicio = ?, descripcion_servicio = ?, precio_servicio = ?, 
                        es_por_persona = ?, foto_servicio = ?, disponible_servicio = ?, 
                        categoria = ?, ubicacion = ?, tipo_registro = ?, id_categoria = ?
                    WHERE id_servicio = ?");
                $stmt->execute([$nombre, $descripcion, $precio, $es_por_persona, $foto, $disponible, $categoria, $ubicacion, $tipo_registro, $id_categoria, $id_servicio]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO servicios 
                    (nombre_servicio, descripcion_servicio, precio_servicio, es_por_persona, foto_servicio, disponible_servicio, categoria, ubicacion, tipo_registro, id_categoria) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nombre, $descripcion, $precio, $es_por_persona, $foto, $disponible, $categoria, $ubicacion, $tipo_registro, $id_categoria]);
            }
            $mensaje = "¡Registro guardado exitosamente como " . strtoupper($tipo_registro) . "!";
        } catch (PDOException $e) {
            // Intento 2 (Fallback si 'ubicacion' aún no existe en la BD)
            try {
                if ($id_servicio) {
                    $stmt = $pdo->prepare("UPDATE servicios 
                        SET nombre_servicio = ?, descripcion_servicio = ?, precio_servicio = ?, 
                            es_por_persona = ?, foto_servicio = ?, disponible_servicio = ?, 
                            categoria = ?, tipo_registro = ?, id_categoria = ?
                        WHERE id_servicio = ?");
                    $stmt->execute([$nombre, $descripcion, $precio, $es_por_persona, $foto, $disponible, $categoria, $tipo_registro, $id_categoria, $id_servicio]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO servicios 
                        (nombre_servicio, descripcion_servicio, precio_servicio, es_por_persona, foto_servicio, disponible_servicio, categoria, tipo_registro, id_categoria) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$nombre, $descripcion, $precio, $es_por_persona, $foto, $disponible, $categoria, $tipo_registro, $id_categoria]);
                }
                $mensaje = "¡Registro guardado exitosamente como " . strtoupper($tipo_registro) . "!";
            } catch (PDOException $ex) {
                $error_db = "Error al guardar en la base de datos: " . $ex->getMessage();
            }
        }
    }
}

// --- CONSULTAR REGISTROS ---
$servicios = [];
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT * FROM servicios ORDER BY id_servicio DESC");
        $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_db = "Error al consultar la base de datos: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Catálogo - Admin Fantasy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { min-height: 100vh; background-color: #e2e8f0; font-family: system-ui, -apple-system, sans-serif; }
        .card-custom { background: #ffffff; border-radius: 12px; border: 2px solid #cbd5e1; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .form-label { font-size: 0.95rem; font-weight: 700; color: #1e293b; margin-bottom: 0.4rem; }
        .form-control, .form-select { border-radius: 8px; border: 1.5px solid #94a3b8; padding: 0.65rem 0.875rem; color: #0f172a; font-weight: 600; }
        .table-responsive { border-radius: 8px; overflow: hidden; }
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
                <h2 class="h3 mb-0"><i class="fa-solid fa-layer-group text-primary me-2"></i>Gestión de Catálogo</h2>
                <button class="btn btn-primary fw-bold px-3 py-2" onclick="abrirModalNuevo()">
                    <i class="fa-solid fa-plus me-1"></i> Agregar Nuevo Registro
                </button>
            </div>

            <?php if ($mensaje): ?>
                <div class="alert alert-success alert-dismissible fade show fw-bold">
                    <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($mensaje) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error_db): ?>
                <div class="alert alert-danger fw-bold">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($error_db) ?>
                </div>
            <?php endif; ?>

            <div class="card-custom p-0 overflow-hidden">
                <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                    <h4 class="fw-bold m-0 text-dark"><i class="fa-solid fa-list text-primary me-2"></i>Elementos del Catálogo</h4>
                    <span class="badge bg-dark fs-6"><?= count($servicios) ?> Registros</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="ps-3">Tipo Registro</th>
                                <th>Tipo Evento</th>
                                <th>Ubicación</th>
                                <th>Nombre</th>
                                <th>Precio</th>
                                <th>Modalidad</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($servicios)): ?>
                                <tr><td colspan="8" class="text-center py-4 text-muted fw-bold">No hay registros en el catálogo.</td></tr>
                            <?php else: ?>
                                <?php foreach ($servicios as $s): ?>
                                    <?php 
                                        $cat = strtolower($s['categoria'] ?? 'infantil'); 
                                        $ubi = strtolower($s['ubicacion'] ?? 'jardin');
                                        $tipo = strtolower($s['tipo_registro'] ?? 'servicio_extra');
                                    ?>
                                    <tr>
                                        <td class="ps-3">
                                            <?php if ($tipo === 'paquete'): ?>
                                                <span class="badge bg-primary text-white"><i class="fa-solid fa-box me-1"></i> Paquete</span>
                                            <?php else: ?>
                                                <span class="badge bg-info text-dark"><i class="fa-solid fa-puzzle-piece me-1"></i> Servicio Extra</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($cat === 'social'): ?>
                                                <span class="badge bg-dark text-white"><i class="fa-solid fa-glass-cheers me-1"></i> Social</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark"><i class="fa-solid fa-child me-1"></i> Infantil</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (strpos($ubi, 'carmelo') !== false): ?>
                                                <span class="badge bg-secondary text-white"><i class="fa-solid fa-building me-1"></i> Carmelo</span>
                                            <?php else: ?>
                                                <span class="badge bg-success text-white"><i class="fa-solid fa-tree me-1"></i> Jardín</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong class="text-dark fs-6"><?= htmlspecialchars($s['nombre_servicio']) ?></strong>
                                            <?php if (!empty($s['descripcion_servicio'])): ?>
                                                <br><small class="text-muted"><?= htmlspecialchars(substr($s['descripcion_servicio'], 0, 50)) . (strlen($s['descripcion_servicio']) > 50 ? '...' : '') ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-bold text-success fs-6">$<?= number_format((float)($s['precio_servicio'] ?? 0), 2) ?></td>
                                        <td>
                                            <?php if (!empty($s['es_por_persona'])): ?>
                                                <span class="badge bg-light text-dark border"><i class="fa-solid fa-user me-1"></i> Por Persona</span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-dark border"><i class="fa-solid fa-tag me-1"></i> Precio Fijo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= !empty($s['disponible_servicio']) ? 'bg-success' : 'bg-danger' ?>">
                                                <?= !empty($s['disponible_servicio']) ? 'Activo' : 'Inactivo' ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button class="btn btn-warning btn-sm fw-bold me-1" onclick='editarServicio(<?= json_encode($s) ?>)'>
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Seguro que deseas eliminar este elemento?');">
                                                    <input type="hidden" name="accion" value="eliminar">
                                                    <input type="hidden" name="id_servicio" value="<?= $s['id_servicio'] ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm fw-bold">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
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

<!-- MODAL -->
<div class="modal fade" id="modalCatalogo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" id="modalTitulo"><i class="fa-solid fa-plus text-primary me-2"></i>Agregar Registro</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="catalogo.php" method="POST" id="form-servicio">
                <div class="modal-body">
                    <input type="hidden" name="id_servicio" id="id_servicio">
                    
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Tipo de Registro:</label>
                            <select name="tipo_registro" id="tipo_registro" class="form-select" required>
                                <option value="paquete">Paquete</option>
                                <option value="servicio_extra">Servicio Extra</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Tipo de Evento:</label>
                            <select name="categoria" id="categoria" class="form-select" required>
                                <option value="infantil">Infantil</option>
                                <option value="social">Social</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Ubicación / Salón:</label>
                            <select name="ubicacion" id="ubicacion" class="form-select" required>
                                <option value="jardin">Jardín</option>
                                <option value="carmelo">Carmelo</option>
                            </select>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Nombre del Paquete o Servicio:</label>
                            <input type="text" name="nombre_servicio" id="nombre_servicio" class="form-control" placeholder="Ej: Carrito de Snacks, Paquete Básico Infantil..." required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Precio ($):</label>
                            <input type="number" step="0.01" name="precio_servicio" id="precio_servicio" class="form-control" placeholder="0.00" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Descripción Detallada:</label>
                            <textarea name="descripcion_servicio" id="descripcion_servicio" class="form-control" rows="3" placeholder="Descripción breve de lo que incluye..."></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Nombre de Imagen (Opcional):</label>
                            <input type="text" name="foto_servicio" id="foto_servicio" class="form-control" placeholder="default.png">
                        </div>

                        <div class="col-md-6 d-flex align-items-center mt-4">
                            <div class="form-check form-switch fs-6">
                                <input class="form-check-input" type="checkbox" name="es_por_persona" id="es_por_persona" value="1">
                                <label class="form-check-label fw-bold text-dark" for="es_por_persona">¿El costo es POR PERSONA?</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch fs-6">
                                <input class="form-check-input" type="checkbox" name="disponible_servicio" id="disponible_servicio" value="1" checked>
                                <label class="form-check-label fw-bold text-dark" for="disponible_servicio">Disponible en el sistema</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-bold"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar Registro</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
let modalInstancia = null;

document.addEventListener('DOMContentLoaded', function() {
    modalInstancia = new bootstrap.Modal(document.getElementById('modalCatalogo'));
});

function abrirModalNuevo() {
    document.getElementById('modalTitulo').innerHTML = '<i class="fa-solid fa-plus text-primary me-2"></i>Agregar Registro al Catálogo';
    document.getElementById('form-servicio').reset();
    document.getElementById('id_servicio').value = '';
    modalInstancia.show();
}

function editarServicio(s) {
    document.getElementById('modalTitulo').innerHTML = '<i class="fa-solid fa-pen-to-square text-primary me-2"></i>Editar Registro #' + s.id_servicio;
    document.getElementById('id_servicio').value = s.id_servicio || '';
    document.getElementById('nombre_servicio').value = s.nombre_servicio || '';
    document.getElementById('precio_servicio').value = s.precio_servicio || '';
    document.getElementById('descripcion_servicio').value = s.descripcion_servicio || '';
    document.getElementById('foto_servicio').value = s.foto_servicio || 'default.png';
    document.getElementById('es_por_persona').checked = (s.es_por_persona == 1);
    document.getElementById('disponible_servicio').checked = (s.disponible_servicio == 1);
    
    let tipo = (s.tipo_registro || '').toLowerCase();
    if (tipo === 'paquete' || (s.nombre_servicio && s.nombre_servicio.toLowerCase().includes('paquete'))) {
        document.getElementById('tipo_registro').value = 'paquete';
    } else {
        document.getElementById('tipo_registro').value = 'servicio_extra';
    }

    let cat = (s.categoria || 'infantil').toLowerCase();
    document.getElementById('categoria').value = cat.includes('social') ? 'social' : 'infantil';

    let ubi = (s.ubicacion || 'jardin').toLowerCase();
    document.getElementById('ubicacion').value = ubi.includes('carmelo') ? 'carmelo' : 'jardin';

    modalInstancia.show();
}
</script>

</body>
</html>
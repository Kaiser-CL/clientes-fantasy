<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

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

// --- PROCESAR POST (GUARDAR / EDITAR / ELIMINAR SERVICIO) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($pdo)) {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'eliminar') {
        $id_eliminar = $_POST['id_servicio'] ?? null;
        if ($id_eliminar) {
            try {
                $stmt = $pdo->prepare("DELETE FROM servicios WHERE id_servicio = ?");
                $stmt->execute([$id_eliminar]);
                $_SESSION['mensaje_exito'] = "Registro eliminado correctamente del catálogo.";
                header("Location: catalogo.php");
                exit;
            } catch (PDOException $e) {
                $_SESSION['error_db'] = "No se puede eliminar: " . $e->getMessage();
                header("Location: catalogo.php");
                exit;
            }
        }
    } 
    else {
        $id_servicio = !empty($_POST['id_servicio']) ? $_POST['id_servicio'] : null;
        $nombre = trim($_POST['nombre_servicio'] ?? '');
        $descripcion = trim($_POST['descripcion_servicio'] ?? '');
        $precio = $_POST['precio_servicio'] ?? 0;
        $tipo_registro = $_POST['tipo_registro'] ?? 'paquete'; 
        $categoria = $_POST['categoria'] ?? 'infantil';
        $id_categoria = ($tipo_registro === 'servicio_extra') ? 3 : (($categoria === 'infantil') ? 2 : 1);
        $ubicacion = $_POST['ubicacion'] ?? 'jardin';
        $es_por_persona = isset($_POST['es_por_persona']) ? 1 : 0;
        $disponible = isset($_POST['disponible_servicio']) ? 1 : 0;
        $foto = trim($_POST['foto_servicio'] ?? '');
        if (empty($foto)) $foto = 'default.png';

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

            $_SESSION['mensaje_exito'] = "¡Registro guardado exitosamente como " . strtoupper($tipo_registro) . "!";
            header("Location: catalogo.php");
            exit;
        } catch (PDOException $e) {
            $_SESSION['error_db'] = "Error al guardar en la base de datos: " . $e->getMessage();
            header("Location: catalogo.php");
            exit;
        }
    }
}

// CONSULTAR CATÁLOGO Y TOTAL DE MULTIMEDIA
$servicios_lista = [];
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT s.*, 
            (SELECT COUNT(*) FROM servicio_galeria g WHERE g.id_servicio = s.id_servicio) as total_fotos 
            FROM servicios s ORDER BY s.id_servicio DESC");
        $servicios_lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $servicios_lista = [];
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
        .dropzone-area { border: 2.5px dashed #3b82f6; background-color: #eff6ff; border-radius: 10px; padding: 2rem; text-align: center; cursor: pointer; transition: all 0.2s ease; }
        .dropzone-area:hover, .dropzone-area.dragover { background-color: #dbeafe; border-color: #1d4ed8; }
        .preview-thumb { width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #cbd5e1; }
        .thumb-container { position: relative; display: inline-block; margin: 5px; }
        .btn-delete-thumb { position: absolute; top: -5px; right: -5px; background: #ef4444; color: white; border: none; border-radius: 50%; width: 22px; height: 22px; font-size: 12px; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <?php 
        $sidebar_path = __DIR__ . '/includes/sidebar.php';
        if (file_exists($sidebar_path)) { include $sidebar_path; }
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
                    <span class="badge bg-dark fs-6"><?= count($servicios_lista) ?> Registros</span>
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
                                <th>Multimedia</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($servicios_lista)): ?>
                                <tr><td colspan="8" class="text-center py-4 text-muted fw-bold">No hay registros en el catálogo.</td></tr>
                            <?php else: ?>
                                <?php foreach ($servicios_lista as $s): ?>
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
                                        </td>
                                        <td class="fw-bold text-success fs-6">$<?= number_format((float)($s['precio_servicio'] ?? 0), 2) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-dark fw-bold" onclick="abrirModalGaleria(<?= $s['id_servicio'] ?>, '<?= htmlspecialchars($s['nombre_servicio'], ENT_QUOTES) ?>', '<?= $tipo ?>')">
                                                <i class="fa-solid fa-images text-warning me-1"></i> <?= $s['total_fotos'] ?> fotos/vid
                                            </button>
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

<!-- MODAL GUARDAR / EDITAR SERVICIO -->
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

<!-- MODAL GESTIÓN DE GALERÍA MULTIMEDIA -->
<div class="modal fade" id="modalGaleria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" id="titulo_modal_galeria"><i class="fa-solid fa-cloud-arrow-up me-2"></i>Galería de Fotos y Videos</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="galeria_id_servicio">
                <input type="hidden" id="galeria_tipo_registro">

                <!-- DROPZONE DRAG & DROP -->
                <div class="dropzone-area mb-3" id="dropzone_target" onclick="document.getElementById('input_archivos_hidden').click()">
                    <i class="fa-solid fa-cloud-arrow-up fa-3x text-primary mb-2"></i>
                    <h5 class="fw-bold text-dark mb-1">Arrastra tus fotos o videos aquí</h5>
                    <p class="text-muted small mb-0">o haz clic para seleccionar archivos desde tu equipo</p>
                    <input type="file" id="input_archivos_hidden" multiple accept="image/*,video/*" class="d-none" onchange="subirArchivosGaleria(this.files)">
                </div>

                <div class="alert alert-warning py-2 px-3 small fw-bold mb-3">
                    <i class="fa-solid fa-circle-info me-1"></i> Recomendación de Servidor:
                    Para mantener el servidor óptimo, sube fotos comprimidas y videos cortos en .mp4.
                </div>

                <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-images me-1"></i> Archivos cargados actualmente:</h6>
                <div id="contenedor_galeria_existente" class="d-flex flex-wrap bg-light p-3 rounded border" style="min-height: 120px;">
                    <span class="text-muted small fw-bold m-auto" id="msg_galeria_vacia">Cargando elementos...</span>
                </div>

                <div id="status_upload_galeria" class="alert py-2 mt-3 d-none fw-bold small"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
let modalInstancia = null;
let bsModalGaleria = null;

document.addEventListener('DOMContentLoaded', function() {
    modalInstancia = new bootstrap.Modal(document.getElementById('modalCatalogo'));
    let modalGaleriaElement = document.getElementById('modalGaleria');
    bsModalGaleria = new bootstrap.Modal(modalGaleriaElement);

    // RECARGAR LA PÁGINA AL CERRAR EL MODAL PARA ACTUALIZAR LOS CONTADORES
    modalGaleriaElement.addEventListener('hidden.bs.modal', function () {
        location.reload();
    });

    let dropzone = document.getElementById('dropzone_target');
    ['dragenter', 'dragover'].forEach(eName => {
        dropzone.addEventListener(eName, (e) => { e.preventDefault(); dropzone.classList.add('dragover'); }, false);
    });
    ['dragleave', 'drop'].forEach(eName => {
        dropzone.addEventListener(eName, (e) => { e.preventDefault(); dropzone.classList.remove('dragover'); }, false);
    });
    dropzone.addEventListener('drop', (e) => {
        subirArchivosGaleria(e.dataTransfer.files);
    });
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
    document.getElementById('es_por_persona').checked = (s.es_por_persona == 1);
    document.getElementById('disponible_servicio').checked = (s.disponible_servicio == 1);
    
    let tipo = (s.tipo_registro || '').toLowerCase();
    document.getElementById('tipo_registro').value = (tipo === 'paquete') ? 'paquete' : 'servicio_extra';

    let cat = (s.categoria || 'infantil').toLowerCase();
    document.getElementById('categoria').value = cat.includes('social') ? 'social' : 'infantil';

    let ubi = (s.ubicacion || 'jardin').toLowerCase();
    document.getElementById('ubicacion').value = ubi.includes('carmelo') ? 'carmelo' : 'jardin';

    modalInstancia.show();
}

function abrirModalGaleria(idServicio, nombreServicio, tipoRegistro) {
    document.getElementById('galeria_id_servicio').value = idServicio;
    let tipo = (tipoRegistro === 'paquete') ? 'paquetes' : 'extras';
    document.getElementById('galeria_tipo_registro').value = tipo;

    document.getElementById('titulo_modal_galeria').innerText = "Multimedia: " + nombreServicio;
    cargarGaleriaServicio(idServicio, tipo);
    bsModalGaleria.show();
}

function cargarGaleriaServicio(idServicio, tipo) {
    let contenedor = document.getElementById('contenedor_galeria_existente');
    contenedor.innerHTML = '<span class="text-muted small fw-bold m-auto">Cargando elementos...</span>';

    if (!tipo) {
        tipo = document.getElementById('galeria_tipo_registro').value || 'paquetes';
    }

    fetch(`../api/obtener_galeria.php?tipo=${tipo}&id=${idServicio}`)
    .then(r => r.json())
    .then(data => {
        contenedor.innerHTML = '';

        if (data.status === 'error' || data.error) {
            contenedor.innerHTML = `<span class="text-danger small fw-bold m-auto">${data.message || data.error}</span>`;
            return;
        }

        const lista = Array.isArray(data) ? data : (data.data || []);

        if (lista.length === 0) {
            contenedor.innerHTML = '<span class="text-muted small fw-bold m-auto">No hay archivos guardados en esta carpeta todavía.</span>';
            return;
        }

        lista.forEach(item => {
            let div = document.createElement('div');
            div.className = 'thumb-container';

            let idGaleria = item.id_galeria || item.id;
            
            // PRIORIZAMOS url_completa, Y SI NO EXISTE ARMAMOS LA RUTA RELATIVA DIRECTA
            let srcFinal = item.url_completa ? item.url_completa : `../${item.ruta_archivo}`;

            let mediaHTML = (item.tipo_archivo === 'video')
                ? `<video class="preview-thumb" src="${srcFinal}" muted controls></video>`
                : `<img src="${srcFinal}" class="preview-thumb">`;

            div.innerHTML = `
                ${mediaHTML}
                <button type="button" class="btn-delete-thumb" onclick="eliminarFotoGaleria(${idGaleria})">&times;</button>
            `;
            contenedor.appendChild(div);
        });
    })
    .catch(err => {
        contenedor.innerHTML = '<span class="text-danger small fw-bold m-auto">Error de comunicación con el servidor.</span>';
    });
}

function subirArchivosGaleria(files) {
    if (!files || files.length === 0) return;

    let idServicio = document.getElementById('galeria_id_servicio').value;
    let tipo = document.getElementById('galeria_tipo_registro').value || 'paquetes';

    let formData = new FormData();
    formData.append('tipo', tipo);
    formData.append('id', idServicio);

    for (let i = 0; i < files.length; i++) {
        formData.append('archivos[]', files[i]);
    }

    let statusBox = document.getElementById('status_upload_galeria');
    statusBox.className = 'alert alert-info py-2 fw-bold small';
    statusBox.innerText = 'Subiendo y registrando archivos en el servidor...';
    statusBox.classList.remove('d-none');

    fetch('../api/subir_galeria.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success' || data.exito) {
            statusBox.className = 'alert alert-success py-2 fw-bold small';
            statusBox.innerText = '✓ ' + (data.message || 'Archivos subidos con éxito.');
            cargarGaleriaServicio(idServicio, tipo);
            setTimeout(() => { statusBox.classList.add('d-none'); }, 2000);
        } else {
            statusBox.className = 'alert alert-danger py-2 fw-bold small';
            statusBox.innerText = 'Error: ' + (data.message || 'No se pudieron subir los archivos.');
        }
    })
    .catch(err => {
        statusBox.className = 'alert alert-danger py-2 fw-bold small';
        statusBox.innerText = 'Error de comunicación con el servidor.';
    });
}

function eliminarFotoGaleria(idGaleria) {
    if (confirm('¿Deseas borrar permanentemente este archivo del servidor?')) {
        let formData = new FormData();
        formData.append('accion', 'eliminar_foto');
        formData.append('id_galeria', idGaleria);

        fetch('../api/subir_galeria.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success' || data.exito) {
                let idServicio = document.getElementById('galeria_id_servicio').value;
                let tipo = document.getElementById('galeria_tipo_registro').value;
                cargarGaleriaServicio(idServicio, tipo);
            } else {
                alert('Error al eliminar: ' + (data.message || 'No se pudo procesar.'));
            }
        })
        .catch(err => {
            alert('Error de red al intentar eliminar la imagen.');
        });
    }
}
</script>

</body>
</html>

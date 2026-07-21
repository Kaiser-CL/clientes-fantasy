<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

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

// --- PROCESAR OPERACIONES POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($pdo)) {
    try {
        // 1. AGREGAR SERVICIO EXTRA A EVENTO
        if (isset($_POST['accion']) && $_POST['accion'] === 'agregar_servicio') {
            $id_evento = $_POST['id_evento'] ?? null;
            $id_servicio = $_POST['id_servicio_extra'] ?? null;

            if ($id_evento && $id_servicio) {
                // Obtener el precio del servicio del catálogo para llenar subtotales obligatorios
                $stmt_precio = $pdo->prepare("SELECT precio_servicio FROM servicios WHERE id_servicio = ? LIMIT 1");
                $stmt_precio->execute([$id_servicio]);
                $precio_unitario = (float)($stmt_precio->fetchColumn() ?: 0.00);

                // Intentar insertar con el campo subtotal obligatorio
                try {
                    $stmt_add = $pdo->prepare("INSERT INTO evento_servicio (id_evento, id_servicio, subtotal_servicio_evento) VALUES (?, ?, ?)");
                    $stmt_add->execute([$id_evento, $id_servicio, $precio_unitario]);
                } catch (PDOException $e_sub) {
                    // Si la columna tiene un nombre ligeramente distinto o no existe:
                    try {
                        $stmt_add = $pdo->prepare("INSERT INTO evento_servicio (id_evento, id_servicio, cantidad, subtotal) VALUES (?, ?, 1, ?)");
                        $stmt_add->execute([$id_evento, $id_servicio, $precio_unitario]);
                    } catch (PDOException $e_sub2) {
                        $stmt_add = $pdo->prepare("INSERT INTO evento_servicio (id_evento, id_servicio) VALUES (?, ?)");
                        $stmt_add->execute([$id_evento, $id_servicio]);
                    }
                }
                $mensaje = "Servicio extra agregado correctamente al evento.";
            }
        }

        // 2. ELIMINAR SERVICIO EXTRA DE EVENTO
        if (isset($_POST['accion']) && $_POST['accion'] === 'eliminar_servicio') {
            $id_evento_servicio = $_POST['id_evento_servicio'] ?? null;
            if ($id_evento_servicio) {
                $stmt_del = $pdo->prepare("DELETE FROM evento_servicio WHERE id_evento_servicio = ?");
                $stmt_del->execute([$id_evento_servicio]);
                $mensaje = "Servicio eliminado del evento.";
            }
        }

        // 3. ACTUALIZAR ESTADO DEL EVENTO
        if (isset($_POST['accion']) && $_POST['accion'] === 'actualizar_estado') {
            $id_evento = $_POST['id_evento'] ?? null;
            $nuevo_estado = $_POST['estado_evento'] ?? 'confirmado';

            if ($id_evento) {
                $stmt_est = $pdo->prepare("UPDATE eventos SET estado = ? WHERE id_evento = ?");
                $stmt_est->execute([$nuevo_estado, $id_evento]);
                $mensaje = "Estado del evento actualizado correctamente.";
            }
        }
    } catch (PDOException $e) {
        $error_db = "Error al actualizar evento: " . $e->getMessage();
    }
}

// --- CONSULTAR EVENTOS CON INFORMACIÓN DEL CLIENTE Y SERVICIOS ---
$eventos_lista = [];
$servicios_catalogo = [];

if (isset($pdo)) {
    try {
        // Consulta principal de eventos relacionando con usuarios
        $sql_eventos = "SELECT 
                            e.id_evento,
                            e.nombre_evento,
                            e.fecha_evento,
                            e.hora_evento,
                            e.ubicacion,
                            e.estado,
                            u.nombre_usuario,
                            u.apellidos_usuario,
                            u.telefono_usuario,
                            u.correo_usuario
                        FROM eventos e
                        LEFT JOIN usuarios u ON e.id_cliente = u.id_usuario
                        ORDER BY e.fecha_evento DESC, e.hora_evento DESC";
        $stmt_e = $pdo->query($sql_eventos);
        $eventos_raw = $stmt_e->fetchAll(PDO::FETCH_ASSOC);

        // Para cada evento, consultar sus servicios asignados
        foreach ($eventos_raw as $evt) {
            $id_evt = $evt['id_evento'];
            
            $sql_servs = "SELECT 
                            es.id_evento_servicio,
                            s.id_servicio,
                            s.nombre_servicio,
                            s.precio_servicio,
                            s.tipo_registro
                          FROM evento_servicio es
                          INNER JOIN servicios s ON es.id_servicio = s.id_servicio
                          WHERE es.id_evento = ?";
            $stmt_s = $pdo->prepare($sql_servs);
            $stmt_s->execute([$id_evt]);
            $evt['servicios_asociados'] = $stmt_s->fetchAll(PDO::FETCH_ASSOC);

            $eventos_lista[] = $evt;
        }

    } catch (PDOException $e) {
        $error_db = "Error al obtener eventos: " . $e->getMessage();
    }

    // Consultar catálogo completo de servicios para llenar el select del modal
    try {
        $stmt_cat = $pdo->query("SELECT id_servicio, nombre_servicio, precio_servicio, tipo_registro FROM servicios WHERE disponible_servicio = 1 ORDER BY nombre_servicio ASC");
        $servicios_catalogo = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $servicios_catalogo = [];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Eventos | Admin Fantasy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { min-height: 100vh; background-color: #e2e8f0; font-family: system-ui, -apple-system, sans-serif; }
        .card-custom { background: #ffffff; border-radius: 12px; border: 2px solid #cbd5e1; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .badge-estado { font-size: 0.85rem; font-weight: 700; padding: 0.4em 0.8em; border-radius: 6px; }
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h3 mb-0 fw-bold text-dark"><i class="fa-solid fa-calendar-check text-primary me-2"></i>Historial de Eventos</h2>
                <a href="generar_evento.php" class="btn btn-primary fw-bold"><i class="fa-solid fa-plus me-1"></i> Nuevo Evento</a>
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
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Folio / Evento</th>
                                <th>Cliente</th>
                                <th>Fecha y Hora</th>
                                <th>Salón / Ubicación</th>
                                <th>Estado</th>
                                <th class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($eventos_lista)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted fw-bold">No hay eventos registrados en la base de datos.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($eventos_lista as $evt): ?>
                                    <tr>
                                        <td>
                                            <span class="fw-bold text-primary">#EV-<?= str_pad($evt['id_evento'], 5, '0', STR_PAD_LEFT) ?></span>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($evt['nombre_evento']) ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars(trim(($evt['nombre_usuario'] ?? '') . ' ' . ($evt['apellidos_usuario'] ?? ''))) ?></div>
                                            <small class="text-muted"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($evt['telefono_usuario'] ?? 'N/A') ?></small>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><i class="fa-regular fa-calendar me-1"></i><?= htmlspecialchars($evt['fecha_evento']) ?></div>
                                            <small class="text-muted"><i class="fa-regular fa-clock me-1"></i><?= htmlspecialchars($evt['hora_evento']) ?></small>
                                        </td>
                                        <td class="fw-semibold text-capitalize"><?= htmlspecialchars($evt['ubicacion'] ?? 'General') ?></td>
                                        <td>
                                            <?php 
                                                $est = strtolower($evt['estado'] ?? 'pendiente');
                                                $class_badge = 'bg-secondary';
                                                if ($est === 'confirmado' || $est === 'activo') $class_badge = 'bg-success';
                                                elseif ($est === 'cancelado') $class_badge = 'bg-danger';
                                                elseif ($est === 'pendiente') $class_badge = 'bg-warning text-dark';
                                            ?>
                                            <span class="badge badge-estado <?= $class_badge ?>"><?= ucfirst($est) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-primary fw-bold" onclick='abrirModalDesglose(<?= json_encode($evt) ?>)'>
                                                <i class="fa-regular fa-eye me-1"></i> Ver / Editar
                                            </button>
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

<!-- MODAL DESGLOSE Y EDICIÓN DEL EVENTO -->
<div class="modal fade" id="modalDesglose" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" id="modal-titulo-evento">Desglose del Evento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                
                <!-- DATOS DEL CLIENTE Y UBICACIÓN -->
                <div class="row mb-3 bg-light p-3 rounded border">
                    <div class="col-md-6 mb-2">
                        <strong>Cliente:</strong> <span id="m-cliente-nombre">---</span>
                    </div>
                    <div class="col-md-6 mb-2 text-md-end">
                        <strong>Fecha:</strong> <span id="m-fecha">---</span>
                    </div>
                    <div class="col-md-6">
                        <strong>Teléfono:</strong> <span id="m-cliente-tel">---</span>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <strong>Categoría/Salón:</strong> <span id="m-salon">---</span>
                    </div>
                </div>

                <h6 class="fw-bold text-primary mb-2"><i class="fa-solid fa-list-check me-1"></i> Servicios Adicionales Contratados</h6>
                
                <!-- TABLA DE SERVICIOS ASIGNADOS -->
                <div class="table-responsive mb-3">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Servicio</th>
                                <th>Precio</th>
                                <th style="width: 100px;" class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="m-tabla-servicios-body">
                            <!-- Se renderiza por JS -->
                        </tbody>
                    </table>
                </div>

                <!-- FORMULARIO AGREGAR SERVICIO EXTRA AL EVENTO -->
                <form action="" method="POST" class="row g-2 align-items-center mb-4">
                    <input type="hidden" name="accion" value="agregar_servicio">
                    <input type="hidden" name="id_evento" id="m-input-id-evento-add">
                    
                    <div class="col-md-8">
                        <select name="id_servicio_extra" class="form-select" required>
                            <option value="">-- Agregar Servicio Adicional --</option>
                            <?php foreach ($servicios_catalogo as $sc): ?>
                                <option value="<?= $sc['id_servicio'] ?>">
                                    <?= htmlspecialchars($sc['nombre_servicio']) ?> ($<?= number_format((float)$sc['precio_servicio'], 2) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-success w-100 fw-bold">
                            <i class="fa-solid fa-plus me-1"></i> Agregar Extra
                        </button>
                    </div>
                </form>

                <hr>

                <!-- FORMULARIO CAMBIAR ESTADO -->
                <form action="" method="POST" class="row align-items-center">
                    <input type="hidden" name="accion" value="actualizar_estado">
                    <input type="hidden" name="id_evento" id="m-input-id-evento-est">

                    <div class="col-md-7 mb-2 mb-md-0">
                        <label class="form-label fw-bold mb-1">Estado del Evento:</label>
                        <select name="estado_evento" id="m-select-estado" class="form-select fw-bold">
                            <option value="confirmado">Activo / Confirmado</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>
                    <div class="col-md-5 text-md-end pt-md-4">
                        <button type="submit" class="btn btn-primary w-100 fw-bold">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Guardar Cambios
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
let modalDesgloseObj = null;

document.addEventListener('DOMContentLoaded', function() {
    modalDesgloseObj = new bootstrap.Modal(document.getElementById('modalDesglose'));
});

function abrirModalDesglose(evt) {
    document.getElementById('modal-titulo-evento').innerText = 'Desglose del Evento #EV-' + String(evt.id_evento).padStart(5, '0');
    document.getElementById('m-cliente-nombre').innerText = (evt.nombre_usuario || '') + ' ' + (evt.apellidos_usuario || '');
    document.getElementById('m-cliente-tel').innerText = evt.telefono_usuario || 'Sin Teléfono';
    document.getElementById('m-fecha').innerText = evt.fecha_evento + ' ' + evt.hora_evento;
    document.getElementById('m-salon').innerText = evt.ubicacion || 'General';

    document.getElementById('m-input-id-evento-add').value = evt.id_evento;
    document.getElementById('m-input-id-evento-est').value = evt.id_evento;
    document.getElementById('m-select-estado').value = evt.estado || 'confirmado';

    let tbody = document.getElementById('m-tabla-servicios-body');
    tbody.innerHTML = '';

    if (!evt.servicios_asociados || evt.servicios_asociados.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted fw-bold">No hay servicios adicionales agregados a este evento.</td></tr>';
    } else {
        evt.servicios_asociados.forEach(s => {
            let precio = parseFloat(s.precio_servicio) || 0;
            let tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="fw-bold text-dark">${s.nombre_servicio}</td>
                <td>$${precio.toFixed(2)}</td>
                <td class="text-center">
                    <form action="" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar este servicio del evento?');">
                        <input type="hidden" name="accion" value="eliminar_servicio">
                        <input type="hidden" name="id_evento_servicio" value="${s.id_evento_servicio}">
                        <button type="submit" class="btn btn-danger btn-sm py-0 px-2 fw-bold">&times;</button>
                    </form>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    modalDesgloseObj.show();
}
</script>

</body>
</html>
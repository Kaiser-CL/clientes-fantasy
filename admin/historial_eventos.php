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

// --- PROCESAR CAMBIOS DE ESTADO O SERVICIOS EN EL MODAL ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($pdo)) {
    if (isset($_POST['accion_evento']) && $_POST['accion_evento'] === 'guardar_cambios') {
        try {
            $id_evento = intval($_POST['id_evento']);
            $nuevo_estado = $_POST['estado_evento'] ?? 'confirmado';
            $num_personas = intval($_POST['num_personas_modal'] ?? 50);

            // Actualizar estado y número de personas en el evento
            try {
                $stmt_upd = $pdo->prepare("UPDATE eventos SET estado = ?, num_personas = ? WHERE id_evento = ?");
                $stmt_upd->execute([$nuevo_estado, $num_personas, $id_evento]);
            } catch (PDOException $e_col) {
                $stmt_upd = $pdo->prepare("UPDATE eventos SET estado = ? WHERE id_evento = ?");
                $stmt_upd->execute([$nuevo_estado, $id_evento]);
            }

            // Agregar un nuevo servicio extra si se envió desde la modal
            if (!empty($_POST['nuevo_id_servicio'])) {
                $id_serv = intval($_POST['nuevo_id_servicio']);
                $stmt_add_s = $pdo->prepare("INSERT INTO evento_servicio (id_evento, id_servicio) VALUES (?, ?)");
                $stmt_add_s->execute([$id_evento, $id_serv]);
            }

            // Eliminar un servicio si se solicitó
            if (!empty($_POST['eliminar_id_evento_servicio'])) {
                $id_es = intval($_POST['eliminar_id_evento_servicio']);
                $stmt_del_s = $pdo->prepare("DELETE FROM evento_servicio WHERE id_evento_servicio = ?");
                $stmt_del_s->execute([$id_es]);
            }

            $mensaje = "Evento #EV-" . str_pad($id_evento, 5, '0', STR_PAD_LEFT) . " actualizado correctamente.";
        } catch (Exception $e) {
            $error_db = "Error al actualizar evento: " . $e->getMessage();
        }
    }
}

// --- CONSULTAR LISTA DE EVENTOS Y CATÁLOGOS ---
$eventos_lista = [];
$servicios_catalogo = [];

if (isset($pdo)) {
    try {
        try {
            $sql_eventos = "SELECT 
                        e.id_evento,
                        e.nombre_evento,
                        e.fecha_evento,
                        e.hora_evento,
                        e.ubicacion,
                        e.num_personas,
                        e.estado,
                        u.nombre_usuario,
                        u.apellidos_usuario,
                        u.telefono_usuario,
                        u.email
                    FROM eventos e
                    LEFT JOIN usuarios u ON e.id_cliente = u.id_usuario
                    ORDER BY e.fecha_evento DESC, e.hora_evento DESC";
            $stmt_e = $pdo->query($sql_eventos);
            $eventos_raw = $stmt_e->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e_col) {
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
                        u.email
                    FROM eventos e
                    LEFT JOIN usuarios u ON e.id_cliente = u.id_usuario
                    ORDER BY e.fecha_evento DESC, e.hora_evento DESC";
            $stmt_e = $pdo->query($sql_eventos);
            $eventos_raw = $stmt_e->fetchAll(PDO::FETCH_ASSOC);
        }

        foreach ($eventos_raw as $evt) {
            $id_evt = $evt['id_evento'];
            
            $sql_servs = "SELECT 
                            es.id_evento_servicio,
                            s.id_servicio,
                            s.nombre_servicio,
                            s.precio_servicio,
                            s.tipo_registro,
                            s.es_por_persona,
                            s.tipo_cobro
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

    try {
        $stmt_cat = $pdo->query("SELECT id_servicio, nombre_servicio, precio_servicio, tipo_registro, es_por_persona, tipo_cobro FROM servicios WHERE disponible_servicio = 1 ORDER BY nombre_servicio ASC");
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
        .badge-estado { padding: 0.5em 0.8em; font-size: 0.85rem; border-radius: 6px; font-weight: 700; text-transform: uppercase; }
        .badge-confirmado { background-color: #10b981; color: white; }
        .badge-pendiente { background-color: #f59e0b; color: white; }
        .badge-cancelado { background-color: #ef4444; color: white; }
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
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h3 fw-bold text-dark"><i class="fa-solid fa-calendar-check text-primary me-2"></i>Historial de Eventos</h2>
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
                                <th>Salón</th>
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
                                <?php foreach ($eventos_lista as $evt): 
                                    $folio = "#EV-" . str_pad($evt['id_evento'], 5, '0', STR_PAD_LEFT);
                                    $cliente_nom = trim(($evt['nombre_usuario'] ?? '') . ' ' . ($evt['apellidos_usuario'] ?? ''));
                                    if (empty($cliente_nom)) $cliente_nom = 'Cliente General';
                                    $estado = strtolower($evt['estado'] ?? 'confirmado');
                                    $badge_cls = ($estado === 'cancelado') ? 'badge-cancelado' : (($estado === 'pendiente') ? 'badge-pendiente' : 'badge-confirmado');
                                ?>
                                    <tr>
                                        <td>
                                            <strong class="text-primary"><?= $folio ?></strong><br>
                                            <span class="fw-semibold text-dark"><?= htmlspecialchars($evt['nombre_evento']) ?></span>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-dark"><?= htmlspecialchars($cliente_nom) ?></span><br>
                                            <small class="text-muted"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($evt['telefono_usuario'] ?? 'Sin tel') ?></small>
                                        </td>
                                        <td>
                                            <i class="fa-solid fa-calendar me-1 text-secondary"></i><?= htmlspecialchars($evt['fecha_evento']) ?><br>
                                            <small class="text-muted"><i class="fa-solid fa-clock me-1"></i><?= htmlspecialchars($evt['hora_evento']) ?></small>
                                        </td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars(ucfirst($evt['ubicacion'] ?? 'Jardín')) ?></span></td>
                                        <td><span class="badge-estado <?= $badge_cls ?>"><?= ucfirst($estado) ?></span></td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-primary fw-bold" onclick='abrirModalDesglose(<?= json_encode($evt) ?>)'>
                                                <i class="fa-solid fa-eye me-1"></i> Ver / Editar
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

<!-- MODAL VER / EDITAR EVENTO CON CONTROL SLIDER DE INVITADOS -->
<div class="modal fade" id="modalDesgloseEvento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" id="modal_titulo_folio">Desglose del Evento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="accion_evento" value="guardar_cambios">
                    <input type="hidden" name="id_evento" id="modal_id_evento">
                    <input type="hidden" name="eliminar_id_evento_servicio" id="modal_eliminar_id_es">

                    <!-- INFO GENERAL Y CLIENTE -->
                    <div class="row g-2 mb-3 bg-light p-3 rounded border">
                        <div class="col-md-6">
                            <strong class="text-secondary small">Cliente:</strong> <span id="modal_cliente_nombre" class="fw-bold text-dark"></span><br>
                            <strong class="text-secondary small">Teléfono:</strong> <span id="modal_cliente_telefono"></span>
                        </div>
                        <div class="col-md-6">
                            <strong class="text-secondary small">Fecha:</strong> <span id="modal_evento_fecha"></span><br>
                            <strong class="text-secondary small">Salón:</strong> <span id="modal_evento_salon" class="fw-bold"></span>
                        </div>
                    </div>

                    <!-- SLIDER DE INVITADOS DENTRO DE LA MODAL -->
                    <div class="mb-3 bg-white p-3 rounded border">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="modal_num_personas_slider" class="form-label fw-bold mb-0">Número de Personas (Invitados):</label>
                            <span class="badge bg-primary fs-6 fw-bold" id="modal_label_num_personas">50 personas</span>
                        </div>
                        <input type="range" class="form-range" id="modal_num_personas_slider" name="num_personas_modal" min="0" max="300" step="5" value="50" oninput="actualizarPersonasModal(this.value)">
                    </div>

                    <!-- PAQUETE CONTRATADO -->
                    <div class="alert alert-primary py-2 mb-3">
                        <h6 class="fw-bold mb-1"><i class="fa-solid fa-box-open me-2"></i>Paquete Contratado:</h6>
                        <div id="modal_paquete_info" class="fs-6 fw-semibold">Cargando paquete...</div>
                    </div>

                    <!-- SERVICIOS EXTRAS -->
                    <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-puzzle-piece me-2"></i>Servicios Adicionales Contratados:</h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Servicio Extra</th>
                                    <th>Precio Unitario / Subtotal</th>
                                    <th class="text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="modal_tabla_extras_body">
                                <!-- Se llena dinámicamente -->
                            </tbody>
                        </table>
                    </div>

                    <!-- AGREGAR NUEVO EXTRA AL EVENTO -->
                    <div class="row g-2 align-items-center mb-3">
                        <div class="col-md-8">
                            <select name="nuevo_id_servicio" class="form-select border-success">
                                <option value="">-- Agregar Servicio Extra Adicional --</option>
                                <?php foreach ($servicios_catalogo as $serv): 
                                    if (strtolower($serv['tipo_registro'] ?? '') === 'paquete') continue;
                                    $es_pp = ($serv['es_por_persona'] == 1 || strtolower($serv['tipo_cobro'] ?? '') === 'por_persona') ? ' / persona' : '';
                                ?>
                                    <option value="<?= $serv['id_servicio'] ?>">
                                        <?= htmlspecialchars($serv['nombre_servicio']) ?> ($<?= number_format((float)$serv['precio_servicio'], 2) ?><?= $es_pp ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-success w-100 fw-bold"><i class="fa-solid fa-plus me-1"></i> Agregar Extra</button>
                        </div>
                    </div>

                    <!-- RESUMEN TOTAL DENTRO DEL MODAL -->
                    <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded border mb-3">
                        <h5 class="fw-bold mb-0">TOTAL RECALCULADO:</h5>
                        <h3 class="fw-bold text-primary mb-0" id="modal_total_recalculado">$0.00</h3>
                    </div>

                    <!-- ESTADO DEL EVENTO -->
                    <div class="mb-2">
                        <label class="form-label fw-bold">Estado del Evento:</label>
                        <select name="estado_evento" id="modal_estado_select" class="form-select">
                            <option value="confirmado">Activo / Confirmado</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary fw-bold"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
let modalEventoActual = null;
let bootstrapModalHistorial = null;

document.addEventListener('DOMContentLoaded', function() {
    bootstrapModalHistorial = new bootstrap.Modal(document.getElementById('modalDesgloseEvento'));
});

function abrirModalDesglose(evt) {
    modalEventoActual = evt;
    
    let folio = "#EV-" . str_pad(evt.id_evento, 5, '0', STR_PAD_LEFT);
    document.getElementById('modal_titulo_folio').innerText = "Desglose del Evento #" + evt.id_evento;
    document.getElementById('modal_id_evento').value = evt.id_evento;
    
    let clienteNom = (evt.nombre_usuario || '') + ' ' + (evt.apellidos_usuario || '');
    document.getElementById('modal_cliente_nombre').innerText = clienteNom.trim() || 'Cliente General';
    document.getElementById('modal_cliente_telefono').innerText = evt.telefono_usuario || 'Sin teléfono';
    document.getElementById('modal_evento_fecha').innerText = evt.fecha_evento + ' ' + evt.hora_evento;
    document.getElementById('modal_evento_salon').innerText = 'Salón ' + (evt.ubicacion || 'Jardín');
    document.getElementById('modal_estado_select').value = (evt.estado || 'confirmado').toLowerCase();

    // Sincronizar número de personas
    let numPers = parseInt(evt.num_personas) || 50;
    let slider = document.getElementById('modal_num_personas_slider');
    slider.value = numPers;
    
    actualizarPersonasModal(numPers);
    bootstrapModalHistorial.show();
}

function actualizarPersonasModal(numPers) {
    document.getElementById('modal_label_num_personas').innerText = numPers + ' personas';
    
    if (!modalEventoActual) return;

    let servicios = modalEventoActual.servicios_asociados || [];
    let paqueteFound = null;
    let extrasList = [];

    servicios.forEach(s => {
        let tipo = (s.tipo_registro || '').toLowerCase();
        let esPaq = tipo === 'paquete' || (s.nombre_servicio || '').toLowerCase().includes('paquete');
        if (esPaq && !paqueteFound) {
            paqueteFound = s;
        } else {
            extrasList.push(s);
        }
    });

    let totalCalculado = 0;

    // Renderizar información del Paquete
    let paqContainer = document.getElementById('modal_paquete_info');
    if (paqueteFound) {
        let precioU = parseFloat(paqueteFound.precio_servicio) || 0;
        let esPP = paqueteFound.es_por_persona == 1 || (paqueteFound.tipo_cobro || '').toLowerCase() === 'por_persona';
        let subtotalPaq = (esPP || paqueteFound.tipo_cobro !== 'fijo') ? (precioU * numPers) : precioU;
        totalCalculado += subtotalPaq;

        paqContainer.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <span>${paqueteFound.nombre_servicio} ($${precioU.toFixed(2)} / persona)</span>
                <span class="badge bg-primary fs-6">$${subtotalPaq.toFixed(2)}</span>
            </div>
        `;
    } else {
        paqContainer.innerHTML = '<span class="text-muted">Sin Paquete Registrado</span>';
    }

    // Renderizar Tabla de Extras
    let tbody = document.getElementById('modal_tabla_extras_body');
    tbody.innerHTML = '';

    if (extrasList.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted fw-bold">No hay servicios adicionales agregados a este evento.</td></tr>';
    } else {
        extrasList.forEach(ext => {
            let precioU = parseFloat(ext.precio_servicio) || 0;
            let esPP = ext.es_por_persona == 1 || (ext.tipo_cobro || '').toLowerCase() === 'por_persona';
            let subtotalExt = esPP ? (precioU * numPers) : precioU;
            totalCalculado += subtotalExt;

            let tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <strong class="text-dark">${ext.nombre_servicio}</strong>
                    ${esPP ? `<br><small class="text-muted">(${numPers} personas x $${precioU.toFixed(2)})</small>` : ''}
                </td>
                <td class="fw-bold text-primary">$${subtotalExt.toFixed(2)}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger fw-bold" onclick="eliminarExtraModal(${ext.id_evento_servicio})">
                        &times; Quitar
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    document.getElementById('modal_total_recalculado').innerText = '$' + totalCalculado.toFixed(2);
}

function eliminarExtraModal(idEventoServicio) {
    if (confirm('¿Deseas remover este servicio del evento?')) {
        document.getElementById('modal_eliminar_id_es').value = idEventoServicio;
        document.querySelector('#modalDesgloseEvento form').submit();
    }
}
</script>

</body>
</html>

<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'auth_check.php';
require_once __DIR__ . '/../db_config.php';

// --- ENDPOINT AJAX PARA APLICAR CAMBIOS Y ELIMINAR EXTRAS SIN CERRAR EL MODAL ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['es_ajax']) && $_POST['es_ajax'] == '1') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $id_evento = intval($_POST['id_evento']);
        $nuevo_estado = $_POST['estado_evento'] ?? 'confirmado';
        $num_personas = intval($_POST['num_personas_modal'] ?? 50);
        $id_paquete_nuevo = !empty($_POST['id_paquete_base_modal']) ? intval($_POST['id_paquete_base_modal']) : null;

        // A) Eliminar un extra individual vía AJAX
        if (!empty($_POST['eliminar_id_evento_servicio'])) {
            $id_es_del = intval($_POST['eliminar_id_evento_servicio']);
            $stmt_del_s = $pdo->prepare("DELETE FROM evento_servicio WHERE id_evento_servicio = ? AND id_evento = ?");
            $stmt_del_s->execute([$id_es_del, $id_evento]);
        }

        // B) Actualizar datos generales del evento
        $stmt_upd = $pdo->prepare("UPDATE eventos SET estado = ?, num_personas = ? WHERE id_evento = ?");
        $stmt_upd->execute([$nuevo_estado, $num_personas, $id_evento]);

        // C) Actualizar Paquete Base
        if ($id_paquete_nuevo) {
            $stmt_del_paq = $pdo->prepare("DELETE es FROM evento_servicio es INNER JOIN servicios s ON es.id_servicio = s.id_servicio WHERE es.id_evento = ? AND LOWER(s.tipo_registro) = 'paquete'");
            $stmt_del_paq->execute([$id_evento]);

            $stmt_p_info = $pdo->prepare("SELECT precio_servicio FROM servicios WHERE id_servicio = ?");
            $stmt_p_info->execute([$id_paquete_nuevo]);
            $p_info = $stmt_p_info->fetch(PDO::FETCH_ASSOC);

            if ($p_info) {
                $precio_paq = floatval($p_info['precio_servicio']);
                $subtotal_paq = $precio_paq * $num_personas;
                $stmt_ins_p = $pdo->prepare("INSERT INTO evento_servicio (id_evento, id_servicio, cantidad_servicio_evento, subtotal_servicio_evento) VALUES (?, ?, ?, ?)");
                $stmt_ins_p->execute([$id_evento, $id_paquete_nuevo, $num_personas, $subtotal_paq]);
            }
        }

        // D) Agregar nuevo servicio extra
        if (!empty($_POST['nuevo_id_servicio'])) {
            $id_serv = intval($_POST['nuevo_id_servicio']);
            $cant_input = intval($_POST['nueva_cantidad_servicio'] ?? 1);
            if ($cant_input < 1) $cant_input = 1;

            $stmt_s_info = $pdo->prepare("SELECT precio_servicio FROM servicios WHERE id_servicio = ?");
            $stmt_s_info->execute([$id_serv]);
            $s_info = $stmt_s_info->fetch(PDO::FETCH_ASSOC);

            if ($s_info) {
                $precio = floatval($s_info['precio_servicio']);
                $subtotal = $precio * $cant_input;
                $stmt_add_s = $pdo->prepare("INSERT INTO evento_servicio (id_evento, id_servicio, cantidad_servicio_evento, subtotal_servicio_evento) VALUES (?, ?, ?, ?)");
                $stmt_add_s->execute([$id_evento, $id_serv, $cant_input, $subtotal]);
            }
        }

        // E) Actualizar cantidades
        if (!empty($_POST['actualizar_cantidades']) && is_array($_POST['actualizar_cantidades'])) {
            $stmt_upd_cant = $pdo->prepare("UPDATE evento_servicio SET cantidad_servicio_evento = ?, subtotal_servicio_evento = ? WHERE id_evento_servicio = ?");
            foreach ($_POST['actualizar_cantidades'] as $id_es => $nueva_cant) {
                $cant_val = max(1, intval($nueva_cant));
                $stmt_price = $pdo->prepare("SELECT s.precio_servicio FROM evento_servicio es INNER JOIN servicios s ON es.id_servicio = s.id_servicio WHERE es.id_evento_servicio = ?");
                $stmt_price->execute([$id_es]);
                $p_row = $stmt_price->fetch(PDO::FETCH_ASSOC);
                if ($p_row) {
                    $sub_val = floatval($p_row['precio_servicio']) * $cant_val;
                    $stmt_upd_cant->execute([$cant_val, $sub_val, $id_es]);
                }
            }
        }

        // F) BITÁCORA
        if (function_exists('registrarBitacora')) {
            registrarBitacora($pdo, 'ACTUALIZAR', 'eventos', $id_evento, null, [
                'estado' => $nuevo_estado,
                'personas' => $num_personas
            ]);
        }

        $sql_servs = "SELECT es.id_evento_servicio, es.cantidad_servicio_evento, es.subtotal_servicio_evento,
                             s.id_servicio, s.nombre_servicio, s.precio_servicio, s.tipo_registro, s.tipo_cobro, s.es_por_persona, s.ubicacion
                      FROM evento_servicio es
                      INNER JOIN servicios s ON es.id_servicio = s.id_servicio
                      WHERE es.id_evento = ?";
        $stmt_s = $pdo->prepare($sql_servs);
        $stmt_s->execute([$id_evento]);
        $servicios_actualizados = $stmt_s->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'exito' => true,
            'mensaje' => 'Cambios aplicados correctamente.',
            'servicios' => $servicios_actualizados
        ]);
        exit;

    } catch (Exception $e) {
        echo json_encode(['exito' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

$mensaje = '';
$error_db = null;

// --- PROCESAR POST REGULAR (ELIMINAR EVENTO) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($pdo)) {
    if (isset($_POST['accion_evento']) && $_POST['accion_evento'] === 'eliminar_evento') {
        try {
            $id_evento_eliminar = intval($_POST['id_evento']);
            if ($id_evento_eliminar > 0) {
                $stmtOld = $pdo->prepare("SELECT * FROM eventos WHERE id_evento = ?");
                $stmtOld->execute([$id_evento_eliminar]);
                $datosEvento = $stmtOld->fetch(PDO::FETCH_ASSOC);

                $stmt_del_servs = $pdo->prepare("DELETE FROM evento_servicio WHERE id_evento = ?");
                $stmt_del_servs->execute([$id_evento_eliminar]);

                $stmt_del_evt = $pdo->prepare("DELETE FROM eventos WHERE id_evento = ?");
                $stmt_del_evt->execute([$id_evento_eliminar]);

                if (function_exists('registrarBitacora')) {
                    registrarBitacora($pdo, 'ELIMINAR', 'eventos', $id_evento_eliminar, $datosEvento, null);
                }

                $mensaje = "El Evento #" . $id_evento_eliminar . " ha sido eliminado correctamente.";
            }
        } catch (Exception $e) {
            $error_db = "Error al eliminar el evento: " . $e->getMessage();
        }
    }
}

// CONSULTAR EVENTOS
$eventos_proximos = [];
$eventos_atrasados = [];
$paquetes_catalogo = [];
$extras_catalogo = [];

if (isset($pdo)) {
    try {
        $fecha_actual = date('Y-m-d');
        $sql_eventos = "SELECT 
                    e.id_evento, e.nombre_evento, e.fecha_evento, e.hora_evento,
                    e.ubicacion, e.num_personas, e.estado,
                    u.nombre_usuario, u.apellidos_usuario, u.telefono_usuario
                FROM eventos e
                LEFT JOIN usuarios u ON e.id_cliente = u.id_usuario
                ORDER BY e.fecha_evento ASC, e.hora_evento ASC";
        $stmt_e = $pdo->query($sql_eventos);
        $eventos_raw = $stmt_e->fetchAll(PDO::FETCH_ASSOC);

        foreach ($eventos_raw as $evt) {
            $id_evt = $evt['id_evento'];
            
            $sql_servs = "SELECT 
                            es.id_evento_servicio, es.cantidad_servicio_evento, es.subtotal_servicio_evento,
                            s.id_servicio, s.nombre_servicio, s.precio_servicio, s.tipo_registro, s.tipo_cobro, s.es_por_persona, s.ubicacion
                          FROM evento_servicio es
                          INNER JOIN servicios s ON es.id_servicio = s.id_servicio
                          WHERE es.id_evento = ?";
            $stmt_s = $pdo->prepare($sql_servs);
            $stmt_s->execute([$id_evt]);
            $evt['servicios_asociados'] = $stmt_s->fetchAll(PDO::FETCH_ASSOC);

            if ($evt['fecha_evento'] >= $fecha_actual) {
                $eventos_proximos[] = $evt;
            } else {
                $eventos_atrasados[] = $evt;
            }
        }

    } catch (PDOException $e) {
        $error_db = "Error al obtener eventos: " . $e->getMessage();
    }

    try {
        $stmt_paq = $pdo->query("SELECT id_servicio, nombre_servicio, precio_servicio, ubicacion FROM servicios WHERE LOWER(tipo_registro) = 'paquete' AND disponible_servicio = 1 ORDER BY nombre_servicio ASC");
        $paquetes_catalogo = $stmt_paq->fetchAll(PDO::FETCH_ASSOC);

        $stmt_ext = $pdo->query("SELECT id_servicio, nombre_servicio, precio_servicio, tipo_cobro, es_por_persona FROM servicios WHERE (LOWER(tipo_registro) != 'paquete' OR tipo_registro IS NULL OR tipo_registro = '') AND disponible_servicio = 1 ORDER BY nombre_servicio ASC");
        $extras_catalogo = $stmt_ext->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $paquetes_catalogo = [];
        $extras_catalogo = [];
    }
}

include __DIR__ . '/includes/header.php';
?>

<style>
    .card-custom { background: #ffffff; border-radius: 12px; border: 1px solid #cbd5e1; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
    .badge-estado { padding: 0.5em 0.8em; font-size: 0.85rem; border-radius: 20px; font-weight: 700; text-transform: uppercase; }
    .badge-confirmado { background-color: var(--color-verde); color: white; }
    .badge-pendiente { background-color: var(--color-amarillo); color: black; }
    .badge-cancelado { background-color: #dc3545; color: white; }
    .search-box { position: relative; }
    .search-box i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #64748b; }
    .search-box input { padding-left: 40px; border-radius: 20px; border: 2px solid #cbd5e1; }
</style>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h3 fw-bold" style="color: var(--color-morado);"><i class="fa-solid fa-calendar-check me-2" style="color: var(--color-rosa);"></i>Historial de Eventos</h2>
                <a href="generar_evento.php" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i> Nuevo Evento</a>
            </div>

            <!-- BUSCADOR -->
            <div class="card-custom py-3 mb-4">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="input_buscador" class="form-control form-control-lg" placeholder="Buscar por cliente, folio (#EV-...), nombre del evento o fecha (YYYY-MM-DD)..." onkeyup="filtrarTablaEventos()">
                </div>
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

            <!-- EVENTOS PRÓXIMOS -->
            <div class="card-custom p-0 overflow-hidden mb-4">
                <div class="p-3 bg-dark text-white fw-bold d-flex justify-content-between align-items-center">
                    <span><i class="fa-solid fa-calendar-days me-2" style="color: var(--color-rosa);"></i> Eventos Vigentes / Próximos</span>
                    <span class="badge rounded-pill bg-light text-dark fs-6"><?= count($eventos_proximos) ?> Registros</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 tabla-eventos">
                        <thead class="table-dark">
                            <tr>
                                <th>Folio / Evento</th>
                                <th>Cliente</th>
                                <th>Fecha y Hora</th>
                                <th>Salón</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($eventos_proximos)): ?>
                                <tr class="fila-sin-datos">
                                    <td colspan="6" class="text-center py-4 text-muted fw-bold">No hay eventos próximos programados.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($eventos_proximos as $evt): 
                                    $folio = "#EV-" . str_pad($evt['id_evento'], 5, '0', STR_PAD_LEFT);
                                    $cliente_nom = trim(($evt['nombre_usuario'] ?? '') . ' ' . ($evt['apellidos_usuario'] ?? ''));
                                    if (empty($cliente_nom)) $cliente_nom = 'Cliente General';
                                    $estado = strtolower($evt['estado'] ?? 'confirmado');
                                    $badge_cls = ($estado === 'cancelado') ? 'badge-cancelado' : (($estado === 'pendiente') ? 'badge-pendiente' : 'badge-confirmado');
                                    $data_json = htmlspecialchars(json_encode($evt), ENT_QUOTES, 'UTF-8');
                                ?>
                                    <tr class="fila-evento">
                                        <td>
                                            <strong style="color: var(--color-morado);"><?= $folio ?></strong><br>
                                            <span class="fw-semibold text-dark col-busqueda"><?= htmlspecialchars($evt['nombre_evento']) ?></span>
                                        </td>
                                        <td class="col-busqueda">
                                            <span class="fw-bold text-dark"><?= htmlspecialchars($cliente_nom) ?></span><br>
                                            <small class="text-muted"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($evt['telefono_usuario'] ?? 'Sin tel') ?></small>
                                        </td>
                                        <td class="col-busqueda">
                                            <i class="fa-solid fa-calendar me-1 text-secondary"></i><?= htmlspecialchars($evt['fecha_evento']) ?><br>
                                            <small class="text-muted"><i class="fa-solid fa-clock me-1"></i><?= htmlspecialchars($evt['hora_evento']) ?></small>
                                        </td>
                                        <td><span class="badge badge-jardin rounded-pill"><?= htmlspecialchars(ucfirst($evt['ubicacion'] ?? 'Jardín')) ?></span></td>
                                        <td><span class="badge-estado <?= $badge_cls ?>"><?= ucfirst($estado) ?></span></td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-primary fw-bold rounded-pill me-1 btn-abrir-modal" data-evento="<?= $data_json ?>">
                                                    <i class="fa-solid fa-eye me-1"></i> Ver / Editar
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger fw-bold rounded-pill" onclick="confirmarEliminarDirecto(<?= $evt['id_evento'] ?>, '<?= htmlspecialchars($evt['nombre_evento'], ENT_QUOTES) ?>')">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SECCIÓN EVENTOS ATRASADOS / HISTÓRICOS -->
            <div class="card-custom p-0 overflow-hidden">
                <div class="p-3 bg-secondary text-white fw-bold d-flex justify-content-between align-items-center" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#collapseAtrasados">
                    <span><i class="fa-solid fa-clock-rotate-left me-2"></i> Ver Eventos Atrasados / Concluidos</span>
                    <div>
                        <span class="badge bg-light text-dark fs-6 me-2"><?= count($eventos_atrasados) ?> Registros</span>
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>
                </div>
                <div class="collapse" id="collapseAtrasados">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 tabla-eventos">
                            <thead class="table-dark">
                                <tr>
                                    <th>Folio / Evento</th>
                                    <th>Cliente</th>
                                    <th>Fecha y Hora</th>
                                    <th>Salón</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($eventos_atrasados)): ?>
                                    <tr class="fila-sin-datos">
                                        <td colspan="6" class="text-center py-4 text-muted fw-bold">No hay eventos atrasados en el registro.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($eventos_atrasados as $evt): 
                                        $folio = "#EV-" . str_pad($evt['id_evento'], 5, '0', STR_PAD_LEFT);
                                        $cliente_nom = trim(($evt['nombre_usuario'] ?? '') . ' ' . ($evt['apellidos_usuario'] ?? ''));
                                        if (empty($cliente_nom)) $cliente_nom = 'Cliente General';
                                        $estado = strtolower($evt['estado'] ?? 'confirmado');
                                        $badge_cls = ($estado === 'cancelado') ? 'badge-cancelado' : (($estado === 'pendiente') ? 'badge-pendiente' : 'badge-confirmado');
                                        $data_json = htmlspecialchars(json_encode($evt), ENT_QUOTES, 'UTF-8');
                                    ?>
                                        <tr class="fila-evento table-light">
                                            <td>
                                                <strong class="text-secondary"><?= $folio ?></strong><br>
                                                <span class="fw-semibold text-dark col-busqueda"><?= htmlspecialchars($evt['nombre_evento']) ?></span>
                                            </td>
                                            <td class="col-busqueda">
                                                <span class="fw-bold text-dark"><?= htmlspecialchars($cliente_nom) ?></span><br>
                                                <small class="text-muted"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($evt['telefono_usuario'] ?? 'Sin tel') ?></small>
                                            </td>
                                            <td class="col-busqueda">
                                                <i class="fa-solid fa-calendar me-1 text-secondary"></i><?= htmlspecialchars($evt['fecha_evento']) ?><br>
                                                <small class="text-muted"><i class="fa-solid fa-clock me-1"></i><?= htmlspecialchars($evt['hora_evento']) ?></small>
                                            </td>
                                            <td><span class="badge badge-jardin rounded-pill"><?= htmlspecialchars(ucfirst($evt['ubicacion'] ?? 'Jardín')) ?></span></td>
                                            <td><span class="badge-estado <?= $badge_cls ?>"><?= ucfirst($estado) ?></span></td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary fw-bold rounded-pill me-1 btn-abrir-modal" data-evento="<?= $data_json ?>">
                                                        <i class="fa-solid fa-eye me-1"></i> Ver / Editar
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger fw-bold rounded-pill" onclick="confirmarEliminarDirecto(<?= $evt['id_evento'] ?>, '<?= htmlspecialchars($evt['nombre_evento'], ENT_QUOTES) ?>')">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
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

<!-- FORMULARIO OCULTO PARA ELIMINAR EVENTOS -->
<form action="" method="POST" id="form_eliminar_evento_directo">
    <input type="hidden" name="accion_evento" value="eliminar_evento">
    <input type="hidden" name="id_evento" id="id_evento_eliminar_input">
</form>

<!-- MODAL VER / EDITAR EVENTO -->
<div class="modal fade" id="modalDesgloseEvento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" id="modal_titulo_folio">Desglose del Evento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST" id="form_modal_evento">
                <div class="modal-body">
                    <input type="hidden" name="accion_evento" id="modal_accion_evento" value="guardar_cambios">
                    <input type="hidden" name="id_evento" id="modal_id_evento">
                    <input type="hidden" name="eliminar_id_evento_servicio" id="modal_eliminar_id_es">

                    <!-- INFO GENERAL -->
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

                    <!-- SLIDER DE INVITADOS -->
                    <div class="mb-3 bg-white p-3 rounded border">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="modal_num_personas_slider" class="form-label fw-bold mb-0">¿Cuántos invitados irán?</label>
                            <span class="badge bg-primary fs-6 fw-bold rounded-pill" id="modal_label_num_personas">50 personas</span>
                        </div>
                        <input type="range" class="form-range" id="modal_num_personas_slider" name="num_personas_modal" min="0" max="300" step="5" value="50" oninput="recalcularModal()">
                    </div>

                    <!-- PAQUETE CONTRATADO -->
                    <div class="alert alert-primary py-3 mb-3 border border-primary">
                        <label class="form-label fw-bold mb-1 text-primary"><i class="fa-solid fa-box-open me-2"></i>Paquete actual:</label>
                        <select name="id_paquete_base_modal" id="modal_select_paquete_base" class="form-select fw-bold border-primary" onchange="recalcularModal()">
                            <option value="">No hay paquete seleccionado</option>
                            <?php foreach ($paquetes_catalogo as $paq): 
                                $ubi_paq = strtolower($paq['ubicacion'] ?? 'ambos');
                            ?>
                                <option value="<?= $paq['id_servicio'] ?>" 
                                        data-precio="<?= $paq['precio_servicio'] ?>" 
                                        data-ubicacion="<?= $ubi_paq ?>">
                                    <?= htmlspecialchars($paq['nombre_servicio']) ?> ($<?= number_format((float)$paq['precio_servicio'], 2) ?> / persona)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top border-primary-subtle">
                            <span class="small fw-semibold text-secondary">Subtotal:</span>
                            <span class="fw-bold text-primary fs-5" id="modal_subtotal_paquete_txt">$0.00</span>
                        </div>
                    </div>

                    <!-- EXTRAS -->
                    <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-puzzle-piece me-2"></i>Servicios adicionales:</h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Servicio Extra</th>
                                    <th>Precio Unitario</th>
                                    <th style="width: 170px;">Cantidad / Personas</th>
                                    <th>Subtotal</th>
                                    <th class="text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="modal_tabla_extras_body"></tbody>
                        </table>
                    </div>

                    <!-- AGREGAR SERVICIO EXTRA -->
                    <div class="row g-2 align-items-center mb-3 p-3 bg-light rounded border">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary mb-1">Agregar otro servicio:</label>
                            <select name="nuevo_id_servicio" id="modal_select_nuevo_servicio" class="form-select border-success" onchange="evaluarSelectNuevoExtra()">
                                <option value="" data-precio="0" data-tipo-cobro="fijo">Elige un extra...</option>
                                <?php foreach ($extras_catalogo as $serv): 
                                    $es_p = ($serv['tipo_cobro'] === 'por_persona' || intval($serv['es_por_persona']) === 1) ? 'persona' : 'fijo';
                                ?>
                                    <option value="<?= $serv['id_servicio'] ?>" data-precio="<?= $serv['precio_servicio'] ?>" data-tipo-cobro="<?= $es_p ?>">
                                        <?= htmlspecialchars($serv['nombre_servicio']) ?> ($<?= number_format((float)$serv['precio_servicio'], 2) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <div id="wrapper_slider_extra_modal" class="d-none">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label fw-bold small text-secondary mb-0">Personas:</label>
                                    <span class="badge bg-success fs-6 fw-bold rounded-pill" id="lbl_val_slider_modal_extra">50 personas</span>
                                </div>
                                <input type="range" class="form-range" id="modal_slider_extra_personas" min="5" max="300" step="5" value="50" oninput="sincronizarSliderModalExtra()">
                            </div>
                            <input type="hidden" name="nueva_cantidad_servicio" id="modal_input_cantidad_extra" value="1">
                        </div>

                        <div class="col-12 d-flex justify-content-between align-items-center mt-2">
                            <span class="text-primary fw-bold" id="lbl_estimado_extra_nuevo"></span>
                            <button type="button" class="btn btn-success fw-bold px-4 rounded-pill" onclick="aplicarCambiosAjax()"><i class="fa-solid fa-plus me-1"></i> Agregar Extra</button>
                        </div>
                    </div>

                    <!-- TOTAL RECALCULADO -->
                    <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded border mb-3">
                        <h5 class="fw-bold mb-0">TOTAL RECALCULADO:</h5>
                        <h3 class="fw-bold text-primary mb-0" id="modal_total_recalculado">$0.00</h3>
                    </div>

                    <!-- NOTIFICACIÓN AJAX -->
                    <div id="alert_ajax_status" class="alert py-2 d-none fw-bold small"></div>

                    <!-- ESTADO -->
                    <div class="mb-2">
                        <label class="form-label fw-bold">Estado del Evento:</label>
                        <select name="estado_evento" id="modal_estado_select" class="form-select">
                            <option value="confirmado">Activo / Confirmado</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>

                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-danger fw-bold rounded-pill" onclick="eliminarEventoDesdeModal()">
                        <i class="fa-solid fa-trash me-1"></i> Eliminar Evento
                    </button>
                    <div>
                        <button type="button" class="btn btn-info text-white fw-bold me-1 rounded-pill" onclick="aplicarCambiosAjax()">
                            <i class="fa-solid fa-rotate me-1"></i> Aplicar Cambios
                        </button>
                        <button type="submit" class="btn btn-primary fw-bold rounded-pill">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Guardar y Cerrar
                        </button>
                    </div>
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
    let modalElem = document.getElementById('modalDesgloseEvento');
    if (modalElem) {
        bootstrapModalHistorial = new bootstrap.Modal(modalElem);
    }

    document.addEventListener('click', function(e) {
        let btn = e.target.closest('.btn-abrir-modal');
        if (btn) {
            let evtData = JSON.parse(btn.getAttribute('data-evento'));
            abrirModalDesglose(evtData);
        }
    });
});

function filtrarTablaEventos() {
    let filtro = document.getElementById('input_buscador').value.toLowerCase().trim();
    let filas = document.querySelectorAll('.fila-evento');

    filas.forEach(fila => {
        let textoFila = fila.innerText.toLowerCase();
        if (textoFila.includes(filtro)) {
            fila.style.display = '';
        } else {
            fila.style.display = 'none';
        }
    });
}

function abrirModalDesglose(evt) {
    modalEventoActual = evt;
    
    document.getElementById('modal_titulo_folio').innerText = "Desglose del Evento #EV-" + String(evt.id_evento).padStart(5, '0');
    document.getElementById('modal_id_evento').value = evt.id_evento;
    document.getElementById('modal_accion_evento').value = 'guardar_cambios';
    
    let clienteNom = ((evt.nombre_usuario || '') + ' ' + (evt.apellidos_usuario || '')).trim();
    document.getElementById('modal_cliente_nombre').innerText = clienteNom || 'Cliente General';
    document.getElementById('modal_cliente_telefono').innerText = evt.telefono_usuario || 'Sin teléfono';
    document.getElementById('modal_evento_fecha').innerText = (evt.fecha_evento || '') + ' ' + (evt.hora_evento || '');
    
    let ubiRaw = (evt.ubicacion || 'Jardín').toLowerCase();
    let ubiLimpia = ubiRaw.replace(/^salón\s+/i, '');
    document.getElementById('modal_evento_salon').innerText = 'Salón ' + ubiLimpia.charAt(0).toUpperCase() + ubiLimpia.slice(1);
    
    document.getElementById('modal_estado_select').value = (evt.estado || 'confirmado').toLowerCase();

    let numPers = parseInt(evt.num_personas) || 50;
    document.getElementById('modal_num_personas_slider').value = numPers;
    
    let selectPaqModal = document.getElementById('modal_select_paquete_base');
    let esJardin = ubiRaw.includes('jardin');

    Array.from(selectPaqModal.options).forEach(opt => {
        let ubiPaq = opt.getAttribute('data-ubicacion') || 'ambos';
        if (opt.value === '') return;

        if (ubiPaq === 'ambos' || (esJardin && ubiPaq === 'jardin') || (!esJardin && ubiPaq === 'carmelo')) {
            opt.style.display = 'block';
            opt.disabled = false;
        } else {
            opt.style.display = 'none';
            opt.disabled = true;
        }
    });

    let paqueteRegistrado = (evt.servicios_asociados || []).find(s => (s.tipo_registro || '').toLowerCase() === 'paquete');
    if (paqueteRegistrado) {
        selectPaqModal.value = paqueteRegistrado.id_servicio;
    } else {
        selectPaqModal.value = '';
    }

    recalcularModal();
    if (bootstrapModalHistorial) {
        bootstrapModalHistorial.show();
    }
}

function evaluarSelectNuevoExtra() {
    let select = document.getElementById('modal_select_nuevo_servicio');
    let opt = select.options[select.selectedIndex];
    let wrapperSlider = document.getElementById('wrapper_slider_extra_modal');
    let inputHiddenCant = document.getElementById('modal_input_cantidad_extra');

    if (opt && opt.value) {
        let tipoCobro = opt.getAttribute('data-tipo-cobro');
        if (tipoCobro === 'persona') {
            wrapperSlider.classList.remove('d-none');
            inputHiddenCant.value = document.getElementById('modal_slider_extra_personas').value;
        } else {
            wrapperSlider.classList.add('d-none');
            inputHiddenCant.value = '1';
        }
    } else {
        wrapperSlider.classList.add('d-none');
        inputHiddenCant.value = '1';
    }

    calcularTotalEstimadoExtra();
}

function sincronizarSliderModalExtra() {
    let val = document.getElementById('modal_slider_extra_personas').value;
    document.getElementById('lbl_val_slider_modal_extra').innerText = val + ' personas';
    document.getElementById('modal_input_cantidad_extra').value = val;
    calcularTotalEstimadoExtra();
}

function calcularTotalEstimadoExtra() {
    let select = document.getElementById('modal_select_nuevo_servicio');
    let opt = select.options[select.selectedIndex];
    let cant = parseInt(document.getElementById('modal_input_cantidad_extra').value) || 1;
    let lbl = document.getElementById('lbl_estimado_extra_nuevo');

    if (opt && opt.value) {
        let precio = parseFloat(opt.getAttribute('data-precio')) || 0;
        let tipoCobro = opt.getAttribute('data-tipo-cobro');
        let total = precio * cant;

        if (tipoCobro === 'persona') {
            lbl.innerText = `Subtotal estimado: $${precio.toFixed(2)} × ${cant} personas = $${total.toLocaleString('es-MX', {minimumFractionDigits: 2})}`;
        } else {
            lbl.innerText = `Costo Fijo Único: $${precio.toLocaleString('es-MX', {minimumFractionDigits: 2})}`;
        }
    } else {
        lbl.innerText = '';
    }
}

function recalcularModal() {
    let numPers = parseInt(document.getElementById('modal_num_personas_slider').value) || 0;
    document.getElementById('modal_label_num_personas').innerText = numPers + ' personas';

    if (!modalEventoActual) return;

    let selectPaq = document.getElementById('modal_select_paquete_base');
    let optPaq = selectPaq.options[selectPaq.selectedIndex];
    let subtotalPaquete = 0;

    if (optPaq && optPaq.value) {
        let precioPaqUnitario = parseFloat(optPaq.getAttribute('data-precio')) || 0;
        subtotalPaquete = precioPaqUnitario * numPers;
    }

    document.getElementById('modal_subtotal_paquete_txt').innerText = '$' + subtotalPaquete.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});

    let servicios = modalEventoActual.servicios_asociados || [];
    let extrasList = servicios.filter(s => (s.tipo_registro || '').toLowerCase() !== 'paquete');

    let tbody = document.getElementById('modal_tabla_extras_body');
    tbody.innerHTML = '';
    let totalExtras = 0;

    if (extrasList.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted fw-bold">No hay servicios adicionales agregados.</td></tr>';
    } else {
        extrasList.forEach(ext => {
            let precioU = parseFloat(ext.precio_servicio) || 0;
            let esPersona = (ext.tipo_cobro === 'por_persona' || parseInt(ext.es_por_persona) === 1);
            let cant = parseInt(ext.cantidad_servicio_evento) || 1;
            
            let subtotalExtra = esPersona ? (precioU * cant) : precioU;
            totalExtras += subtotalExtra;

            let celdaCantidad = '';
            if (esPersona) {
                celdaCantidad = `<input type="number" name="actualizar_cantidades[${ext.id_evento_servicio}]" class="form-control form-control-sm text-center fw-bold" value="${cant}" min="1" oninput="actualizarSubtotalFila(this, ${precioU}, true)">`;
            } else {
                celdaCantidad = `<span class="text-muted fw-bold fs-5">-</span><input type="hidden" name="actualizar_cantidades[${ext.id_evento_servicio}]" value="1">`;
            }

            let tr = document.createElement('tr');
            tr.innerHTML = `
                <td><strong class="text-dark">${ext.nombre_servicio}</strong></td>
                <td>$${precioU.toFixed(2)}</td>
                <td class="text-center">${celdaCantidad}</td>
                <td class="fw-bold text-primary subtotal-fila-val" data-subtotal="${subtotalExtra}">$${subtotalExtra.toLocaleString('es-MX', {minimumFractionDigits: 2})}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger fw-bold rounded-pill" onclick="eliminarExtraModalAjax(${ext.id_evento_servicio})">
                        &times; Quitar
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    let totalGeneral = subtotalPaquete + totalExtras;
    document.getElementById('modal_total_recalculado').innerText = '$' + totalGeneral.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function actualizarSubtotalFila(inputElem, precioUnitario, esPersona) {
    let cant = parseInt(inputElem.value) || 1;
    if (cant < 1) cant = 1;
    let nuevoSubtotal = esPersona ? (precioUnitario * cant) : precioUnitario;

    let tdSubtotal = inputElem.closest('tr').querySelector('.subtotal-fila-val');
    tdSubtotal.setAttribute('data-subtotal', nuevoSubtotal);
    tdSubtotal.innerText = '$' + nuevoSubtotal.toLocaleString('es-MX', {minimumFractionDigits: 2});

    recalcularTotalSumado();
}

function recalcularTotalSumado() {
    let numPers = parseInt(document.getElementById('modal_num_personas_slider').value) || 0;
    
    let selectPaq = document.getElementById('modal_select_paquete_base');
    let optPaq = selectPaq.options[selectPaq.selectedIndex];
    let subtotalPaquete = 0;
    if (optPaq && optPaq.value) {
        subtotalPaquete = (parseFloat(optPaq.getAttribute('data-precio')) || 0) * numPers;
    }

    let subtotalExtras = 0;
    document.querySelectorAll('.subtotal-fila-val').forEach(td => {
        subtotalExtras += parseFloat(td.getAttribute('data-subtotal')) || 0;
    });

    let totalGen = subtotalPaquete + subtotalExtras;
    document.getElementById('modal_total_recalculado').innerText = '$' + totalGen.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function aplicarCambiosAjax() {
    let form = document.getElementById('form_modal_evento');
    let formData = new FormData(form);
    formData.append('es_ajax', '1');

    let alertBox = document.getElementById('alert_ajax_status');
    alertBox.className = 'alert alert-info py-2 fw-bold small';
    alertBox.innerText = 'Guardando y recalculando cambios...';
    alertBox.classList.remove('d-none');

    fetch('historial_eventos.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.exito) {
            alertBox.className = 'alert alert-success py-2 fw-bold small';
            alertBox.innerText = '✓ Cambios guardados correctamente.';
            
            if (data.servicios) {
                modalEventoActual.servicios_asociados = data.servicios;
            }
            document.getElementById('modal_select_nuevo_servicio').value = '';
            document.getElementById('modal_input_cantidad_extra').value = '1';
            document.getElementById('wrapper_slider_extra_modal').classList.add('d-none');
            document.getElementById('lbl_estimado_extra_nuevo').innerText = '';
            
            recalcularModal();

            setTimeout(() => { alertBox.classList.add('d-none'); }, 2500);
        } else {
            alertBox.className = 'alert alert-danger py-2 fw-bold small';
            alertBox.innerText = 'Error: ' + (data.error || 'No se pudieron aplicar los cambios.');
        }
    })
    .catch(err => {
        alertBox.className = 'alert alert-danger py-2 fw-bold small';
        alertBox.innerText = 'Error de conexión con el servidor.';
    });
}

function eliminarExtraModalAjax(idEventoServicio) {
    if (confirm('¿Deseas remover este servicio del evento?')) {
        let form = document.getElementById('form_modal_evento');
        let formData = new FormData(form);
        formData.append('es_ajax', '1');
        formData.append('eliminar_id_evento_servicio', idEventoServicio);

        fetch('historial_eventos.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.exito) {
                modalEventoActual.servicios_asociados = data.servicios || [];
                recalcularModal();
            }
        });
    }
}

function eliminarEventoDesdeModal() {
    if (!modalEventoActual) return;
    let folio = "#EV-" . str_pad(modalEventoActual.id_evento, 5, '0', STR_PAD_LEFT);
    let nom = modalEventoActual.nombre_evento || '';
    
    if (confirm(`¿Estás seguro de que deseas eliminar permanentemente el evento ${folio} ("${nom}")?\n\nEsta acción NO eliminará al cliente registrado.`)) {
        document.getElementById('modal_accion_evento').value = 'eliminar_evento';
        document.getElementById('form_modal_evento').submit();
    }
}

function confirmarEliminarDirecto(idEvento, nombreEvento) {
    let folio = "#EV-" + String(idEvento).padStart(5, '0');
    if (confirm(`¿Estás seguro de que deseas eliminar permanentemente el evento ${folio} ("${nombreEvento}")?\n\nEl cliente seguirá conservándose en tu catálogo.`)) {
        document.getElementById('id_evento_eliminar_input').value = idEvento;
        document.getElementById('form_eliminar_evento_directo').submit();
    }
}
</script>

</body>
</html>

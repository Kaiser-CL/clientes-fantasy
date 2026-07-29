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

        // F) BITÁCORA DE EDICIÓN DE EVENTO
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

// --- PROCESAR POST REGULAR ---
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
        </main>
    </div>
</div>

<form action="" method="POST" id="form_eliminar_evento_directo">
    <input type="hidden" name="accion_evento" value="eliminar_evento">
    <input type="hidden" name="id_evento" id="id_evento_eliminar_input">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

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

// Procesa el guardado de un evento y sus servicios asociados.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($pdo)) {
    try {
        $id_cliente_existente = !empty($_POST['id_cliente_existente']) ? $_POST['id_cliente_existente'] : null;
        $id_usuario_final = null;

        // 1. OBTENER O CREAR EL CLIENTE
        if ($id_cliente_existente) {
            $id_usuario_final = $id_cliente_existente;
        } else {
            $nombre_cliente = trim($_POST['nombre_cliente'] ?? '');
            $apellidos_cliente = trim($_POST['apellidos_cliente'] ?? '');
            $correo_cliente = trim($_POST['correo_cliente'] ?? '');
            $telefono_cliente = trim($_POST['telefono_cliente'] ?? '');

            if (!empty($correo_cliente) && !empty($nombre_cliente)) {
                $stmt_chk = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = ? LIMIT 1");
                $stmt_chk->execute([$correo_cliente]);
                $user_found = $stmt_chk->fetch(PDO::FETCH_ASSOC);

                if ($user_found) {
                    $id_usuario_final = $user_found['id_usuario'];
                } else {
                    $pass_default = password_hash('fantasy2026', PASSWORD_BCRYPT);
                    $id_empleado_logueado = $_SESSION['id_usuario'] ?? null;
                    
                    $stmt_ins = $pdo->prepare("INSERT INTO usuarios (nombre_usuario, apellidos_usuario, email, telefono_usuario, contrasena_usuario, id_rol, id_empleado_registro) VALUES (?, ?, ?, ?, ?, 2, ?)");
                    $stmt_ins->execute([$nombre_cliente, $apellidos_cliente, $correo_cliente, $telefono_cliente, $pass_default, $id_empleado_logueado]);   
                    $id_usuario_final = $pdo->lastInsertId();

                    if (function_exists('registrarBitacora')) {
                        registrarBitacora($pdo, 'AGREGAR', 'usuarios', $id_usuario_final, null, [
                            'nombre' => $nombre_cliente . ' ' . $apellidos_cliente,
                            'email' => $correo_cliente,
                            'origen' => 'Generar Evento'
                        ]);
                    }
                }
            }
        }

        if (!$id_usuario_final) {
            throw new Exception("Debes seleccionar un cliente existente o registrar un cliente nuevo.");
        }

        // 2. PARÁMETROS DEL EVENTO
        $nombre_evento = trim($_POST['nombre_evento'] ?? 'Evento Sin Nombre');
        $fecha_evento = $_POST['fecha_evento'] ?? null;
        $hora_evento = $_POST['hora_evento'] ?? null;
        $salon_evento = $_POST['salon_evento'] ?? 'jardin';
        $num_personas = intval($_POST['num_personas'] ?? 0);
        $id_paquete = $_POST['id_paquete_base'] ?? null;
        $comentarios = trim($_POST['comentarios_evento'] ?? '');

        if (strtolower($salon_evento) === 'jardin' && $num_personas > 150) {
            $num_personas = 150;
        }

        $id_sucursal = (strtolower($salon_evento) === 'carmelo') ? 2 : 1;

        // 3. INSERTAR EVENTO
        try {
            $sql_event = "INSERT INTO eventos (id_cliente, id_sucursal, nombre_evento, fecha_evento, hora_evento, ubicacion, num_personas, notas, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'confirmado')";
            $stmt_event = $pdo->prepare($sql_event);
            $stmt_event->execute([$id_usuario_final, $id_sucursal, $nombre_evento, $fecha_evento, $hora_evento, $salon_evento, $num_personas, $comentarios]);
        } catch (PDOException $e_col) {
            $sql_event = "INSERT INTO eventos (id_cliente, id_sucursal, nombre_evento, fecha_evento, hora_evento, ubicacion, estado) VALUES (?, ?, ?, ?, ?, ?, 'confirmado')";
            $stmt_event = $pdo->prepare($sql_event);
            $stmt_event->execute([$id_usuario_final, $id_sucursal, $nombre_evento, $fecha_evento, $hora_evento, $salon_evento]);
        }
        
        $id_evento_creado = $pdo->lastInsertId();

        // 4. GUARDAR SERVICIOS (PAQUETE Y EXTRAS)
        if ($id_evento_creado) {
            $stmt_es = $pdo->prepare("INSERT INTO evento_servicio (id_evento, id_servicio, cantidad_servicio_evento, subtotal_servicio_evento) VALUES (?, ?, ?, ?)");
            $stmt_info = $pdo->prepare("SELECT precio_servicio, tipo_cobro, es_por_persona FROM servicios WHERE id_servicio = ?");

            if ($id_paquete) {
                $stmt_info->execute([$id_paquete]);
                $paq_info = $stmt_info->fetch(PDO::FETCH_ASSOC);

                if ($paq_info) {
                    $precio_paq = floatval($paq_info['precio_servicio']);
                    $subtotal_paq = $precio_paq * $num_personas;
                    $stmt_es->execute([$id_evento_creado, $id_paquete, $num_personas, $subtotal_paq]);
                }
            }

            if (!empty($_POST['extras_id']) && is_array($_POST['extras_id'])) {
                foreach ($_POST['extras_id'] as $idx => $id_extra) {
                    $stmt_info->execute([$id_extra]);
                    $ext_info = $stmt_info->fetch(PDO::FETCH_ASSOC);

                    if ($ext_info) {
                        $precio_ext = floatval($ext_info['precio_servicio']);
                        $es_persona = ($ext_info['tipo_cobro'] === 'por_persona' || intval($ext_info['es_por_persona']) === 1);
                        $cant_ext = intval($_POST['extras_cant'][$idx] ?? 1);
                        $subtotal_ext = $es_persona ? ($precio_ext * $cant_ext) : $precio_ext;
                        $stmt_es->execute([$id_evento_creado, $id_extra, $cant_ext, $subtotal_ext]);
                    }
                }
            }

            // REGISTRO BITÁCORA DEL EVENTO
            if (function_exists('registrarBitacora')) {
                registrarBitacora($pdo, 'AGREGAR', 'eventos', $id_evento_creado, null, [
                    'nombre_evento' => $nombre_evento,
                    'fecha' => $fecha_evento,
                    'personas' => $num_personas,
                    'salon' => $salon_evento
                ]);
            }
        }

        $mensaje = "¡Evento generado exitosamente!";

    } catch (Exception $e) {
        $error_db = "Error al guardar el evento: " . $e->getMessage();
    }
}

// Consulta los clientes y los servicios disponibles para construir la cotización.
$clientes_lista = [];
$paquetes_catalogo = [];
$extras_catalogo = [];

if (isset($pdo)) {
    try {
        $stmt_cli = $pdo->query("SELECT id_usuario, nombre_usuario, apellidos_usuario, email FROM usuarios WHERE id_rol = 2 ORDER BY id_usuario DESC");
        $clientes_lista = $stmt_cli->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { $clientes_lista = []; }

    try {
        $stmt_paquetes = $pdo->query("SELECT * FROM servicios WHERE LOWER(tipo_registro) = 'paquete' AND disponible_servicio = 1 ORDER BY nombre_servicio ASC");
        $paquetes_catalogo = $stmt_paquetes->fetchAll(PDO::FETCH_ASSOC);

        $stmt_extras = $pdo->query("SELECT * FROM servicios WHERE (LOWER(tipo_registro) != 'paquete' OR tipo_registro IS NULL OR tipo_registro = '') AND disponible_servicio = 1 ORDER BY nombre_servicio ASC");
        $extras_catalogo = $stmt_extras->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $error_db = "Error al consultar catálogo: " . $e->getMessage();
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <h2 class="h3 mb-3 fw-bold" style="color: var(--color-morado);"><i class="fa-solid fa-calendar-plus me-2" style="color: var(--color-rosa);"></i>Generar Nuevo Evento</h2>

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

            <form action="" method="POST" id="form-generar-evento">
                <div class="row g-4">
                    
                    <!-- COLUMNA IZQUIERDA -->
                    <div class="col-lg-7">
                        <div class="card shadow-sm border-0 p-4 rounded-3 bg-white mb-4">
                            <h5 class="fw-bold mb-3" style="color: var(--color-morado);"><i class="fa-solid fa-user me-2" style="color: var(--color-rosa);"></i>Información del Cliente</h5>
                            
                            <div class="mb-3">
                                <label class="form-label text-success fw-bold"><i class="fa-solid fa-user-check me-1"></i> ¿Ya tiene un cliente registrado?</label>
                                <select name="id_cliente_existente" id="id_cliente_existente" class="form-select border-success" onchange="evaluarClienteExistente()">
                                    <option value="">No, registra uno nuevo aquí abajo</option>
                                    <?php foreach ($clientes_lista as $cli): ?>
                                        <option value="<?= $cli['id_usuario'] ?>">
                                            <?= htmlspecialchars(trim(($cli['nombre_usuario'] ?? '') . ' ' . ($cli['apellidos_usuario'] ?? ''))) ?> (<?= htmlspecialchars($cli['email'] ?? '') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <hr class="my-3">

                            <div id="seccion-cliente-nuevo">
                                <h6 class="fw-bold text-secondary mb-2"><i class="fa-solid fa-user-plus me-1"></i> Datos del cliente nuevo:</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Nombre</label>
                                        <input type="text" id="nombre_cliente" name="nombre_cliente" class="form-control" placeholder="Ej: Juan" maxlength="80" pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+" title="Solo letras y espacios">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Apellidos</label>
                                        <input type="text" id="apellidos_cliente" name="apellidos_cliente" class="form-control" placeholder="Ej: García" maxlength="80" pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+" title="Solo letras y espacios">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Email</label>
                                        <input type="email" id="correo_cliente" name="correo_cliente" class="form-control" placeholder="nombre@email.com" maxlength="100" autocomplete="email">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Teléfono</label>
                                        <input type="tel" id="telefono_cliente" name="telefono_cliente" class="form-control" placeholder="3001234567" maxlength="10" minlength="10" pattern="[0-9]{10}" inputmode="numeric" title="10 dígitos">
                                    </div>
                                </div>
                                <div class="alert alert-info py-2 px-3 mt-3 mb-0 fs-6">
                                    <i class="fa-solid fa-key me-1"></i> <strong>La contraseña se crea automáticamente:</strong> fantasy2026
                                </div>
                            </div>
                        </div>

                        <div class="card shadow-sm border-0 p-4 rounded-3 bg-white">
                            <h5 class="fw-bold mb-3" style="color: var(--color-morado);"><i class="fa-solid fa-calendar-days me-2" style="color: var(--color-rosa);"></i>Detalles del Evento</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">¿Qué día?</label>
                                    <input type="date" name="fecha_evento" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">¿A qué hora?</label>
                                    <input type="time" name="hora_evento" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">¿Dónde? (Salón)</label>
                                    <select name="salon_evento" id="salon_evento" class="form-select" onchange="filtrarPorUbicacion()" required>
                                        <option value="jardin">Salón Jardín</option>
                                        <option value="carmelo">Salón Carmelo</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Nombre del evento</label>
                                    <input type="text" name="nombre_evento" class="form-control" placeholder="Ej: Cumpleaños de Sofía" required maxlength="80" pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+" title="Solo letras y espacios">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">¿Algo especial que debamos saber?</label>
                                    <textarea name="comentarios_evento" class="form-control" rows="3" maxlength="500" placeholder="Alergias, preferencias, acuerdos especiales..."></textarea>
                                    <small id="contador_comentarios_evento" class="form-text text-muted">0/500</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- COLUMNA DERECHA -->
                    <div class="col-lg-5">
                        <div class="card shadow-sm border-0 p-4 rounded-3 bg-white">
                            <h5 class="fw-bold mb-3" style="color: var(--color-morado);"><i class="fa-solid fa-receipt me-2" style="color: var(--color-rosa);"></i>Cotización Inicial</h5>

                            <div class="mb-3">
                                <label class="form-label fw-bold">¿Qué paquete quieres?</label>
                                <select name="id_paquete_base" id="select_paquete_base" class="form-select" onchange="actualizarCalculosEventos()" required>
                                    <option value="" data-precio="0" data-ubicacion="ambos">Elige un paquete...</option>
                                    <?php foreach ($paquetes_catalogo as $paq): 
                                        $ubi = strtolower($paq['ubicacion'] ?? 'ambos');
                                    ?>
                                        <option value="<?= $paq['id_servicio'] ?>" 
                                                data-precio="<?= $paq['precio_servicio'] ?>" 
                                                data-ubicacion="<?= $ubi ?>">
                                            <?= htmlspecialchars($paq['nombre_servicio']) ?> ($<?= number_format((float)$paq['precio_servicio'], 2) ?> / persona)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3 bg-light p-3 rounded border" id="contenedor_slider_personas">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="num_personas_slider" class="form-label fw-bold mb-0">¿Cuántos invitados?</label>
                                    <span class="badge rounded-pill bg-primary fs-6 fw-bold" id="label_num_personas">0 personas</span>
                                </div>
                                <input type="range" class="form-range" id="num_personas_slider" name="num_personas" min="0" max="150" step="5" value="0" oninput="sincronizarPersonas(this.value)">
                                <small id="msg_aforo_jardin" class="text-danger fw-bold d-none mt-1">⚠️ Aforo máximo para Jardín: 150 personas.</small>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-bold mb-0">Servicios Extra</label>
                                <button type="button" class="btn btn-sm btn-secondary fw-bold" data-bs-toggle="modal" data-bs-target="#modalAgregarExtra" onclick="abrirModalExtra()">
                                    <i class="fa-solid fa-plus me-1"></i> Agregar Extra
                                </button>
                            </div>

                            <div id="lista_extras_contenedor" class="border rounded p-3 mb-3 bg-light text-center">
                                <span class="text-muted small fw-bold" id="empty_extras_msg">No hay servicios adicionales agregados</span>
                                <div id="lista_extras_items"></div>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-between mb-1 fw-semibold">
                                <span>Subtotal Paquete Base:</span>
                                <span id="txt_subtotal_paquete">$0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 fw-semibold">
                                <span>Subtotal Servicios Extra:</span>
                                <span id="txt_subtotal_extras">$0.00</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-2" style="color: var(--color-morado);">
                                <h5 class="fw-bold mb-0">TOTAL DE REFERENCIA:</h5>
                                <h3 class="fw-bold mb-0" id="txt_total_evento">$0.00</h3>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 fw-bold mt-4 py-2 fs-5">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Guardar Evento
                            </button>
                        </div>
                    </div>

                </div>
            </form>
        </main>
    </div>
</div>

<!-- MODAL AGREGAR EXTRA -->
<div class="modal fade" id="modalAgregarExtra" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-puzzle-piece me-2" style="color: var(--color-rosa);"></i>Agregar Servicio Extra</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Seleccionar Extra del Catálogo:</label>
                    <select id="modal_select_extra" class="form-select" onchange="evaluarModalExtraPrecio()">
                        <option value="" data-precio="0" data-tipo-cobro="fijo">-- Seleccionar Servicio --</option>
                        <?php foreach ($extras_catalogo as $ext): 
                            $es_p = ($ext['tipo_cobro'] === 'por_persona' || intval($ext['es_por_persona']) === 1) ? 'persona' : 'fijo';
                        ?>
                            <option value="<?= $ext['id_servicio'] ?>" 
                                    data-nombre="<?= htmlspecialchars($ext['nombre_servicio']) ?>"
                                    data-precio="<?= $ext['precio_servicio'] ?>"
                                    data-tipo-cobro="<?= $es_p ?>">
                                <?= htmlspecialchars($ext['nombre_servicio']) ?> ($<?= number_format((float)$ext['precio_servicio'], 2) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="wrapper_slider_extra_generar" class="mb-3 d-none bg-light p-3 rounded border">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fw-bold mb-0">Número de Personas:</label>
                        <span class="badge bg-primary fs-6 fw-bold" id="lbl_val_slider_extra_generar">50 personas</span>
                    </div>
                    <input type="range" class="form-range" id="slider_extra_personas_generar" min="5" max="300" step="5" value="50" oninput="sincronizarSliderExtraGenerar()">
                </div>

                <div class="fw-bold fs-6 mt-2" style="color: var(--color-morado);" id="lbl_subtotal_extra_preview"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="confirmarAgregarExtra()">Agregar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
let extrasAgregados = [];
let modalExtraBootstrap = null;

document.addEventListener('DOMContentLoaded', function() {
    modalExtraBootstrap = new bootstrap.Modal(document.getElementById('modalAgregarExtra'));
    filtrarPorUbicacion();

    let campoComentarios = document.getElementById('comentarios_evento');
    let contadorComentarios = document.getElementById('contador_comentarios_evento');

    if (campoComentarios && contadorComentarios) {
        const actualizarContadorComentarios = () => {
            const restante = 500 - campoComentarios.value.length;
            contadorComentarios.textContent = `${campoComentarios.value.length}/500`;
            contadorComentarios.className = restante < 0 ? 'form-text text-danger' : 'form-text text-muted';
        };

        campoComentarios.addEventListener('input', actualizarContadorComentarios);
        actualizarContadorComentarios();
    }
});

function evaluarClienteExistente() {
    let select = document.getElementById('id_cliente_existente');
    let seccionNuevo = document.getElementById('seccion-cliente-nuevo');
    let inputsNuevo = seccionNuevo.querySelectorAll('input');

    if (select.value !== '') {
        seccionNuevo.style.opacity = '0.4';
        inputsNuevo.forEach(i => { i.value = ''; i.disabled = true; });
    } else {
        seccionNuevo.style.opacity = '1';
        inputsNuevo.forEach(i => { i.disabled = false; });
    }
}

function sincronizarPersonas(valor) {
    let salon = document.getElementById('salon_evento').value.toLowerCase();
    let slider = document.getElementById('num_personas_slider');
    let msgAforo = document.getElementById('msg_aforo_jardin');

    if (salon === 'jardin' && parseInt(valor) > 150) {
        slider.value = 150;
        valor = 150;
        msgAforo.classList.remove('d-none');
    } else {
        msgAforo.classList.add('d-none');
    }

    document.getElementById('label_num_personas').innerText = valor + ' personas';
    actualizarCalculosEventos();
}

function filtrarPorUbicacion() {
    let salon = document.getElementById('salon_evento').value.toLowerCase();
    let esJardin = (salon === 'jardin');
    let slider = document.getElementById('num_personas_slider');
    
    if (esJardin) {
        slider.max = 150;
        if (parseInt(slider.value) > 150) {
            slider.value = 150;
            sincronizarPersonas(150);
        }
    } else {
        slider.max = 300;
    }

    let selectPaq = document.getElementById('select_paquete_base');
    Array.from(selectPaq.options).forEach(opt => {
        let ubi = opt.getAttribute('data-ubicacion') || 'ambos';
        if (opt.value === '') return;

        if (ubi === 'ambos' || (esJardin && ubi === 'jardin') || (!esJardin && ubi === 'carmelo')) {
            opt.style.display = 'block';
            opt.disabled = false;
        } else {
            opt.style.display = 'none';
            opt.disabled = true;
        }
    });

    actualizarCalculosEventos();
}

function abrirModalExtra() {
    document.getElementById('modal_select_extra').value = '';
    document.getElementById('slider_extra_personas_generar').value = '50';
    document.getElementById('wrapper_slider_extra_generar').classList.add('d-none');
    document.getElementById('lbl_subtotal_extra_preview').innerText = '';
}

function evaluarModalExtraPrecio() {
    let select = document.getElementById('modal_select_extra');
    let opt = select.options[select.selectedIndex];
    let wrapperSlider = document.getElementById('wrapper_slider_extra_generar');
    let lblSubtotal = document.getElementById('lbl_subtotal_extra_preview');

    if (opt && opt.value) {
        let precio = parseFloat(opt.getAttribute('data-precio')) || 0;
        let tipoCobro = opt.getAttribute('data-tipo-cobro');

        if (tipoCobro === 'persona') {
            wrapperSlider.classList.remove('d-none');
            let cant = parseInt(document.getElementById('slider_extra_personas_generar').value) || 50;
            let total = precio * cant;
            lblSubtotal.innerText = `Subtotal estimado: $${precio.toFixed(2)} × ${cant} personas = $${total.toLocaleString('es-MX', {minimumFractionDigits: 2})}`;
        } else {
            wrapperSlider.classList.add('d-none');
            lblSubtotal.innerText = `Costo Fijo Único: $${precio.toLocaleString('es-MX', {minimumFractionDigits: 2})}`;
        }
    } else {
        wrapperSlider.classList.add('d-none');
        lblSubtotal.innerText = '';
    }
}

function sincronizarSliderExtraGenerar() {
    let val = document.getElementById('slider_extra_personas_generar').value;
    document.getElementById('lbl_val_slider_extra_generar').innerText = val + ' personas';
    evaluarModalExtraPrecio();
}

function confirmarAgregarExtra() {
    let select = document.getElementById('modal_select_extra');
    let id = select.value;
    if (!id) return;

    let opt = select.options[select.selectedIndex];
    let nombre = opt.getAttribute('data-nombre');
    let precio = parseFloat(opt.getAttribute('data-precio')) || 0;
    let tipoCobro = opt.getAttribute('data-tipo-cobro');

    let cantidad = 1;
    let subtotal = precio;

    if (tipoCobro === 'persona') {
        cantidad = parseInt(document.getElementById('slider_extra_personas_generar').value) || 50;
        subtotal = precio * cantidad;
    }

    extrasAgregados.push({
        id_servicio: id,
        nombre: nombre,
        precio: precio,
        cantidad: cantidad,
        subtotal: subtotal,
        es_persona: (tipoCobro === 'persona')
    });

    renderizarExtrasUI();
    actualizarCalculosEventos();
    modalExtraBootstrap.hide();
}

function renderizarExtrasUI() {
    let contenedorMsg = document.getElementById('empty_extras_msg');
    let contenedorItems = document.getElementById('lista_extras_items');
    contenedorItems.innerHTML = '';

    if (extrasAgregados.length === 0) {
        contenedorMsg.classList.remove('d-none');
        return;
    }

    contenedorMsg.classList.add('d-none');

    extrasAgregados.forEach((item, index) => {
        let div = document.createElement('div');
        div.className = 'd-flex justify-content-between align-items-center border-bottom py-2 text-start';
        
        let detalleTexto = item.es_persona ? `(${item.cantidad} personas x $${item.precio.toFixed(2)})` : `($${item.precio.toFixed(2)} Pago Único)`;

        div.innerHTML = `
            <div>
                <strong class="text-dark fs-6">${item.nombre}</strong> <small class="text-muted">${detalleTexto}</small>
                <input type="hidden" name="extras_id[]" value="${item.id_servicio}">
                <input type="hidden" name="extras_cant[]" value="${item.cantidad}">
            </div>
            <div class="d-flex align-items-center">
                <span class="fw-bold me-2" style="color: var(--color-morado);">$${item.subtotal.toFixed(2)}</span>
                <button type="button" class="btn btn-danger btn-sm py-0 px-2 fw-bold" onclick="eliminarExtra(${index})">&times;</button>
            </div>
        `;
        contenedorItems.appendChild(div);
    });
}

function eliminarExtra(index) {
    extrasAgregados.splice(index, 1);
    renderizarExtrasUI();
    actualizarCalculosEventos();
}

function actualizarCalculosEventos() {
    let selectPaq = document.getElementById('select_paquete_base');
    let optPaq = selectPaq.options[selectPaq.selectedIndex];
    
    let subtotalPaquete = 0;
    let numPersonas = parseInt(document.getElementById('num_personas_slider').value) || 0;

    if (optPaq && optPaq.value) {
        let precioUnitario = parseFloat(optPaq.getAttribute('data-precio')) || 0;
        subtotalPaquete = precioUnitario * numPersonas;
    }

    let subtotalExtras = extrasAgregados.reduce((sum, item) => sum + item.subtotal, 0);
    let totalGeneral = subtotalPaquete + subtotalExtras;

    document.getElementById('txt_subtotal_paquete').innerText = '$' + subtotalPaquete.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('txt_subtotal_extras').innerText = '$' + subtotalExtras.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('txt_total_evento').innerText = '$' + totalGeneral.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}
</script>

</body>
</html>

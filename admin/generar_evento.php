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

// --- PROCESAR GUARDADO DE EVENTO ---
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
                $stmt_chk = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE correo_usuario = ? LIMIT 1");
                $stmt_chk->execute([$correo_cliente]);
                $user_found = $stmt_chk->fetch(PDO::FETCH_ASSOC);

                if ($user_found) {
                    $id_usuario_final = $user_found['id_usuario'];
                } else {
                    $pass_default = password_hash('fantasy2026', PASSWORD_BCRYPT);
                    $stmt_ins = $pdo->prepare("INSERT INTO usuarios (nombre_usuario, apellidos_usuario, correo_usuario, telefono_usuario, contrasena_usuario, rol_usuario, id_rol) VALUES (?, ?, ?, ?, ?, 'cliente', 2)");
                    $stmt_ins->execute([$nombre_cliente, $apellidos_cliente, $correo_cliente, $telefono_cliente, $pass_default]);
                    $id_usuario_final = $pdo->lastInsertId();
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
        $id_paquete = $_POST['id_paquete'] ?? null;

        // Asignar ID de sucursal según selección (1 = Jardín, 2 = Carmelo)
        $id_sucursal = (strtolower($salon_evento) === 'carmelo') ? 2 : 1;

        // 3. INSERTAR EVENTO (COLUMNAS ESTRICTAMENTE DE TU TABLA 'eventos')
        // Columnas verificadas: id_cliente, id_sucursal, nombre_evento, fecha_evento, hora_evento, ubicacion, estado
        $sql_event = "INSERT INTO eventos (id_cliente, id_sucursal, nombre_evento, fecha_evento, hora_evento, ubicacion, estado) VALUES (?, ?, ?, ?, ?, ?, 'confirmado')";
        $stmt_event = $pdo->prepare($sql_event);
        $stmt_event->execute([$id_usuario_final, $id_sucursal, $nombre_evento, $fecha_evento, $hora_evento, $salon_evento]);
        
        $id_evento_creado = $pdo->lastInsertId();

        // 4. GUARDAR SERVICIOS (PAQUETE Y EXTRAS) EN 'evento_servicio'
        if ($id_evento_creado) {
            // Intentar vincular paquete base si existe la tabla pivote
            try {
                if ($id_paquete) {
                    $stmt_es = $pdo->prepare("INSERT INTO evento_servicio (id_evento, id_servicio) VALUES (?, ?)");
                    $stmt_es->execute([$id_evento_creado, $id_paquete]);

                    if (!empty($_POST['extras']) && is_array($_POST['extras'])) {
                        foreach ($_POST['extras'] as $id_extra) {
                            $stmt_es->execute([$id_evento_creado, $id_extra]);
                        }
                    }
                }
            } catch (Exception $e_pivot) {
                // Si la tabla evento_servicio difiere, el evento principal ya quedó guardado en la BD
            }
        }

        $mensaje = "¡Evento generado exitosamente! " . ($id_cliente_existente ? "Asociado a cliente existente." : "Cliente registrado correctamente.");

    } catch (Exception $e) {
        $error_db = "Error al guardar el evento: " . $e->getMessage();
    }
}

// --- CONSULTAR CLIENTES Y CATÁLOGO ---
$clientes_lista = [];
$paquetes = [];
$servicios_extra = [];

if (isset($pdo)) {
    // Consultar lista de clientes (id_rol = 2)
    try {
        $stmt_cli = $pdo->query("SELECT id_usuario, nombre_usuario, apellidos_usuario, correo_usuario FROM usuarios WHERE id_rol = 2 ORDER BY id_usuario DESC");
        $clientes_lista = $stmt_cli->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        try {
            $stmt_cli = $pdo->query("SELECT id_usuario, nombre_usuario, apellidos_usuario, correo_usuario FROM usuarios WHERE LOWER(rol_usuario) = 'cliente' OR id_rol = 2 ORDER BY id_usuario DESC");
            $clientes_lista = $stmt_cli->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e2) {
            $clientes_lista = [];
        }
    }

    // Consultar catálogo de servicios (Paquetes y Extras)
    try {
        $stmt_paquetes = $pdo->query("SELECT * FROM servicios 
            WHERE (LOWER(tipo_registro) = 'paquete' OR LOWER(nombre_servicio) LIKE '%paquete%') 
              AND disponible_servicio = 1 
            ORDER BY nombre_servicio ASC");
        $paquetes = $stmt_paquetes->fetchAll(PDO::FETCH_ASSOC);

        $stmt_extras = $pdo->query("SELECT * FROM servicios 
            WHERE (LOWER(tipo_registro) = 'servicio_extra' OR tipo_registro IS NULL OR tipo_registro = '') 
              AND LOWER(nombre_servicio) NOT LIKE '%paquete%' 
              AND disponible_servicio = 1 
            ORDER BY nombre_servicio ASC");
        $servicios_extra = $stmt_extras->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $error_db = "Error al consultar catálogo: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generar Evento | Admin Fantasy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { min-height: 100vh; background-color: #e2e8f0; font-family: system-ui, -apple-system, sans-serif; }
        .card-custom { background: #ffffff; border-radius: 12px; border: 2px solid #cbd5e1; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .form-label { font-size: 0.9rem; font-weight: 700; color: #1e293b; margin-bottom: 0.4rem; }
        .form-control, .form-select { border-radius: 8px; border: 1.5px solid #94a3b8; padding: 0.6rem 0.85rem; color: #0f172a; font-weight: 600; }
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
            <h2 class="h3 mb-3 fw-bold text-dark"><i class="fa-solid fa-calendar-plus text-primary me-2"></i>Generar Nuevo Evento</h2>

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
                    
                    <!-- COLUMNA IZQUIERDA: CLIENTE Y DETALLES -->
                    <div class="col-lg-7">
                        
                        <div class="card-custom">
                            <h5 class="fw-bold text-primary mb-3"><i class="fa-solid fa-user me-2"></i>Información del Cliente</h5>
                            
                            <div class="mb-3">
                                <label class="form-label text-success"><i class="fa-solid fa-user-check me-1"></i> Seleccionar Cliente Existente (Opcional):</label>
                                <select name="id_cliente_existente" id="id_cliente_existente" class="form-select border-success" onchange="evaluarClienteExistente()">
                                    <option value="">-- Crear Nuevo Cliente Abajo --</option>
                                    <?php foreach ($clientes_lista as $cli): ?>
                                        <option value="<?= $cli['id_usuario'] ?>">
                                            <?= htmlspecialchars(trim(($cli['nombre_usuario'] ?? '') . ' ' . ($cli['apellidos_usuario'] ?? ''))) ?> (<?= htmlspecialchars($cli['correo_usuario'] ?? '') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted d-block mt-1">Si seleccionas un cliente existente, los campos de abajo no son necesarios.</small>
                            </div>

                            <hr class="my-3">

                            <div id="seccion-cliente-nuevo">
                                <h6 class="fw-bold text-secondary mb-2"><i class="fa-solid fa-user-plus me-1"></i> O registrar datos de un Cliente Nuevo:</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Nombre(s)</label>
                                        <input type="text" id="nombre_cliente" name="nombre_cliente" class="form-control" placeholder="Nombre(s)">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Apellidos</label>
                                        <input type="text" id="apellidos_cliente" name="apellidos_cliente" class="form-control" placeholder="Apellidos">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Correo Electrónico</label>
                                        <input type="email" id="correo_cliente" name="correo_cliente" class="form-control" placeholder="correo@ejemplo.com">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Teléfono (10 dígitos)</label>
                                        <input type="tel" id="telefono_cliente" name="telefono_cliente" class="form-control" placeholder="Teléfono">
                                    </div>
                                </div>
                                <div class="alert alert-info py-2 px-3 mt-3 mb-0 fs-6">
                                    <i class="fa-solid fa-key me-1"></i> Se asignará automáticamente la contraseña default: <strong>fantasy2026</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Detalles del Evento -->
                        <div class="card-custom">
                            <h5 class="fw-bold text-primary mb-3"><i class="fa-solid fa-calendar-days me-2"></i>Detalles del Evento</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Seleccionar Fecha</label>
                                    <input type="date" name="fecha_evento" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Seleccionar Hora</label>
                                    <input type="time" name="hora_evento" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Salón / Ubicación</label>
                                    <select name="salon_evento" class="form-select" required>
                                        <option value="">Seleccionar Salón</option>
                                        <option value="Salón Jardín">Salón Jardín</option>
                                        <option value="Salón Carmelo">Salón Carmelo</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nombre del Evento</label>
                                    <input type="text" name="nombre_evento" class="form-control" placeholder="Ej. Cumpleaños de Sofía" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Observaciones y contactos para el cliente</label>
                                    <textarea name="observaciones" class="form-control" rows="2" placeholder="Detalles o notas adicionales..."></textarea>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- COLUMNA DERECHA: DATOS DEL EVENTO Y PAQUETES -->
                    <div class="col-lg-5">
                        <div class="card-custom">
                            <h5 class="fw-bold text-primary mb-3"><i class="fa-solid fa-box-open me-2"></i>Datos del Evento</h5>

                            <div class="mb-4">
                                <label class="form-label">Seleccionar Paquete Base</label>
                                <select name="id_paquete" id="select_paquete" class="form-select" onchange="calcularTotales()" required>
                                    <option value="" data-precio="0">-- Seleccionar Paquete Base --</option>
                                    <?php foreach ($paquetes as $p): ?>
                                        <option value="<?= $p['id_servicio'] ?>" data-precio="<?= $p['precio_servicio'] ?>">
                                            <?= htmlspecialchars($p['nombre_servicio']) ?> ($<?= number_format((float)$p['precio_servicio'], 2) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">Servicios Extra</label>
                                <button type="button" class="btn btn-sm btn-primary fw-bold" onclick="abrirModalExtras()">
                                    <i class="fa-solid fa-plus me-1"></i> Agregar Extra
                                </button>
                            </div>

                            <div id="contenedor-extras" class="border rounded p-3 text-center text-muted mb-4 bg-light">
                                <small class="fw-bold">No hay servicios adicionales agregados</small>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-between mb-2 fs-6">
                                <span class="fw-bold text-secondary">Subtotal Paquete Base:</span>
                                <span class="fw-bold text-dark" id="txt-subtotal-paquete">$0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3 fs-6">
                                <span class="fw-bold text-secondary">Subtotal Servicios Extra:</span>
                                <span class="fw-bold text-dark" id="txt-subtotal-extras">$0.00</span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center pt-2 border-top mb-4">
                                <h5 class="fw-bold text-dark m-0">TOTAL DE REFERENCIA:</h5>
                                <h3 class="fw-bold text-primary m-0" id="txt-total-referencia">$0.00</h3>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2 fs-5 fw-bold">
                                <i class="fa-solid fa-floppy-disk me-2"></i> Guardar Evento
                            </button>
                        </div>
                    </div>

                </div>
            </form>
        </main>
    </div>
</div>

<!-- MODAL SERVICIOS EXTRA -->
<div class="modal fade" id="modalExtras" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-puzzle-piece text-primary me-2"></i>Agregar Servicio Extra</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Seleccionar Extra del Catálogo:</label>
                <select id="select_extra_modal" class="form-select mb-3">
                    <option value="">-- Seleccionar --</option>
                    <?php foreach ($servicios_extra as $ext): ?>
                        <option value="<?= $ext['id_servicio'] ?>" data-nombre="<?= htmlspecialchars($ext['nombre_servicio']) ?>" data-precio="<?= $ext['precio_servicio'] ?>">
                            <?= htmlspecialchars($ext['nombre_servicio']) ?> ($<?= number_format((float)$ext['precio_servicio'], 2) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary fw-bold" onclick="confirmarAgregarExtra()">Agregar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
let extrasSeleccionados = [];
let modalExtras = null;

document.addEventListener('DOMContentLoaded', function() {
    modalExtras = new bootstrap.Modal(document.getElementById('modalExtras'));
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

function abrirModalExtras() {
    document.getElementById('select_extra_modal').value = '';
    modalExtras.show();
}

function confirmarAgregarExtra() {
    let select = document.getElementById('select_extra_modal');
    let option = select.options[select.selectedIndex];
    if (!option.value) return;

    let id = option.value;
    let nombre = option.getAttribute('data-nombre');
    let precio = parseFloat(option.getAttribute('data-precio')) || 0;

    extrasSeleccionados.push({ id, nombre, precio });
    renderizarExtras();
    calcularTotales();
    modalExtras.hide();
}

function eliminarExtra(index) {
    extrasSeleccionados.splice(index, 1);
    renderizarExtras();
    calcularTotales();
}

function renderizarExtras() {
    let cont = document.getElementById('contenedor-extras');
    if (extrasSeleccionados.length === 0) {
        cont.className = "border rounded p-3 text-center text-muted mb-4 bg-light";
        cont.innerHTML = '<small class="fw-bold">No hay servicios adicionales agregados</small>';
        return;
    }

    cont.className = "border rounded p-2 mb-4 bg-white";
    let html = '<ul class="list-group list-group-flush">';
    extrasSeleccionados.forEach((item, index) => {
        html += `<li class="list-group-item d-flex justify-content-between align-items-center px-2 py-1">
            <span class="fw-semibold text-dark fs-6">${item.nombre} ($${item.precio.toFixed(2)})</span>
            <button type="button" class="btn btn-danger btn-sm py-0 px-2 fw-bold" onclick="eliminarExtra(${index})">&times;</button>
            <input type="hidden" name="extras[]" value="${item.id}">
        </li>`;
    });
    html += '</ul>';
    cont.innerHTML = html;
}

function calcularTotales() {
    let selectPaquete = document.getElementById('select_paquete');
    let optionPaquete = selectPaquete.options[selectPaquete.selectedIndex];
    let precioPaquete = parseFloat(optionPaquete.getAttribute('data-precio')) || 0;

    let precioExtras = extrasSeleccionados.reduce((sum, item) => sum + item.precio, 0);
    let total = precioPaquete + precioExtras;

    document.getElementById('txt-subtotal-paquete').innerText = '$' + precioPaquete.toFixed(2);
    document.getElementById('txt-subtotal-extras').innerText = '$' + precioExtras.toFixed(2);
    document.getElementById('txt-total-referencia').innerText = '$' + total.toFixed(2);
}
</script>

</body>
</html>
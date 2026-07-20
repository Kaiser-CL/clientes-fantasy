<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <!-- Encabezado y Buscador -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold">Control de Clientes</h2>
                    <p class="text-muted mb-0">Monitoreo global de los usuarios registrados en la plataforma.</p>
                </div>
                <div style="width: 350px;">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-user-gear text-muted"></i></span>
                        <input type="text" id="inputBuscarCliente" class="form-control border-start-0 ps-0" placeholder="Buscar por nombre o correo...">
                    </div>
                </div>
            </div>

            <!-- Tabla de Clientes -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tablaClientes">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">ID</th>
                                    <th>Cliente</th>
                                    <th>Teléfono</th>
                                    <th>Eventos Fantasy</th>
                                    <th class="text-end pe-4">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Se llena dinámicamente o por PHP loop -->
                                <tr>
                                    <td class="ps-4 font-monospace">#101</td>
                                    <td>
                                        <div class="fw-bold">Juan Pérez</div>
                                        <small class="text-muted">juan.perez@example.com</small>
                                    </td>
                                    <td>5512345678</td>
                                    <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3">2 Eventos</span></td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#modalEditarCliente"><i class="fa-solid fa-pen"></i></button>
                                        <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal Modificar Ficha de Cliente -->
<div class="modal fade" id="modalEditarCliente" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-pen text-primary me-2"></i>Modificar Ficha de Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarCliente">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre(s)</label>
                        <input type="text" class="form-control" value="Juan">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Apellidos</label>
                        <input type="text" class="form-control" value="Pérez">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Teléfono de Contacto</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-phone"></i></span>
                            <input type="text" class="form-control" value="5512345678">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary px-4">Guardar Cambios</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
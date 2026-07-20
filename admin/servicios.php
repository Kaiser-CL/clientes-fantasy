<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <!-- Encabezado con Botón Flotante/Acción -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold">Editor de Catálogos Maestros</h2>
                    <p class="text-muted mb-0">Edita costos y lógicas de cobro en tiempo real para cotizaciones y app móvil.</p>
                </div>
                <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNuevoItem">
                    <i class="fa-solid fa-circle-plus me-2"></i>Nuevo Paquete o Servicio
                </button>
            </div>

            <!-- Navegación por Pestañas (Tabs) -->
            <ul class="nav nav-tabs nav-fill mb-4 border-bottom-0" id="catalogoTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold border-0 shadow-sm rounded-top-3 me-2" id="paquetes-tab" data-bs-toggle="tab" data-bs-target="#paquetes-tab-pane" type="button"><i class="fa-solid fa-box me-2"></i>Paquetes Base</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold border-0 shadow-sm rounded-top-3" id="servicios-tab" data-bs-toggle="tab" data-bs-target="#servicios-tab-pane" type="button"><i class="fa-solid fa-sliders me-2"></i>Servicios Extra y Complementos</button>
                </li>
            </ul>

            <div class="tab-content" id="catalogoTabContent">
                <!-- Tab 1: Paquetes Base -->
                <div class="tab-pane fade show active" id="paquetes-tab-pane">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-0">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">ID</th>
                                        <th>Nombre del Paquete</th>
                                        <th>Precio Base ($ por persona)</th>
                                        <th class="text-end pe-4">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="ps-4 font-monospace">#1</td>
                                        <td><input type="text" class="form-control border-0 bg-transparent fw-bold" value="Paquete Fantasy Premium"></td>
                                        <td>
                                            <div class="input-group input-group-sm" style="width: 150px;">
                                                <span class="input-group-text">$</span>
                                                <input type="number" class="form-control fw-bold text-primary" value="350">
                                            </div>
                                        </td>
                                        <td class="text-end pe-4">
                                            <button class="btn btn-sm btn-success"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Servicios Extra -->
                <div class="tab-pane fade" id="servicios-tab-pane">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-0">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">ID</th>
                                        <th>Nombre del Servicio</th>
                                        <th>Costo ($)</th>
                                        <th>Tipo de Cobro</th>
                                        <th class="text-end pe-4">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="ps-4 font-monospace">#12</td>
                                        <td><input type="text" class="form-control border-0 bg-transparent fw-bold" value="Mesa de Dulces Temática"></td>
                                        <td>
                                            <div class="input-group input-group-sm" style="width: 150px;">
                                                <span class="input-group-text">$</span>
                                                <input type="number" class="form-control fw-bold" value="2500">
                                            </div>
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm" style="width: 180px;">
                                                <option value="0">📦 Precio Fijo</option>
                                                <option value="1">👤 Por Persona</option>
                                            </select>
                                        </td>
                                        <td class="text-end pe-4">
                                            <button class="btn btn-sm btn-success"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal Agregar Nuevo Registro -->
<div class="modal fade" id="modalNuevoItem" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title fw-bold text-primary"><i class="fa-solid fa-plus-circle me-2"></i>Agregar Nuevo Registro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipo de Registro</label>
                        <select class="form-select">
                            <option value="paquete">📦 Paquete Base</option>
                            <option value="servicio">⚙️ Servicio Extra / Complemento</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre Completo</label>
                        <input type="text" class="form-control" placeholder="Ej: Show Infantil Especial">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Descripción</label>
                        <textarea class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Precio / Costo ($)</label>
                        <input type="number" class="form-control" placeholder="0.00">
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary px-4">Guardar Elemento</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold">Panel de Eventos Activos</h2>
                    <p class="text-muted mb-0">Contratos próximos registrados en la agenda.</p>
                </div>
                <div style="width: 350px;">
                    <input type="text" class="form-control" placeholder="Buscar por cliente o evento...">
                </div>
            </div>

            <!-- Tabla Activos -->
            <div class="card border-0 shadow-sm rounded-4 mb-5">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">ID</th>
                                    <th>Cliente</th>
                                    <th>Evento</th>
                                    <th>Fecha y Hora</th>
                                    <th>Espacio</th>
                                    <th>Invitados</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th class="text-end pe-4">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="ps-4 font-monospace">#501</td>
                                    <td>
                                        <div class="fw-bold">María Gómez</div>
                                        <small class="text-muted">maria@gmail.com</small>
                                    </td>
                                    <td><span class="fw-bold text-primary">XV AÑOS VALERIA</span></td>
                                    <td>2026-08-15 | 16:00</td>
                                    <td>Salón Carmelo</td>
                                    <td>150 pers.</td>
                                    <td class="fw-bold">$48,000.00</td>
                                    <td><span class="badge bg-success-subtle text-success rounded-pill px-3">CONFIRMADO</span></td>
                                    <td class="text-end pe-4">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light border-0" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                                <li><a class="dropdown-item" href="#"><i class="fa-solid fa-rotate me-2 text-warning"></i>Cambiar Estado</a></li>
                                                <li><a class="dropdown-item" href="#"><i class="fa-solid fa-pen me-2 text-primary"></i>Editar Evento</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item text-danger" href="#"><i class="fa-solid fa-trash me-2"></i>Eliminar</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Sección Histórico Acordeón -->
            <div class="accordion" id="accordionHistorico">
                <div class="accordion-item border-0 shadow-sm rounded-4 overflow-hidden">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed fw-bold fs-5 bg-white py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseHistorico">
                            <i class="fa-solid fa-clock-rotate-left me-3 text-secondary"></i>Ver Histórico de Eventos Finalizados
                        </button>
                    </h2>
                    <div id="collapseHistorico" class="accordion-collapse collapse" data-bs-parent="#accordionHistorico">
                        <div class="accordion-body p-0">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">ID</th>
                                        <th>Cliente</th>
                                        <th>Evento</th>
                                        <th>Fecha Concluida</th>
                                        <th>Total</th>
                                        <th class="text-end pe-4">Estatus</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="ps-4 font-monospace">#420</td>
                                        <td>Carlos López</td>
                                        <td class="fw-bold">BODA CARLOS Y SOFÍA</td>
                                        <td>2026-05-10</td>
                                        <td class="fw-bold">$62,000.00</td>
                                        <td class="text-end pe-4"><span class="badge bg-secondary-subtle text-secondary rounded-pill px-3">FINALIZADO</span></td>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
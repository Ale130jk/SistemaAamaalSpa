<?php
$page_title = "Gestión de Pagos";
 include_once __DIR__ . '/header.php';

// Verificar que el usuario esté autenticado (el middleware debe estar en header.php)
// Si necesitas validación adicional, descomenta:
// require_login();
?>

<!-- Contenedor Principal -->
<div class="main-header">
    <h2 class="page-title">Gestión de Pagos y Ventas</h2>
    <div>
        <!-- Botón para abrir modal de nuevo pago -->
        <button type="button" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#modalRegistrarPago">
            <i class="bi bi-plus-circle-fill"></i> Registrar Nuevo Pago
        </button>
    </div>
</div>
    <!-- Sección de Filtros y Búsqueda -->
    <div class="section-card mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="filtro-buscar" class="form-label">
                    <i class="bi bi-search"></i> Buscar Pago
                </label>
                <input type="text" class="form-control" id="filtro-buscar" 
                       placeholder="ID, Cliente, Método...">
            </div>
            <div class="col-md-3">
                <label for="filtro-metodo" class="form-label">Método de Pago</label>
                <select class="form-select" id="filtro-metodo">
                    <option value="efectivo">Efectivo</option>
                    <option value="tarjeta">Tarjeta</option>
                    <option value="transferencia">Transferencia</option>
                    <option value="otro">Otro</option>
                </select>
            </div>

            <div class="col-md-2">
                <button type="button" class="btn btn-primary w-100 right" id="btn-aplicar-filtros">
                    <i class="bi bi-funnel-fill"></i> Filtrar
                </button>
            </div>
        </div>
    </div>
    <!-- Tabla de Pagos Registrados -->
    <div class="section-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="section-title mb-0">
                <i class="bi bi-table"></i> Historial de Pagos
            </h3>
        </div>

        <!-- Tabla Responsive -->
        <div class="table-container">
            <table class="table table-hover table-striped" id="tabla-pagos">
                <thead>
                    <tr>
                        <th style="width: 80px;">ID</th>
                        <th style="width: 120px;">Fecha</th>
                        <th>Cliente</th>
                        <th style="width: 120px;">Monto</th>
                        <th style="width: 120px;">Método</th>
                        <th style="width: 100px;">Estado</th>
                        <th style="width: 150px;" class="text-center">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>

        <!-- Paginación -->
        <nav aria-label="Paginación de pagos" class="mt-3">
            <ul class="pagination justify-content-center" id="paginacion-pagos">
                
            </ul>
        </nav>
    </div>

</div>

<!-- ===== MODAL: REGISTRAR NUEVO PAGO ===== -->
<div class="modal fade" id="modalRegistrarPago" tabindex="-1" aria-labelledby="modalRegistrarPagoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalRegistrarPagoLabel">
                    <i class="bi bi-cash-stack"></i> Registrar Nuevo Pago
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formRegistrarPago">
                    <!-- Token CSRF para seguridad -->
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    
                    <!-- Datos del Pago -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="pago_monto" class="form-label">
                                Monto a Pagar (S/) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">S/</span>
                                <input type="number" step="0.01" min="0.01" class="form-control form-control-lg" 
                                       id="pago_monto" name="monto" required placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="pago_metodo" class="form-label">
                                Método de Pago <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-lg" id="pago_metodo" name="metodo_pago" required>
                                <option value="" disabled selected>Seleccione método...</option>
                                <option value="efectivo">💵 Efectivo</option>
                                <option value="tarjeta">💳 Tarjeta (Débito/Crédito)</option>
                                <option value="transferencia">📱 Transferencia (Yape/Plin)</option>
                                <option value="otro">📝 Otro</option>
                            </select>
                        </div>
                    </div>

                    <hr class="my-4">
                    
                    <!-- Asociación Opcional -->
                    <h6 class="mb-3">
                        <i class="bi bi-link-45deg"></i> Asociar Pago (Opcional)
                    </h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="pago_cliente_id" class="form-label">Cliente</label>
                            <select class="form-select" id="pago_cliente_id" name="cliente_id">
                                <option value="">-- Sin cliente --</option>
                                <!-- Opciones cargadas por AJAX -->
                            </select>
                            <small class="form-text text-muted">Opcional: asociar a un cliente existente</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="pago_reserva_id" class="form-label">ID Reserva</label>
                            <input type="number" class="form-control" id="pago_reserva_id" 
                                   name="reserva_id" placeholder="Ej: 123">
                            <small class="form-text text-muted">Opcional: ID de reserva a pagar</small>
                        </div>
                    </div>

                    <!-- Notas Adicionales -->
                    <div class="mb-3">
                        <label for="pago_notas" class="form-label">Notas / Observaciones</label>
                        <textarea class="form-control" id="pago_notas" name="notas" rows="3" 
                                  placeholder="Notas adicionales sobre el pago (opcional)"></textarea>
                    </div>

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg"></i> Cancelar
                </button>
                <button type="submit" form="formRegistrarPago" class="btn btn-success btn-lg">
                    <i class="bi bi-check-circle-fill"></i> Registrar Pago
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===== MODAL: VER DETALLE DE PAGO ===== -->
<div class="modal fade" id="modalDetallePago" tabindex="-1" aria-labelledby="modalDetallePagoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="modalDetallePagoLabel">
                    <i class="bi bi-receipt"></i> Detalle del Pago
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="detalle-pago-contenido">
                <!-- Contenido cargado dinámicamente por AJAX -->
                <div class="text-center py-4">
                    <div class="spinner-border text-info" role="status"></div>
                    <p class="mt-2">Cargando información...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="btn-imprimir-comprobante">
                    <i class="bi bi-printer-fill"></i> Imprimir
                </button>
            </div>
        </div>
    </div>
</div>
<?php
include_once __DIR__ . '/footer.php';
?>

<script src="/public/js/pagos.js"></script>
<?php
require_once __DIR__ . '/../config/auth.php';
require_login();
$page_title = "Gestión de Pagos";

include_once __DIR__ . '/header.php'; 
?>

<div class="main-header d-flex justify-content-between align-items-center mb-4">
    <h2 class="page-title mb-0 text-primary fw-bold"><i class="bi bi-cash-coin"></i> Gestión de Pagos</h2>
    <button type="button" class="btn btn-success btn-lg shadow-sm" id="btnNuevoPago">
        <i class="bi bi-plus-circle-fill"></i> Registrar Nuevo Pago
    </button>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-5">
                <label for="filtro-buscar" class="form-label fw-bold text-muted small">
                    <i class="bi bi-search"></i> Buscar en Historial
                </label>
                <input type="text" class="form-control" id="filtro-buscar" placeholder="Escribe nombre, monto o método...">
            </div>
        </div>
    </div>
</div>

<div class="card shadow border-0">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold text-secondary"><i class="bi bi-table"></i> Historial de Pagos</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabla-pagos">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Concepto / Reserva</th>
                        <th>Monto</th>
                        <th>Método</th>
                        <th>Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRegistrarPago" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-cash-stack"></i> Registrar Pago</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body p-4">
                <form id="formRegistrarPago">
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold text-primary">¿Qué deseas cobrar?</label>
                        <select class="form-select form-select-lg" id="pago_reserva_id" name="reserva_id">
                            <option value="">-- Venta Directa / Sin Reserva --</option>
                        </select>
                        <div class="form-text">Si seleccionas una reserva, el monto se llena solo.</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Monto (S/)</label>
                            <input type="number" step="0.01" min="0.01" class="form-control form-control-lg fw-bold text-success" id="pago_monto" name="monto" required placeholder="0.00">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Método de Pago</label>
                            <select class="form-select form-select-lg" name="metodo_pago" required>
                                <option value="efectivo">💵 Efectivo</option>
                                <option value="tarjeta">💳 Tarjeta</option>
                                <option value="yape">📱 Yape/Plin</option>
                                <option value="transferencia">🏦 Transferencia</option>
                                <option value="otro">📝 Otro</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3" id="div-cliente-manual">
                        <label class="form-label">Cliente (Opcional)</label>
                        <select class="form-select" id="pago_cliente_id" name="cliente_id">
                            <option value="">-- Público General --</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notas</label>
                        <textarea class="form-control" name="notas" rows="2" placeholder="Detalles opcionales"></textarea>
                    </div>
                </form>
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="formRegistrarPago" class="btn btn-success btn-lg px-4">
                    <i class="bi bi-check-circle-fill"></i> Confirmar Pago
                </button>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/footer.php'; ?>

<script src="<?php echo BASE_URL; ?>public/js/pagos.js"></script>
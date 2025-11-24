<?php
require_once __DIR__ . '/../config/auth.php';
require_login();
$page_title = "Gestión de Gastos";
include_once __DIR__ . '/header.php';
?>

<div class="main-header">
    <h2 class="page-title">Gestión de Gastos y Compras</h2>
    <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalNuevoGasto">
        <i class="bi bi-plus-circle"></i> Registrar Gasto
    </button>
</div>

<div class="content-section">
    <div class="section-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="section-title mb-0">Historial de Movimientos</h3>
            
            <div class="input-group w-auto">
                <span class="input-group-text bg-white border-end-0">
                    <i class="bi bi-search text-muted"></i>
                </span>
                <input type="text" id="txtBuscarGasto" class="form-control border-start-0 ps-0" placeholder="Buscar gasto" autocomplete="off">
            </div>
        </div>

        <div class="table-container">
            <table class="table table-hover align-middle" id="tablaCompras">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Descripción</th>
                        <th class="text-end">Monto</th>
                    </tr>
                </thead>
                <tbody>
                    </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNuevoGasto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Registrar Salida de Dinero</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formNuevoGasto">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Monto Total (S/)</label>
                        <div class="input-group">
                            <span class="input-group-text">S/</span>
                            <input type="number" step="0.01" class="form-control form-control-lg" name="total" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" name="notas" rows="3" placeholder="Ej: Pago de recibo de luz..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Guardar Gasto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/footer.php'; ?>

<script src="../public/js/compras.js"></script>
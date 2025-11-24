<?php
require_once __DIR__ . '/../config/auth.php';
require_login();
$page_title = "Reportes Detallados";
include_once __DIR__ . '/header.php';
?>

<div class="main-header">
    <h2 class="page-title">📊Generar Reportes</h2>
</div>

<div class="content-section">

    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-3"><i class="bi bi-calendar-range"></i> Seleccionar Rango de Fechas</h5>
            <form id="form-generar-reporte" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Fecha Inicio</label>
                    <input type="date" class="form-control" name="fecha_inicio" 
                           value="<?php echo date('Y-m-01'); ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fecha Fin</label>
                    <input type="date" class="form-control" name="fecha_fin" 
                           value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Generar Reporte
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="contenedor-resultados-reporte" style="display: none;">

    </div>
>
    <div class="card shadow-sm mt-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-table"></i> Detalle de Movimientos</h5>
            <div>
                <button id="btn-exportar-resultados" class="btn btn-success btn-sm me-1" data-params="">
                    <i class="bi bi-file-earmark-excel"></i> Excel
                </button>
                <button id="btn-descargar-pdf" class="btn btn-danger btn-sm" data-params="">
                    <i class="bi bi-file-earmark-pdf"></i> PDF
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0" id="tablaReporteDetalle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Concepto</th>
                            <th>Método</th>
                            <th class="text-end">Monto</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-reporte-pagos">
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
                                Selecciona un rango y haz clic en "Generar Reporte".
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>


<?php include_once __DIR__ . '/footer.php'; ?>

<script src="../public/js/reportes.js"></script>
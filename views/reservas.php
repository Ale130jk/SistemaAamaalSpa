<?php
$page_title = "Calendario";
require_once __DIR__ . '/../config/auth.php';
require_login();
include_once __DIR__ . '/header.php';
?>

<div class="main-header">
    <h2 class="page-title">Calendario de Reservas</h2>
    <button class="btn btn-primary btn-lg" id="btnNuevaReservaManual">
        <i class="bi bi-plus-lg"></i> Agendar Cita
    </button>
</div>

<div class="content-section">
    <div class="section-card">
        <div class="mb-3 position-relative">
             <input type="text" id="buscadorReservas" class="form-control" placeholder="🔍 Buscar reserva..." autocomplete="off">
             <div id="listaResultadosBusqueda" class="list-group position-absolute w-100 shadow" style="z-index: 1000; display: none; max-height: 200px; overflow-y: auto;"></div>
        </div>
        <div id="calendario-reservas"></div>
    </div>
</div>

<div class="modal fade" id="modalNuevaReserva" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Nueva Cita</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form id="formNuevaReserva">
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Cliente</label><select class="form-select" id="select-cliente" name="cliente_id" required></select></div>
                    <div class="mb-3"><label class="form-label">Servicio</label><input type="text" class="form-control" name="servicio_texto" placeholder="Ej: Corte" required></div>
                    <div class="row">
                        <div class="col-6 mb-3"><label>Fecha</label><input type="datetime-local" class="form-control" id="input-fecha-inicio" name="fecha_inicio" required></div>
                        <div class="col-6 mb-3"><label>Precio</label><input type="number" step="0.01" class="form-control" name="precio" value="0.00"></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarReserva" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Editar Cita</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form id="formEditarReserva">
                <div class="modal-body">
                    <input type="hidden" id="edit_reserva_id" name="id"> <div class="mb-3"><label class="form-label">Cliente</label><select class="form-select" id="edit_cliente" name="cliente_id" required></select></div>
                    
                    <div class="mb-3"><label class="form-label">Servicio</label><input type="text" class="form-control" id="edit_servicio" name="servicio_texto" required></div>
                    
                    <div class="row">
                        <div class="col-6 mb-3"><label>Fecha</label><input type="datetime-local" class="form-control" id="edit_fecha" name="fecha_inicio" required></div>
                        <div class="col-6 mb-3"><label>Precio</label><input type="number" step="0.01" class="form-control" id="edit_precio" name="precio"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger me-auto" id="btnEliminarReserva">Cancelar Cita</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/footer.php'; ?>
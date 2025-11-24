<?php

require_once __DIR__ . '/../config/auth.php';
require_login();
$page_title = "Gestión de Clientes";
include_once __DIR__ . '/header.php';
?>

<div class="main-header">
    <h2 class="page-title">Gestión de Clientes</h2>
    <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#modalNuevoCliente">
        <i class="bi bi-person-plus-fill"></i> Nuevo Cliente
    </button>
</div>

<div class="content-section">
    <div class="section-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="section-title mb-0">Listado de Clientes</h3>
            
            <div class="input-group" style="width: 300px;">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control" id="buscadorClientes" placeholder="Buscar por nombre o teléfono...">
            </div>
        </div>
        
        <div class="table-container">
            <table class="table table-hover" id="tablaClientes">
                <thead>
                    <tr>
                        <th>ID</th> 
                        <th>Nombre Completo</th>
                        <th>Teléfono</th>
                        <th>Estado</th>
                        <th>Acciones</th> 
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="5" class="text-center">Cargando...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNuevoCliente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formNuevoCliente">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre *</label>
                        <input type="text" class="form-control" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Teléfono</label>
                        <input type="tel" class="form-control" name="telefono">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarCliente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditarCliente">
                <div class="modal-body">
                    <input type="hidden" id="edit_id" name="id"> <div class="mb-3">
                        <label class="form-label">Nombre *</label>
                        <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Teléfono</label>
                        <input type="tel" class="form-control" id="edit_telefono" name="telefono">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/footer.php'; ?>
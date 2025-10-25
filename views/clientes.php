<?php
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
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nombre Completo</th>
                        <th>Teléfono</th>
                        <th>Email</th>
                        <th>Estado</th>
                        </tr>
                </thead>
                <tbody id="tablaClientesBody">
                    <tr>
                        <td colspan="4" class="text-center">Cargando clientes...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNuevoCliente" tabindex="-1" aria-labelledby="modalNuevoClienteLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNuevoClienteLabel">Crear Nuevo Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formNuevoCliente">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    
                    <div class="mb-3">
                        <label for="cli_nombre" class="form-label">Nombre Completo *</label>
                        <input type="text" class="form-control" id="cli_nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="cli_telefono" class="form-label">Teléfono</label>
                        <input type="tel" class="form-control" id="cli_telefono" name="telefono">
                    </div>
                    <div class="mb-3">
                        <label for="cli_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="cli_email" name="email">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cliente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/footer.php'; ?>
<?php
require_once __DIR__ . '/../config/auth.php';
require_login();
$page_title = "Gestión de Inventario";
include_once __DIR__ . '/header.php';
?>

<div class="main-header">
    <h2 class="page-title">Inventario de Productos</h2>
    <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#modalNuevoProducto">
        <i class="bi bi-plus-lg"></i> Nuevo Producto
    </button>
</div>
<div class="content-section">
    <div class="section-card">
        
        <div class="row g-3 mb-4">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" id="filtroBusqueda" placeholder="Buscar por nombre o SKU...">
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="filtroCategoria">
                    <option value="">Todas las Categorías</option>
                    </select>
            </div>
            <div class="col-md-4">
                <select class="form-select" id="filtroEstado">
                    <option value="">Todos los Estados</option>
                    <option value="disponible">✅Disponible</option>
                    <option value="bajo">⚠️Poco Stock (<=5)</option>
                    <option value="agotado">⛔ Agotado</option>
                </select>
            </div>
        </div>

        <div class="table-container">
            <table class="table table-hover align-middle" id="tablaProductos">
                <thead class="table-light">
                    <tr>
                        <th style="width:15%">SKU</th>
                        <th style="width:30%">Producto</th>
                        <th style="width:15%">Categoría</th>
                        <th style="width:15%" class="text-end">Precio</th>
                        <th style="width:10%" class="text-center">Stock</th>
                        <th style="width:15%" class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="6" class="text-center py-5 text-muted">Cargando inventario...</td></tr>
                </tbody>
            </table>
            
            <div id="emptyState" class="text-center py-5 d-none">
                <i class="bi bi-box-seam display-4 text-muted"></i>
                <p class="mt-2 text-muted">No se encontraron productos con estos filtros.</p>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNuevoProducto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formNuevoProducto">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nombre del Producto *</label>
                            <input type="text" class="form-control" name="nombre" required placeholder="Ej: Crema Hidratante">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Categoría</label>
                            <input type="text" class="form-control" name="categoria" list="listaCategorias" placeholder="Ej: Facial">
                            <datalist id="listaCategorias"></datalist>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">SKU (Opcional)</label>
                            <input type="text" class="form-control" name="sku" placeholder="Auto">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Precio (S/) *</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="precio_venta" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Stock Inicial *</label>
                            <input type="number" min="0" class="form-control" name="stock" required>
                        </div>
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

<div class="modal fade" id="modalEditarProducto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditarProducto">
                <div class="modal-body">
                    <input type="hidden" id="edit_id" name="id">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nombre *</label>
                            <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Categoría</label>
                            <input type="text" class="form-control" id="edit_categoria" name="categoria">
                        </div>
                        <div class="col-md-6">
                            </div>
                        <div class="col-md-6">
                            <label class="form-label">Precio (S/) *</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="edit_precio" name="precio_venta" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Stock *</label>
                            <input type="number" min="0" class="form-control" id="edit_stock" name="stock" required>
                        </div>
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
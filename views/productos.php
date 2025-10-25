<?php
$page_title = "Inventario de Productos";
include_once __DIR__ . '/header.php';
?>

<div class="main-header">
    <h2 class="page-title">Inventario de Productos</h2>
    <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#modalNuevoProducto">
        <i class="bi bi-plus-circle-fill"></i> Nuevo Producto
    </button>
</div>

<div class="content-section">
    <div class="section-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="section-title mb-0">Listado de Productos</h3>
            <div class="input-group" style="width: 300px;">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control" id="buscadorProductos" placeholder="Buscar por SKU o nombre...">
            </div>
        </div>
        
        <div class="table-container">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Precio Venta</th>
                        <th>Stock Actual</th>
                        <th>Estado</th>
                        </tr>
                </thead>
                <tbody id="tablaProductosBody">
                    <tr>
                        <td colspan="6" class="text-center">Cargando productos...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNuevoProducto" tabindex="-1" aria-labelledby="modalNuevoProductoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNuevoProductoLabel">Crear Nuevo Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formNuevoProducto">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="prod_nombre" class="form-label">Nombre del Producto *</label>
                            <input type="text" class="form-control" id="prod_nombre" name="nombre" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="prod_sku" class="form-label">SKU (Código)</label>
                            <input type="text" class="form-control" id="prod_sku" name="sku">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="prod_categoria" class="form-label">Categoría</label>
                            <input type="text" class="form-control" id="prod_categoria" name="categoria">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="prod_precio" class="form-label">Precio Venta (S/) *</label>
                            <input type="number" step="0.01" class="form-control" id="prod_precio" name="precio_venta" required>
                        </div>
                         <div class="col-md-4 mb-3">
                            <label for="prod_stock" class="form-label">Stock Inicial *</label>
                            <input type="number" step="1" class="form-control" id="prod_stock" name="stock" value="0" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Producto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/footer.php'; ?>
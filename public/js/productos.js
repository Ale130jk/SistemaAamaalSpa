$(function () {
    const $tablaTbody = $('#tablaProductos tbody');
    const $emptyState = $('#emptyState');
    const $filtroBusqueda = $('#filtroBusqueda');
    const $filtroCategoria = $('#filtroCategoria');
    const $filtroEstado = $('#filtroEstado');
    const modalNuevo = new bootstrap.Modal(document.getElementById('modalNuevoProducto'));
    const modalEditar = new bootstrap.Modal(document.getElementById('modalEditarProducto'));
    function cargarInventario() {
        const params = {
            action: 'listar',
            q: $filtroBusqueda.val(),
            categoria: $filtroCategoria.val(),
            stock_status: $filtroEstado.val()
        };

        $.get('../controllers/ProductoController.php', params, function(res) {
            $tablaTbody.empty();
            if (res.success) {
                actualizarComboCategorias(res.categorias);
                if (res.data.length > 0) {
                    $emptyState.addClass('d-none');
                    $tablaTbody.show();
                    res.data.forEach(p => {
                        let badge = '';
                        if(p.estado_stock === 'agotado') badge = '<span class="badge bg-danger">AGOTADO</span>';
                        else if(p.estado_stock === 'bajo') badge = `<span class="badge bg-warning text-dark">BAJO (${p.stock})</span>`;
                        else badge = `<span class="badge bg-success-subtle text-success border border-success">${p.stock} unid.</span>`;

                        const precio = parseFloat(p.precio_venta).toFixed(2);

                        $tablaTbody.append(`
                            <tr>
                                <td><span class="font-monospace text-muted small">${p.sku}</span></td>
                                <td class="fw-bold">${p.nombre}</td>
                                <td><span class="badge bg-light text-dark border">${p.categoria || 'General'}</span></td>
                                <td class="text-end">S/ ${precio}</td>
                                <td class="text-center">${badge}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-primary btn-editar" data-id="${p.id}"><i class="bi bi-pencil"></i></button>
                                    <button class="btn btn-sm btn-outline-danger btn-eliminar" data-id="${p.id}"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        `);
                    });
                } else {
                    $tablaTbody.hide();
                    $emptyState.removeClass('d-none');
                }
            }
        }, 'json');
    }

    function actualizarComboCategorias(cats) {
        if ($('#filtroCategoria option').length <= 1 && cats.length > 0) {
            cats.forEach(c => {
                $('#filtroCategoria').append(`<option value="${c}">${c}</option>`);
                $('#listaCategorias').append(`<option value="${c}">`);
            });
        }
    }

    $filtroBusqueda.on('keyup', cargarInventario); 
    $filtroCategoria.on('change', cargarInventario);
    $filtroEstado.on('change', cargarInventario);
    $('#formNuevoProducto').on('submit', function(e) {
        e.preventDefault();
        Swal.fire({ title: 'Guardando..', didOpen: () => Swal.showLoading() });
        
        $.post('../controllers/ProductoController.php', $(this).serialize() + '&action=crear', function(res) {
            if(res.success) {
                Swal.fire('¡Éxito!', 'Producto registrado.', 'success');
                $('#formNuevoProducto')[0].reset();
                modalNuevo.hide();
                cargarInventario(); 
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }, 'json');
    });

    $tablaTbody.on('click', '.btn-editar', function() {
        const id = $(this).data('id');
        $.get('../controllers/ProductoController.php', { action: 'obtener', id: id }, function(res) {
            if(res.success) {
                $('#edit_id').val(res.data.id);
                $('#edit_nombre').val(res.data.nombre);
                $('#edit_categoria').val(res.data.categoria);
                $('#edit_precio').val(res.data.precio_venta);
                $('#edit_stock').val(res.data.stock);
                modalEditar.show();
            }
        }, 'json');
    });

    $('#formEditarProducto').on('submit', function(e) {
        e.preventDefault();
        Swal.fire({ title: 'Actualizando...', didOpen: () => Swal.showLoading() });
        $.post('../controllers/ProductoController.php', $(this).serialize() + '&action=actualizar', function(res) {
            if(res.success) {
                Swal.fire('Actualizado', 'Producto modificado.', 'success');
                modalEditar.hide();
                cargarInventario();
            } else { Swal.fire('Error', res.message, 'error'); }
        }, 'json');
    });


    $tablaTbody.on('click', '.btn-eliminar', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: '¿Eliminar?', text: "El producto no aparecerá en nuevas operaciones.", icon: 'warning',
            showCancelButton: true, confirmButtonText: 'Sí, eliminar', confirmButtonColor: '#d33'
        }).then((r) => {
            if(r.isConfirmed) {
                $.post('../controllers/ProductoController.php', { action: 'eliminar', id: id }, function(res) {
                    if(res.success) { Swal.fire('Eliminado', 'Producto desactivado.', 'success'); cargarInventario(); }
                    else { Swal.fire('Error', res.message, 'error'); }
                }, 'json');
            }
        });
    });

    cargarInventario();
});
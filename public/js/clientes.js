$(function () {
    const $tablaTbody = $('#tablaClientes tbody');
    const $formNuevo = $('#formNuevoCliente');
    const $modalNuevo = new bootstrap.Modal(document.getElementById('modalNuevoCliente'));
    const $formEditar = $('#formEditarCliente');
    const $modalEditarEl = document.getElementById('modalEditarCliente');
    const $modalEditar = $modalEditarEl ? new bootstrap.Modal($modalEditarEl) : null;

    $('input[name="telefono"]').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 9);
    });
    function cargarTabla() {
        $.ajax({
            url: '../controllers/ClienteController.php',
            type: 'GET',
            data: { action: 'listar' },
            dataType: 'json'
        }).done(function(response) {
            $tablaTbody.empty();
            if (response.success && response.data.length > 0) {
                $.each(response.data, function(i, cli) {
                    $tablaTbody.append(`
                        <tr>
                            <td>${cli.id}</td>
                            <td>${cli.nombre}</td>
                            <td>${cli.telefono || '-'}</td>
                            <td><span class="badge bg-success">${cli.estado}</span></td>
                            <td>
                                <button class="btn btn-sm btn-info btn-editar" data-id="${cli.id}"><i class="bi bi-pencil-square"></i></button>
                                <button class="btn btn-sm btn-danger btn-eliminar" data-id="${cli.id}"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    `);
                });
            } else {
                $tablaTbody.html('<tr><td colspan="5" class="text-center">No hay clientes registrados.</td></tr>');
            }
        });
    }
    $('#buscadorClientes').on('keyup', function() {
        const valor = $(this).val().toLowerCase();
        $("#tablaClientes tbody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(valor) > -1)
        });
    });
    $formNuevo.on('submit', function(e) {
        e.preventDefault();
        Swal.fire({ title: 'Guardando...', didOpen: () => Swal.showLoading() });

        $.post('../controllers/ClienteController.php', $(this).serialize() + '&action=crear', function(res) {
            if(res.success) {
                Swal.fire('¡Creado!', 'Cliente registrado.', 'success');
                $formNuevo[0].reset();
                $modalNuevo.hide();
                cargarTabla();
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }, 'json').fail(() => Swal.fire('Error', 'Error de conexión.', 'error'));
    });


    $tablaTbody.on('click', '.btn-editar', function() {
        const id = $(this).data('id');
        $.get('../controllers/ClienteController.php', { action: 'obtener', id: id }, function(res) {
            if(res.success) {
                $('#edit_id').val(res.data.id);        
                $('#edit_nombre').val(res.data.nombre);
                $('#edit_telefono').val(res.data.telefono); 
                
                $modalEditar.show();
            } else {
                Swal.fire('Error', 'No se pudo cargar el cliente.', 'error');
            }
        }, 'json');
    });
    $formEditar.on('submit', function(e) {
        e.preventDefault();
        Swal.fire({
            title: '¿Guardar cambios?',
            showCancelButton: true,
            confirmButtonText: 'Sí, actualizar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('../controllers/ClienteController.php', $(this).serialize() + '&action=actualizar', function(res) {
                    if(res.success) {
                        Swal.fire('¡Actualizado!', 'Cliente modificado correctamente.', 'success');
                        $modalEditar.hide();
                        cargarTabla();
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }, 'json');
            }
        });
    });

    $tablaTbody.on('click', '.btn-eliminar', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: '¿Eliminar Cliente?',
            text: "El cliente pasará a estado inactivo.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('../controllers/ClienteController.php', { action: 'eliminar', id: id }, function(res) {
                    if(res.success) {
                        Swal.fire('Eliminado', 'El cliente ha sido desactivado.', 'success');
                        cargarTabla();
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }, 'json');
            }
        });
    });
    cargarTabla();
});
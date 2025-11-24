
$(function () {

    const $tablaTbody = $('#tablaServicios tbody');
    const $formNuevo = $('#formNuevoServicio');
    const $modalNuevo = $('#modalNuevoServicio');
    function cargarTablaServicios() {
        $tablaTbody.html('<tr><td colspan="6" class="text-center">Cargando servicios...</td></tr>');

        $.ajax({
            url: BASE_URL + 'controllers/ServicioController.php',
            type: 'GET',
            data: { action: 'listar' },
            dataType: 'json'
        })
        .done(function(response) {
            $tablaTbody.empty();
            if (response.success && response.data.length > 0) {
                $.each(response.data, function(index, srv) {
                    const precioF = parseFloat(srv.precio).toLocaleString('es-PE', { style: 'currency', currency: 'PEN' });
                    const duracionTotal = parseInt(srv.duracion_min) + parseInt(srv.tiempo_buffer_min);
                    const fila = `
                        <tr>
                            <td>${srv.id}</td>
                            <td>${srv.nombre}</td>
                            <td class="text-end">${precioF}</td>
                            <td>${srv.duracion_min} min</td>
                            <td>${srv.tiempo_buffer_min} min</td>
                            <td>${duracionTotal} min</td>
                        </tr>
                    `;
                    $tablaTbody.append(fila);
                });
            } else {
                $tablaTbody.html('<tr><td colspan="6" class="text-center">No hay servicios registrados.</td></tr>');
            }
        })
        .fail(function() {
            $tablaTbody.html('<tr class="text-danger"><td colspan="6" class="text-center">Error al cargar servicios.</td></tr>');
        });
    }
    cargarTablaServicios();
});
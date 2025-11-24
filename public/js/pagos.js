$(document).ready(function() {
    const baseUrl = (typeof BASE_URL !== 'undefined') ? BASE_URL : '../';
    const modalElement = document.getElementById('modalRegistrarPago');
    const modalPago = new bootstrap.Modal(modalElement);
    function cargarPagos() {
        $('#tabla-pagos tbody').html('<tr><td colspan="8" class="text-center"><div class="spinner-border spinner-border-sm"></div> Cargando...</td></tr>');
        
        $.get(baseUrl + 'controllers/PagoController.php?action=listar', function(res) {
            let html = '';
            if (res.success && res.data.length > 0) {
                res.data.forEach(p => {
                    const fechaRaw = p.fecha_pago || p.creado_en; 
                    const fechaF = fechaRaw ? new Date(fechaRaw).toLocaleString('es-PE') : '-';

                    let concepto = p.servicio_texto 
                        ? `<span class="badge bg-info text-dark">Reserva #${p.reserva_id}</span> ${p.servicio_texto}` 
                        : '<span class="text-muted">Venta Directa</span>';
                    
                    let badgeClass = p.estado_pago === 'pagado' ? 'success' : 'danger';

                    html += `
                    <tr>
                        <td><strong>#${p.id}</strong></td>
                        <td>${fechaF}</td> <td>${p.cliente_nombre || 'Público General'}</td>
                        <td>${concepto}</td>
                        <td class="fw-bold text-success">S/ ${parseFloat(p.monto).toFixed(2)}</td>
                        <td><span class="badge bg-light text-dark border">${p.metodo_pago}</span></td>
                        <td><span class="badge bg-${badgeClass}">${p.estado_pago}</span></td>
                        <td class="text-center">
                            ${p.estado_pago === 'pagado' ? 
                            `<button class="btn btn-sm btn-outline-danger btn-anular" data-id="${p.id}" title="Anular"><i class="bi bi-x-lg"></i></button>` : '-'}
                        </td>
                    </tr>`;
                });
            } else {
                html = '<tr><td colspan="8" class="text-center py-4 text-muted">No hay pagos registrados.</td></tr>';
            }
            $('#tabla-pagos tbody').html(html);
        }, 'json').fail(() => {
            $('#tabla-pagos tbody').html('<tr><td colspan="8" class="text-center text-danger">Error de conexión</td></tr>');
        });
    }

    function cargarSelectReservas() {
        $.get(baseUrl + 'controllers/PagoController.php?action=reservas_pendientes', function(res) {
            let opts = '<option value="">-- Venta Directa / Sin Reserva --</option>';
            if (res.success && res.data) {
                res.data.forEach(r => {
                    opts += `<option value="${r.id}" data-precio="${r.precio}">
                                📅 #${r.id} | ${r.cliente} - ${r.servicio_texto} (S/ ${r.precio})
                             </option>`;
                });
            }
            $('#pago_reserva_id').html(opts);
        }, 'json');
    }


    function cargarSelectClientes() {
        $.get(baseUrl + 'controllers/ClienteController.php?action=listar', function(res) {
            if(res.success && res.data) {
                let opts = '<option value="">-- Público General --</option>';
                res.data.forEach(c => {
                    if(c.estado === 'activo') opts += `<option value="${c.id}">${c.nombre}</option>`;
                });
                $('#pago_cliente_id').html(opts);
            }
        }, 'json');
    }


    $('#pago_reserva_id').change(function() {
        let precio = $(this).find(':selected').data('precio');
        if (precio) {
            $('#pago_monto').val(precio);
            $('#div-cliente-manual').hide();
        } else {
            $('#pago_monto').val('');
            $('#div-cliente-manual').show();
        }
    });

    $('#btnNuevoPago').click(function() {
        $('#formRegistrarPago')[0].reset();
        $('#div-cliente-manual').show();
        cargarSelectReservas();
        cargarSelectClientes();
        modalPago.show();
    });

    $(document).off('submit', '#formRegistrarPago').on('submit', '#formRegistrarPago', function(e) {
        e.preventDefault();
        
        let monto = $('#pago_monto').val();
        if (parseFloat(monto) <= 0) {
            Swal.fire('Error', 'Monto inválido', 'warning');
            return;
        }

        let btn = $(this).find('button[type="submit"]');
        let btnText = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Guardando...');

        let formData = $(this).serialize();

        $.post(baseUrl + 'controllers/PagoController.php?action=registrar', formData, function(res) {
            if(res.success) {
                Swal.fire('¡Éxito!', 'Pago registrado correctamente', 'success');
                modalPago.hide();
                cargarPagos();
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }, 'json')
        .fail(() => Swal.fire('Error', 'Fallo de red', 'error'))
        .always(() => btn.prop('disabled', false).html(btnText));
    });
    $(document).on('click', '.btn-anular', function() {
        let id = $(this).data('id');
        Swal.fire({
            title: '¿Anular pago?',
            text: "Se marcará como reembolsado.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Sí, anular'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(baseUrl + 'controllers/PagoController.php', {action:'anular', id:id}, function(res) {
                    if(res.success) {
                        cargarPagos();
                        Swal.fire('Anulado', 'Pago reembolsado.', 'success');
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }, 'json');
            }
        });
    });

    $('#filtro-buscar').on('keyup', function() {
        let value = $(this).val().toLowerCase();
        $("#tabla-pagos tbody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    cargarPagos();
});
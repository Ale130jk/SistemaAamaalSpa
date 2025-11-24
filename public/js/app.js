$(document).ready(function() {
    let iaAlertOpen = false;
    let reservasNotificadas = [];
    const ToastAlerta = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: true, 
        confirmButtonText: 'Entendido',
        timer: 15000,  
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });
    const getBaseUrl = () => (typeof BASE_URL !== 'undefined') ? BASE_URL : '../';
    $('#btnLogout').on('click', function(e) {
        e.preventDefault();

        Swal.fire({
            title: '¿Cerrar Sesión?',
            text: '¿Está seguro de que desea salir del sistema?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, salir',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Cerrando sesión..',
                    text: 'Por favor espere',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                $.ajax({
                    url: getBaseUrl() + 'controllers/AuthController.php',
                    type: 'POST',
                    data: { action: 'logout' },
                    dataType: 'json'
                })
                .done(function(response) {
                    if (response.success) {
                        window.location.href = response.redirect;
                    } else {
                        Swal.fire('Error', 'No se pudo cerrar la sesión.', 'error');
                    }
                })
                .fail(function() {
                    Swal.fire('Error', 'Error de conexión al cerrar sesión.', 'error');
                });
            }
        });
    });

    function verificarAccionesIA() {
        if (iaAlertOpen) return;

        $.ajax({
            url: getBaseUrl() + 'controllers/ApiController.php',
            type: 'GET',
            data: { action: 'verificar_acciones' },
            dataType: 'json'
        })
        .done(function(response) {
            if (response.success && response.data && response.data.length > 0) {
                iaAlertOpen = true;
                mostrarConfirmacionIA(response.data[0]);
            }
        })
        .fail(function() {
        });
    }

    function mostrarConfirmacionIA(accion) {
        let textoAccion = `Acción desconocida ID: ${accion.id}`;
        let payload;

        try {
            payload = JSON.parse(accion.payload);
        } catch (e) {
            payload = {};
        }

        const solicitadoPor = `<small class="text-muted">(Solicitado por Usuario ID: ${accion.solicitado_por})</small>`;
        switch (accion.accion_tipo) {
            case 'crear_cliente':
                textoAccion = `IA: Crear cliente <strong>${payload.nombre}</strong> (Tel: ${payload.telefono}).`; break;
            case 'actualizar_cliente':
                textoAccion = `IA: Actualizar cliente ID <strong>${payload.id}</strong>.<br>Nuevo: ${payload.nombre}`; break;
            case 'borrar_cliente':
                textoAccion = `IA: Borrar cliente ID <strong>${payload.id}</strong>.`; break;
            case 'crear_producto':
                textoAccion = `IA: Crear producto <strong>${payload.nombre}</strong> (Stock: ${payload.stock}).`; break;
            case 'actualizar_stock':
                textoAccion = `IA: Actualizar stock Producto ID <strong>${payload.id || payload.producto_id}</strong> (+${payload.cantidad}).`; break;
            case 'actualizar_producto':
                textoAccion = `IA: Actualizar datos Producto ID <strong>${payload.id}</strong> (Nombre/Precio).`; break;
            case 'borrar_producto':
                textoAccion = `IA: Borrar producto ID <strong>${payload.id}</strong>.`; break;
            case 'crear_reserva':
                textoAccion = `IA: Crear reserva para <strong>Cliente ${payload.cliente_id}</strong><br>Servicio: ${payload.servicio_texto}<br>Fecha: ${payload.fecha_inicio}`; break;
            case 'actualizar_reserva':
                textoAccion = `IA: Modificar reserva ID <strong>${payload.id}</strong>.`; break;
            case 'cancelar_reserva':
                textoAccion = `IA: <strong class="text-danger">Cancelar</strong> reserva ID <strong>${payload.id}</strong>.`; break;
            case 'borrar_reserva':
                textoAccion = `IA: <strong class="text-danger">Eliminar Físicamente</strong> reserva ID <strong>${payload.id}</strong>.`; break;
            case 'registrar_pago':
                textoAccion = `IA: Registrar pago de <strong>S/ ${payload.monto}</strong> (Reserva #${payload.reserva_id}).`; break;
            case 'anular_pago':
                textoAccion = `IA: Anular pago ID <strong>${payload.id}</strong>.`; break;
        }

        Swal.fire({
            title: 'Acción Pendiente de la ia',
            html: `${textoAccion}<br><br>${solicitadoPor}<br><strong>¿Apruebas esta acción?</strong>`,
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Sí, aprobar',
            cancelButtonText: 'Rechazar / Ignorar',
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            allowOutsideClick: false
        }).then((result) => {
            iaAlertOpen = false;

            if (result.isConfirmed) {
                ejecutarAccionIA(accion.id, 'confirmar_accion');
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                ejecutarAccionIA(accion.id, 'rechazar_accion');
            }
        });
    }

    function ejecutarAccionIA(accionId, tipoAccion) {
        if (tipoAccion === 'rechazar_accion') {
            $.ajax({
                url: getBaseUrl() + 'controllers/ApiController.php',
                type: 'POST',
                data: { action: 'rechazar_accion', id: accionId },
                dataType: 'json'
            });
            const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            Toast.fire({ icon: 'info', title: 'No se pudo realizar la acción' });
            return;
        }

        Swal.fire({
            title: 'Procesando..',
            text: 'ejecutando acción..',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: getBaseUrl() + 'controllers/ApiController.php',
            type: 'POST',
            data: { action: 'confirmar_accion', id: accionId },
            dataType:'json'
        })
        .done(function(response) {
            if (response.success) {
                Swal.fire('Exito', response.message, 'success')
                .then(() => { location.reload(); }); 
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        })
        .fail(function() {
            Swal.fire('Error', 'error');
        });
    }

  function verificarReservasProximas() {
        $.get('../controllers/ReservaController.php', { action: 'verificar_alertas' }, function(res) {
            if(res.success && res.data.length > 0) {
                res.data.forEach(reserva => {
                    if (!reservasNotificadas.has(reserva.id)) {
                        const horaInicio = new Date(reserva.fecha_inicio).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 15000,
                            timerProgressBar: true,
                            didOpen: (toast) => {
                                toast.addEventListener('mouseenter', Swal.stopTimer)
                                toast.addEventListener('mouseleave', Swal.resumeTimer)
                            }
                        });

                        Toast.fire({
                            icon: 'warning',
                            title: 'Reserva Próxima',
                            html: `
                                <strong>Cliente:</strong> ${reserva.cliente}<br>
                                <strong>Servicio:</strong> ${reserva.servicio}<br>
                                <strong>Hora:</strong> ${horaInicio}
                            `
                        });

                        reservasNotificadas.add(reserva.id);
                    }
                });
            }
        }, 'json');
    }
    setInterval(verificarAccionesIA, 15000);
    setInterval(verificarReservasProximas, 30000);
    verificarAccionesIA(); 
    verificarReservasProximas();
});
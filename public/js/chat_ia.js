$(document).ready(function() {
    const btnFlotanteIA = $('#btn-flotante-ia');
    const chatContainer = $('#chat-ia-container');
    const btnCerrarChat = $('#btn-cerrar-chat');
    const btnEnviarChat = $('#btn-enviar-chat');
    const inputEntrada = $('#chat-ia-entrada');
    const mensajesContainer = $('#chat-ia-mensajes');

    const getBaseUrl = () => (typeof BASE_URL !== 'undefined') ? BASE_URL : '../';
    const API_URL = getBaseUrl() + 'controllers/ChatIAController.php';

    btnFlotanteIA.on('click', function() {
        chatContainer.removeClass('oculto'); 
        btnFlotanteIA.addClass('oculto');
        setTimeout(() => inputEntrada.focus(), 100);
    });

    btnCerrarChat.on('click', function() {
        chatContainer.addClass('oculto');
        btnFlotanteIA.removeClass('oculto');
    });
    function enviarMensaje() {
        const mensajeTexto = inputEntrada.val().trim();
        if (mensajeTexto === '') return;

        const htmlUsuario = `<div class="mensaje usuario">${mensajeTexto}</div>`;
        mensajesContainer.append(htmlUsuario);
        
        inputEntrada.val('');
        hacerScrollAbajo();

        const idTyping = 'typing-' + Date.now();
        const htmlTyping = `<div id="${idTyping}" class="mensaje ia">...</div>`;
        mensajesContainer.append(htmlTyping);
        hacerScrollAbajo();
        $.ajax({
            url: API_URL,
            type: 'POST',
            data: {
                action: 'procesar_mensaje',
                mensaje: mensajeTexto
            },
            dataType: 'json'
        })
        .done(function(res) {
            $(`#${idTyping}`).remove();
            let texto = res.respuesta_ia || res.message || "Sin respuesta.";
            let claseExtra = res.success ? '' : 'error'; 
            const htmlIA = `<div class="mensaje ia ${claseExtra}">${texto}</div>`;
            
            mensajesContainer.append(htmlIA);
            hacerScrollAbajo();
        })
        .fail(function(xhr) {
            $(`#${idTyping}`).remove();
            console.error(xhr.responseText);
            const htmlError = `<div class="mensaje ia error">Error de conexión.</div>`;
            mensajesContainer.append(htmlError);
            hacerScrollAbajo();
        });
    }

    function hacerScrollAbajo() {
        mensajesContainer.scrollTop(mensajesContainer[0].scrollHeight);
    }

    btnEnviarChat.on('click', enviarMensaje);
    
    inputEntrada.on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            enviarMensaje();
        }
    });

});

    $(document).on('click', '.btn-aprobar-accion', function() {
        const accionId = $(this).data('id');
        const $boton = $(this);
        
        Swal.fire({
            title: '¿Confirmar acción?',
            text: 'Se ejecutará la operación solicitada por la ia',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, ejecutar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                ejecutarAccionIA(accionId, $boton);
            }
        });
    });
    
    function ejecutarAccionIA(accionId, $boton) {
        Swal.fire({
            title: 'Ejecutando...',
            text: 'Procesando solicitud de la ia',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
        
        $.ajax({
            url: getBaseUrl() + 'controllers/ApiController.php',
            method: 'POST',
            data: {
                action: 'confirmar_accion',
                id: accionId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '✅ Ejecutado',
                        html: response.message,
                        timer: 3000
                    });

                    if (response.recargar_vista) {
                        recargarVista(response.recargar_vista);
                    }
                    $boton.closest('.accion-item, .card').fadeOut(300, function() {
                        $(this).remove();
                    });
                    if (typeof cargarAccionesPendientes === 'function') {
                        setTimeout(cargarAccionesPendientes, 500);
                    }
                    
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        html: response.message
                    });
                }
            },
            error: function(xhr, status, error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error del servidor',
                    text: error
                });
            }
        });
    }
    function recargarVista(vista) {
        console.log('🔄 Recargando vista:', vista);
        
        switch(vista) {
            case 'calendario':
                setTimeout(function() {
                    if (typeof window.recargarCalendarioIA === 'function') {
                        window.recargarCalendarioIA();
                        console.log('✅ Calendario recargado');
                    } else {
                        console.warn('⚠️ window.recargarCalendarioIA no existe');
                    }
                }, 500); 
                break;
            
            case 'clientes':
                if (typeof cargarClientes === 'function') {
                    cargarClientes();
                } else if ($('#tabla-clientes').length) {
                    $('#tabla-clientes').DataTable().ajax.reload(null, false);
                }
                break;
                
            case 'inventario':
                if (typeof cargarProductos === 'function') {
                    cargarProductos();
                } else if ($('#tabla-productos').length) {
                    $('#tabla-productos').DataTable().ajax.reload(null, false);
                }
                break;
                
            case 'pagos':
                if (typeof cargarPagos === 'function') {
                    cargarPagos();
                } else if ($('#tabla-pagos').length) {
                    $('#tabla-pagos').DataTable().ajax.reload(null, false);
                }
                break;
                
            case 'egresos':
                if (typeof cargarEgresos === 'function') {
                    cargarEgresos();
                } else if ($('#tabla-egresos, #tabla-compras').length) {
                    $('#tabla-egresos, #tabla-compras').DataTable().ajax.reload(null, false);
                }
                break;
        }
    }
    $(document).on('click', '.btn-rechazar-accion', function() {
        const accionId = $(this).data('id');
        const $boton = $(this);
        Swal.fire({
            title: '¿Rechazar acción?',
            text: 'La solicitud de IA será descartada',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, rechazar',
            confirmButtonColor: '#dc3545',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: getBaseUrl() + 'controllers/ApiController.php',
                    method: 'POST',
                    data: {
                        action: 'rechazar_accion',
                        id: accionId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Rechazado', 'Acción descartada', 'info');
                            $boton.closest('.accion-item, .card').fadeOut(300, function() {
                                $(this).remove();
                            });
                            
                            if (typeof cargarAccionesPendientes === 'function') {
                                setTimeout(cargarAccionesPendientes, 500);
                            }
                        }
                    }
                });
            }
        });
});

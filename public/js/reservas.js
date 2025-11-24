
$(function () {

    const calendarEl = document.getElementById('calendario-reservas');
    const modalNuevoEl = document.getElementById('modalNuevaReserva');
    const modalEditarEl = document.getElementById('modalEditarReserva');
    
    const bsModalNuevo = modalNuevoEl ? new bootstrap.Modal(modalNuevoEl) : null;
    const bsModalEditar = modalEditarEl ? new bootstrap.Modal(modalEditarEl) : null;

    const $formNuevo = $('#formNuevaReserva');
    const $formEditar = $('#formEditarReserva');
    const $inputBusqueda = $('#buscadorReservas');
    const $listaResultados = $('#listaResultadosBusqueda');

    let calendar;

    if (calendarEl) {
        inicializarCalendario();
        cargarClientes();
    }

    window.recargarCalendarioIA = function() {
        if(calendar) {
            calendar.refetchEvents();
            console.log("🤖 IA ordenó actualizar el calendario.");
        }
    };

    function inicializarCalendario() {
        calendar = new FullCalendar.Calendar(calendarEl, {
            themeSystem: 'bootstrap5',
            locale: 'es',
            initialView: 'timeGridWeek',
            headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listWeek' },
            timeZone: 'local',
            initialDate: new Date(),
            slotMinTime: '07:00:00', 
            slotMaxTime: '22:00:00', 
            hiddenDays: [],
            firstDay: 1,
            navLinks: true, editable: true, selectable: true, nowIndicator: true, height: 'auto',

       events: function(fetchInfo, success, fail) {
                $.get('../controllers/ReservaController.php', {
                    action: 'listar', 
                    start: fetchInfo.startStr, 
                    end: fetchInfo.endStr
                }, function(data) {
                    if (!Array.isArray(data)) {
                        console.error(" La respuesta esta mal:", data);
                        success([]);
                        return;
                    }
                    const eventos = data.map(r => ({
                        ...r,
                        backgroundColor: getColorEstado(r.estado_reserva || r.extendedProps?.estado_reserva),
                        borderColor: 'transparent',
                        textColor: '#fff'
                    }));
                    success(eventos);
                }, 'json').fail(function(xhr, status, error) {
                    console.error("Error cargando eventos:", error, xhr.responseText);
                    fail(error);
                });
            },
            select: function(info) {
                if(!bsModalNuevo) return;
                $formNuevo[0].reset();
                const fechaLocal = new Date(info.startStr);
                fechaLocal.setMinutes(fechaLocal.getMinutes() - fechaLocal.getTimezoneOffset());
                $('#input-fecha-inicio').val(fechaLocal.toISOString().slice(0, 16));
                
                bsModalNuevo.show();
            },         

            eventClick: function(info) {
                if(!bsModalEditar) return;
                
                const props = info.event.extendedProps;
                console.log("Editando reserva:", props); 

                $('#edit_reserva_id').val(info.event.id);
                $('#edit_cliente').val(props.cliente_id);
                const textoServicio = props.servicio_texto || props.servicio_nombre || '';
                $('#edit_servicio').val(textoServicio);
                $('#edit_precio').val(props.precio);
                const fechaISO = info.event.start.toISOString().substring(0, 16);
                $('#edit_fecha').val(fechaISO);

                bsModalEditar.show();
            },
            eventDrop: function(info) {
                actualizarFechaRapida(info.event);
            }
        });
        calendar.render();
    }

    $formNuevo.on('submit', function(e) {
        e.preventDefault();
        Swal.fire({ title: 'Guardando...', didOpen: () => Swal.showLoading() });
        $.post('../controllers/ReservaController.php', $(this).serialize() + '&action=crear', function(res) {
            if(res.success) {
                Swal.fire('Listo', 'Reserva creada.', 'success');
                $formNuevo[0].reset(); bsModalNuevo.hide(); calendar.refetchEvents();
            } else { Swal.fire('Error', res.message, 'error'); }
        }, 'json');
    });

    $formEditar.on('submit', function(e) {
        e.preventDefault();
        Swal.fire({ title: 'Actualizando...', didOpen: () => Swal.showLoading() });
        $.post('../controllers/ReservaController.php', $(this).serialize() + '&action=actualizar', function(res) {
            if(res.success) {
                Swal.fire('Actualizado', 'Cambios guardados.', 'success');
                bsModalEditar.hide(); calendar.refetchEvents();
            } else { Swal.fire('Error', res.message, 'error'); }
        }, 'json');
    });

    $('#btnEliminarReserva').on('click', function() {
        if(confirm('¿Cancelar esta cita?')) {
            $.post('../controllers/ReservaController.php', { 
                action: 'cancelar', 
                id: $('#edit_reserva_id').val() 
            }, function(res) {
                if(res.success) {
                    Swal.fire('Cancelada', 'Cita cancelada.', 'success');
                    bsModalEditar.hide(); calendar.refetchEvents();
                } else { Swal.fire('Error', res.message, 'error'); }
            }, 'json');
        }
    });

    function actualizarFechaRapida(event) {
        const fecha = new Date(event.start.getTime() - (event.start.getTimezoneOffset() * 60000))
                        .toISOString().slice(0, 19).replace('T', ' ');
        $.post('../controllers/ReservaController.php', {
            action: 'actualizar_fecha', id: event.id, fecha_inicio: fecha
        });
    }

    function cargarClientes() {
        $.get('../controllers/ClienteController.php', {action:'listar'}, res => {
            if(res.success) {
                const opts = res.data.map(c => `<option value="${c.id}">${c.nombre}</option>`);
                $('#select-cliente, #edit_cliente').html(opts);
            }
        }, 'json');
    }

    function getColorEstado(estado) {
        const colores = { 'programada': '#0dcaf0', 'confirmada': '#198754', 'en_progreso': '#ffc107', 'completada': '#0d6efd', 'cancelada': '#dc3545' };
        return colores[estado] || '#6c757d';
    }
    
    $inputBusqueda.on('input', function() {
        const q = $(this).val();
        if(q.length < 2) { $listaResultados.hide(); return; }
        
        $.get('../controllers/ReservaController.php', {action: 'buscar', q: q}, function(res) {
            $listaResultados.empty();
            if(res.success && res.data.length) {
                res.data.forEach(r => {
                    const item = $(`<button class="list-group-item list-group-item-action"><strong>${r.cliente_nombre}</strong> - ${r.servicio_texto}<br><small>${r.fecha_inicio}</small></button>`);
                    item.click(() => {
                        calendar.gotoDate(r.fecha_inicio);
                        calendar.changeView('timeGridDay');
                        $listaResultados.hide();
                    });
                    $listaResultados.append(item);
                });
                $listaResultados.show();
            } else { $listaResultados.html('<div class="p-2 text-muted">Sin resultados</div>').show(); }
        }, 'json');
    });
    
    $(document).click(e => { if(!$(e.target).closest('#buscadorReservas').length) $listaResultados.hide(); });
    
    $('#btnNuevaReservaManual').click(() => {
        $formNuevo[0].reset();
        $('#input-fecha-inicio').val(new Date().toISOString().slice(0, 16));
        bsModalNuevo.show();
    });
});
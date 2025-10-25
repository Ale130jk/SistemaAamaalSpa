/**
 * Archivo: public/js/reservas.js
 * Autor: Sistema Aamaal Spa
 * Fecha: 2025-10-25
 * Propósito: Gestión del calendario de reservas con FullCalendar 6.x
 * 
 * Dependencias:
 * - FullCalendar 6.1.15 (cargado en footer.php)
 * - jQuery 3.7.1
 * - SweetAlert2
 * 
 * Funcionalidades:
 * - Renderizar calendario mensual/semanal/diario
 * - Cargar reservas desde la base de datos via AJAX
 * - Crear nueva reserva (clic en fecha)
 * - Ver/editar reserva existente (clic en evento)
 * - Validar conflictos de horario
 */

// Esperar a que el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', function() {
    
    console.log('🗓️ Iniciando calendario de reservas...');
    
    // 1. Obtener el elemento HTML donde se renderizará el calendario
    const calendarEl = document.getElementById('calendario-reservas');
    
    // Validar que el elemento existe en el DOM
    if (!calendarEl) {
        console.error('❌ Error: No se encontró el elemento #calendario-reservas');
        return; // Salir si no existe el elemento
    }

    // 2. Configuración del calendario (FullCalendar 6.x usa "FullCalendar.Calendar")
    const calendar = new FullCalendar.Calendar(calendarEl, {
        
        // --- CONFIGURACIÓN REGIONAL ---
        locale: 'es', // Idioma español
        
        // --- VISTA INICIAL ---
        initialView: 'dayGridMonth', // Empezar en vista mensual
        
        // --- ALTURA AUTOMÁTICA ---
        height: 'auto', // Se ajusta al contenido
        
        // --- BARRA DE HERRAMIENTAS ---
        headerToolbar: {
            left: 'prev,next today',   // Botones: anterior, siguiente, hoy
            center: 'title',            // Título centrado (ej: "Octubre 2025")
            right: 'dayGridMonth,timeGridWeek,timeGridDay' // Vistas disponibles
        },
        
        // --- BOTONES PERSONALIZADOS ---
        buttonText: {
            today: 'Hoy',
            month: 'Mes',
            week: 'Semana',
            day: 'Día'
        },
        
        // --- HORARIO DE TRABAJO ---
        slotMinTime: '08:00:00', // Hora de inicio (8 AM)
        slotMaxTime: '20:00:00', // Hora de fin (8 PM)
        slotDuration: '00:30:00', // Intervalos de 30 minutos
        
        // --- DÍAS LABORABLES ---
        hiddenDays: [0], // Ocultar domingos (0 = domingo, 6 = sábado)
        
        // --- FORMATO DE HORA ---
        slotLabelFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false // Formato 24 horas
        },
        
        // --- EVENTOS (Reservas) ---
        // OPCIÓN 1: Datos estáticos (para pruebas)
        events: [
            {
                id: '1',
                title: 'Masaje Relajante - Ana García',
                start: '2025-10-27T10:30:00',
                end: '2025-10-27T11:30:00',
                backgroundColor: '#22c55e', // Verde
                borderColor: '#16a34a',
                extendedProps: {
                    cliente: 'Ana García',
                    servicio: 'Masaje Relajante',
                    telefono: '987654321',
                    estado: 'confirmado'
                }
            },
            {
                id: '2',
                title: 'Limpieza Facial - María López',
                start: '2025-10-28T14:00:00',
                end: '2025-10-28T15:00:00',
                backgroundColor: '#3b82f6', // Azul
                borderColor: '#2563eb',
                extendedProps: {
                    cliente: 'María López',
                    servicio: 'Limpieza Facial',
                    telefono: '912345678',
                    estado: 'pendiente'
                }
            }
        ],
        
        /* OPCIÓN 2: Cargar desde servidor (comentado por ahora)
        events: function(info, successCallback, failureCallback) {
            // info contiene: start, end, startStr, endStr
            console.log('📅 Cargando reservas del servidor...');
            
            $.ajax({
                url: '/controllers/ReservaController.php',
                method: 'GET',
                data: {
                    action: 'listar_calendario',
                    start: info.startStr, // Fecha inicial del rango
                    end: info.endStr      // Fecha final del rango
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Formatear eventos para FullCalendar
                        const eventos = response.data.map(reserva => ({
                            id: reserva.id,
                            title: `${reserva.servicio} - ${reserva.cliente}`,
                            start: reserva.fecha_inicio,
                            end: reserva.fecha_fin,
                            backgroundColor: getColorPorEstado(reserva.estado),
                            extendedProps: reserva
                        }));
                        successCallback(eventos); // Pasar eventos al calendario
                    } else {
                        console.error('Error al cargar reservas:', response.message);
                        failureCallback(response.message);
                    }
                },
                error: function(xhr) {
                    console.error('Error AJAX:', xhr);
                    failureCallback('Error al conectar con el servidor');
                }
            });
        },
        */
        
        // --- INTERACCIONES DEL USUARIO ---
        
        // Cuando se hace clic en una FECHA (para crear nueva reserva)
        dateClick: function(info) {
            console.log('📅 Clic en fecha:', info.dateStr);
            
            // Mostrar SweetAlert de confirmación
            Swal.fire({
                title: '📅 Nueva Reserva',
                html: `
                    <p>¿Desea agendar una cita para el <strong>${formatearFecha(info.date)}</strong>?</p>
                    <small class="text-muted">Se abrirá el formulario de reserva</small>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i class="bi bi-calendar-plus"></i> Sí, agendar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#22c55e',
                cancelButtonColor: '#6b7280'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Abrir modal de nueva reserva (implementar en Fase 3)
                    abrirModalNuevaReserva(info.dateStr);
                }
            });
        },
        
        // Cuando se hace clic en un EVENTO (reserva existente)
        eventClick: function(info) {
            console.log('🎯 Clic en evento:', info.event.id);
            
            // Prevenir comportamiento por defecto
            info.jsEvent.preventDefault();
            
            // Obtener datos extendidos del evento
            const reserva = info.event.extendedProps;
            
            // Mostrar SweetAlert con detalles de la reserva
            Swal.fire({
                title: info.event.title,
                html: `
                    <div class="text-start">
                        <p><strong>📅 Fecha:</strong> ${formatearFecha(info.event.start)}</p>
                        <p><strong>🕐 Hora:</strong> ${formatearHora(info.event.start)} - ${formatearHora(info.event.end)}</p>
                        <p><strong>👤 Cliente:</strong> ${reserva.cliente || 'No especificado'}</p>
                        <p><strong>💆 Servicio:</strong> ${reserva.servicio || 'No especificado'}</p>
                        <p><strong>📱 Teléfono:</strong> ${reserva.telefono || 'No especificado'}</p>
                        <p><strong>📊 Estado:</strong> <span class="badge bg-${getBadgeColor(reserva.estado)}">${reserva.estado}</span></p>
                    </div>
                `,
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: '<i class="bi bi-pencil-square"></i> Editar',
                cancelButtonText: 'Cerrar',
                confirmButtonColor: '#3b82f6'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Abrir modal de edición (implementar en Fase 3)
                    abrirModalEditarReserva(info.event.id);
                }
            });
        },
        
        // Cambiar tamaño del evento (arrastrar para cambiar duración)
        editable: true, // Permitir arrastrar eventos
        eventResize: function(info) {
            console.log('📏 Evento redimensionado:', info.event.id);
            actualizarDuracionReserva(info.event);
        },
        
        // Arrastrar evento a otra fecha/hora
        eventDrop: function(info) {
            console.log('🔄 Evento movido:', info.event.id);
            actualizarFechaReserva(info.event);
        },
        
        // Colores de eventos según su tipo
        eventClassNames: function(arg) {
            return ['reserva-evento']; // Clase CSS personalizada
        }
        
    }); // Fin de configuración del calendario

    // 3. Renderizar el calendario en el DOM
    calendar.render();
    console.log('✅ Calendario renderizado correctamente');
    
    // --- FUNCIONES AUXILIARES ---
    
    /**
     * Abrir modal para crear nueva reserva
     * @param {string} fecha - Fecha en formato ISO (YYYY-MM-DD)
     */
    function abrirModalNuevaReserva(fecha) {
        console.log('🆕 Abriendo modal nueva reserva para:', fecha);
        // TODO: Implementar en Fase 3
        // - Abrir modal con Bootstrap
        // - Pre-llenar campo de fecha
        // - Cargar servicios disponibles
        alert('Modal de nueva reserva - Por implementar');
    }
    
    /**
     * Abrir modal para editar reserva existente
     * @param {string} reservaId - ID de la reserva
     */
    function abrirModalEditarReserva(reservaId) {
        console.log('✏️ Abriendo modal editar reserva:', reservaId);
        // TODO: Implementar en Fase 3
        alert('Modal de editar reserva - Por implementar');
    }
    
    /**
     * Actualizar duración de reserva (cuando se redimensiona)
     * @param {Object} event - Objeto evento de FullCalendar
     */
    function actualizarDuracionReserva(event) {
        // TODO: Validar que no haya conflictos
        // TODO: Hacer petición AJAX para actualizar en BD
        console.log('Actualizar duración:', {
            id: event.id,
            inicio: event.start,
            fin: event.end
        });
    }
    
    /**
     * Actualizar fecha/hora de reserva (cuando se arrastra)
     * @param {Object} event - Objeto evento de FullCalendar
     */
    function actualizarFechaReserva(event) {
        // TODO: Validar disponibilidad en nueva fecha
        // TODO: Hacer petición AJAX para actualizar en BD
        console.log('Actualizar fecha:', {
            id: event.id,
            nueva_fecha: event.start
        });
    }
    
    /**
     * Formatear fecha para mostrar (ej: "Lunes, 27 de Octubre 2025")
     * @param {Date} fecha
     * @returns {string}
     */
    function formatearFecha(fecha) {
        return new Intl.DateTimeFormat('es-ES', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        }).format(fecha);
    }
    
    /**
     * Formatear hora (ej: "10:30")
     * @param {Date} fecha
     * @returns {string}
     */
    function formatearHora(fecha) {
        return new Intl.DateTimeFormat('es-ES', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        }).format(fecha);
    }
    
    /**
     * Obtener color de fondo según estado de reserva
     * @param {string} estado - 'confirmado', 'pendiente', 'cancelado'
     * @returns {string} Color hexadecimal
     */
    function getColorPorEstado(estado) {
        const colores = {
            'confirmado': '#22c55e',  // Verde
            'pendiente': '#f59e0b',   // Amarillo
            'cancelado': '#ef4444',   // Rojo
            'completado': '#8b5cf6'   // Púrpura
        };
        return colores[estado] || '#6b7280'; // Gris por defecto
    }
    
    /**
     * Obtener clase de badge de Bootstrap según estado
     * @param {string} estado
     * @returns {string} Clase CSS
     */
    function getBadgeColor(estado) {
        const badges = {
            'confirmado': 'success',
            'pendiente': 'warning',
            'cancelado': 'danger',
            'completado': 'primary'
        };
        return badges[estado] || 'secondary';
    }

}); 
$(document).ready(function() {
    $('#btnNuevaReservaManual').on('click', function() {
        console.log('➕ Botón Agendar Cita presionado');
        Swal.fire({
            title: 'Nueva Reserva',
            text: 'Se abrirá el formulario de reserva',
            icon: 'info'
        });
    });
});
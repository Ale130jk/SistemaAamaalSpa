$(function () {
    const $form=$('#form-generar-reporte');
    const $tbody = $('#tbody-reporte-pagos');
    const $contenedorResultados=$('#contenedor-resultados-reporte');
    const $btnExportarCSV =$('#btn-exportar-resultados'); 
    const $btnDescargarPDF = $('#btn-descargar-pdf');

    $form.on('submit', function(e) {
        e.preventDefault();
        
        const fechaInicio = $('input[name="fecha_inicio"]').val();
        const fechaFin = $('input[name="fecha_fin"]').val();
        Swal.fire({
            title: 'Generando Reporte...',
            text: 'Procesando ingresos, gastos y balance...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
        
        const formData = $(this).serialize() + '&action=generar_reporte_detallado';

        $.ajax({
            url: '../controllers/ReporteController.php',
            type: 'GET',
            data: formData,
            dataType: 'json'
        })
        .done(function(response) {
            Swal.close(); 
            $tbody.empty(); 
            
            if (response.success && response.data) {
                const t = response.data.totales;        
                const pagos = response.data.detalle_pagos;
                const evolucion = response.data.evolucion_ingresos; 
                $contenedorResultados.show();
                const balance = parseFloat(t.balance || 0);
                const ingresos = parseFloat(t.ingresos || 0);
                const gastos = parseFloat(t.gastos || 0);
                const colorBalance = balance >= 0 ? 'bg-success' : 'bg-danger';
                const textoBalance = balance >= 0 ? 'Ganancia Neta' : 'Pérdida Neta';
                $contenedorResultados.html(`
                    <div class="row g-3 mb-4">
                        <!-- TARJETA 1: INGRESOS -->
                        <div class="col-md-4">
                            <div class="p-3 bg-info text-white rounded shadow-sm d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1 opacity-75">Total Ingresos</h6>
                                    <h3 class="mb-0 fw-bold">S/ ${ingresos.toLocaleString('es-PE', {minimumFractionDigits: 2})}</h3>
                                    <small style="font-size: 0.8em">(${t.cant_ingresos || 0} pagos)</small>
                                </div>
                                <i class="bi bi-wallet2 fs-1 opacity-50"></i>
                            </div>
                        </div>

                        <!-- TARJETA 2: GASTOS -->
                        <div class="col-md-4">
                            <div class="p-3 bg-danger text-white rounded shadow-sm d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1 opacity-75">Total Gastos</h6>
                                    <h3 class="mb-0 fw-bold">S/ ${gastos.toLocaleString('es-PE', {minimumFractionDigits: 2})}</h3>
                                    <small style="font-size: 0.8em">(${t.cant_gastos || 0} compras)</small>
                                </div>
                                <i class="bi bi-cart-x fs-1 opacity-50"></i>
                            </div>
                        </div>

                        <!-- TARJETA 3: BALANCE (UTILIDAD) -->
                        <div class="col-md-4">
                            <div class="p-3 ${colorBalance} text-white rounded shadow-sm d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1 opacity-75">${textoBalance}</h6>
                                    <h3 class="mb-0 fw-bold">S/ ${balance.toLocaleString('es-PE', {minimumFractionDigits: 2})}</h3>
                                    <small style="font-size: 0.8em">Ingresos - Gastos</small>
                                </div>
                                <i class="bi bi-calculator fs-1 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                `);
                if (pagos && pagos.length > 0) {
                    $.each(pagos, function(index, pago) {
                        const montoF = parseFloat(pago.monto).toLocaleString('es-PE', { style: 'currency', currency: 'PEN' });
                        const fechaF = new Date(pago.fecha_pago).toLocaleString('es-PE');
                        const fila = `
                            <tr>
                                <td>${pago.id}</td>
                                <td>${fechaF}</td>
                                <td>${pago.cliente_nombre}</td>
                                <td>
                                    ${pago.servicio_nombre || '-'} 
                                    <small class="text-muted d-block" style="font-size: 0.75em;">(Reserva ID: ${pago.reserva_id || 'N/A'})</small>
                                </td>
                                <td>${pago.metodo_pago}</td>
                                <td class="text-end fw-bold text-success">+ ${montoF}</td>
                            </tr>
                        `;
                        $tbody.append(fila);
                    });
                } else {
                    $tbody.append('<tr><td colspan="6" class="text-center py-4 text-muted">No hay ingresos registrados en este rango de fechas.</td></tr>');
                }
                if (evolucion) {
                    renderizarGraficoEvolucion(evolucion);
                }
                const queryParams = `&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;
                $btnExportarCSV.data('params', queryParams);
                $btnDescargarPDF.data('params', queryParams);

            } else {
                Swal.fire('Error', response.message || 'No se pudo generar el reporte.', 'error');
            }
        })
        .fail(function(xhr, status, error) {
            Swal.close();
            console.error('Error AJAX:', error);
            Swal.fire(
                'Error de Conexión', 
                'No se pudo comunicar con el servidor', 
                'error'
            );
        });
    });

    $btnExportarCSV.on('click', function(e) {
        e.preventDefault();
        const params = $(this).data('params');
        
        if (!params) {
            return Swal.fire({
                icon: 'warning',
                title: 'Atención',
                text: 'Por favor, genera un reporte primero.'
            });
        }

        window.location.href = '../controllers/ReporteController.php?action=generar_csv_detallado' + params;
    });

    $btnDescargarPDF.on('click', function(e) {
        e.preventDefault();
        const params = $(this).data('params');
        
        if (!params) {
            return Swal.fire({
                icon: 'warning',
                title: 'Atención',
                text: 'Por favor, genere un reporte primero.'
            });
        }
        const url = '../controllers/ReporteController.php?action=generar_pdf_detallado' + params;
        Swal.fire({
            title: 'Preparando el pdf espere..',
            text: 'Tu descarga comenzará en breve.',
            icon: 'info',
            timer: 2500,
            showConfirmButton: false
        });
        
        window.open(url, '_blank');
    });

});
$(function () {
    let chartIngresos;
    let chartPopulares;

function cargarDatosDashboard() {
        $.ajax({
            url: '../controllers/ReporteController.php',
            type: 'GET',
            data: { action: 'get_dashboard_data' },
            dataType: 'json'
        })
.done(function(response) {
            if (response.success && response.data) {
                const r = response.data.resumen;
                $('#stat-servicios').html(crearHTMLCard('bi-scissors', r.total_servicios_activos || 0, 'Servicios Activos'));
                $('#stat-stock').html(crearHTMLCard('bi-box-seam', r.productos_stock_bajo || 0, 'Stock Bajo'));
                $('#stat-reservas').html(crearHTMLCard('bi-calendar-check', r.reservas_hoy || 0, 'Reservas Hoy'));
                if ($('#stat-ingresos').length) {
                    const ingresos = parseFloat(r.ingresos_mes || 0);
                    const gastos = parseFloat(r.gastos_mes || 0);
                    const utilidad = ingresos - gastos;
                    $('#stat-ingresos').html(crearHTMLCard('bi-cash-coin', 'S/ ' + ingresos.toFixed(2), 'Ingresos (Mes)'));
                    let color = utilidad >= 0 ? 'rgba(34, 197, 94, 0.8)' : 'rgba(220, 53, 69, 0.8)';
                    let icono = utilidad >= 0 ? 'bi-graph-up-arrow' : 'bi-graph-down-arrow';
                    let texto = utilidad >= 0 ? 'Ganancia' : 'Pérdida';

                    $('#stat-balance').css('background', color).html(`
                        <div class="d-flex align-items-center">
                            <i class="bi ${icono} card-icon text-white fs-1 me-3"></i>
                            <div>
                                <h3 class="card-value text-white mb-0">S/ ${utilidad.toFixed(2)}</h3>
                                <p class="card-title text-white mb-0">${texto}</p>
                                <small class="text-white opacity-75" style="font-size: 0.8rem;">(Gastos: -S/ ${gastos.toFixed(2)})</small>
                            </div>
                        </div>
                    `);
                }
                renderizarGraficoIngresos(response.data.ingresos, response.data.gastos);
                renderizarGraficoPopulares(response.data.populares);
                llenarTablaReservas(response.data.ultimas_reservas);
                llenarTablaStock(response.data.bajo_stock);
                
            } else {
                Swal.fire('Error', response.message || 'Error al cargar datos.', 'error');
            }
        })
        .fail(function() {
            mostrarErrorTabla('#tabla-ultimas-reservas');
            mostrarErrorTabla('#tabla-bajo-stock');
        });
    }

function crearHTMLCard(icono, valor, titulo) {
    return `
        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; padding: 20px; text-align: center;">
            <div style="margin-bottom: 15px; opacity: 0.8;">
                <i class="bi ${icono} text-white" style="font-size: 3rem;"></i>
            </div>
            <div>
                <h3 class="text-white fw-bold mb-0" style="font-size: 2.2rem; line-height: 1;">${valor}</h3>
                <p class="text-white mb-0" style="font-size: 1rem; opacity: 0.9; margin-top: 5px;">${titulo}</p>
            </div>
        </div>
    `;
}

function renderizarGraficoIngresos(dataIngresos, dataGastos) {
        const ctx = document.getElementById('chartIngresosGastos');
        if (!ctx) return;
        const chartInstance = Chart.getChart(ctx);
        if (chartInstance) {
            chartInstance.destroy();
        }

        const meses = [...new Set([...dataIngresos.map(i=>i.mes), ...dataGastos.map(g=>g.mes)])].sort();
        const dI = meses.map(m => parseFloat(dataIngresos.find(i=>i.mes===m)?.total_ingresos||0));
        const dG = meses.map(m => parseFloat(dataGastos.find(g=>g.mes===m)?.total_gastos||0));
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: meses,
                datasets: [
                    { label: 'Ingresos', data: dI, backgroundColor: '#20c997', borderRadius: 4 },
                    { label: 'Gastos', data: dG, backgroundColor: '#dc3545', borderRadius: 4 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: function(value) { return 'S/ ' + value; } }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) label += ': ';
                                if (context.parsed.y !== null) label += 'S/ ' + parseFloat(context.parsed.y).toLocaleString('es-PE', {minimumFractionDigits: 2});
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }

    function renderizarGraficoPopulares(dataPopulares) {
        const canvas = document.getElementById('chartServiciosPopulares');
        if (!canvas) return;
        const container = canvas.parentElement; 

        if (chartPopulares) {
            chartPopulares.destroy();
        }
        
        if (!dataPopulares || dataPopulares.length === 0) {
            canvas.style.display = 'none'; 
            $(container).find('.chart-no-data').remove(); 
            $(container).append(`
                <div class="chart-no-data text-center text-muted" style="padding-top: 80px;">
                    <p><i class="bi bi-pie-chart-fill fs-1"></i></p>
                    <p>No hay servicios vendidos este mes.</p>
                </div>
            `);
            return; 
        }

        canvas.style.display = 'block'; 
        $(container).find('.chart-no-data').remove(); 

        const labels = dataPopulares.map(item => item.nombre);
        const datasetReservas = dataPopulares.map(item => item.total_reservas);

        chartPopulares = new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total Reservas',
                    data: datasetReservas,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(153, 102, 255, 0.7)'
                    ],
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
    function llenarTablaReservas(data) {
        const $tbody = $('#tabla-ultimas-reservas tbody');
        $tbody.empty();

        if (!data || data.length === 0) {
            $tbody.html(`
                <tr>
                    <td colspan="4" class="text-center py-4">
                        <i class="bi bi-calendar-x text-muted fs-3"></i>
                        <p class="mt-2 mb-0 text-muted">No hay reservas recientes.</p>
                    </td>
                </tr>
            `);
            return;
        }
        data.slice(0, 5).forEach(r => {
            const fecha = new Date(r.fecha_inicio).toLocaleDateString();
            let badgeClass = 'bg-secondary';
            if (r.estado_reserva === 'confirmada') badgeClass = 'bg-success';
            if (r.estado_reserva === 'programada') badgeClass = 'bg-info text-dark';
            if (r.estado_reserva === 'cancelada') badgeClass = 'bg-danger';

            $tbody.append(`
                <tr>
                    <td><span class="fw-bold">${r.cliente_nombre}</span></td>
                    <td>${r.servicio}</td>
                    <td>${fecha}</td>
                    <td><span class="badge ${badgeClass}">${r.estado_reserva}</span></td>
                </tr>
            `);
        });
    }

    function llenarTablaStock(data) {
        const $tbody = $('#tabla-bajo-stock tbody');
        $tbody.empty();
        const bajoStock = data ? data.filter(p => p.stock <= 10) : [];

        if (bajoStock.length === 0) {
            $tbody.html(`
                <tr>
                    <td colspan="4" class="text-center py-4">
                        <i class="bi bi-check-circle-fill text-success fs-3"></i>
                        <p class="mt-2 mb-0 text-success fw-bold">¡Todo bien!</p>
                        <p class="small text-muted">No hay productos con bajo stock.</p>
                    </td>
                </tr>
            `);
            return;
        }

        bajoStock.forEach(p => {
            $tbody.append(`
                <tr>
                    <td class="fw-bold text-dark">${p.nombre}</td>
                    <td class="text-center"><span class="badge bg-danger">${p.stock}</span></td>
                    <td class="text-center text-muted">10</td>
                    <td class="text-end"><a href="productos.php" class="btn btn-sm btn-outline-primary">Ver</a></td>
                </tr>
            `);
        });
    }
    function actualizarTarjeta(id, valor) {
        $(`#stat-${id} .card-value`).text(valor || 0);
        $(`#stat-${id} .spinner-border`).remove();
    }

    function mostrarErrorTabla(selector) {
        $(selector + ' tbody').html('<tr><td colspan="4" class="text-center text-danger py-3">Error al cargar datos</td></tr>');
    }

    function renderizarGrafico(ingresos, gastos) {
        const ctx = document.getElementById('chartIngresosGastos');
        if(!ctx) return;
        if(chartIngresos) chartIngresos.destroy();

        const labels = [...new Set([...ingresos.map(i=>i.mes), ...gastos.map(g=>g.mes)])].sort();
        
        if(labels.length === 0) {
            $(ctx.parentElement).html('<div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted"><i class="bi bi-bar-chart fs-1"></i><p class="mt-2">Sin movimientos financieros</p></div>');
            return;
        }
        
        const dI = labels.map(m => ingresos.find(i=>i.mes===m)?.total_ingresos||0);
        const dG = labels.map(m => gastos.find(g=>g.mes===m)?.total_gastos||0);

        chartIngresos = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {label:'Ingresos', data:dI, backgroundColor:'#20c997', borderRadius: 4},
                    {label:'Gastos', data:dG, backgroundColor:'#dc3545', borderRadius: 4}
                ]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top' } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }
    cargarDatosDashboard();

});
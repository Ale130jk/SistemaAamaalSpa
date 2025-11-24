$(function() {
    const $tabla = $('#tablaCompras tbody');
    const $form = $('#formNuevoGasto');
    const modalEl = document.getElementById('modalNuevoGasto');
    const modal = new bootstrap.Modal(modalEl);
    const $buscador = $('#txtBuscarGasto');
    function cargarCompras(termino = '') {
        $tabla.html('<tr><td colspan="4" class="text-center py-3"><div class="spinner-border text-danger" role="status"></div></td></tr>');
        $.get('../controllers/CompraController.php', { 
            action: 'listar', 
            search: termino 
        }, function(res) {
            $tabla.empty();
            
            if(res.success && res.data.length > 0) {
                res.data.forEach(c => {
                    $tabla.append(`
                        <tr>
                            <td><span class="badge bg-light text-dark border">#${c.id}</span></td>
                            <td>${new Date(c.fecha).toLocaleDateString()} <small class="text-muted">${new Date(c.fecha).toLocaleTimeString()}</small></td>
                            <td>${c.notas || '<span class="text-muted fst-italic">Sin descripción</span>'}</td>
                            <td class="text-end text-danger fw-bold">- S/ ${parseFloat(c.total).toFixed(2)}</td>
                        </tr>
                    `);
                });
            } else {
                $tabla.html(`
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">
                            <i class="bi bi-wallet2 fs-3 d-block mb-2"></i>
                            ${termino ? 'No se encontraron gastos con ese criterio.' : 'No hay gastos registrados.'}
                        </td>
                    </tr>
                `);
            }
        }, 'json')
        .fail(() => {
            $tabla.html('<tr><td colspan="4" class="text-center text-danger">Error de conexión</td></tr>');
        });
    }
    $buscador.on('keyup', function() {
        const valor = $(this).val();
        cargarCompras(valor);
    });

    $form.on('submit', function(e) {
        e.preventDefault();
        const btn = $(this).find('button[type="submit"]');
        const originalText = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Guardando...');

        $.post('../controllers/CompraController.php', $(this).serialize() + '&action=crear', function(res) {
            if(res.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Registrado!',
                    text: 'El gasto se ha guardado correctamente.',
                    timer: 2000,
                    showConfirmButton: false
                });
                
                $form[0].reset();
                modal.hide();
                cargarCompras(); 
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }, 'json')
        .always(() => {
            btn.prop('disabled', false).html(originalText);
        });
    });

    cargarCompras();
});
<?php
$user_role = $_SESSION['rol'] ?? 'guest'; 
$user_name = $_SESSION['user_name'] ?? 'Usuario';
$user_id = $_SESSION['user_id'] ?? null;
$current_page = basename($_SERVER['PHP_SELF']); 
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <img src="/img/logoaamaalspa.jfif" alt="Logo Aamaal Spa">
        </div>
        <h1 class="sidebar-title">Aamaal Spa's</h1>
    </div>
    <ul class="sidebar-menu">
        <li class="sidebar-item">
            <a href="inicio.php" class="sidebar-link <?php echo ($current_page == 'inicio.php') ? 'active' : ''; ?>">
                <i class="bi bi-grid-fill"></i>
                <span>Inicio</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="reservas.php" class="sidebar-link <?php echo ($current_page == 'reservas.php') ? 'active' : ''; ?>">
                <i class="bi bi-calendar-check-fill"></i>
                <span>Reservas</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="clientes.php" class="sidebar-link <?php echo ($current_page == 'clientes.php') ? 'active' : ''; ?>">
                <i class="bi bi-people-fill"></i>
                <span>Clientes</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="productos.php" class="sidebar-link <?php echo ($current_page == 'productos.php') ? 'active' : ''; ?>">
                <i class="bi bi-box-seam-fill"></i>
                <span>Inventario</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="pagos.php" class="sidebar-link <?php echo ($current_page == 'pagos.php') ? 'active' : ''; ?>">
                <i class="bi bi-cash-coin"></i>
                <span>Pagos y Ventas</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="reportes.php" class="sidebar-link <?php echo ($current_page == 'reportes.php') ? 'active' : ''; ?>">
                <i class="bi bi-file-earmark-bar-graph-fill"></i>
                <span>Reportes</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="compras.php" class="sidebar-link <?php echo ($current_page == 'compras.php') ? 'active' : ''; ?>">
                <i class="bi bi-cash-stack"></i> <span>Egresos/Gastos</span>
            </a>
        </li>
    </ul>
    <button type="button" class="logout-btn" id="btnLogout">
        <i class="bi bi-box-arrow-right"></i>
        Salir
    </button>
</aside>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnLogout = document.getElementById('btnLogout');
    if (btnLogout) {
        btnLogout.addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: '¿Cerrar sesión?',
                text: "Tu sesión actual será cerrada",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#6b4423',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, salir',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Cerrando sesión...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    fetch('/controllers/AuthController.php?action=logout', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Sesión cerrada',
                                text: 'Hasta pronto!',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                        
                                window.location.href = data.redirect || '/index.php';
                            });
                        } else {
                            throw new Error(data.message || 'Error al cerrar sesión');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        
                        window.location.href = '/index.php';
                    });
                }
            });
        });
    }
});
</script>
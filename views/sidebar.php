<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <img src="/img/logoaamaalspa.jfif" alt="Logo Aammaal Spa">
        </div>
        <h1 class="sidebar-title">Aamaal Spa's</h1>
    </div>
    <ul class="sidebar-menu">
        <li class="sidebar-item">
            <a href="/views/inicio.php" class="sidebar-link active">
                <i class="bi bi-grid-fill"></i>
                <span>Inicio</span>
            </a>
        </li>
    
        <?php if ($_SESSION['rol'] == 'admin' || $_SESSION['rol'] == 'empleado'): ?>
            <li class="sidebar-item">
                <a href="/views/reservas.php" class="sidebar-link">
                    <i class="bi bi-calendar-check-fill"></i>
                    <span>Reservas</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="/views/clientes.php" class="sidebar-link">
                    <i class="bi bi-people-fill"></i>
                    <span>Clientes</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="/views/productos.php" class="sidebar-link">
                    <i class="bi bi-box-seam-fill"></i>
                    <span>Inventario</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="/views/pagos.php" class="sidebar-link">
                    <i class="bi bi-cart-fill"></i>
                    <span>Registrar Pago/Venta</span>
                </a>
            </li>
        <?php endif; ?>
        <?php if ($_SESSION['rol'] == 'admin'): ?>
            <li class="sidebar-item">
                <a href="/views/reportes.php" class="sidebar-link">
                    <i class="bi bi-file-earmark-bar-graph-fill"></i>
                    <span>Reportes</span>
                </a>
            </li>
            <?php endif; ?>
    </ul>
    <a href="/controllers/AuthController.php?action=logout" class="logout-btn">
        <i class="bi bi-box-arrow-right"></i>
        Salir
    </a>
</aside>
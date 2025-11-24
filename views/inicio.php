<?php
include_once __DIR__ . '/header.php';
?>
<div class="main-header">
    <h2 class="page-title">
        👋 Bienvenida al Sistema, <?php echo htmlspecialchars($user_name); ?>!
    </h2>
</div>
<div class="stats-grid content-section">

    <div class="stat-card" id="stat-servicios" style="background: rgba(34, 197, 94, 0.7);">
        <div class="spinner-border text-white" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
    </div>

    <div class="stat-card" id="stat-stock" style="background: rgba(59, 130, 246, 0.7);">
        <div class="spinner-border text-white" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
    </div>

    <div class="stat-card" id="stat-reservas" style="background: rgba(168, 85, 247, 0.7);">
        <div class="spinner-border text-white" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
    </div>
    <?php if ($user_role === 'admin'): ?>
        <div class="stat-card" id="stat-ingresos" style="background: rgba(249, 115, 22, 0.7);">
            <div class="spinner-border text-white" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="text-white">Cargando Ingresos...</p>
        </div>

        <div class="stat-card" id="stat-balance" style="background: rgba(100, 116, 139, 0.7);">
            <div class="spinner-border text-white" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="text-white">Calculando Balance...</p>
        </div>
    <?php endif; ?>
    
</div>

<div class="content-section">
    <div class="row">
        <div class="col-lg-7">
            <div class="section-card">
                <h3 class="section-title">
                    <i class="bi bi-bar-chart-fill"></i> Ingresos vs Gastos (Últimos 6 meses)
                </h3>
                <div class="chart-container" style="height: 350px;">
                    <canvas id="chartIngresosGastos"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="section-card">
                <h3 class="section-title">
                    <i class="bi bi-pie-chart-fill"></i> Servicios Más Vendidos (Este Mes)
                </h3>
                <div class="chart-container" style="height: 350px;">
                    <canvas id="chartServiciosPopulares"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-lg-6">
            <div class="section-card">
                <h3 class="section-title">
                    <i class="bi bi-calendar-check"></i> Últimas Reservas
                </h3>
                <div class="table-container">
                    <table class="table table-hover" id="tabla-ultimas-reservas">
                        <thead>
                            <tr><th>Cliente</th><th>Servicio</th><th>Fecha</th><th>Estado</th></tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="4" class="text-center">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="section-card">
                <h3 class="section-title">
                    <i class="bi bi-exclamation-triangle-fill text-warning"></i> Productos con Bajo Stock
                </h3>
                <div class="table-container">
                    <table class="table table-hover" id="tabla-bajo-stock">
                        <thead>
                            <tr><th>Producto</th><th>Stock</th><th>Mínimo</th><th>Acción</th></tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="4" class="text-center">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
include_once __DIR__ . '/footer.php'; 
?>

<script src="../public/js/dashboard.js"></script>
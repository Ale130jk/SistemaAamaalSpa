
<?php
$page_title = "Inicio";
include_once __DIR__ . '/header.php';
?>
<div class="main-header">
    <h2 class="page-title">Bienvenida al Sistema, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Usuario'); ?> !!</h2>
</div>
<div class="stats-grid content-section">
    <div class="stat-card" id="stat-servicios">
        Cargando Servicios...
    </div>
    
    <div class="stat-card" id="stat-stock">
        Cargando Stock...
    </div>
    
    <div class="stat-card" id="stat-reservas">
        Cargando Reservas...
    </div>
    
    <?php if ($_SESSION['rol'] == 'admin'): ?>
    <div class="stat-card" id="stat-ingresos">
        Cargando Ingresos...    
    </div>
    <?php else: ?>
    <div class="stat-card hidden" id="stat-ingresos"></div>
    <?php endif; ?>
</div>

<div class="content-section">
    <div class="row">
        <div class="col-lg-7">
            <div class="section-card">
                <h3 class="section-title">Ingresos vs Gastos (Últimos 6 meses)</h3>
                <div class="chart-container" style="height: 350px;">
                    <canvas id="chartIngresosGastos"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-lg-5">
            <div class="section-card">
                <h3 class="section-title">Servicios Más Vendidos (Este Mes)</h3>
                <div class="chart-container" style="height: 350px;">
                    <canvas id="chartServiciosPopulares"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/footer.php'; ?>
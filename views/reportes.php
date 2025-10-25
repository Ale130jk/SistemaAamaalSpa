<?php
$page_title = "Reportes";
include_once __DIR__ . '/header.php';

// Verificación de rol (Doble chequeo, aunque el sidebar lo oculta)
if ($_SESSION['rol'] !== 'admin') {
    // Redirigir si no es admin
    echo "<script>window.location.href = '/views/inicio.php';</script>";
    exit;
}
?>
<div class="main-header">
    <h2 class="page-title">Reportes Gerenciales</h2>
    <a href="/controllers/ReporteController.php?action=generar_cierre_excel" class="btn btn-success btn-lg">
        Generar Cierre Mensual(Excel)
    </a>
</div>

<div class="content-section">
    <div class="section-card">
        <h3 class="section-title">Evolución de Ingresos Mensuales</h3>
        <p>Total de ingresos..</p>
        <div class="chart-container" style="height: 400px;">
            <canvas id="chartIngresosMensuales"></canvas>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/footer.php'; ?>
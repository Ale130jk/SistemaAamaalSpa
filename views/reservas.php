<?php
$page_title = "Calendario de Reservas";
include_once __DIR__ . '/header.php';
?>

<div class="main-header">
    <h2 class="page-title">Calendario de Reservas</h2>
    <button class="btn btn-primary btn-lg" id="btnNuevaReservaManual">
        <i class="bi bi-calendar-plus-fill"></i> Agendar Cita
    </button>
</div>

<div class="content-section">
    <div class="section-card">
        <div id="calendario-reservas">
            </div>
    </div>
</div>

<?php include_once __DIR__ . '/footer.php'; ?>
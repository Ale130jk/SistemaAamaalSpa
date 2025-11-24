</main> 
    <button id="btn-flotante-ia" class="btn-flotante-ia">
        <i class="bi bi-robot"></i>
    </button>
    
    <?php include_once __DIR__ . '/chat_ia.php'; ?>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/locales/es.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="../public/js/app.js"></script>
    <script src="../public/js/chat_ia.js"></script>
    <?php
    $current = basename($_SERVER['PHP_SELF']);
    $scripts = [
        'inicio.php'    => '../public/js/dashboard.js',
        'productos.php' => '../public/js/productos.js',
        'clientes.php'  => '../public/js/clientes.js',
        'reservas.php'  => '../public/js/reservas.js',
        'pagos.php'     => '../public/js/pagos.js',
        'servicios.php' => '../public/js/servicios.js',
        'reportes.php'  => '../public/js/reportes.js'
    ];
    
    if (isset($scripts[$current])) {
        echo '<script src="' . $scripts[$current] . '"></script>';
    }
    ?>
    
</body>
</html>

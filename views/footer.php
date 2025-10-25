<?php
?>
</main>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" 
            integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" 
            crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.11.0/dist/sweetalert2.all.min.js" integrity="sha256-R5BLw8xRSk8qNhfrJecEA7ve796XGJhcL0Qn4Z3+VTE=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js" 
            integrity="sha256-Q6I0P3M7BkgYSCzPzVfT1OhCsRsHKa6SrZjCLGhxbRQ=" 
            crossorigin="anonymous"></script>
    <script src="/public/js/app.js"></script>
    <?php
    $current_file = basename($_SERVER['PHP_SELF']);
    $page_scripts = [
        'inicio.php' => '/public/js/dashboard.js',
        'productos.php'=> '/public/js/productos.js',
        'clientes.php'  =>'/public/js/clientes.js',
        'reservas.php'  =>'/public/js/reservas.js',
        'pagos.php'=> '/public/js/pagos.js',
        'servicios.php'=>'/public/js/servicios.js',
        'reportes.php'=>'/public/js/reportes.js'
    ];
    if (isset($page_scripts[$current_file])) {
        echo '<script src="' . $page_scripts[$current_file] . '"></script>' . PHP_EOL;
    }
    ?>
</body>
</html>
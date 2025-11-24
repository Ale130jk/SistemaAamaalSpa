<?php

session_start();
ob_start();
try {
    require_once '../config/auth.php';
    require_once '../config/Database.php';
    require_once '../config/helpers.php';
    require_once '../models/Reporte.php';
    require_once '../models/Producto.php';
    require_once '../models/Pago.php'; 

    if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'generar_pdf_detallado') {
        $fpdf_path = __DIR__ . '/../vendor/fpdf/fpdf.php';
        if (file_exists($fpdf_path)) { 
            require_once $fpdf_path; 
        } else {
            throw new Exception("Librería FPDF no encontrada.");
        }
    }
} catch (Throwable $e) {
    ob_end_clean(); 
    http_response_code(500);
    echo json_encode(['error' => 'Error cargando archivos: ' . $e->getMessage()]);
    exit;
}

require_login();

try {
    $db = (new Database())->getConnection();
} catch (Exception $e) {
    manejarExcepcion($e, 'Error de conexión a la base de datos');
}

$reporteModel = new Reporte($db);
$action = $_REQUEST['action'] ?? '';

if (class_exists('FPDF')) {
    class PDF_Reporte extends FPDF {
        private $FechaInicio = ''; private $FechaFin = '';
        
        function SetDateRange($i, $f) { $this->FechaInicio = $i; $this->FechaFin = $f; }
        
        function clean($str) {
            return mb_convert_encoding($str, 'ISO-8859-1', 'UTF-8');
        }

        function Header() {
            $this->SetFont('Arial', 'B', 16); 
            $this->Cell(0, 10, $this->clean('Reporte Detallado de Ingresos'), 0, 1, 'C');
            $this->SetFont('Arial', '', 10); 
            $this->Cell(0, 7, 'Rango: ' . $this->FechaInicio . ' al ' . $this->FechaFin, 0, 1, 'C'); 
            $this->Ln(5);
        }
        
        function Footer() { 
            $this->SetY(-15); 
            $this->SetFont('Arial', 'I', 8); 
            $this->Cell(0, 10, 'Pagina ' . $this->PageNo() . '/{nb}', 0, 0, 'C'); 
        }
        
        function FancyTable($h, $d) {
            $this->SetFillColor(224, 235, 255); 
            $this->SetTextColor(0); 
            $this->SetDrawColor(128, 128, 128); 
            $this->SetFont('Arial', 'B', 9);
            
            $w = [15, 35, 60, 35, 20, 25];

            for ($i = 0; $i < count($h); $i++) { 
                $this->Cell($w[$i], 7, $this->clean($h[$i]), 1, 0, 'C', true); 
            }
            $this->Ln(); 
            
            $this->SetFont('Arial', '', 8); 
            $this->SetFillColor(245, 245, 245); 
            $fill = false; 
            $total = 0;
            
            if (empty($d)) { 
                $this->Cell(array_sum($w), 10, $this->clean('No se encontraron registros'), 1, 1, 'C'); 
                return; 
            }
            
            foreach ($d as $r) {
                $m = (float)$r['monto']; 
                $total += $m;
                
                $this->Cell($w[0], 6, $r['id'], 'LR', 0, 'C', $fill);
                $this->Cell($w[1], 6, date('d/m/Y H:i', strtotime($r['fecha_pago'])), 'LR', 0, 'L', $fill);
                $this->Cell($w[2], 6, $this->clean(substr($r['cliente_nombre'], 0, 35)), 'LR', 0, 'L', $fill);
                
                $serv = $r['servicio_nombre'] ? substr($r['servicio_nombre'], 0, 22) : 'N/A';
                $this->Cell($w[3], 6, $this->clean($serv), 'LR', 0, 'L', $fill);
                
                $this->Cell($w[4], 6, $this->clean($r['metodo_pago']), 'LR', 0, 'C', $fill);
                $this->Cell($w[5], 6, number_format($m, 2), 'LR', 0, 'R', $fill);
                
                $this->Ln(); 
                $fill = !$fill;
            }
            
            $this->Cell(array_sum($w), 0, '', 'T'); 
            $this->Ln(); 
            $this->SetFont('Arial', 'B', 10);
            $this->Cell($w[0]+$w[1]+$w[2]+$w[3]+$w[4], 8, 'TOTAL INGRESOS (S/)', 1, 0, 'R');
            $this->Cell($w[5], 8, number_format($total, 2), 1, 1, 'R');
        }
    }
}

switch ($action) {

    case 'get_dashboard_data':
        ob_clean(); 
        try {
            $prodModel = new Producto($db);
            $data = [
                'resumen' => $reporteModel->getResumenDashboard(),
                'ingresos' => $reporteModel->getIngresosMensuales(),
                'gastos' => $reporteModel->getGastosMensuales(),
                'populares' => $reporteModel->getServiciosPopulares(),
                'ultimas_reservas' => $reporteModel->getUltimasReservas(5), 
                'bajo_stock' => $prodModel->listar(['stock_status' => 'bajo']) 
            ];
            enviarRespuesta(true, 'Datos cargados', $data);
        } catch (Exception $e) {
            manejarExcepcion($e, 'Error dashboard');
        }
        break;

    case 'generar_reporte_detallado':
        ob_clean(); 
        try {
            $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
            $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');

            $totales = $reporteModel->getFinanzasPorRango($fecha_inicio, $fecha_fin);

            $inicio = $fecha_inicio . ' 00:00:00';
            $fin = $fecha_fin . ' 23:59:59';

            $sql_tabla = "SELECT p.id, p.fecha_pago, p.monto, p.metodo_pago,
                            IFNULL(c.nombre, 'Venta Directa') as cliente_nombre,
                            s.nombre as servicio_nombre,
                            r.id as reserva_id
                        FROM pagos p
                        LEFT JOIN clientes c ON p.cliente_id = c.id
                        LEFT JOIN reservas r ON p.reserva_id = r.id
                        LEFT JOIN servicios s ON r.servicio_id = s.id
                        WHERE p.estado_pago = 'pagado'
                        AND p.fecha_pago BETWEEN :inicio AND :fin
                        ORDER BY p.fecha_pago DESC";
            $stmt_tabla = $db->prepare($sql_tabla);
            $stmt_tabla->execute([':inicio' => $inicio, ':fin' => $fin]);
            $pagos_detalle = $stmt_tabla->fetchAll(PDO::FETCH_ASSOC);

            $data = [
                'totales' => $totales,
                'detalle_pagos' => $pagos_detalle
            ];
            enviarRespuesta(true, 'Reporte generado.', $data);

        } catch (Exception $e) {
            manejarExcepcion($e, 'Error al generar el reporte.');
        }
        break;

    case 'generar_pdf_detallado':
        ob_end_clean(); 
        try {
            if (!class_exists('FPDF')) throw new Exception("Librería FPDF no cargada.");

            $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
            $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
            $inicio = $fecha_inicio . ' 00:00:00';
            $fin = $fecha_fin . ' 23:59:59';

            $sql_tabla = "SELECT p.id, p.fecha_pago, p.monto, p.metodo_pago,
                            IFNULL(c.nombre, 'Venta Directa') as cliente_nombre,
                            s.nombre as servicio_nombre,
                            r.id as reserva_id
                        FROM pagos p
                        LEFT JOIN clientes c ON p.cliente_id = c.id
                        LEFT JOIN reservas r ON p.reserva_id = r.id
                        LEFT JOIN servicios s ON r.servicio_id = s.id
                        WHERE p.estado_pago = 'pagado'
                        AND p.fecha_pago BETWEEN :inicio AND :fin
                        ORDER BY p.fecha_pago ASC";
            $stmt_tabla = $db->prepare($sql_tabla);
            $stmt_tabla->execute([':inicio' => $inicio, ':fin' => $fin]);
            $pagos_detalle = $stmt_tabla->fetchAll(PDO::FETCH_ASSOC);
            
            $pdf = new PDF_Reporte(); 
            $pdf->SetDateRange($fecha_inicio, $fecha_fin);
            $pdf->AliasNbPages(); 
            $pdf->AddPage();
            $pdf->SetFont('Arial', '', 10);
            
            $header = ['ID', 'Fecha', 'Cliente', 'Servicio', 'Metodo', 'Monto (S/)'];
            $pdf->FancyTable($header, $pagos_detalle);
            
            $pdf->Output('D', "Reporte_SPA_{$fecha_inicio}_a_{$fecha_fin}.pdf");
            exit;

        } catch (Exception $e) {
            die("Error al generar el PDF: " . $e->getMessage());
        }

    case 'generar_csv_detallado':
        ob_end_clean(); 
        try {
            $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
            $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
            $inicio = $fecha_inicio . ' 00:00:00';
            $fin = $fecha_fin . ' 23:59:59';

            $sql_tabla = "SELECT p.id, p.fecha_pago, p.monto, p.metodo_pago,
                            IFNULL(c.nombre, 'Venta Directa') as cliente_nombre,
                            s.nombre as servicio_nombre,
                            r.id as reserva_id
                        FROM pagos p
                        LEFT JOIN clientes c ON p.cliente_id = c.id
                        LEFT JOIN reservas r ON p.reserva_id = r.id
                        LEFT JOIN servicios s ON r.servicio_id = s.id
                        WHERE p.estado_pago = 'pagado'
                        AND p.fecha_pago BETWEEN :inicio AND :fin
                        ORDER BY p.fecha_pago ASC";
            $stmt_tabla = $db->prepare($sql_tabla);
            $stmt_tabla->execute([':inicio' => $inicio, ':fin' => $fin]);
            $pagos_detalle = $stmt_tabla->fetchAll(PDO::FETCH_ASSOC);

            $filename = "Reporte_Pagos_{$fecha_inicio}_a_{$fecha_fin}.csv";
            header('Content-Type: text/csv; charset=utf-8');
            header("Content-Disposition: attachment; filename=\"$filename\"");

            $output = fopen('php://output', 'w');
            fputs($output, "\xEF\xBB\xBF"); 
            $header = ['ID Pago', 'Fecha y Hora', 'Cliente', 'Servicio', 'Reserva ID', 'Metodo de Pago', 'Monto (S/)'];
            fputcsv($output, $header);

            foreach ($pagos_detalle as $row) {
                $fila_datos = [
                    $row['id'],
                    $row['fecha_pago'],
                    $row['cliente_nombre'],
                    $row['servicio_nombre'] ?? 'N/A',
                    $row['reserva_id'] ?? 'N/A',
                    $row['metodo_pago'],
                    $row['monto']
                ];
                fputcsv($output, $fila_datos);
            }
            
            fclose($output);
            exit; 

        } catch (Exception $e) {
            die("Error al generar el CSV: " . $e->getMessage());
        }

    default:
        ob_end_clean();
        enviarRespuesta(false, 'Acción no válida.', null, 404);
        break;
}
?>
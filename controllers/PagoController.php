<?php

ob_start();
session_start();

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/auth.php';
    require_once __DIR__ . '/../config/Database.php';
    require_once __DIR__ . '/../config/helpers.php';
    require_once __DIR__ . '/../models/Pago.php';

    require_login();

    $db = (new Database())->getConnection();
    
    if (!$db) {
        throw new Exception("Error al conectar con la bd , llame a soporte");
    }

    $pagoModel = new Pago($db);
    $action = $_REQUEST['action'] ?? '';
    $usuario_id = $_SESSION['user_id'] ?? 0;

    ob_clean();

    switch ($action) {
        
        case 'listar':

            $sql = "SELECT 
                        p.id, 
                        p.reserva_id,
                        p.monto, 
                        p.metodo_pago, 
                        p.estado_pago,
                        p.fecha_pago,
                        p.creado_en, 
                        COALESCE(c.nombre, 'Público General') as cliente_nombre,
                        r.servicio_texto
                    FROM pagos p
                    LEFT JOIN clientes c ON p.cliente_id = c.id
                    LEFT JOIN reservas r ON p.reserva_id = r.id
                    ORDER BY p.fecha_pago DESC 
                    LIMIT 100";
            
            $stmt = $db->query($sql);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'reservas_pendientes':
            $sql = "SELECT 
                        r.id, 
                        r.servicio_texto, 
                        r.precio, 
                        c.nombre as cliente 
                    FROM reservas r 
                    JOIN clientes c ON r.cliente_id = c.id
                    WHERE r.estado_reserva IN ('programada', 'confirmada', 'en_progreso')
                    AND r.id NOT IN (
                        SELECT COALESCE(reserva_id, 0) FROM pagos 
                        WHERE estado_pago = 'pagado'
                    )
                    ORDER BY r.fecha_inicio ASC";
            
            $stmt = $db->query($sql);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'registrar':
            if (empty($_POST['monto'])) {
                throw new Exception("El monto es requerido.");
            }
            
            $reserva_id = !empty($_POST['reserva_id']) ? (int)$_POST['reserva_id'] : null;
            $cliente_id = !empty($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : null;
            $monto = (float)$_POST['monto'];
            $metodo = $_POST['metodo_pago'] ?? 'efectivo';

            if ($monto <= 0) {
                throw new Exception("El monto debe ser mayor a 0.");
            }

            $pagoModel->registrar($reserva_id, $cliente_id, $monto, $metodo, 'pagado', $usuario_id);
            
            if ($reserva_id) {
                $db->prepare("UPDATE reservas SET estado_reserva = 'completada' WHERE id = ?")->execute([$reserva_id]);
            }

            echo json_encode(['success' => true, 'message' => 'Pago registrado correctamente.']);
            break;

    case 'anular':
                try {
                    if (empty($_POST['id'])) throw new Exception("ID de pago no proporcionado.");
                    
                    $id = (int)$_POST['id'];
                    $usuario_id = $_SESSION['user_id'];


                    $stmtGet = $db->prepare("SELECT reserva_id FROM pagos WHERE id = ?");
                    $stmtGet->execute([$id]);
                    $pago = $stmtGet->fetch(PDO::FETCH_ASSOC);

                    $stmt = $db->prepare("UPDATE pagos SET estado_pago = 'reembolsado' WHERE id = :id");
                    $stmt->execute([':id' => $id]);

                    if ($pago && $pago['reserva_id']) {
                        $stmtRes = $db->prepare("UPDATE reservas SET estado_reserva = 'programada', modificado_por = :u WHERE id = :rid");
                        $stmtRes->execute([':u' => $usuario_id, ':rid' => $pago['reserva_id']]);
                    }
                    $auditoriaModel->solicitarAccionIA('anular_pago', ['pago_id' => $id], $usuario_id);

                    enviarRespuesta(true, 'Pago anulado. La reserva ha vuelto a estar pendiente.');
                
                } catch (Exception $e) {
                    manejarExcepcion($e);
                }
                break;

        } 
    } catch (Throwable $e) {
        ob_clean();
        error_log("PAGO_ERROR: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'Error del sistema: ' . $e->getMessage(),
            'data' => []
        ]);
    }
?>
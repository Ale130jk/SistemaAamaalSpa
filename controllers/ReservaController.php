<?php
session_start();

try {
    require_once '../config/auth.php';
    require_once '../config/Database.php';
    require_once '../config/helpers.php';
    require_once '../models/Reserva.php';
    require_once '../models/Cliente.php';
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error cargando archivos: ' . $e->getMessage()]);
    exit;
}

require_login();

try {
    $db = (new Database())->getConnection();
} catch (Exception $e) {
    manejarExcepcion($e, 'Error de conexión DB');
}

$reservaModel = new Reserva($db);
$action = $_REQUEST['action'] ?? '';
$usuario_id = (int)$_SESSION['user_id'];

switch ($action) {
   case 'listar':
    try {
        $start = $_GET['start'] ?? date('Y-m-01');
        $end = $_GET['end'] ?? date('Y-m-t');
        error_log("📅 Listando reservas entre: $start y $end");
        $eventos = $reservaModel->obtenerParaCalendario($start, $end);
        $eventos = array_filter($eventos, function($e) { return $e !== null; });
        $eventos = array_values($eventos);
        
        error_log("📊 Eventos a enviar: " . count($eventos));
        
        header('Content-Type: application/json');
        echo json_encode($eventos);
        
    } catch (Exception $e) {
        error_log("Error en reservacontroller: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    break;

   case 'crear':
    try {
        $cliente_id = (int)$_POST['cliente_id'];
        $servicio = $_POST['servicio_texto'];
        $fecha = $_POST['fecha_inicio'];
        $duracion = (int)($_POST['duracion'] ?? 60);
        $precio = (float)($_POST['precio'] ?? 0);
        error_log("Creando reserva: Cliente=$cliente_id, Servicio=$servicio, Fecha=$fecha, Duración=$duracion");
        
        $id = $reservaModel->crear(
            $cliente_id, 
            null, 
            $servicio, 
            $fecha, 
            $duracion, 
            $precio, 
            $usuario_id
        );
        
        error_log("✅ Reserva creada con ID: $id");
        
        enviarRespuesta(true, 'Reserva creada', ['id' => $id]);
        
    } catch (Exception $e) { 
        error_log("Error creando reserva: " . $e->getMessage());
        if(str_contains($e->getMessage(),'Conflicto')) {
            enviarRespuesta(false, $e->getMessage());
        } else {
            manejarExcepcion($e); 
        }
    }
    break;

    case 'actualizar':
        try {
            $reservaModel->actualizar(
                (int)$_POST['id'], 
                (int)$_POST['cliente_id'], 
                $_POST['servicio_texto'], 
                $_POST['fecha_inicio'], 
                (float)$_POST['precio'], 
                (int)($_POST['duracion'] ?? 60), 
                $usuario_id
            );
            enviarRespuesta(true, 'Reserva actualizada');
        } catch (Exception $e) { manejarExcepcion($e); }
        break;

    case 'actualizar_fecha':
        try {
            $reservaModel->actualizarFecha((int)$_POST['id'], $_POST['fecha_inicio'], $usuario_id);
            enviarRespuesta(true, 'Movido');
        } catch (Exception $e) { manejarExcepcion($e); }
        break;

    case 'cancelar':
        try {
            $reservaModel->cancelar((int)$_POST['id'], $usuario_id);
            enviarRespuesta(true, 'Cancelada');
        } catch (Exception $e) { manejarExcepcion($e); }
        break;
        
    case 'buscar':
        echo json_encode(['success'=>true, 'data'=>$reservaModel->buscar($_GET['q']??'')]);
        break;

    case 'verificar_alertas':
        try {
            echo json_encode(['success'=>true, 'data'=>$reservaModel->obtenerProximasAlertas()]);
        } catch (Exception $e) { echo json_encode(['success'=>false, 'data'=>[]]); }
        break;
    case 'obtener':
         $r = $reservaModel->obtenerPorId($_GET['id']);
         if($r) enviarRespuesta(true, 'OK', $r); else enviarRespuesta(false, 'No existe');
         break;  

    default:
        enviarRespuesta(false, 'Acción no válida', null, 404);
}
?>
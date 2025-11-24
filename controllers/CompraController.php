<?php
session_start();
require_once '../config/auth.php';
require_once '../config/Database.php';
require_once '../config/helpers.php';

require_login();

try {
    $db = (new Database())->getConnection();
} catch (Exception $e) {
    manejarExcepcion($e);
}

$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'listar':
        try {
            $busqueda = $_GET['search'] ?? '';
            $sql = "SELECT * FROM compras WHERE estado = 'registrado'";
            $params = [];
            if (!empty($busqueda)) {
                $sql .= " AND (notas LIKE :b OR id LIKE :b)";
                $params[':b'] = "%$busqueda%";
            }

            $sql .= " ORDER BY fecha DESC LIMIT 50";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'crear':
        try {
            $total = (float)$_POST['total'];
            $notas = $_POST['notas'] ?? '';
            
            if ($total <= 0) throw new Exception("El monto debe ser mayor a 0");
            $sql = "INSERT INTO compras (fecha, total, notas, estado, creado_por) 
                    VALUES (NOW(), ?, ?, 'registrado', ?)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$total, $notas, $_SESSION['user_id']]);
            
            enviarRespuesta(true, 'Gasto registrado correctamente');
        } catch (Exception $e) {
            manejarExcepcion($e);
        }
        break;
        
    default:
        enviarRespuesta(false, 'Acción inválida');
}
?>
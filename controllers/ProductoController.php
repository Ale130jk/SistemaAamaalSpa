<?php

ob_start(); 
session_start();

header('Content-Type: application/json');

try {

    try {
        require_once '../config/auth.php';
        require_once '../config/Database.php';
        require_once '../config/helpers.php';
        require_once '../models/Producto.php';
    } catch (Throwable $e) {
        throw new Exception("Error cargando archivos: " . $e->getMessage());
    }

    require_login();

    // conexion no olvidar
    $db = (new Database())->getConnection();
    $productoModel = new Producto($db);
    
    $action = $_REQUEST['action'] ?? '';
    $usuario_id = $_SESSION['user_id'] ?? 0;

    ob_clean();

    switch ($action) {
        
        case 'listar':
            $filtros = [
                'q' => $_GET['q'] ?? '',
                'categoria' => $_GET['categoria'] ?? '',
                'stock_status' => $_GET['stock_status'] ?? '',
                'orden' => $_GET['orden'] ?? 'nombre_asc'
            ];

            if (!method_exists($productoModel, 'listar')) {
                throw new Exception("El método 'listar' no existe en Producto.php. ¿Actualizaste el modelo?");
            }

            $data = $productoModel->listar($filtros);

            $categorias = method_exists($productoModel, 'obtenerCategorias') 
                ? $productoModel->obtenerCategorias() 
                : []; 
            
            echo json_encode([
                'success' => true, 
                'data' => $data,
                'categorias' => $categorias
            ]);
            break;

        case 'crear':
            $nombre = trim($_POST['nombre'] ?? '');
            $sku = trim($_POST['sku'] ?? '');
            if (empty($sku) && method_exists($productoModel, 'generarSKU')) {
                $sku = $productoModel->generarSKU($nombre);
            } elseif (empty($sku)) {
                $sku = 'PROD-' . rand(1000,9999);
            }
            
            $id = $productoModel->crear(
                $sku,
                $nombre,
                $_POST['categoria'] ?? '',
                (float)$_POST['precio_venta'],
                (int)$_POST['stock'],
                $usuario_id
            );
            
            echo json_encode(['success' => true, 'message' => 'Producto creado', 'id' => $id]);
            break;

        case 'actualizar':
            $productoModel->actualizar(
                (int)$_POST['id'],
                $_POST['nombre'],
                $_POST['categoria'],
                (float)$_POST['precio_venta'],
                (int)$_POST['stock'],
                $usuario_id
            );
            echo json_encode(['success' => true, 'message' => 'Actualizado']);
            break;

        case 'eliminar':
            $productoModel->eliminarLogico((int)$_POST['id'], $usuario_id);
            echo json_encode(['success' => true, 'message' => 'Eliminado']);
            break;

        case 'obtener':
            $prod = $productoModel->obtenerPorId((int)$_GET['id']);
            if($prod) echo json_encode(['success' => true, 'data' => $prod]);
            else echo json_encode(['success' => false, 'message' => 'No encontrado']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción inválida']);
    }

} catch (Throwable $e) {

    ob_clean(); 
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error del Servidor: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
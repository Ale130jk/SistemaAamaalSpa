<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once '../config/Database.php';
$action = $_POST['action'] ?? '';
try {
    $db = (new Database())->getConnection();
    if ($action === 'login') {
        $user = trim($_POST['username'] ?? '');
        $pass = trim($_POST['password'] ?? '');
        $stmt = $db->prepare("SELECT id, username, password_hash, rol, nombre_completo FROM usuarios WHERE username = :u AND estado = 'activo' LIMIT 1");
        $stmt->execute([':u' => $user]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($usuario && password_verify($pass, $usuario['password_hash'])) {

            session_regenerate_id(true);
            $_SESSION['user_id'] = $usuario['id'];
            $_SESSION['username'] = $usuario['username'];
            $_SESSION['rol'] = $usuario['rol'];
            $_SESSION['user_name'] = $usuario['nombre_completo'];
            
            echo json_encode([
                'success' => true, 
                'message' => 'Bienvenido',
                'redirect' => 'views/inicio.php' 
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Usuario o contraseña incorrectos']);
        }
    } 
    elseif ($action === 'logout') {
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();
        
        echo json_encode(['success' => true, 'redirect' => '../index.php']);
    }
    
    else {
        echo json_encode(['success' => false, 'message' => 'Acción no válida en Auth']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de servidor: ' . $e->getMessage()]);
}
?>
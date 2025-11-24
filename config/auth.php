<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        // Detectar si es una petición AJAX/Fetch
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') 
                  || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);
        if ($isAjax) {
            ob_clean(); 
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Sesión expirada. Recarga la página.']);
            exit;
        } else {
            header('Location: ../index.php'); 
            exit;
        }
    }
}
function require_role($rol_requerido) {
    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== $rol_requerido) {
        // Si es AJAX
        ob_clean();
        header('Content-Type: application/json');
        http_response_code(403); // Forbidden
        echo json_encode(['success' => false, 'message' => 'Acceso denegado: Requiere rol ' . $rol_requerido]);
        exit;
    }
}
?>
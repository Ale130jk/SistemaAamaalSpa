<?php
if (session_status() === PHP_SESSION_NONE) {    
$secure = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
session_set_cookie_params([
    'lifetime' => 3600,
    'path'     => '/',
    'domain'   => $_SERVER['HTTP_HOST'] ?? '',
    'secure'   => $secure,
    'httponly' => true,
    'samesite' => 'Lax'
]);
    session_start();
}
function require_login(): void 
{
    if (!isset($_SESSION['user_id'])) {
        // Si es una petición AJAX (Fetch/jQuery)...
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            http_response_code(401); // 401 No Autorizado
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Sesión expirada. Por favor, inicie sesión.']);
        } else {
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            header('Location: index.php?page=login&error=session_required');
        }
        
        exit;
    }
}
function require_role(string $required_role): void 
{
    require_login();
    if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== $required_role) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            http_response_code(403); // 403 Prohibido
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Acceso denegado. Permisos insuficientes.']);
        } else {
            header('Location: index.php?page=inicio&error=permission_denied');
        }
        
        exit;
    }
}

function get_current_user_id(): ?int {
    if (!isset($_SESSION['user_id'])) {
    return null;
}
return is_numeric($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}
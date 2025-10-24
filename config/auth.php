<?php
/**
 * auth.php
 * Manejo de sesiones, autenticación, roles y seguridad (CSRF).
*/
// Iniciar la sesión solo si no hay una activa
if (session_status() === PHP_SESSION_NONE) {    
    // Configuración segura de cookies de sesión
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
// --- 2. Funciones de Seguridad (CSRF) ---
/**
 * Genera y almacena un token CSRF (Cross-Site Request Forgery) en la sesión.
 *
 * @return string El token generado.
 */
function generate_csrf_token(): string 
{
    // Si no existe un token, crea uno nuevo.
    // Si ya existe, reutiliza el mismo para toda la sesión.
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida un token CSRF (recibido por POST o GET).
 *
 * @param string $token El token enviado desde el formulario/AJAX.
 * @return bool True si es válido, false si no.
 */
function validate_csrf_token(?string $token): bool 
{
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }

    if ($token === null) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], (string) $token);
}

/**
 * Helper: Imprime el campo input hidden para CSRF.
 * Úsalo dentro de tus formularios HTML.
 */
function csrf_input(): void 
{
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}
/*
 * Middleware verifica si el usuario ha iniciado sesión
 * Si no,detiene la ejecución y redirige o devuelve un JSON 401.
 */
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
/**
 *Verifica si el usuario tiene un rol específico (ej: 'admin').
 *
 * @param string $required_role El rol requerido ('admin', 'empleado').
 */
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
        
        exit; // Detener la ejecución del script
    }
}
/**
 * Obtiene el ID del usuario actual de la sesión.
 */
function get_current_user_id(): ?int {
    if (!isset($_SESSION['user_id'])) {
    return null;
}
return is_numeric($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}
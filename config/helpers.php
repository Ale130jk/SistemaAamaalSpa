<?php
/**
 * Archivo: config/helpers.php
 * Contiene funciones globales de ayuda.
 */

/**
 * Envía una respuesta JSON estandarizada y termina la ejecución.
 *
 * @param bool $success Éxito o fallo de la operación.
 * @param string $message Mensaje descriptivo.
 * @param mixed $data Datos a enviar (opcional).
 * @param int $statusCode Código de estado HTTP (opcional).
 */
function enviarRespuesta(bool $success, string $message, $data = null, int $statusCode = 200) {
    // Aseguramos que el header sea JSON
    if (!headers_sent()) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
    }
    
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}
/**
 * @param Exception
 * @param string .
 */
function manejarExcepcion(Exception $e, string $mensajeAmigable = 'Error interno del servidor.') {
    error_log($e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());
    enviarRespuesta(false, $mensajeAmigable, null, 500);
}
?>
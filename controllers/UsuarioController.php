<?php

session_start();
require_once '../config/auth.php';
require_once '../config/Database.php';
require_once '../config/helpers.php';
require_once '../models/Usuario.php';

require_role('admin');

try {
    $db = (new Database())->getConnection();
} catch (PDOException $e) {
    manejarExcepcion($e, 'Error de conexión a la base de datos.');
}

$usuarioModel = new Usuario($db);
$action = $_REQUEST['action'] ?? '';

switch ($action) {

    case 'listar':
        try {
            $usuarios = $usuarioModel->listar();
            enviarRespuesta(true, 'Usuarios listados.', $usuarios);
        } catch (Exception $e) {
            manejarExcepcion($e, 'Error al listar');
        }
        break;

    case 'crear':
        try {
            $username = $_POST['username'] ?? null;
            $nombre_completo = $_POST['nombre_completo'] ?? null;
            $password_plano = $_POST['password'] ?? null;
            $rol = $_POST['rol'] ?? 'trabajador';
            $estado = $_POST['estado'] ?? 'activo';

            if (empty($username) || empty($nombre_completo) || empty($password_plano)) {
                enviarRespuesta(false, 'Username, Nombre y Contraseña son requeridos.');
            }
            if (!in_array($rol, ['admin', 'trabajador'])) {
                 enviarRespuesta(false, 'Rol no válido.');
            }

            $nuevoId = $usuarioModel->crear($username, $nombre_completo, $password_plano, $rol, $estado);
            enviarRespuesta(true, 'Usuario creado.', ['id' => $nuevoId]);

        } catch (Exception $e) {
            manejarExcepcion($e, $e->getMessage()); 
        }
        break;

    case 'actualizar':
         try {
            $id = (int)($_POST['id'] ?? 0);
            $username = $_POST['username'] ?? null;
            $nombre_completo = $_POST['nombre_completo'] ?? null;
            $password_plano = $_POST['password'] ?? null; 
            $rol = $_POST['rol'] ?? 'trabajador';
            $estado = $_POST['estado'] ?? 'activo';

            if ($id === 0 || empty($username) || empty($nombre_completo)) {
                enviarRespuesta(false, 'ID, Username y Nombre son requeridos.');
            }

            if ($id === (int)$_SESSION['user_id'] && $rol !== 'admin') {
                enviarRespuesta(false, 'No puede cambiar su propio rol de administrador.');
            }
            
            $pass = empty($password_plano) ? null : $password_plano;

            $usuarioModel->actualizar($id, $username, $nombre_completo, $rol, $estado, $pass);
            enviarRespuesta(true, 'Usuario actualizado.');

        } catch (Exception $e) {
            manejarExcepcion($e, $e->getMessage());
        }
        break;

    default:
        enviarRespuesta(false, 'Acción no válida.', null, 404);
        break;
}
?>
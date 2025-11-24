<?php

session_start();
require_once '../config/auth.php';
require_once '../config/Database.php';
require_once '../config/helpers.php';
require_once '../models/Cliente.php';

require_login();

try { $db = (new Database())->getConnection(); } 
catch (PDOException $e) { manejarExcepcion($e); }

$clienteModel = new Cliente($db);
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'listar':
        try {
            enviarRespuesta(true, 'Listado', $clienteModel->listarActivos());
        } catch (Exception $e) { manejarExcepcion($e); }
        break;
    case 'crear':
        try {
            $id = $clienteModel->crear($_POST['nombre'], $_POST['telefono'], $_SESSION['user_id']);
            enviarRespuesta(true, 'Creado', ['id' => $id]);
        } catch (Exception $e) { manejarExcepcion($e); }
        break;
    case 'obtener':
        try {
            $cliente = $clienteModel->obtenerPorId($_GET['id']);
            if ($cliente) enviarRespuesta(true, 'Ok', $cliente);
            else enviarRespuesta(false, 'Cliente no encontrado');
        } catch (Exception $e) { manejarExcepcion($e); }
        break;
    case 'actualizar':
        try {
            $clienteModel->actualizar($_POST['id'], $_POST['nombre'], $_POST['telefono'], $_SESSION['user_id']);
            enviarRespuesta(true, 'Actualizado');
        } catch (Exception $e) { manejarExcepcion($e); }
        break;
    case 'eliminar':
        try {
            $clienteModel->eliminarLogico($_POST['id'], $_SESSION['user_id']);
            enviarRespuesta(true, 'Eliminado');
        } catch (Exception $e) { manejarExcepcion($e); }
        break;

    default:
        enviarRespuesta(false, 'Acción no válida', null, 404);
}
?>
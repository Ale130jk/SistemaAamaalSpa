<?php
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/Usuario.php';

$usuario_admin = "admin";
$password_admin = "123456";


try {
    $database = new Database();
    $db = $database->getConnection();

    $password_hash = password_hash($password_admin, PASSWORD_DEFAULT);

    $usuarioModel = new Usuario($db);

    try {
        $nuevoId = $usuarioModel->crear(
            $usuario_admin,
            'Administrador del Sistema',
            $password_admin, 
            'admin',
            'activo'
        );

        echo "<h1>¡ÉXITO!</h1>";
        echo "Usuario '$usuario_admin' creado con ID: $nuevoId.<br>";
        echo "Contraseña: '$password_admin'<br>";
        echo "<h3>BORRA ESTE ARCHIVO AHORA</h3>";

    } catch (Exception $e) {
        if (str_contains($e->getMessage(), '1062')) {
            echo "<h1>USUARIO YA EXISTE</h1>";
            echo "Actualizando la contraseña de '$usuario_admin'...<br>";

            $user = $usuarioModel->buscarPorLogin($usuario_admin);
            if ($user) {
                $usuarioModel->actualizar(
                    $user['id'],
                    $user['username'],
                    $user['nombre_completo'],
                    $user['rol'],
                    $user['estado'],
                    $password_admin 
                );

                echo "Contraseña de '$usuario_admin' actualizada a '$password_admin'.<br>";
                echo "<h3>BORRA ESTE ARCHIVO AHORA</h3>";
            } else {
                echo "Error: El usuario ya existe pero no se pudo encontrar.";
            }
        } else {
            echo "Error inesperado: " . $e->getMessage();
        }
    }

} catch (PDOException $e) {
    die("Error de conexión a BBDD: " . $e->getMessage());
}
?>
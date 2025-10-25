<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/auth.php';
class Usuario{
    private PDO $db;
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    public function crear(string $username, string $nombre_completo, string $password, string $rol): bool
    {
        try {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $sql = "INSERT INTO usuarios (username, nombre_completo, password_hash, rol, estado)
                    VALUES (:username, :nombre, :pass, :rol, 'activo')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':username' => $username,
                ':nombre'   => $nombre_completo,
                ':pass'     => $hash,
                ':rol'      => $rol
            ]);
            return true;
        } catch (PDOException $e) {
            error_log("Error al crear usuario: " . $e->getMessage());
            return false;
        }
    }
    /*Actualiza datos de un usuario*/
    public function actualizar(int $id, string $nombre_completo, string $rol, string $estado): bool
    {
        try {
            $sql = "UPDATE usuarios SET nombre_completo=:nombre, rol=:rol, estado=:estado WHERE id=:id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':nombre' => $nombre_completo,
                ':rol'    => $rol,
                ':estado' => $estado,
                ':id'     => $id
            ]);
            return true;
        } catch (PDOException $e) {
            error_log("Error al actualizar usuario: " . $e->getMessage());
            return false;
        }
    }
    /*Obtiene todos los usuarios activos*/
    public function listar(): array
    {
        $stmt = $this->db->query("SELECT id, username, nombre_completo, rol, estado FROM usuarios WHERE estado='activo'");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    /*Busca usuario por ID.*/
    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }
    /*Elimina lógicamente (desactiva) un usuario*/
    public function desactivar(int $id): bool
    {
        try {
            $sql = "UPDATE usuarios SET estado='inactivo' WHERE id=:id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return true;
        } catch (PDOException $e) {
            error_log("Error al desactivar usuario: " . $e->getMessage());
            return false;
        }
    }
}

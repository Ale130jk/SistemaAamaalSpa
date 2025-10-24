<?php
/**
 * Cliente.php
 * Modelo para la gestión de clientes (personas que reservan servicios).
 *
 * @package spa_mype\models
 */

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/auth.php';

class Cliente
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Crea un nuevo cliente.
     */
    public function crear(string $nombre, string $telefono, string $correo, string $direccion): bool
    {
        try {
            $sql = "INSERT INTO clientes (nombre, telefono, correo, direccion, creado_por)
                    VALUES (:nombre, :telefono, :correo, :direccion, :creado_por)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':nombre'     => $nombre,
                ':telefono'   => $telefono,
                ':correo'     => $correo,
                ':direccion'  => $direccion,
                ':creado_por' => get_current_user_id()
            ]);
            return true;
        } catch (PDOException $e) {
            error_log("Error al crear cliente: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza datos del cliente.
     */
    public function actualizar(int $id, string $nombre, string $telefono, string $correo, string $direccion): bool
    {
        try {
            $sql = "UPDATE clientes SET nombre=:nombre, telefono=:telefono, correo=:correo, direccion=:direccion WHERE id=:id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':nombre'    => $nombre,
                ':telefono'  => $telefono,
                ':correo'    => $correo,
                ':direccion' => $direccion,
                ':id'        => $id
            ]);
            return true;
        } catch (PDOException $e) {
            error_log("Error al actualizar cliente: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Listar todos los clientes.
     */
    public function listar(): array
    {
        $stmt = $this->db->query("SELECT * FROM clientes ORDER BY nombre ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar cliente por ID.
     */
    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM clientes WHERE id=:id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        return $cliente ?: null;
    }

    /**
     * Eliminar cliente (opcional: lógica o física según reglas).
     */
    public function eliminar(int $id): bool
    {
        try {
            $sql = "DELETE FROM clientes WHERE id=:id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return true;
        } catch (PDOException $e) {
            error_log("Error al eliminar cliente: " . $e->getMessage());
            return false;
        }
    }
}

<?php
class Cliente {
    
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function crear(string $nombre, ?string $telefono, int $creado_por): int
    {
        try {

            $sql = "INSERT INTO clientes (nombre, telefono, estado, creado_por, modificado_por)
                    VALUES (:nombre, :telefono, 'activo', :creado_por, :modificado_por)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':nombre'         => $nombre,
                ':telefono'       => $telefono,
                ':creado_por'     => $creado_por,
                ':modificado_por' => $creado_por 
            ]);
            
            $lastId = (int)$this->db->lastInsertId();
            if ($lastId === 0) {
                 throw new Exception("No se pudo obtener el ID del nuevo cliente después de la inserción.");
            }
            return $lastId;

        } catch (PDOException $e) {
            error_log("Error al crear cliente: " . $e->getMessage());
            throw new Exception("Error en la base de datos al crear el cliente: " . $e->getMessage());
        }
    }

    public function actualizar(int $id, string $nombre, ?string $telefono, int $modificado_por): bool
    {
        try {
            $sql = "UPDATE clientes 
                    SET nombre = :nombre, 
                        telefono = :telefono, 
                        modificado_por = :modificado_por
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            
            $stmt->execute([
                ':nombre'         => $nombre,
                ':telefono'       => $telefono,
                ':modificado_por' => $modificado_por,
                ':id'             => $id
            ]);
            
            return $stmt->rowCount() > 0; 

        } catch (PDOException $e) {
            error_log("Error al actualizar cliente: " . $e->getMessage());
            throw new Exception("Error en la base de datos al actualizar el cliente: " . $e->getMessage());
        }
    }

    public function listarActivos(): array
    {
        $sql = "SELECT id, nombre, telefono, estado FROM clientes WHERE estado = 'activo' ORDER BY nombre ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function obtenerPorId(int $id): array|false
    {
        $stmt = $this->db->prepare("SELECT id, nombre, telefono, estado FROM clientes WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function eliminarLogico(int $id, int $modificado_por): bool
    {
        try {
            $sql = "UPDATE clientes 
                    SET estado = 'inactivo', 
                        modificado_por = :modificado_por
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id'             => $id,
                ':modificado_por' => $modificado_por
            ]);
            
            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            error_log("Error al eliminar lógicamente cliente: " . $e->getMessage());
            throw new Exception("Error en la base de datos al eliminar el cliente: " . $e->getMessage());
        }
    }
    public function obtenerTodos($filtros = []) {
    try {
        $sql = "SELECT * FROM clientes WHERE 1=1";
        $params = [];
        
        if (!empty($filtros['estado'])) {
            $sql .= " AND estado = :estado";
            $params[':estado'] = $filtros['estado'];
        }
        
        $sql .= " ORDER BY nombre ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        throw new Exception("Error al obtener clientes: " . $e->getMessage());
    }
}
}
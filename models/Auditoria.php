<?php

class Auditoria {
    
    private PDO $db;
    
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function solicitarAccionIA(string $accion_tipo, array $payload, int $solicitado_por_id): int
    {
        try {
            $sql = "INSERT INTO acciones_log (accion_tipo, payload, estado, solicitado_por)
                    VALUES (:accion_tipo, :payload, 'pendiente', :solicitado_por)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':accion_tipo'     => $accion_tipo,
                ':payload'         => json_encode($payload),
                ':solicitado_por'  => $solicitado_por_id
            ]);
            
            $lastId = (int)$this->db->lastInsertId();
            if ($lastId === 0) {
                 throw new Exception("No se pudo obtener el ID de la nueva acción.");
            }
            return $lastId;

        } catch (PDOException $e) {
            error_log("Error al solicitar acción IA: " . $e->getMessage());
            throw new Exception("Error en BBDD al solicitar acción IA: " . $e->getMessage());
        }
    }

    public function confirmarAccion(int $accion_id, int $admin_user_id): bool
    {
        try {
            $sql = "UPDATE acciones_log 
                    SET estado = 'ejecutado', 
                        ejecutado_por = :admin_id, 
                        ejecutado_en = CURRENT_TIMESTAMP
                    WHERE id = :accion_id AND estado = 'pendiente'";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':admin_id'  => $admin_user_id,
                ':accion_id' => $accion_id
            ]);
            return $stmt->rowCount() > 0; 

        } catch (PDOException $e) {
            error_log("Error al confirmar acción IA: " . $e->getMessage());
            throw new Exception("Error en BBDD al confirmar acción IA: " . $e->getMessage());
        }
    }

    public function getAccionesPendientes(): array
    {
        $sql = "SELECT * FROM acciones_log 
                WHERE estado = 'pendiente' 
                ORDER BY creado_en ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerAccionPorId(int $id): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM acciones_log WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
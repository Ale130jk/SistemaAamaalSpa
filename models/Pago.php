<?php

class Pago {
    private PDO $db;
    public function __construct(PDO $db){
        $this->db = $db;
    }
    public function registrar(
        ?int $reserva_id, 
        ?int $cliente_id, 
        float $monto, 
        string $metodo_pago, 
        string $estado_pago,
        int $registrado_por
    ): bool {
        
        try {
            if ($reserva_id && !$cliente_id) {
                $stmtRes = $this->db->prepare("SELECT cliente_id FROM reservas WHERE id = ?");
                $stmtRes->execute([$reserva_id]);
                $res = $stmtRes->fetch(PDO::FETCH_ASSOC);
                if ($res) $cliente_id = $res['cliente_id'];
            }
            $sql = "INSERT INTO pagos (reserva_id, cliente_id, monto, metodo_pago, estado_pago, fecha_pago, registrado_por) 
                    VALUES (:rid, :cid, :monto, :metodo, :estado, NOW(), :user)";
            
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([
                ':rid'    => $reserva_id,
                ':cid'    => $cliente_id,
                ':monto'  => $monto,
                ':metodo' => $metodo_pago,
                ':estado' => $estado_pago,
                ':user'   => $registrado_por
            ]);

        } catch (PDOException $e) {
            error_log("Error al registrar pago: " . $e->getMessage());
            throw new Exception("Error en BBDD al registrar pago: " . $e->getMessage());
        }
    }

    public function listar(int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT 
                    p.id, p.fecha_pago, p.monto, p.metodo_pago, p.estado_pago,
                    IFNULL(c.nombre, 'Público General') as cliente_nombre,
                    r.servicio_texto,
                    p.reserva_id,
                    p.created_at
                FROM pagos p
                LEFT JOIN clientes c ON p.cliente_id = c.id
                LEFT JOIN reservas r ON p.reserva_id = r.id
                ORDER BY p.created_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
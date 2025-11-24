<?php
class Reserva {

    private PDO $db;

    public function __construct(PDO $db){
        $this->db = $db;
    }

    public function crear(
        int $cliente_id, 
        ?int $servicio_id,      
        string $servicio_texto,  
        string $fecha_inicio_str, 
        int $duracion_total_min,
        float $precio_servicio,
        int $creado_por
    ): int {
        try {
            $fecha_inicio = new DateTime($fecha_inicio_str);
            $fecha_fin_calculada = (clone $fecha_inicio)->add(new DateInterval("PT{$duracion_total_min}M"));
            
            $sql_check = "SELECT id FROM reservas 
                          WHERE estado_reserva NOT IN ('cancelada', 'completada')
                          AND (:inicio < (fecha_inicio + INTERVAL duracion_min MINUTE) AND :fin > fecha_inicio)";
            
            $stmt_check = $this->db->prepare($sql_check);
            $stmt_check->execute([
                ':inicio' => $fecha_inicio->format('Y-m-d H:i:s'),
                ':fin'    => $fecha_fin_calculada->format('Y-m-d H:i:s')
            ]);

            if ($stmt_check->fetch()) {
                throw new Exception("Conflicto de horario. Ya existe una reserva en ese rango.");
            }
            $sql_insert = "INSERT INTO reservas 
                                (cliente_id, servicio_id, servicio_texto, fecha_inicio, duracion_min, precio, estado_reserva, creado_por, modificado_por)
                           VALUES 
                                (:cliente_id, :servicio_id, :servicio_texto, :fecha_inicio, :duracion, :precio, 'programada', :creado_por, :creado_por)";
            
            $stmt_insert = $this->db->prepare($sql_insert);
            $stmt_insert->execute([
                ':cliente_id'    => $cliente_id,
                ':servicio_id'   => $servicio_id,
                ':servicio_texto'=> $servicio_texto, 
                ':fecha_inicio'  => $fecha_inicio->format('Y-m-d H:i:s'),
                ':duracion'      => $duracion_total_min,
                ':precio'        => $precio_servicio,
                ':creado_por'    => $creado_por
            ]);

            $lastId = (int)$this->db->lastInsertId();
            if ($lastId === 0) throw new Exception("No se pudo crear la reserva");
            return $lastId;

        } catch (PDOException $e) {
            error_log("Error BBDD crear reserva: " . $e->getMessage());
            throw new Exception("Error al crear reserva");
        }
    }

    public function actualizar(int $id, int $cliente_id, string $servicio_texto, string $fecha_inicio_str, float $precio, int $modificado_por): bool {
        try {
            $sql = "UPDATE reservas 
                    SET cliente_id = :cliente_id, 
                        servicio_texto = :servicio_texto, 
                        fecha_inicio = :fecha_inicio, 
                        precio = :precio,
                        modificado_por = :modificado_por
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':cliente_id' => $cliente_id,
                ':servicio_texto' => $servicio_texto,
                ':fecha_inicio' => $fecha_inicio_str,
                ':precio' => $precio,
                ':modificado_por' => $modificado_por,
                ':id' => $id
            ]);
        } catch (PDOException $e) {
            throw new Exception("Error al actualizar reserva: " . $e->getMessage());
        }
    }
    public function actualizarFecha(int $id, string $fecha_inicio_str, int $modificado_por): bool {
        try {
            $sql = "UPDATE reservas SET fecha_inicio = :fecha, modificado_por = :mod WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':fecha' => $fecha_inicio_str, ':mod' => $modificado_por, ':id' => $id]);
        } catch (PDOException $e) {
            throw new Exception("Error al mover reserva: " . $e->getMessage());
        }
    }

    public function cancelar(int $id, int $modificado_por): bool {
        try {
            $sql = "UPDATE reservas SET estado_reserva = 'cancelada', modificado_por = :mod WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':mod' => $modificado_por, ':id' => $id]);
        } catch (PDOException $e) {
            throw new Exception("Error al cancelar reserva: " . $e->getMessage());
        }
    }

    // calendar.js
    public function obtenerParaCalendario(string $start, string $end): array
    {
        $sql = "SELECT 
                    r.id, 
                    CONCAT(COALESCE(s.nombre, r.servicio_texto), ' - ', c.nombre) as title, 
                    r.fecha_inicio as start,
                    (r.fecha_inicio + INTERVAL r.duracion_min MINUTE) as end,
                    r.estado_reserva,
                    r.precio,
                    c.id as cliente_id,
                    c.nombre as cliente_nombre,
                    COALESCE(s.nombre, r.servicio_texto) as servicio_nombre, 
                    c.telefono as cliente_telefono
                FROM reservas r
                JOIN clientes c ON r.cliente_id = c.id
                LEFT JOIN servicios s ON r.servicio_id = s.id
                WHERE r.fecha_inicio BETWEEN :start AND :end
                AND r.estado_reserva <> 'cancelada'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':start' => $start, ':end' => $end]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function buscar(string $termino): array
    {
        $sql = "SELECT 
                    r.id, 
                    r.fecha_inicio, 
                    r.servicio_texto,
                    c.nombre as cliente_nombre
                FROM reservas r
                JOIN clientes c ON r.cliente_id = c.id
                WHERE c.nombre LIKE :term 
                   OR r.servicio_texto LIKE :term
                ORDER BY r.fecha_inicio DESC 
                LIMIT 10";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':term' => "%$termino%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerProximasAlertas(): array
    {
        $sql = "SELECT r.id, r.fecha_inicio, c.nombre as cliente, COALESCE(s.nombre, r.servicio_texto) as servicio 
                FROM reservas r JOIN clientes c ON r.cliente_id=c.id LEFT JOIN servicios s ON r.servicio_id=s.id 
                WHERE r.fecha_inicio BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 20 MINUTE) AND r.estado_reserva IN ('programada','confirmada')";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId(int $id): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM reservas WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
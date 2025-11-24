<?php

if (!class_exists('Reserva')) {

class Reserva {
    private PDO $db;

    public function __construct(PDO $db){
        $this->db = $db;
    }

    public function crear(int $cliente_id, ?int $servicio_id, string $servicio_texto, string $fecha_inicio_str, int $duracion_min, float $precio, int $creado_por): int {
        try {
            $fecha = new DateTime($fecha_inicio_str);
            $sql_check = "SELECT id FROM reservas 
                          WHERE estado_reserva NOT IN ('cancelada', 'completada')
                          AND (:inicio < DATE_ADD(fecha_inicio, INTERVAL duracion_min MINUTE) 
                          AND DATE_ADD(:fin, INTERVAL :dur MINUTE) > fecha_inicio)";
            
            $stmt = $this->db->prepare($sql_check);
            $stmt->execute([
                ':inicio' => $fecha->format('Y-m-d H:i:s'),
                ':fin'    => $fecha->format('Y-m-d H:i:s'),
                ':dur'    => $duracion_min
            ]);

            if ($stmt->fetch()) {
                throw new Exception("⚠️ Conflicto: Ya existe una cita en ese horario.");
            }

            $sql = "INSERT INTO reservas (cliente_id, servicio_id, servicio_texto, fecha_inicio, duracion_min, precio, estado_reserva, creado_por, modificado_por) 
                    VALUES (:c, :sid, :stxt, :fini, :dur, :pre, 'programada', :crea, :modi)";
            
            $this->db->prepare($sql)->execute([
                ':c' => $cliente_id, ':sid' => $servicio_id, ':stxt' => $servicio_texto, 
                ':fini' => $fecha->format('Y-m-d H:i:s'), ':dur' => $duracion_min, ':pre' => $precio, 
                ':crea' => $creado_por, ':modi' => $creado_por
            ]);

            return (int)$this->db->lastInsertId();

        } catch (PDOException $e) {
            error_log("Error BBDD crear: " . $e->getMessage());
            throw new Exception("Error en BBDD al guardar.");
        }
    }
    public function getUltimasReservas(int $limit = 5): array
    {
        $sql = "SELECT 
                    r.id, 
                    r.fecha_inicio, 
                    r.estado_reserva,
                    c.nombre as cliente_nombre,
                    r.servicio_texto as servicio
                FROM reservas r
                JOIN clientes c ON r.cliente_id = c.id
                WHERE r.estado_reserva != 'cancelada'
                ORDER BY r.fecha_inicio DESC
                LIMIT :limite"; 
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limite', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function actualizar(int $id, int $cliente_id, string $servicio_texto, string $fecha_inicio_str, float $precio, int $duracion_min, int $modificado_por): bool {
        try {
            $fecha = new DateTime($fecha_inicio_str);
            $sql = "UPDATE reservas 
                    SET cliente_id = :cid, 
                        servicio_id = NULL, 
                        servicio_texto = :stxt, 
                        fecha_inicio = :fini, 
                        duracion_min = :dur,
                        precio = :pre,
                        modificado_por = :mod
                    WHERE id = :id";
            
            return $this->db->prepare($sql)->execute([
                ':cid' => $cliente_id, ':stxt' => $servicio_texto, 
                ':fini' => $fecha->format('Y-m-d H:i:s'), ':dur' => $duracion_min, 
                ':pre' => $precio, ':mod' => $modificado_por, ':id' => $id
            ]);
        } catch (PDOException $e) {
            throw new Exception("Error al actualizar: " . $e->getMessage());
        }
    }

    public function actualizarFecha(int $id, string $fecha, int $u): bool {
        return $this->db->prepare("UPDATE reservas SET fecha_inicio=?, modificado_por=? WHERE id=?")->execute([$fecha, $u, $id]);
    }
    
    public function cancelar(int $id, int $u): bool {
        return $this->db->prepare("UPDATE reservas SET estado_reserva='cancelada', modificado_por=? WHERE id=?")->execute([$u, $id]);
    }
public function obtenerParaCalendario($start, $end) {
    try {
        $sql = "
            SELECT 
                r.id, 
                r.fecha_inicio,
                r.estado_reserva,
                r.servicio_texto,
                r.duracion_min,
                r.precio,
                c.nombre AS cliente_nombre,
                c.id AS cliente_id
            FROM reservas r
            JOIN clientes c ON r.cliente_id = c.id
            WHERE r.estado_reserva != 'cancelada'
            AND r.fecha_inicio BETWEEN ? AND ?
            ORDER BY r.fecha_inicio ASC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$start, $end]); 
        
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("📅 Reservas encontradas: " . count($data));

        return array_map(function($r) {
            try {
                $inicio = new DateTime($r['fecha_inicio']);
                $duracion = (int)($r['duracion_min'] ?? 60); 
                $fin = (clone $inicio)->add(new DateInterval("PT{$duracion}M"));
                
                return [
                    'id' => (string)$r['id'],
                    'title' => $r['cliente_nombre'] . ' - ' . $r['servicio_texto'],
                    'start' => $inicio->format('Y-m-d\TH:i:s'),
                    'end' => $fin->format('Y-m-d\TH:i:s'),
                    'fecha_inicio' => $r['fecha_inicio'],
                    'cliente_nombre' => $r['cliente_nombre'],
                    'estado_reserva' => $r['estado_reserva'],
                    
                    'extendedProps' => [
                        'cliente_id' => (int)$r['cliente_id'],
                        'servicio_texto' => $r['servicio_texto'],
                        'precio' => (float)$r['precio'],
                        'duracion_min' => $duracion,
                        'estado_reserva' => $r['estado_reserva']
                    ]
                ];
            } catch (Exception $e) {
                error_log("❌ Error procesando reserva: " . $e->getMessage());
                return null;
            }
        }, $data);
        
    } catch (PDOException $e) {
        error_log("❌ Error en obtenerParaCalendario: " . $e->getMessage());
        return [];
    }
}
    public function obtenerPorId(int $id) {
        $stmt = $this->db->prepare("SELECT * FROM reservas WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function buscar(string $termino): array {
        $stmt = $this->db->prepare("SELECT r.id, r.fecha_inicio, r.servicio_texto, c.nombre as cliente_nombre FROM reservas r JOIN clientes c ON r.cliente_id=c.id WHERE c.nombre LIKE ? OR r.servicio_texto LIKE ? ORDER BY fecha_inicio DESC LIMIT 10");
        $stmt->execute(["%$termino%", "%$termino%"]); 
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerProximasAlertas(): array {
        return $this->db->query("SELECT r.id, r.fecha_inicio, c.nombre as cliente, r.servicio_texto as servicio FROM reservas r JOIN clientes c ON r.cliente_id=c.id WHERE r.fecha_inicio BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 20 MINUTE) AND r.estado_reserva IN ('programada','confirmada')")->fetchAll(PDO::FETCH_ASSOC);
    }
}

} 
?>
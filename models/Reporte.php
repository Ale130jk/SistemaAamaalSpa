<?php

class Reporte {
    
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

public function getResumenDashboard(): array|false
    {
        $sql = "SELECT

          (SELECT COUNT(*) FROM clientes WHERE estado='activo') AS total_clientes_activos,
          (SELECT COUNT(DISTINCT servicio_texto) FROM reservas) AS total_servicios_activos,
          (SELECT COUNT(*) FROM reservas WHERE DATE(fecha_inicio) = DATE(CURRENT_DATE()) AND estado_reserva <> 'cancelada') AS reservas_hoy,
          (SELECT COUNT(*) FROM productos WHERE stock < 10 AND estado='activo') AS productos_stock_bajo,

          (SELECT IFNULL(SUM(monto),0) FROM pagos 
           WHERE estado_pago='pagado' 
           AND YEAR(fecha_pago)=YEAR(CURRENT_DATE()) AND MONTH(fecha_pago)=MONTH(CURRENT_DATE())
          ) AS ingresos_mes,

          (SELECT IFNULL(SUM(total),0) FROM compras 
           WHERE estado='registrado' 
           AND YEAR(fecha)=YEAR(CURRENT_DATE()) AND MONTH(fecha)=MONTH(CURRENT_DATE())
          ) AS gastos_mes";
        
        $stmt = $this->db->query($sql);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            $data['utilidad_mes'] = $data['ingresos_mes'] - $data['gastos_mes'];
        }

        return $data;
    }

    public function getFinanzasPorRango($inicio, $fin): array
    {

        $sqlIngresos = "SELECT IFNULL(SUM(monto), 0) as total, COUNT(*) as cantidad 
                        FROM pagos 
                        WHERE estado_pago='pagado' 
                        AND fecha_pago BETWEEN :i1 AND :f1";
        $stmtI = $this->db->prepare($sqlIngresos);
        $stmtI->execute([':i1' => $inicio . ' 00:00:00', ':f1' => $fin . ' 23:59:59']);
        $ingresos = $stmtI->fetch(PDO::FETCH_ASSOC);

        $sqlGastos = "SELECT IFNULL(SUM(total), 0) as total, COUNT(*) as cantidad 
                      FROM compras 
                      WHERE estado='registrado' 
                      AND fecha BETWEEN :i2 AND :f2";
        $stmtG = $this->db->prepare($sqlGastos);
        $stmtG->execute([':i2' => $inicio . ' 00:00:00', ':f2' => $fin . ' 23:59:59']);
        $gastos = $stmtG->fetch(PDO::FETCH_ASSOC);

        return [
            'ingresos' => $ingresos['total'],
            'cant_ingresos' => $ingresos['cantidad'],
            'gastos' => $gastos['total'],
            'cant_gastos' => $gastos['cantidad'],
            'balance' => $ingresos['total'] - $gastos['total']
        ];
    }

    public function getIngresosMensuales(): array
    {
        $sql = "SELECT DATE_FORMAT(fecha_pago, '%Y-%m') AS mes, SUM(monto) AS total_ingresos 
                FROM pagos WHERE estado_pago='pagado' 
                AND fecha_pago >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY mes ORDER BY mes ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getGastosMensuales(): array
    {
        $sql = "SELECT 
                    DATE_FORMAT(fecha, '%Y-%m') AS mes, 
                    SUM(total) AS total_gastos
                FROM compras 
                WHERE estado = 'registrado'
                AND fecha >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY mes 
                ORDER BY mes ASC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function getUltimasReservas(int $limit = 5): array {
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
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getServiciosPopulares(int $limit = 5): array {
        $inicioMes = date('Y-m-01 00:00:00');
        $finMes    = date('Y-m-t 23:59:59');

        $sql = "SELECT 
                    servicio_texto as nombre, 
                    COUNT(id) as total_reservas
                FROM reservas
                WHERE fecha_inicio BETWEEN :inicio AND :fin
                  AND estado_reserva != 'cancelada' 
                GROUP BY servicio_texto
                ORDER BY total_reservas DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':inicio', $inicioMes);
        $stmt->bindValue(':fin', $finMes);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMovimientosRecientes(int $limit = 10): array {
        $sql = "(SELECT 'Ingreso' as tipo, fecha_pago as fecha, monto, 'Pago Servicio' as descripcion FROM pagos WHERE estado_pago = 'pagado')
                UNION ALL
                (SELECT 'Gasto' as tipo, fecha, total as monto, 'Compra Insumos' as descripcion FROM compras WHERE estado = 'registrado')
                ORDER BY fecha DESC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
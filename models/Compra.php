<?php
class Compra {

    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function registrar(float $total, ?string $notas, int $creado_por, array $items): int
    {
        try {
            $this->db->beginTransaction();
            
            $sql_compra = "INSERT INTO compras (total, notas, estado, creado_por, modificado_por)
                           VALUES (:total, :notas, 'registrado', :creado_por, :creado_por)";
            
            $stmt_compra = $this->db->prepare($sql_compra);
            $stmt_compra->execute([
                ':total' => $total,
                ':notas' => $notas,
                ':creado_por' => $creado_por
            ]);
            $compra_id = (int)$this->db->lastInsertId();

            if ($compra_id === 0) {
                throw new Exception("No se pudo crear la cabecera de la compra.");
            }

            $sql_item = "INSERT INTO compra_items (compra_id, producto_id, cantidad, precio_unit)
                         VALUES (:compra_id, :producto_id, :cantidad, :precio)";
            $stmt_item = $this->db->prepare($sql_item);

            foreach ($items as $item) {
                $stmt_item->execute([
                    ':compra_id'   => $compra_id,
                    ':producto_id' => (int)$item['producto_id'],
                    ':cantidad'    => (int)$item['cantidad'],
                    ':precio'      => (float)$item['precio']
                ]);
            }
            
            $this->db->commit();
            return $compra_id;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error al registrar compra: " . $e->getMessage());
            throw new Exception("Error en BBDD al registrar la compra: ". $e->getMessage()); 
        }
    }
}
<?php
class Producto {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function listar(array $filtros = []): array {
        $sql = "SELECT id, sku, nombre, categoria, precio_venta, stock, estado,
                CASE 
                    WHEN stock = 0 THEN 'agotado'
                    WHEN stock <= 5 THEN 'bajo'
                    ELSE 'disponible'
                END as estado_stock
                FROM productos WHERE estado = 'activo'";
        
        $params = [];

        if (!empty($filtros['q'])) {
            $sql .= " AND (nombre LIKE :q OR sku LIKE :q)";
            $params[':q'] = "%" . $filtros['q'] . "%";
        }
        if (!empty($filtros['categoria'])) {
            $sql .= " AND categoria = :cat";
            $params[':cat'] = $filtros['categoria'];
        }
        if (!empty($filtros['stock_status'])) {
            if ($filtros['stock_status'] === 'agotado') $sql .= " AND stock = 0";
            elseif ($filtros['stock_status'] === 'bajo') $sql .= " AND stock > 0 AND stock <= 5";
            elseif ($filtros['stock_status'] === 'disponible') $sql .= " AND stock > 5";
        }

        $orden = $filtros['orden'] ?? 'nombre_asc';
        if ($orden === 'precio_desc') $sql .= " ORDER BY precio_venta DESC";
        else if ($orden === 'precio_asc') $sql .= " ORDER BY precio_venta ASC";
        else if ($orden === 'stock_asc') $sql .= " ORDER BY stock ASC";
        else $sql .= " ORDER BY nombre ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerCategorias(): array {
        $stmt = $this->db->query("SELECT DISTINCT categoria FROM productos WHERE estado='activo' AND categoria IS NOT NULL ORDER BY categoria");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

        public function crear($sku, $nombre, $cat, $precio, $stock, $creador): int {
        if ($precio < 0 || $stock < 0) throw new Exception("Valores negativos no permitidos.");

        if (empty($sku)) $sku = $this->generarSKU($nombre);
        $stmt = $this->db->prepare("SELECT id FROM productos WHERE sku = ? AND estado='activo'");
        $stmt->execute([$sku]);
        if($stmt->fetch()) throw new Exception("El SKU $sku ya existe.");
        $sql = "INSERT INTO productos (sku, nombre, categoria, precio_venta, stock, estado, creado_por, modificado_por) 
                VALUES (?, ?, ?, ?, ?, 'activo', ?, ?)";

        $this->db->prepare($sql)->execute([
            $sku,       
            $nombre,      
            $cat,          
            $precio,      
            $stock,        
            $creador,      
            $creador      
        ]);
        
        return (int)$this->db->lastInsertId();
    }

    public function actualizar($id, $nombre, $cat, $precio, $stock, $modificador): bool {
        $sql = "UPDATE productos SET nombre=?, categoria=?, precio_venta=?, stock=?, modificado_por=?, actualizado_en=NOW() WHERE id=?";
        return $this->db->prepare($sql)->execute([$nombre, $cat, $precio, $stock, $modificador, $id]);
    }

    public function eliminarLogico($id, $modificador): bool {
        return $this->db->prepare("UPDATE productos SET estado='inactivo', modificado_por=? WHERE id=?")->execute([$modificador, $id]);
    }

    public function obtenerPorId($id) {
        $stmt = $this->db->prepare("SELECT * FROM productos WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function generarSKU($nombre) {
        return strtoupper(substr($nombre, 0, 3)) . '-' . rand(1000, 9999);
    }
}
?>
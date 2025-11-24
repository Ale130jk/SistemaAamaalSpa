<?php
class Usuario {
    private PDO $db;
    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function buscarPorLogin(string $username): array|false {
        try {
            $sql = "SELECT * FROM usuarios 
                    WHERE username = :username
                    AND estado = 'activo'";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':username' => $username]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error al buscar usuario: " . $e->getMessage());
        }
    }
    public function verificarPassword(string $password_plano, string $password_hash): bool {
        return password_verify($password_plano, $password_hash);
    }

    public function listar(): array {
        try {
            $sql = "SELECT id, username, nombre_completo, rol, estado, creado_en FROM usuarios ORDER BY nombre_completo ASC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error al listar usuarios: " . $e->getMessage());
        }
    }

    public function obtenerPorId(int $id): array|false {
        try {
            $sql = "SELECT id, username, nombre_completo, rol, estado FROM usuarios WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error al obtener usuario: " . $e->getMessage());
        }
    }

    public function crear(string $username, string $nombre_completo, string $password_plano, string $rol, string $estado): ?int {
        try {
            $sql = "INSERT INTO usuarios (username, nombre_completo, password_hash, rol, estado) 
                    VALUES (:username, :nombre_completo, :password_hash, :rol, :estado)";
            
            $stmt = $this->db->prepare($sql);
            
            $password_hash = password_hash($password_plano, PASSWORD_DEFAULT);
            
            $stmt->execute([
                ':username' => $username,
                ':nombre_completo' => $nombre_completo,
                ':password_hash' => $password_hash,
                ':rol' => $rol,
                ':estado' => $estado
            ]);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) { 
                throw new Exception("Error: El nombre de usuario '{$username}' ya existe.");
            }
            throw new Exception("Error al crear usuario: " . $e->getMessage());
        }
    }
    public function actualizar(int $id, string $username, string $nombre_completo, string $rol, string $estado, ?string $password_plano = null): bool {
        try {
            $sql = "UPDATE usuarios SET
                        username = :username,
                        nombre_completo = :nombre_completo,
                        rol = :rol,
                        estado = :estado,
                        actualizado_en = CURRENT_TIMESTAMP";
            if ($password_plano) {
                $sql .= ", password_hash = :password_hash";
            }
            
            $sql .= " WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);

            $params = [
                ':id' => $id,
                ':username' => $username,
                ':nombre_completo' => $nombre_completo,
                ':rol' => $rol,
                ':estado' => $estado
            ];

            if ($password_plano) {
                $params[':password_hash'] = password_hash($password_plano, PASSWORD_DEFAULT);
            }
            
            $stmt->execute($params);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) { 
                throw new Exception("Error: El nombre de usuario '{$username}' ya existe.");
            }
            throw new Exception("Error al actualizar usuario: " . $e->getMessage());
        }
    }
}
?>
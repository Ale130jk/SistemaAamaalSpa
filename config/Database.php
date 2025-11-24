<?php
class Database {

    private $host = "localhost";
    private $port = "3307"; 
    private $db_name = "spa_mype";
    private $username = "root";
    private $password = "";
    private $charset = "utf8mb4";
    private $conn = null;
    public function getConnection() {
        if ($this->conn !== null) {
            return $this->conn;
        }

        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset={$this->charset}";
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            return $this->conn;
            
        } catch (PDOException $e) {
            error_log("DATABASE ERROR: " . $e->getMessage());
            throw new Exception("Error de conexión a la base de datos");
        }
    }
}
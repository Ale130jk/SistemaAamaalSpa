<?php
/**
 * Database.php
 * Conexión PDO usando el Patrón Singleton.
 */
class Database{
    private const DB_HOST = 'localhost'; // En Hostinger será 'sqlXXX.main-hosting.eu'
    private const DB_NAME = 'spa_mype';
    private const DB_USER = 'root';      // En Hostinger será 'uXXXXXXXX_user'
    private const DB_PASS = '';          // Tu contraseña de DB
    private const DB_CHARSET = 'utf8mb4';
    // --- Instancia única ---
    private static ?Database $instance = null;
    private PDO $pdo;

    /**
     * Se ejecuta una sola vez al llamar a getInstance().
     */
    private function __construct()
    {
        $dsn = "mysql:host=" . self::DB_HOST .
               ";dbname=" . self::DB_NAME .
               ";charset=" . self::DB_CHARSET;
               
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Errores con excepciones
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Arrays asociativos
            PDO::ATTR_EMULATE_PREPARES   => false,                  // Prepareds nativos
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'"
        ];
        try {
            $this->pdo = new PDO($dsn, self::DB_USER, self::DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('Error de conexión a la base de datos: ' . $e->getMessage());
            die('Error: no se pudo conectar a la base de datos');
        }
    }
    /**
     * --Método público para obtener la instancia ---
     * Retorna la instancia única del Singleton.
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    /**
     * --Retornar el objeto PDO ---
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    /** -- Prevenir clonación o deserialización ---*/
    private function __clone() {}

    public function __wakeup()
    {
        throw new Exception("No se puede deserializar una instancia de Database.");
    }
}
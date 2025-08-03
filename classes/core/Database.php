<?php
// Archivo: /creator/classes/core/Database.php
// Propósito: Clase principal para manejo de base de datos

class Database {
    private static $instance = null;
    private $connection;
    private $statement;
    
    // Constructor privado (Singleton)
    private function __construct() {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, PDO_OPTIONS);
        } catch (PDOException $e) {
            $this->logError('Error de conexión a BD: ' . $e->getMessage());
            die('Error al conectar con la base de datos. Por favor, contacte al administrador.');
        }
    }
    
    // Obtener instancia única (Singleton)
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Prevenir clonación
    private function __clone() {}
    
    // Obtener conexión PDO directa
    public function getConnection() {
        return $this->connection;
    }
    
    // Ejecutar query con parámetros
    public function query($sql, $params = []) {
        try {
            $this->statement = $this->connection->prepare($sql);
            $this->statement->execute($params);
            return $this;
        } catch (PDOException $e) {
            $this->logError('Error en query: ' . $e->getMessage() . ' | SQL: ' . $sql);
            return false;
        }
    }
    
    // Obtener todos los resultados
    public function fetchAll() {
        return $this->statement->fetchAll();
    }
    
    // Obtener un solo resultado
    public function fetch() {
        return $this->statement->fetch();
    }
    
    // Obtener una sola columna
    public function fetchColumn() {
        return $this->statement->fetchColumn();
    }
    
    // Contar filas afectadas
    public function rowCount() {
        return $this->statement->rowCount();
    }
    
    // Obtener último ID insertado
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    // Iniciar transacción
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    // Confirmar transacción
    public function commit() {
        return $this->connection->commit();
    }
    
    // Revertir transacción
    public function rollBack() {
        return $this->connection->rollBack();
    }
    
    // Verificar si existe un registro
    public function exists($table, $where, $params = []) {
        $sql = "SELECT COUNT(*) FROM $table WHERE $where";
        $this->query($sql, $params);
        return $this->fetchColumn() > 0;
    }
    
    // Insertar registro
    public function insert($table, $data) {
        $fields = array_keys($data);
        $values = array_map(function($field) { return ':' . $field; }, $fields);
        
        $sql = "INSERT INTO $table (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $values) . ")";
        
        return $this->query($sql, $data);
    }
    
    // Actualizar registro
    public function update($table, $data, $where, $whereParams = []) {
        $fields = array_map(function($field) { 
            return "$field = :$field"; 
        }, array_keys($data));
        
        $sql = "UPDATE $table SET " . implode(', ', $fields) . " WHERE $where";
        
        $params = array_merge($data, $whereParams);
        return $this->query($sql, $params);
    }
    
    // Eliminar registro
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        return $this->query($sql, $params);
    }
    
    // Log de errores
    private function logError($message) {
        $logFile = STORAGE_PATH . '/logs/database_errors.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message" . PHP_EOL;
        
        // Crear directorio si no existe
        if (!is_dir(dirname($logFile))) {
            mkdir(dirname($logFile), 0777, true);
        }
        
        error_log($logMessage, 3, $logFile);
    }
}
?>
<?php
/**
 * Sistema de logs
 */
class Logger {
    private $db;
    
    public function __construct() {
        require_once __DIR__ . '/Database.php';
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Registrar log
     */
    public function log($tipo, $modulo, $accion, $mensaje, $detalles = null, $usuario_id = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO logs (tipo, modulo, accion, mensaje, detalles, usuario_id, ip, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $tipo,
                $modulo,
                $accion,
                $mensaje,
                $detalles ? json_encode($detalles) : null,
                $usuario_id,
                $this->getIP(),
                $this->getUserAgent()
            ]);
            
            // También escribir en archivo si es error crítico
            if ($tipo === 'critical' || $tipo === 'error') {
                $this->writeToFile($tipo, $modulo, $mensaje, $detalles);
            }
            
            return true;
        } catch (Exception $e) {
            // Si falla el log en BD, al menos escribir en archivo
            $this->writeToFile('error', 'logger', 'Error guardando log: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Métodos rápidos para diferentes tipos
     */
    public function info($modulo, $accion, $mensaje, $detalles = null) {
        return $this->log('info', $modulo, $accion, $mensaje, $detalles);
    }
    
    public function error($modulo, $accion, $mensaje, $detalles = null) {
        return $this->log('error', $modulo, $accion, $mensaje, $detalles);
    }
    
    public function warning($modulo, $accion, $mensaje, $detalles = null) {
        return $this->log('warning', $modulo, $accion, $mensaje, $detalles);
    }
    
    public function success($modulo, $accion, $mensaje, $detalles = null) {
        return $this->log('success', $modulo, $accion, $mensaje, $detalles);
    }
    
    public function critical($modulo, $accion, $mensaje, $detalles = null) {
        return $this->log('critical', $modulo, $accion, $mensaje, $detalles);
    }
    
    /**
     * Obtener IP del cliente
     */
    private function getIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        }
    }
    
    /**
     * Obtener User Agent
     */
    private function getUserAgent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }
    
    /**
     * Escribir en archivo
     */
    private function writeToFile($tipo, $modulo, $mensaje, $detalles = null) {
        $logDir = __DIR__ . '/../../storage/logs/';
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $filename = $logDir . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        
        $logEntry = sprintf(
            "[%s] [%s] [%s] %s %s\n",
            $timestamp,
            strtoupper($tipo),
            $modulo,
            $mensaje,
            $detalles ? json_encode($detalles) : ''
        );
        
        file_put_contents($filename, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Limpiar logs antiguos
     */
    public function cleanOldLogs($days = 30) {
        try {
            // Limpiar de la BD
            $stmt = $this->db->prepare("
                DELETE FROM logs 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$days]);
            
            // Limpiar archivos
            $logDir = __DIR__ . '/../../storage/logs/';
            $files = glob($logDir . '*.log');
            
            foreach ($files as $file) {
                if (filemtime($file) < strtotime("-$days days")) {
                    unlink($file);
                }
            }
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Obtener logs recientes
     */
    public function getRecentLogs($limit = 100, $tipo = null, $modulo = null) {
        $sql = "SELECT * FROM logs WHERE 1=1";
        $params = [];
        
        if ($tipo) {
            $sql .= " AND tipo = ?";
            $params[] = $tipo;
        }
        
        if ($modulo) {
            $sql .= " AND modulo = ?";
            $params[] = $modulo;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
<?php
/**
 * Configuración compartida para todos los crons
 */

// Token de seguridad para ejecutar crons vía web
define('CRON_TOKEN', 'TU_TOKEN_SEGURO_AQUI_' . md5('creator_content_2025'));

// Verificar acceso
function verificarAccesoCron() {
    if (php_sapi_name() !== 'cli') {
        $token = $_GET['token'] ?? '';
        if ($token !== CRON_TOKEN) {
            http_response_code(403);
            die('Acceso no autorizado');
        }
    }
}

// Función para verificar si otro cron está ejecutándose
function verificarCronEnEjecucion($nombre) {
    $lockFile = __DIR__ . '/../storage/temp/' . $nombre . '.lock';
    
    // Crear directorio si no existe
    $dir = dirname($lockFile);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    // Verificar si existe archivo lock
    if (file_exists($lockFile)) {
        // Verificar si tiene más de 2 horas (proceso colgado)
        if (filemtime($lockFile) < time() - 7200) {
            unlink($lockFile);
            return false;
        }
        return true;
    }
    
    // Crear archivo lock
    file_put_contents($lockFile, getmypid());
    return false;
}

// Función para liberar lock
function liberarLockCron($nombre) {
    $lockFile = __DIR__ . '/../storage/temp/' . $nombre . '.lock';
    if (file_exists($lockFile)) {
        unlink($lockFile);
    }
}

// Función para enviar notificación (placeholder)
function enviarNotificacion($tipo, $titulo, $mensaje, $detalles = []) {
    require_once __DIR__ . '/../classes/core/Logger.php';
    $logger = new Logger();
    
    // Por ahora solo loguear
    $logger->log($tipo, 'notificacion', $titulo, $mensaje, $detalles);
    
    // TODO: Implementar envío de emails cuando se configure Mailer
}

// Función helper para mostrar memoria usada
function mostrarUsoMemoria() {
    $memoria = memory_get_usage(true);
    $memoriaMax = memory_get_peak_usage(true);
    
    echo "Memoria usada: " . formatBytes($memoria) . "\n";
    echo "Memoria máxima: " . formatBytes($memoriaMax) . "\n";
}

// Función para formatear bytes
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

// Configurar límites de ejecución para crons
ini_set('max_execution_time', 3600); // 1 hora máximo
ini_set('memory_limit', '512M');

// Configurar reporte de errores
error_reporting(E_ALL);
ini_set('display_errors', php_sapi_name() === 'cli' ? 1 : 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/cron-errors.log');
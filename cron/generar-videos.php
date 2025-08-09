<?php
/**
 * Cron para generar videos automáticamente
 * Ejecutar cada 3 horas
 */

// Incluir configuración común de crons
require_once __DIR__ . '/cron-config.php';

// Verificar acceso
verificarAccesoCron();

// Verificar si ya está en ejecución
if (verificarCronEnEjecucion('generar_videos')) {
    die("El cron ya está en ejecución\n");
}

// Incluir archivos necesarios
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/core/Database.php';
require_once __DIR__ . '/../classes/core/Logger.php';
require_once __DIR__ . '/../classes/generators/ContentGenerator.php';

// Configurar zona horaria
date_default_timezone_set('America/Lima');

// Inicializar
$logger = new Logger();
$startTime = microtime(true);

echo "[" . date('Y-m-d H:i:s') . "] Iniciando generación automática de videos...\n";

try {
    // Verificar configuración
    $db = Database::getInstance()->getConnection();
    
    // Obtener cantidad de videos a generar
    $stmt = $db->prepare("SELECT valor FROM configuracion WHERE clave = ?");
    $stmt->execute(['videos_cortos_dia']);
    $videosCortosConfig = $stmt->fetch(PDO::FETCH_ASSOC);
    $videosCortosPorDia = intval($videosCortosConfig['valor'] ?? 5);
    
    // Calcular cuántos videos generar en esta ejecución
    // Si corre cada 3 horas = 8 veces al día
    $videosEstaCorrida = ceil($videosCortosPorDia / 8);
    
    echo "Configuración: $videosCortosPorDia videos cortos por día\n";
    echo "Esta ejecución: $videosEstaCorrida videos\n\n";
    
    // Verificar si hay suficientes tendencias
    $tendenciasDisponibles = $db->query("
        SELECT COUNT(*) FROM tendencias 
        WHERE usado = 0 
        AND score_viral >= 50
        AND (expira IS NULL OR expira > NOW())
    ")->fetchColumn();
    
    if ($tendenciasDisponibles < $videosEstaCorrida) {
        echo "ADVERTENCIA: Solo hay $tendenciasDisponibles tendencias disponibles\n";
        $videosEstaCorrida = $tendenciasDisponibles;
    }
    
    if ($videosEstaCorrida == 0) {
        echo "No hay tendencias disponibles para generar videos\n";
        $logger->warning('cron', 'generar_videos', 'No hay tendencias disponibles');
        liberarLockCron('generar_videos');
        exit;
    }
    
    // Crear generador
    $generator = new ContentGenerator();
    
    // Generar videos
    echo "Iniciando generación de $videosEstaCorrida videos...\n";
    $results = $generator->generateBatch($videosEstaCorrida);
    
    // Calcular tiempo de ejecución
    $executionTime = round(microtime(true) - $startTime, 2);
    
    // Mostrar resultados
    echo "\n=== RESULTADOS ===\n";
    echo "Total procesados: {$results['total']}\n";
    echo "Exitosos: {$results['success']}\n";
    echo "Errores: {$results['errors']}\n";
    echo "Tiempo total: {$executionTime} segundos\n\n";
    
    // Mostrar detalles
    if (!empty($results['details'])) {
        echo "DETALLES:\n";
        foreach ($results['details'] as $detail) {
            $status = $detail['result']['success'] ? '✓' : '✗';
            $message = $detail['result']['success'] 
                ? "Video ID: " . $detail['result']['video_id']
                : "Error: " . $detail['result']['error'];
            
            echo "$status {$detail['trend']}: $message\n";
        }
    }
    
    // Registrar en logs
    $logger->info(
        'cron',
        'generar_videos',
        "Generación completada: {$results['success']}/{$results['total']} videos",
        [
            'execution_time' => $executionTime,
            'total' => $results['total'],
            'exitosos' => $results['success'],
            'errores' => $results['errors']
        ]
    );
    
    // Enviar notificación si hay muchos errores
    if ($results['errors'] > $results['success']) {
        enviarNotificacion(
            'warning',
            'Alto índice de errores en generación',
            "Se generaron {$results['errors']} errores de {$results['total']} intentos",
            $results['details']
        );
    }
    
    // Mostrar uso de memoria
    echo "\n";
    mostrarUsoMemoria();
    
} catch (Exception $e) {
    $errorMsg = "Error crítico en generación de videos: " . $e->getMessage();
    echo "[ERROR] " . $errorMsg . "\n";
    
    $logger->critical(
        'cron',
        'generar_videos',
        $errorMsg,
        [
            'exception' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]
    );
    
    enviarNotificacion(
        'critical',
        'Error crítico en generación de videos',
        $e->getMessage()
    );
}

// Liberar lock
liberarLockCron('generar_videos');

echo "\n[" . date('Y-m-d H:i:s') . "] Proceso finalizado.\n";

// Si se ejecuta desde web, mostrar resultado simple
if (php_sapi_name() !== 'cli') {
    echo "<pre>Proceso completado</pre>";
}
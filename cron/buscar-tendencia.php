<?php
/**
 * Cron para buscar tendencias automáticamente
 * Ejecutar cada 2 horas
 */

// Verificar que se ejecute desde CLI o con token
if (php_sapi_name() !== 'cli') {
    // Si no es CLI, verificar token de seguridad
    $token = $_GET['token'] ?? '';
    if ($token !== 'TU_TOKEN_SEGURO_AQUI') {
        die('Acceso no autorizado');
    }
}

// Incluir configuración
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/core/Database.php';
require_once __DIR__ . '/../classes/core/TrendingFinder.php';
require_once __DIR__ . '/../classes/core/Logger.php';

// Configurar zona horaria
date_default_timezone_set('America/Lima');

// Inicializar
$logger = new Logger();
$startTime = microtime(true);

echo "[" . date('Y-m-d H:i:s') . "] Iniciando búsqueda de tendencias...\n";

try {
    // Crear instancia del buscador
    $trendingFinder = new TrendingFinder();
    
    // Limpiar tendencias expiradas primero
    echo "Limpiando tendencias expiradas...\n";
    $trendingFinder->cleanExpiredTrends();
    
    // Buscar nuevas tendencias
    echo "Buscando tendencias en todas las fuentes...\n";
    $resultado = $trendingFinder->searchAllSources();
    
    // Calcular tiempo de ejecución
    $executionTime = round(microtime(true) - $startTime, 2);
    
    // Preparar resumen
    $resumen = "Búsqueda completada en {$executionTime}s. ";
    $resumen .= "Encontradas: {$resultado['total']}, ";
    $resumen .= "Guardadas: {$resultado['saved']}";
    
    if (!empty($resultado['errors'])) {
        $resumen .= ". Errores: " . count($resultado['errors']);
    }
    
    echo $resumen . "\n";
    
    // Registrar en logs
    $logger->success(
        'cron',
        'buscar_tendencias',
        $resumen,
        [
            'execution_time' => $executionTime,
            'tendencias_encontradas' => $resultado['total'],
            'tendencias_guardadas' => $resultado['saved'],
            'errores' => $resultado['errors']
        ]
    );
    
    // Si hay muchos errores, enviar alerta
    if (count($resultado['errors']) > 2) {
        alertarErrores($resultado['errors']);
    }
    
} catch (Exception $e) {
    $errorMsg = "Error crítico en búsqueda de tendencias: " . $e->getMessage();
    echo "[ERROR] " . $errorMsg . "\n";
    
    $logger->critical(
        'cron',
        'buscar_tendencias',
        $errorMsg,
        [
            'exception' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]
    );
    
    // Enviar alerta crítica
    alertarErrorCritico($e->getMessage());
}

echo "[" . date('Y-m-d H:i:s') . "] Proceso finalizado.\n";

/**
 * Enviar alerta de errores
 */
function alertarErrores($errores) {
    // Por ahora solo loguear
    // En el futuro, enviar email
    error_log("Errores en búsqueda de tendencias: " . json_encode($errores));
}

/**
 * Enviar alerta de error crítico
 */
function alertarErrorCritico($mensaje) {
    // Por ahora solo loguear
    // En el futuro, enviar email urgente
    error_log("ERROR CRÍTICO en tendencias: " . $mensaje);
}

// Si se ejecuta desde web, mostrar resultado simple
if (php_sapi_name() !== 'cli') {
    echo "<pre>Proceso completado</pre>";
}
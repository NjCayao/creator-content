<?php
// Archivo: /creator/config/config.php
// Propósito: Configuración general del sistema

// Prevenir acceso directo
if (!defined('SECURE_ACCESS')) {
    define('SECURE_ACCESS', true);
}

// Configuración de errores (cambiar en producción)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/error.log');

// Zona horaria
date_default_timezone_set('America/Lima');

// Rutas del sistema
define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('CLASSES_PATH', ROOT_PATH . '/classes');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('ADMIN_PATH', ROOT_PATH . '/admin');

// URLs del sistema (ACTUALIZA ESTOS VALORES)
define('BASE_URL', 'http://localhost/creator');  // Cambia según tu configuración
define('ADMIN_URL', BASE_URL . '/admin');
define('PUBLIC_URL', BASE_URL . '/public');
define('API_URL', BASE_URL . '/api');

// Sesiones
define('SESSION_NAME', 'creator_session');
define('SESSION_LIFETIME', 1800); // 30 minutos
define('SESSION_PATH', '/');

// Seguridad
define('MAX_LOGIN_ATTEMPTS', 5);
define('BLOCK_TIME', 900); // 15 minutos de bloqueo
define('CSRF_TOKEN_TIME', 3600); // 1 hora
define('PASSWORD_MIN_LENGTH', 8);

// Sistema
define('SYSTEM_NAME', 'Creator Content System');
define('SYSTEM_VERSION', '1.0.0');
define('SYSTEM_EMAIL', 'noreply@creator.com');

// Cargar configuración de base de datos
require_once 'database.php';

// Cargar funciones de seguridad
require_once 'security.php';
?>
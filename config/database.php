<?php
// Archivo: /creator/config/database.php
// Propósito: Configuración de la base de datos

// Prevenir acceso directo
if (!defined('SECURE_ACCESS')) {
    die('Acceso directo no permitido');
}

// Configuración de base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'creator_content');  // Tu nombre de BD
define('DB_USER', 'root');              // Tu usuario
define('DB_PASS', '');                  // Tu contraseña
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', 'utf8mb4_unicode_ci');

// Opciones PDO
define('PDO_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE " . DB_COLLATE
]);
?>
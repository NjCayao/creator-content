<?php
// Funciones auxiliares para el sistema

// Función para cargar clases automáticamente
function autoloadClass($className) {
    $paths = [
        'classes/core/',
        'classes/generators/',
        'classes/publishers/',
        'libraries/'
    ];
    
    foreach ($paths as $path) {
        $file = __DIR__ . '/../../' . $path . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
}

// Registrar autoloader
spl_autoload_register('autoloadClass');

// Función para encriptar/desencriptar datos sensibles
function encrypt($data, $key = null) {
    if (!$key) $key = 'tu-clave-secreta-aqui';
    $method = 'AES-256-CBC';
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt($data, $method, $key, 0, $iv);
    return base64_encode($encrypted . '::' . $iv);
}

function decrypt($data, $key = null) {
    if (!$key) $key = 'tu-clave-secreta-aqui';
    $method = 'AES-256-CBC';
    list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
    return openssl_decrypt($encrypted_data, $method, $key, 0, $iv);
}

// Función para sanitizar inputs
function sanitize($input, $type = 'string') {
    switch ($type) {
        case 'email':
            return filter_var($input, FILTER_SANITIZE_EMAIL);
        case 'int':
            return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
        case 'url':
            return filter_var($input, FILTER_SANITIZE_URL);
        default:
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

// Función para validar datos
function validate($input, $type) {
    switch ($type) {
        case 'email':
            return filter_var($input, FILTER_VALIDATE_EMAIL);
        case 'url':
            return filter_var($input, FILTER_VALIDATE_URL);
        case 'int':
            return filter_var($input, FILTER_VALIDATE_INT);
        default:
            return !empty($input);
    }
}

// Función para generar tokens seguros
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Función para formatear fechas
function formatDate($date, $format = 'd/m/Y H:i') {
    return date($format, strtotime($date));
}

// Función para debug (solo en desarrollo)
function debug($data, $die = false) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    if ($die) die();
}
?>
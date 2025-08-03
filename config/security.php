<?php
// Archivo: /creator/config/security.php
// Propósito: Funciones y configuraciones de seguridad

// Prevenir acceso directo
if (!defined('SECURE_ACCESS')) {
    die('Acceso directo no permitido');
}

// Clave de encriptación (CAMBIA ESTO POR UNA CLAVE SEGURA)
define('ENCRYPTION_KEY', 'Mi$Cl4v3$S3cr3t4$2024$P4r4$Enc1');
define('ENCRYPTION_METHOD', 'AES-256-CBC');

// Headers de seguridad
function setSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Content Security Policy básico
    header("Content-Security-Policy: default-src 'self' https: data: 'unsafe-inline' 'unsafe-eval'");
}

// Generar token CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    
    // Renovar token si es muy antiguo
    if (time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_TIME) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    
    return $_SESSION['csrf_token'];
}

// Validar token CSRF
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    if (time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_TIME) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Limpiar input
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Validar email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Generar contraseña segura
function generateSecurePassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    return substr(str_shuffle($chars), 0, $length);
}
?>
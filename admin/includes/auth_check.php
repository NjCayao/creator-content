<?php
// Archivo: /creator/admin/includes/auth_check.php
// Propósito: Verificar autenticación en cada página del admin

// Definir acceso seguro
define('SECURE_ACCESS', true);

// Cargar configuración
require_once __DIR__ . '/../../config/config.php';

// Cargar clases necesarias
require_once CLASSES_PATH . '/core/Database.php';
require_once CLASSES_PATH . '/core/Auth.php';

// Crear instancia de Auth
$auth = new Auth();

// Verificar si está autenticado
if (!$auth->isAuthenticated()) {
    // Guardar página intentada
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    
    // Redirigir al login
    header('Location: ' . PUBLIC_URL . '/login.php');
    exit;
}

// Establecer headers de seguridad
setSecurityHeaders();

// Obtener usuario actual
$currentUser = $auth->getCurrentUser();
?>
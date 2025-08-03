<?php
// Archivo: /creator/api/oauth/facebook-auth.php
// Propósito: Iniciar proceso de autenticación con Facebook

session_start();
if (!isset($_SESSION['user_id'])) {
    die('Acceso no autorizado');
}

define('SECURE_ACCESS', true);
require_once '../../config/config.php';
require_once '../../classes/core/Database.php';

// Obtener configuración
$db = Database::getInstance();
$appId = getConfig('facebook_app_id');
$appSecret = getConfig('facebook_app_secret');

if (empty($appId) || empty($appSecret)) {
    die('Error: Primero debes configurar Facebook App ID y Secret en la página de APIs');
}

// URLs de OAuth 2.0 de Facebook
$authUrl = 'https://www.facebook.com/v18.0/dialog/oauth';
$redirectUri = API_URL . '/callbacks/facebook.php';

// Permisos necesarios para publicar en páginas
$permissions = [
    'pages_manage_posts',
    'pages_read_engagement',
    'pages_show_list',
    'publish_video',
    'pages_read_user_content',
    'pages_manage_metadata'
];

// State para seguridad
$state = bin2hex(random_bytes(16));
$_SESSION['fb_oauth_state'] = $state;

// Parámetros para la autorización
$params = [
    'client_id' => $appId,
    'redirect_uri' => $redirectUri,
    'state' => $state,
    'scope' => implode(',', $permissions),
    'response_type' => 'code'
];

// Redirigir a Facebook para autorización
$authorizationUrl = $authUrl . '?' . http_build_query($params);
header('Location: ' . $authorizationUrl);
exit;

// Función helper
function getConfig($key) {
    global $db;
    $result = $db->query("SELECT valor FROM configuracion WHERE clave = ?", [$key])->fetch();
    
    if ($result && !empty($result['valor'])) {
        $value = $result['valor'];
        if (strpos($value, '::') !== false) {
            $key = ENCRYPTION_KEY;
            list($encrypted_data, $iv) = explode('::', base64_decode($value), 2);
            $value = openssl_decrypt($encrypted_data, ENCRYPTION_METHOD, $key, 0, $iv);
        }
        return $value;
    }
    
    return '';
}
?>
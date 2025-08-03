<?php
// Archivo: /creator/api/oauth/youtube-auth.php
// Propósito: Iniciar proceso de autenticación con YouTube

session_start();
if (!isset($_SESSION['user_id'])) {
    die('Acceso no autorizado');
}

define('SECURE_ACCESS', true);
require_once '../../config/config.php';
require_once '../../classes/core/Database.php';

// Obtener configuración
$db = Database::getInstance();
$clientId = getConfig('youtube_client_id');
$clientSecret = getConfig('youtube_client_secret');

if (empty($clientId) || empty($clientSecret)) {
    die('Error: Primero debes configurar YouTube Client ID y Secret en la página de APIs');
}

// URLs de OAuth 2.0 de Google
$authUrl = 'https://accounts.google.com/o/oauth2/v2/auth';
$redirectUri = API_URL . '/callbacks/youtube.php';

// Parámetros para la autorización
$params = [
    'client_id' => $clientId,
    'redirect_uri' => $redirectUri,
    'response_type' => 'code',
    'scope' => 'https://www.googleapis.com/auth/youtube.upload https://www.googleapis.com/auth/youtube.readonly',
    'access_type' => 'offline',
    'prompt' => 'consent'
];

// Redirigir a Google para autorización
$authorizationUrl = $authUrl . '?' . http_build_query($params);
header('Location: ' . $authorizationUrl);
exit;

// Función helper
function getConfig($key) {
    global $db;
    $result = $db->query("SELECT valor FROM configuracion WHERE clave = ?", [$key])->fetch();
    
    if ($result && !empty($result['valor'])) {
        // Desencriptar si es necesario
        $value = $result['valor'];
        if (strpos($value, '::') !== false) {
            // Está encriptado
            $key = ENCRYPTION_KEY;
            list($encrypted_data, $iv) = explode('::', base64_decode($value), 2);
            $value = openssl_decrypt($encrypted_data, ENCRYPTION_METHOD, $key, 0, $iv);
        }
        return $value;
    }
    
    return '';
}
?>
<?php
// Archivo: /creator/api/oauth/tiktok-auth.php
// Propósito: Iniciar proceso de autenticación con TikTok

session_start();
if (!isset($_SESSION['user_id'])) {
    die('Acceso no autorizado');
}

define('SECURE_ACCESS', true);
require_once '../../config/config.php';
require_once '../../classes/core/Database.php';

// Obtener configuración
$db = Database::getInstance();
$clientKey = getConfig('tiktok_client_key');
$clientSecret = getConfig('tiktok_client_secret');

if (empty($clientKey) || empty($clientSecret)) {
    die('Error: Primero debes configurar TikTok Client Key y Secret en la página de APIs');
}

// URLs de OAuth 2.0 de TikTok
$authUrl = 'https://www.tiktok.com/v2/auth/authorize';
$redirectUri = API_URL . '/callbacks/tiktok.php';

// Scopes necesarios para publicar videos
$scopes = [
    'user.info.basic',
    'video.upload',
    'video.publish'
];

// State y code verifier para PKCE
$state = bin2hex(random_bytes(16));
$codeVerifier = base64_encode(random_bytes(32));
$codeChallenge = base64_encode(hash('sha256', $codeVerifier, true));

// Guardar en sesión para el callback
$_SESSION['tiktok_oauth_state'] = $state;
$_SESSION['tiktok_code_verifier'] = $codeVerifier;

// Parámetros para la autorización
$params = [
    'client_key' => $clientKey,
    'scope' => implode(',', $scopes),
    'response_type' => 'code',
    'redirect_uri' => $redirectUri,
    'state' => $state,
    'code_challenge' => $codeChallenge,
    'code_challenge_method' => 'S256'
];

// Redirigir a TikTok para autorización
$authorizationUrl = $authUrl . '?' . http_build_query($params);
header('Location: ' . $authorizationUrl);
exit;

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
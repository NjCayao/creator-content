<?php
// Archivo: /creator/api/callbacks/tiktok.php
// Propósito: Manejar callback de TikTok OAuth

session_start();
if (!isset($_SESSION['user_id'])) {
    die('Acceso no autorizado');
}

define('SECURE_ACCESS', true);
require_once '../../config/config.php';
require_once '../../classes/core/Database.php';

$db = Database::getInstance();

// Verificar state
if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['tiktok_oauth_state']) {
    header('Location: ' . ADMIN_URL . '/plataformas.php?error=invalid_state');
    exit;
}

// Verificar si hay error
if (isset($_GET['error'])) {
    header('Location: ' . ADMIN_URL . '/plataformas.php?error=' . urlencode($_GET['error_description']));
    exit;
}

// Verificar código de autorización
if (!isset($_GET['code'])) {
    header('Location: ' . ADMIN_URL . '/plataformas.php?error=no_code');
    exit;
}

$code = $_GET['code'];
$codeVerifier = $_SESSION['tiktok_code_verifier'];

// Intercambiar código por access token
$tokenUrl = 'https://open.tiktokapis.com/v2/oauth/token/';
$clientKey = getConfig('tiktok_client_key');
$clientSecret = getConfig('tiktok_client_secret');
$redirectUri = API_URL . '/callbacks/tiktok.php';

$postData = [
    'client_key' => $clientKey,
    'client_secret' => $clientSecret,
    'code' => $code,
    'grant_type' => 'authorization_code',
    'redirect_uri' => $redirectUri,
    'code_verifier' => $codeVerifier
];

$ch = curl_init($tokenUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    header('Location: ' . ADMIN_URL . '/plataformas.php?error=token_exchange_failed');
    exit;
}

$tokenData = json_decode($response, true);

// Obtener información del usuario
$userInfo = getTikTokUserInfo($tokenData['access_token']);

// Guardar información
try {
    $exists = $db->query("SELECT id FROM oauth_tokens WHERE plataforma = 'tiktok'")->fetch();
    
    $data = [
        'access_token' => $tokenData['access_token'],
        'refresh_token' => $tokenData['refresh_token'] ?? null,
        'expires_in' => $tokenData['expires_in'] ?? 86400,
        'cuenta_info' => json_encode($userInfo)
    ];
    
    if ($exists) {
        $db->query("
            UPDATE oauth_tokens SET 
                access_token = :access_token,
                refresh_token = :refresh_token,
                expires_at = DATE_ADD(NOW(), INTERVAL :expires_in SECOND),
                cuenta_info = :cuenta_info,
                activo = 1,
                actualizado = NOW()
            WHERE plataforma = 'tiktok'
        ", $data);
    } else {
        $db->query("
            INSERT INTO oauth_tokens 
            (plataforma, access_token, refresh_token, expires_at, cuenta_info, activo) 
            VALUES 
            ('tiktok', :access_token, :refresh_token, DATE_ADD(NOW(), INTERVAL :expires_in SECOND), :cuenta_info, 1)
        ", $data);
    }
    
    header('Location: ' . ADMIN_URL . '/plataformas.php?success=tiktok_connected');
    
} catch (Exception $e) {
    header('Location: ' . ADMIN_URL . '/plataformas.php?error=save_failed');
}

// Obtener información del usuario de TikTok
function getTikTokUserInfo($accessToken) {
    $url = 'https://open.tiktokapis.com/v2/user/info/';
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    if (isset($data['data']['user'])) {
        $user = $data['data']['user'];
        return [
            'id' => $user['open_id'],
            'name' => $user['display_name'],
            'username' => $user['username'] ?? '',
            'avatar' => $user['avatar_url'] ?? ''
        ];
    }
    
    return null;
}

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
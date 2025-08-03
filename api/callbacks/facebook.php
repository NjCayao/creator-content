<?php
// Archivo: /creator/api/callbacks/facebook.php
// Propósito: Manejar callback de Facebook OAuth

session_start();
if (!isset($_SESSION['user_id'])) {
    die('Acceso no autorizado');
}

define('SECURE_ACCESS', true);
require_once '../../config/config.php';
require_once '../../classes/core/Database.php';

$db = Database::getInstance();

// Verificar state
if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['fb_oauth_state']) {
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

// Intercambiar código por access token
$tokenUrl = 'https://graph.facebook.com/v18.0/oauth/access_token';
$appId = getConfig('facebook_app_id');
$appSecret = getConfig('facebook_app_secret');
$redirectUri = API_URL . '/callbacks/facebook.php';

$params = [
    'client_id' => $appId,
    'client_secret' => $appSecret,
    'redirect_uri' => $redirectUri,
    'code' => $code
];

$tokenUrl .= '?' . http_build_query($params);

$ch = curl_init($tokenUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    header('Location: ' . ADMIN_URL . '/plataformas.php?error=token_exchange_failed');
    exit;
}

$tokenData = json_decode($response, true);

// Intercambiar por token de larga duración
$longLivedToken = getLongLivedToken($tokenData['access_token'], $appId, $appSecret);

// Obtener páginas del usuario
$pages = getFacebookPages($longLivedToken);

// Guardar información
try {
    $exists = $db->query("SELECT id FROM oauth_tokens WHERE plataforma = 'facebook'")->fetch();
    
    $data = [
        'access_token' => $longLivedToken,
        'expires_in' => 5184000, // 60 días
        'cuenta_info' => json_encode([
            'pages' => $pages,
            'token_type' => 'long_lived'
        ])
    ];
    
    if ($exists) {
        $db->query("
            UPDATE oauth_tokens SET 
                access_token = :access_token,
                expires_at = DATE_ADD(NOW(), INTERVAL :expires_in SECOND),
                cuenta_info = :cuenta_info,
                activo = 1,
                actualizado = NOW()
            WHERE plataforma = 'facebook'
        ", $data);
    } else {
        $db->query("
            INSERT INTO oauth_tokens 
            (plataforma, access_token, expires_at, cuenta_info, activo) 
            VALUES 
            ('facebook', :access_token, DATE_ADD(NOW(), INTERVAL :expires_in SECOND), :cuenta_info, 1)
        ", $data);
    }
    
    header('Location: ' . ADMIN_URL . '/plataformas.php?success=facebook_connected');
    
} catch (Exception $e) {
    header('Location: ' . ADMIN_URL . '/plataformas.php?error=save_failed');
}

// Obtener token de larga duración
function getLongLivedToken($shortToken, $appId, $appSecret) {
    $url = 'https://graph.facebook.com/v18.0/oauth/access_token';
    $params = [
        'grant_type' => 'fb_exchange_token',
        'client_id' => $appId,
        'client_secret' => $appSecret,
        'fb_exchange_token' => $shortToken
    ];
    
    $url .= '?' . http_build_query($params);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    return $data['access_token'] ?? $shortToken;
}

// Obtener páginas de Facebook
function getFacebookPages($accessToken) {
    $url = 'https://graph.facebook.com/v18.0/me/accounts?fields=id,name,access_token,category';
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    $pages = [];
    if (isset($data['data'])) {
        foreach ($data['data'] as $page) {
            $pages[] = [
                'id' => $page['id'],
                'name' => $page['name'],
                'category' => $page['category'],
                'access_token' => $page['access_token']
            ];
        }
    }
    
    return $pages;
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
<?php
// Archivo: /creator/api/callbacks/youtube.php
// Propósito: Manejar callback de YouTube OAuth

session_start();
if (!isset($_SESSION['user_id'])) {
    die('Acceso no autorizado');
}

define('SECURE_ACCESS', true);
require_once '../../config/config.php';
require_once '../../classes/core/Database.php';

$db = Database::getInstance();

// Verificar si hay error
if (isset($_GET['error'])) {
    header('Location: ' . ADMIN_URL . '/plataformas.php?error=' . urlencode($_GET['error']));
    exit;
}

// Verificar código de autorización
if (!isset($_GET['code'])) {
    header('Location: ' . ADMIN_URL . '/plataformas.php?error=no_code');
    exit;
}

$code = $_GET['code'];

// Intercambiar código por access token
$tokenUrl = 'https://oauth2.googleapis.com/token';
$clientId = getConfig('youtube_client_id');
$clientSecret = getConfig('youtube_client_secret');
$redirectUri = API_URL . '/callbacks/youtube.php';

$postData = [
    'code' => $code,
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'redirect_uri' => $redirectUri,
    'grant_type' => 'authorization_code'
];

$ch = curl_init($tokenUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    header('Location: ' . ADMIN_URL . '/plataformas.php?error=token_exchange_failed');
    exit;
}

$tokenData = json_decode($response, true);

// Obtener información del canal
$channelInfo = getYouTubeChannelInfo($tokenData['access_token']);

// Guardar tokens en base de datos
try {
    // Verificar si ya existe
    $exists = $db->query("SELECT id FROM oauth_tokens WHERE plataforma = 'youtube'")->fetch();
    
    if ($exists) {
        // Actualizar
        $db->query("
            UPDATE oauth_tokens SET 
                access_token = :access_token,
                refresh_token = :refresh_token,
                expires_at = DATE_ADD(NOW(), INTERVAL :expires_in SECOND),
                cuenta_info = :cuenta_info,
                activo = 1,
                actualizado = NOW()
            WHERE plataforma = 'youtube'
        ", [
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'] ?? null,
            'expires_in' => $tokenData['expires_in'],
            'cuenta_info' => json_encode($channelInfo)
        ]);
    } else {
        // Insertar
        $db->query("
            INSERT INTO oauth_tokens 
            (plataforma, access_token, refresh_token, expires_at, cuenta_info, activo) 
            VALUES 
            ('youtube', :access_token, :refresh_token, DATE_ADD(NOW(), INTERVAL :expires_in SECOND), :cuenta_info, 1)
        ", [
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'] ?? null,
            'expires_in' => $tokenData['expires_in'],
            'cuenta_info' => json_encode($channelInfo)
        ]);
    }
    
    header('Location: ' . ADMIN_URL . '/plataformas.php?success=youtube_connected');
    
} catch (Exception $e) {
    header('Location: ' . ADMIN_URL . '/plataformas.php?error=save_failed');
}

// Función para obtener info del canal
function getYouTubeChannelInfo($accessToken) {
    $url = 'https://www.googleapis.com/youtube/v3/channels?part=snippet&mine=true';
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    if (isset($data['items'][0])) {
        $channel = $data['items'][0]['snippet'];
        return [
            'id' => $data['items'][0]['id'],
            'name' => $channel['title'],
            'description' => $channel['description'],
            'thumbnail' => $channel['thumbnails']['default']['url']
        ];
    }
    
    return null;
}

// Función helper para obtener config
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
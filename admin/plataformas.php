<?php
// Archivo: /creator/admin/plataformas.php
// Propósito: Conectar y gestionar redes sociales

$pageTitle = 'Plataformas de Publicación';
require_once 'includes/header.php';
require_once 'includes/functions.php';

// require_once '../config/security.php';

$db = Database::getInstance();

// Verificar tokens actuales
$platforms = [
    'youtube' => [
        'name' => 'YouTube',
        'icon' => 'fab fa-youtube',
        'color' => 'danger',
        'connected' => false,
        'account' => null
    ],
    'tiktok' => [
        'name' => 'TikTok',
        'icon' => 'fab fa-tiktok',
        'color' => 'dark',
        'connected' => false,
        'account' => null
    ],
    'facebook' => [
        'name' => 'Facebook',
        'icon' => 'fab fa-facebook',
        'color' => 'primary',
        'connected' => false,
        'account' => null
    ],
    'instagram' => [
        'name' => 'Instagram',
        'icon' => 'fab fa-instagram',
        'color' => 'purple',
        'connected' => false,
        'account' => null
    ]
];

// Función helper para obtener configuración
// function getConfig($configKey) {
//     global $db;
//     $result = $db->query("SELECT valor FROM configuracion WHERE clave = ?", [$configKey])->fetch();
    
//     if ($result && !empty($result['valor'])) {
//         $value = $result['valor'];
        
//         // Si está encriptado, desencriptar
//         if (strpos($value, '::') !== false && defined('ENCRYPTION_KEY') && defined('ENCRYPTION_METHOD')) {
//             list($encrypted_data, $iv) = explode('::', base64_decode($value), 2);
//             $value = openssl_decrypt($encrypted_data, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, $iv);
//         }
        
//         return $value;
//     }
    
//     return '';
// }

// Verificar conexiones existentes
$tokens = $db->query("SELECT * FROM oauth_tokens WHERE activo = 1")->fetchAll();
foreach ($tokens as $token) {
    if (isset($platforms[$token['plataforma']])) {
        $platforms[$token['plataforma']]['connected'] = true;
        $platforms[$token['plataforma']]['account'] = json_decode($token['cuenta_info'], true);
        $platforms[$token['plataforma']]['expires'] = $token['expires_at'];
    }
}

// Procesar desconexión
if (isset($_POST['disconnect'])) {
    $platform = $_POST['platform'];
    $db->query("UPDATE oauth_tokens SET activo = 0 WHERE plataforma = ?", [$platform]);
    header('Location: plataformas.php');
    exit;
}
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Plataformas de Publicación</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?php echo ADMIN_URL; ?>">Inicio</a></li>
                    <li class="breadcrumb-item">Sistema</li>
                    <li class="breadcrumb-item active">Plataformas</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">

        <div class="row">
            <?php foreach ($platforms as $key => $platform): ?>
                <div class="col-md-6 col-lg-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="<?php echo $platform['icon']; ?> fa-5x text-<?php echo $platform['color']; ?>"></i>
                            </div>

                            <h4><?php echo $platform['name']; ?></h4>

                            <?php if ($platform['connected']): ?>
                                <div class="text-success mb-2">
                                    <i class="fas fa-check-circle"></i> Conectado
                                </div>

                                <?php if ($platform['account']): ?>
                                    <p class="small text-muted mb-2">
                                        <?php echo htmlspecialchars($platform['account']['name'] ?? 'Cuenta conectada'); ?>
                                    </p>
                                <?php endif; ?>

                                <?php if (isset($platform['expires'])): ?>
                                    <p class="small text-muted">
                                        Expira: <?php echo formatDate($platform['expires'], 'd/m/Y'); ?>
                                    </p>
                                <?php endif; ?>

                                <form method="POST" class="mt-2">
                                    <input type="hidden" name="disconnect" value="1">
                                    <input type="hidden" name="platform" value="<?php echo $key; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger"
                                        onclick="return confirm('¿Desconectar <?php echo $platform['name']; ?>?')">
                                        <i class="fas fa-unlink"></i> Desconectar
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="text-muted mb-3">
                                    <i class="fas fa-times-circle"></i> No conectado
                                </div>

                                <a href="<?php echo API_URL; ?>/oauth/<?php echo $key; ?>-auth.php"
                                    class="btn btn-sm btn-<?php echo $platform['color']; ?>">
                                    <i class="fas fa-link"></i> Conectar
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Información adicional -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-info-circle"></i> Información Importante
                        </h3>
                    </div>
                    <div class="card-body">
                        <h5>Requisitos para conectar plataformas:</h5>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h6><i class="fab fa-youtube text-danger"></i> YouTube</h6>
                                <ul>
                                    <li>Cuenta de Google</li>
                                    <li>Canal de YouTube creado</li>
                                    <li>API habilitada en Google Console</li>
                                    <li>Verificación del canal (para videos largos)</li>
                                </ul>
                            </div>

                            <div class="col-md-6">
                                <h6><i class="fab fa-tiktok"></i> TikTok</h6>
                                <ul>
                                    <li>Cuenta TikTok for Business</li>
                                    <li>Solicitar acceso a la API</li>
                                    <li>App creada en TikTok Developers</li>
                                </ul>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h6><i class="fab fa-facebook text-primary"></i> Facebook</h6>
                                <ul>
                                    <li>Página de Facebook (no perfil personal)</li>
                                    <li>App creada en Facebook Developers</li>
                                    <li>Permisos de publicación aprobados</li>
                                </ul>
                            </div>

                            <div class="col-md-6">
                                <h6><i class="fab fa-instagram text-purple"></i> Instagram</h6>
                                <ul>
                                    <li>Cuenta Business o Creator</li>
                                    <li>Conectada a página de Facebook</li>
                                    <li>API de Instagram Basic Display</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estado de las APIs -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-plug"></i> Estado de Configuración
                </h3>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Plataforma</th>
                            <th>API Configurada</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- YouTube -->
                        <tr>
                            <td><i class="fab fa-youtube text-danger"></i> YouTube</td>
                            <td>
                                <?php
                                $ytConfigured = !empty(getConfig('youtube_client_id')) && !empty(getConfig('youtube_client_secret'));
                                echo $ytConfigured ? '<span class="badge badge-success">Sí</span>' : '<span class="badge badge-warning">No</span>';
                                ?>
                            </td>
                            <td><?php echo $platforms['youtube']['connected'] ? 'Conectado' : 'Desconectado'; ?></td>
                            <td>
                                <a href="apis.php#youtube" class="btn btn-xs btn-info">Configurar API</a>
                            </td>
                        </tr>

                        <!-- TikTok -->
                        <tr>
                            <td><i class="fab fa-tiktok"></i> TikTok</td>
                            <td>
                                <?php
                                $tiktokConfigured = !empty(getConfig('tiktok_client_key')) && !empty(getConfig('tiktok_client_secret'));
                                echo $tiktokConfigured ? '<span class="badge badge-success">Sí</span>' : '<span class="badge badge-warning">No</span>';
                                ?>
                            </td>
                            <td><?php echo $platforms['tiktok']['connected'] ? 'Conectado' : 'Desconectado'; ?></td>
                            <td>
                                <a href="apis.php#tiktok" class="btn btn-xs btn-info">Configurar API</a>
                            </td>
                        </tr>

                        <!-- Facebook -->
                        <tr>
                            <td><i class="fab fa-facebook text-primary"></i> Facebook</td>
                            <td>
                                <?php
                                $fbConfigured = !empty(getConfig('facebook_app_id')) && !empty(getConfig('facebook_app_secret'));
                                echo $fbConfigured ? '<span class="badge badge-success">Sí</span>' : '<span class="badge badge-warning">No</span>';
                                ?>
                            </td>
                            <td><?php echo $platforms['facebook']['connected'] ? 'Conectado' : 'Desconectado'; ?></td>
                            <td>
                                <a href="apis.php#facebook" class="btn btn-xs btn-info">Configurar API</a>
                            </td>
                        </tr>

                        <!-- Instagram -->
                        <tr>
                            <td><i class="fab fa-instagram text-purple"></i> Instagram</td>
                            <td><span class="badge badge-secondary">Próximamente</span></td>
                            <td>No disponible</td>
                            <td>-</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
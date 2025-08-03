<?php
// Archivo: /creator/admin/apis.php
// Propósito: Configuración de todas las APIs del sistema

$pageTitle = 'Configuración de APIs';
require_once 'includes/header.php';
require_once 'includes/functions.php';

$db = Database::getInstance();
$message = '';
$error = '';

// Procesar formulario si se envió
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] == 'save_apis') {
        try {
            // Guardar cada API key
            foreach ($_POST['api'] as $key => $value) {
                // Encriptar si no está vacío
                $encrypted = !empty($value) ? encrypt($value) : '';

                $db->query(
                    "UPDATE configuracion SET valor = :valor WHERE clave = :clave",
                    ['valor' => $encrypted, 'clave' => $key]
                );
            }

            $message = 'APIs actualizadas correctamente.';
        } catch (Exception $e) {
            $error = 'Error al actualizar APIs: ' . $e->getMessage();
        }
    }

    // Test de conexión individual
    if ($_POST['action'] == 'test_api') {
        $apiType = $_POST['api_type'];

        switch ($apiType) {
            case 'openai':
                require_once '../classes/generators/OpenAI.php';
                try {
                    $openai = new OpenAI();
                    $test = $openai->testConnection();
                    if ($test['success']) {
                        $message = 'OpenAI conectado correctamente: ' . $test['message'];
                    } else {
                        $error = 'Error OpenAI: ' . $test['message'];
                    }
                } catch (Exception $e) {
                    $error = 'Error al conectar con OpenAI: ' . $e->getMessage();
                }
                break;

            case 'invideo':
                require_once '../classes/generators/InVideo.php';
                try {
                    $invideo = new InVideo();
                    $test = $invideo->testConnection();
                    if ($test['success']) {
                        $message = 'InVideo conectado correctamente: ' . $test['message'];
                    } else {
                        $error = 'Error InVideo: ' . $test['message'];
                    }
                } catch (Exception $e) {
                    $error = 'Error al conectar con InVideo: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Obtener valores actuales (desencriptados para mostrar)
$apis = [];
$apiKeys = [
    'openai_api_key', 
    'invideo_api_key', 
    'youtube_client_id', 
    'youtube_client_secret',
    'facebook_app_id',
    'facebook_app_secret',
    'tiktok_client_key',
    'tiktok_client_secret'
];

foreach ($apiKeys as $key) {
    $result = $db->query("SELECT valor FROM configuracion WHERE clave = ?", [$key])->fetch();
    $apis[$key] = $result ? decrypt($result['valor']) : '';
}

// Función para encriptar
function encrypt($data)
{
    $key = ENCRYPTION_KEY;
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt($data, ENCRYPTION_METHOD, $key, 0, $iv);
    return base64_encode($encrypted . '::' . $iv);
}

// Función para desencriptar
function decrypt($data)
{
    if (empty($data)) return '';

    $key = ENCRYPTION_KEY;
    list($encrypted_data, $iv) = array_pad(explode('::', base64_decode($data), 2), 2, null);

    if (!$iv) return '';

    return openssl_decrypt($encrypted_data, ENCRYPTION_METHOD, $key, 0, $iv);
}
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Configuración de APIs</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?php echo ADMIN_URL; ?>">Inicio</a></li>
                    <li class="breadcrumb-item">Sistema</li>
                    <li class="breadcrumb-item active">APIs</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">

        <?php if ($message): ?>
            <?php echo showAlert($message, 'success'); ?>
        <?php endif; ?>

        <?php if ($error): ?>
            <?php echo showAlert($error, 'danger'); ?>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="action" value="save_apis">

            <!-- OpenAI -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fab fa-openai"></i> OpenAI (ChatGPT)
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="openai_api_key">API Key</label>
                                <div class="input-group">
                                    <input type="password"
                                        class="form-control"
                                        id="openai_api_key"
                                        name="api[openai_api_key]"
                                        value="<?php echo htmlspecialchars($apis['openai_api_key']); ?>"
                                        placeholder="sk-...">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-default" onclick="togglePassword('openai_api_key')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="form-text text-muted">
                                    Obtén tu API Key en <a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com</a>
                                </small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="button"
                                    class="btn btn-info btn-block"
                                    onclick="testAPI('openai')"
                                    <?php echo empty($apis['openai_api_key']) ? 'disabled' : ''; ?>>
                                    <i class="fas fa-plug"></i> Probar Conexión
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Modelo GPT</label>
                                <select class="form-control" name="api[openai_model]">
                                    <option value="gpt-3.5-turbo">GPT-3.5 Turbo (Económico)</option>
                                    <option value="gpt-4">GPT-4 (Más potente)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Máximo de Tokens</label>
                                <input type="number"
                                    class="form-control"
                                    name="api[openai_max_tokens]"
                                    value="2000"
                                    min="100"
                                    max="4000">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Temperatura (Creatividad)</label>
                                <input type="number"
                                    class="form-control"
                                    name="api[openai_temperature]"
                                    value="0.8"
                                    min="0"
                                    max="1"
                                    step="0.1">
                                <small class="text-muted">0 = Preciso, 1 = Creativo</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- InVideo -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-video"></i> InVideo
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="invideo_api_key">API Key</label>
                                <div class="input-group">
                                    <input type="password"
                                        class="form-control"
                                        id="invideo_api_key"
                                        name="api[invideo_api_key]"
                                        value="<?php echo htmlspecialchars($apis['invideo_api_key']); ?>"
                                        placeholder="Tu API Key de InVideo">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-default" onclick="togglePassword('invideo_api_key')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="form-text text-muted">
                                    Obtén tu API Key en <a href="https://invideo.io/developers" target="_blank">invideo.io</a>
                                </small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="button"
                                    class="btn btn-info btn-block"
                                    onclick="testAPI('invideo')"
                                    <?php echo empty($apis['invideo_api_key']) ? 'disabled' : ''; ?>>
                                    <i class="fas fa-plug"></i> Probar Conexión
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Calidad de Video por Defecto</label>
                                <select class="form-control" name="api[invideo_quality]">
                                    <option value="720p">720p (HD)</option>
                                    <option value="1080p" selected>1080p (Full HD)</option>
                                    <option value="4k">4K (Ultra HD)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Template por Defecto</label>
                                <select class="form-control" name="api[invideo_template]">
                                    <option value="modern">Moderno</option>
                                    <option value="minimal">Minimalista</option>
                                    <option value="dynamic">Dinámico</option>
                                    <option value="elegant">Elegante</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- YouTube -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fab fa-youtube"></i> YouTube Data API
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="youtube_client_id">Client ID</label>
                                <input type="text"
                                    class="form-control"
                                    id="youtube_client_id"
                                    name="api[youtube_client_id]"
                                    value="<?php echo htmlspecialchars($apis['youtube_client_id']); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="youtube_client_secret">Client Secret</label>
                                <div class="input-group">
                                    <input type="password"
                                        class="form-control"
                                        id="youtube_client_secret"
                                        name="api[youtube_client_secret]"
                                        value="<?php echo htmlspecialchars($apis['youtube_client_secret']); ?>">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-default" onclick="togglePassword('youtube_client_secret')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <small class="form-text text-muted">
                        Configura tu proyecto en <a href="https://console.cloud.google.com" target="_blank">Google Cloud Console</a>
                    </small>
                </div>
            </div>

            <!-- Facebook -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fab fa-facebook"></i> Facebook
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="facebook_app_id">App ID</label>
                                <input type="text"
                                    class="form-control"
                                    id="facebook_app_id"
                                    name="api[facebook_app_id]"
                                    value="<?php echo htmlspecialchars($apis['facebook_app_id'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="facebook_app_secret">App Secret</label>
                                <div class="input-group">
                                    <input type="password"
                                        class="form-control"
                                        id="facebook_app_secret"
                                        name="api[facebook_app_secret]"
                                        value="<?php echo htmlspecialchars($apis['facebook_app_secret'] ?? ''); ?>">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-default" onclick="togglePassword('facebook_app_secret')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <small class="form-text text-muted">
                        Crea tu app en <a href="https://developers.facebook.com" target="_blank">developers.facebook.com</a>
                    </small>
                </div>
            </div>

            <!-- TikTok -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fab fa-tiktok"></i> TikTok
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tiktok_client_key">Client Key</label>
                                <input type="text"
                                    class="form-control"
                                    id="tiktok_client_key"
                                    name="api[tiktok_client_key]"
                                    value="<?php echo htmlspecialchars($apis['tiktok_client_key'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tiktok_client_secret">Client Secret</label>
                                <div class="input-group">
                                    <input type="password"
                                        class="form-control"
                                        id="tiktok_client_secret"
                                        name="api[tiktok_client_secret]"
                                        value="<?php echo htmlspecialchars($apis['tiktok_client_secret'] ?? ''); ?>">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-default" onclick="togglePassword('tiktok_client_secret')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <small class="form-text text-muted">
                        Registra tu app en <a href="https://developers.tiktok.com" target="_blank">developers.tiktok.com</a>
                    </small>
                </div>
            </div>

            <!-- Otras APIs -->
            <div class="card collapsed-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-plus"></i> Otras APIs (TikTok, Instagram, etc.)
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted">Próximamente...</p>
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="row">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Configuración
                    </button>
                    <a href="<?php echo ADMIN_URL; ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </div>
        </form>

    </div>
</section>

<!-- Modal para mostrar resultados de pruebas -->
<div class="modal fade" id="testModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Resultado de la Prueba</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="testResult">
                <!-- Resultado aquí -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Función para mostrar/ocultar contraseñas
    function togglePassword(fieldId) {
        var field = document.getElementById(fieldId);
        var button = event.currentTarget;
        var icon = button.querySelector('i');

        if (field.type === "password") {
            field.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        } else {
            field.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }
    }

    // Función para probar APIs
    function testAPI(apiType) {
        // Crear un formulario temporal
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '';

        // Agregar campos ocultos
        var actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'test_api';
        form.appendChild(actionInput);

        var apiTypeInput = document.createElement('input');
        apiTypeInput.type = 'hidden';
        apiTypeInput.name = 'api_type';
        apiTypeInput.value = apiType;
        form.appendChild(apiTypeInput);

        // Agregar al body y enviar
        document.body.appendChild(form);
        form.submit();
    }
</script>

<?php require_once 'includes/footer.php'; ?>
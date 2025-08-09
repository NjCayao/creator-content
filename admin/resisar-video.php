<?php
session_start();

// Actualizar última actividad
if (isset($_SESSION['user_id'])) {
    $_SESSION['last_activity'] = time();
}

require_once '../config/config.php';
require_once 'includes/auth_check.php';
require_once '../classes/core/Database.php';
require_once 'includes/functions.php';

$db = Database::getInstance()->getConnection();
$pageTitle = 'Revisar Video';

// Obtener ID del video
$videoId = intval($_GET['id'] ?? 0);

if (!$videoId) {
    header('Location: revisor.php');
    exit;
}

// Obtener datos del video
$stmt = $db->prepare("SELECT * FROM videos WHERE id = ?");
$stmt->execute([$videoId]);
$video = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$video) {
    header('Location: revisor.php');
    exit;
}

// Procesar actualizaciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    switch ($accion) {
        case 'actualizar':
            // Actualizar información del video
            $stmt = $db->prepare("
                UPDATE videos SET 
                    titulo = ?,
                    descripcion = ?,
                    hashtags = ?,
                    categoria = ?,
                    viral_score = ?,
                    calidad_score = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $_POST['titulo'],
                $_POST['descripcion'],
                $_POST['hashtags'],
                $_POST['categoria'],
                intval($_POST['viral_score']),
                intval($_POST['calidad_score']),
                $videoId
            ]);
            
            $mensaje = "Video actualizado correctamente.";
            break;
            
        case 'actualizar_guion':
            // Actualizar guion
            $guion = $_POST['guion'];
            if ($_POST['formato_guion'] === 'json') {
                // Validar JSON
                $guionArray = json_decode($guion, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $error = "Error en formato JSON: " . json_last_error_msg();
                } else {
                    $stmt = $db->prepare("UPDATE videos SET guion = ? WHERE id = ?");
                    $stmt->execute([$guion, $videoId]);
                    $mensaje = "Guion actualizado correctamente.";
                }
            } else {
                $stmt = $db->prepare("UPDATE videos SET guion = ? WHERE id = ?");
                $stmt->execute([$guion, $videoId]);
                $mensaje = "Guion actualizado correctamente.";
            }
            break;
            
        case 'aprobar':
            $stmt = $db->prepare("UPDATE videos SET estado = 'aprobado', calidad_score = ? WHERE id = ?");
            $stmt->execute([intval($_POST['calidad_score']), $videoId]);
            header("Location: revisor.php?mensaje=aprobado");
            exit;
            break;
            
        case 'rechazar':
            $stmt = $db->prepare("UPDATE videos SET estado = 'rechazado', notas = ? WHERE id = ?");
            $stmt->execute([$_POST['razon_rechazo'], $videoId]);
            header("Location: revisor.php?mensaje=rechazado");
            exit;
            break;
    }
    
    // Recargar datos
    $stmt = $db->prepare("SELECT * FROM videos WHERE id = ?");
    $stmt->execute([$videoId]);
    $video = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Decodificar guion si es JSON
$guion = $video['guion'];
$esJson = false;
if (is_string($guion) && substr($guion, 0, 1) === '{') {
    $guionArray = json_decode($guion, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $esJson = true;
    }
}

// Obtener métricas si existen
$stmt = $db->prepare("
    SELECT 
        SUM(views) as total_views,
        SUM(likes) as total_likes,
        SUM(comments) as total_comments,
        SUM(shares) as total_shares
    FROM metricas 
    WHERE video_id = ?
");
$stmt->execute([$videoId]);
$metricas = $stmt->fetch(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Revisar Video #<?php echo $videoId; ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?php echo ADMIN_URL; ?>">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="revisor.php">Revisor</a></li>
                    <li class="breadcrumb-item active">Revisar Video</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        
        <?php if (isset($mensaje)): ?>
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <i class="fas fa-check"></i> <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Panel Principal -->
            <div class="col-md-8">
                <!-- Información del Video -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Información del Video</h3>
                        <div class="card-tools">
                            <?php echo getStatusBadge($video['estado']); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="accion" value="actualizar">
                            
                            <div class="form-group">
                                <label>Título <span class="text-danger">*</span></label>
                                <input type="text" name="titulo" class="form-control" 
                                       value="<?php echo htmlspecialchars($video['titulo']); ?>" 
                                       maxlength="100" required>
                                <small class="form-text text-muted">
                                    <?php echo strlen($video['titulo']); ?>/100 caracteres
                                </small>
                            </div>
                            
                            <div class="form-group">
                                <label>Descripción</label>
                                <textarea name="descripcion" class="form-control" rows="4"
                                          maxlength="500"><?php echo htmlspecialchars($video['descripcion'] ?? ''); ?></textarea>
                                <small class="form-text text-muted">
                                    <?php echo strlen($video['descripcion'] ?? ''); ?>/500 caracteres
                                </small>
                            </div>
                            
                            <div class="form-group">
                                <label>Hashtags</label>
                                <textarea name="hashtags" class="form-control" rows="3"
                                          placeholder="#viral #fyp #parati"><?php echo htmlspecialchars($video['hashtags'] ?? ''); ?></textarea>
                                <small class="form-text text-muted">Separados por espacios, máximo 30</small>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Categoría</label>
                                        <select name="categoria" class="form-control">
                                            <option value="general">General</option>
                                            <option value="educacion" <?php echo $video['categoria'] === 'educacion' ? 'selected' : ''; ?>>Educación</option>
                                            <option value="entretenimiento" <?php echo $video['categoria'] === 'entretenimiento' ? 'selected' : ''; ?>>Entretenimiento</option>
                                            <option value="tecnologia" <?php echo $video['categoria'] === 'tecnologia' ? 'selected' : ''; ?>>Tecnología</option>
                                            <option value="lifestyle" <?php echo $video['categoria'] === 'lifestyle' ? 'selected' : ''; ?>>Lifestyle</option>
                                            <option value="misterios" <?php echo $video['categoria'] === 'misterios' ? 'selected' : ''; ?>>Misterios</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Score Viral (0-100)</label>
                                        <input type="number" name="viral_score" class="form-control" 
                                               value="<?php echo $video['viral_score']; ?>" 
                                               min="0" max="100">
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Score Calidad (0-100)</label>
                                        <input type="number" name="calidad_score" class="form-control" 
                                               value="<?php echo $video['calidad_score']; ?>" 
                                               min="0" max="100">
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Editor de Guion -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-file-alt"></i> Guion del Video
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" onclick="toggleGuionEditor()">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Vista del guion -->
                        <div id="guionVista">
                            <?php if ($esJson && $guionArray): ?>
                                <div class="mb-3">
                                    <h5>Hook (Gancho inicial):</h5>
                                    <div class="alert alert-info">
                                        <i class="fas fa-bolt"></i> 
                                        <?php echo htmlspecialchars($guionArray['hook'] ?? 'No disponible'); ?>
                                    </div>
                                </div>
                                
                                <?php if (isset($guionArray['desarrollo']) && is_array($guionArray['desarrollo'])): ?>
                                    <div class="mb-3">
                                        <h5>Desarrollo:</h5>
                                        <ol>
                                            <?php foreach ($guionArray['desarrollo'] as $punto): ?>
                                                <li class="mb-2"><?php echo htmlspecialchars($punto); ?></li>
                                            <?php endforeach; ?>
                                        </ol>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (isset($guionArray['climax'])): ?>
                                    <div class="mb-3">
                                        <h5>Clímax:</h5>
                                        <div class="alert alert-warning">
                                            <i class="fas fa-star"></i> 
                                            <?php echo htmlspecialchars($guionArray['climax']); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (isset($guionArray['call_to_action'])): ?>
                                    <div class="mb-3">
                                        <h5>Call to Action:</h5>
                                        <div class="alert alert-success">
                                            <i class="fas fa-bullhorn"></i> 
                                            <?php echo htmlspecialchars($guionArray['call_to_action']); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <pre><?php echo htmlspecialchars($video['guion'] ?? 'No hay guion disponible'); ?></pre>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Editor del guion (oculto por defecto) -->
                        <div id="guionEditor" style="display: none;">
                            <form method="POST">
                                <input type="hidden" name="accion" value="actualizar_guion">
                                <input type="hidden" name="formato_guion" value="<?php echo $esJson ? 'json' : 'texto'; ?>">
                                
                                <div class="form-group">
                                    <label>Editar Guion:</label>
                                    <textarea name="guion" class="form-control" rows="15"><?php echo htmlspecialchars($video['guion']); ?></textarea>
                                    <?php if ($esJson): ?>
                                        <small class="form-text text-muted">
                                            Formato JSON detectado. Mantén la estructura válida.
                                        </small>
                                    <?php endif; ?>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Guardar Guion
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="toggleGuionEditor()">
                                    Cancelar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Panel Lateral -->
            <div class="col-md-4">
                <!-- Acciones -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Acciones de Revisión</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($video['estado'] === 'revision'): ?>
                            <form method="POST" class="mb-2">
                                <input type="hidden" name="accion" value="aprobar">
                                <input type="hidden" name="calidad_score" value="<?php echo $video['calidad_score']; ?>">
                                <button type="submit" class="btn btn-success btn-block">
                                    <i class="fas fa-check"></i> Aprobar para Publicación
                                </button>
                            </form>
                            
                            <button type="button" class="btn btn-danger btn-block" onclick="mostrarRechazo()">
                                <i class="fas fa-times"></i> Rechazar Video
                            </button>
                            
                            <form method="POST" id="formRechazo" style="display: none;" class="mt-2">
                                <input type="hidden" name="accion" value="rechazar">
                                <div class="form-group">
                                    <label>Razón del rechazo:</label>
                                    <textarea name="razon_rechazo" class="form-control" rows="3" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-danger btn-sm">Confirmar Rechazo</button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="ocultarRechazo()">Cancelar</button>
                            </form>
                        <?php else: ?>
                            <p class="text-muted">
                                Estado actual: <strong><?php echo ucfirst($video['estado']); ?></strong>
                            </p>
                        <?php endif; ?>
                        
                        <hr>
                        
                        <a href="ver-video.php?id=<?php echo $videoId; ?>" class="btn btn-info btn-block">
                            <i class="fas fa-eye"></i> Ver Detalles Completos
                        </a>
                        
                        <a href="revisor.php" class="btn btn-secondary btn-block">
                            <i class="fas fa-arrow-left"></i> Volver al Revisor
                        </a>
                    </div>
                </div>
                
                <!-- Detalles Técnicos -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">Detalles Técnicos</h3>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-5">ID:</dt>
                            <dd class="col-sm-7"><?php echo $video['id']; ?></dd>
                            
                            <dt class="col-sm-5">Tipo:</dt>
                            <dd class="col-sm-7"><?php echo ucfirst($video['tipo'] ?? 'short'); ?></dd>
                            
                            <dt class="col-sm-5">Duración:</dt>
                            <dd class="col-sm-7"><?php echo $video['duracion'] ?? 60; ?> seg</dd>
                            
                            <dt class="col-sm-5">Calidad:</dt>
                            <dd class="col-sm-7"><?php echo $video['calidad']; ?></dd>
                            
                            <dt class="col-sm-5">Idioma:</dt>
                            <dd class="col-sm-7"><?php echo strtoupper($video['idioma'] ?? 'es'); ?></dd>
                            
                            <dt class="col-sm-5">Creado:</dt>
                            <dd class="col-sm-7"><?php echo formatDate($video['creado']); ?></dd>
                            
                            <dt class="col-sm-5">Actualizado:</dt>
                            <dd class="col-sm-7"><?php echo formatDate($video['actualizado']); ?></dd>
                        </dl>
                    </div>
                </div>
                
                <!-- Métricas (si existen) -->
                <?php if ($video['estado'] === 'publicado' && $metricas): ?>
                    <div class="card mt-3">
                        <div class="card-header">
                            <h3 class="card-title">Métricas</h3>
                        </div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-sm-5">Views:</dt>
                                <dd class="col-sm-7"><?php echo formatNumber($metricas['total_views'] ?? 0); ?></dd>
                                
                                <dt class="col-sm-5">Likes:</dt>
                                <dd class="col-sm-7"><?php echo formatNumber($metricas['total_likes'] ?? 0); ?></dd>
                                
                                <dt class="col-sm-5">Comentarios:</dt>
                                <dd class="col-sm-7"><?php echo formatNumber($metricas['total_comments'] ?? 0); ?></dd>
                                
                                <dt class="col-sm-5">Compartidos:</dt>
                                <dd class="col-sm-7"><?php echo formatNumber($metricas['total_shares'] ?? 0); ?></dd>
                            </dl>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Preview del Video -->
                <?php if ($video['thumbnail_url'] || $video['video_url']): ?>
                    <div class="card mt-3">
                        <div class="card-header">
                            <h3 class="card-title">Preview</h3>
                        </div>
                        <div class="card-body text-center">
                            <?php if ($video['thumbnail_url']): ?>
                                <img src="<?php echo $video['thumbnail_url']; ?>" 
                                     class="img-fluid" alt="Thumbnail">
                            <?php endif; ?>
                            
                            <?php if ($video['video_url']): ?>
                                <a href="<?php echo $video['video_url']; ?>" 
                                   target="_blank" class="btn btn-sm btn-primary mt-2">
                                    <i class="fas fa-play"></i> Ver Video
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</section>

<script>
function toggleGuionEditor() {
    const vista = document.getElementById('guionVista');
    const editor = document.getElementById('guionEditor');
    
    if (editor.style.display === 'none') {
        vista.style.display = 'none';
        editor.style.display = 'block';
    } else {
        vista.style.display = 'block';
        editor.style.display = 'none';
    }
}

function mostrarRechazo() {
    document.getElementById('formRechazo').style.display = 'block';
}

function ocultarRechazo() {
    document.getElementById('formRechazo').style.display = 'none';
}
</script>

<?php require_once 'includes/footer.php'; ?>
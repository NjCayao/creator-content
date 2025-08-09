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
$pageTitle = 'Revisor de Videos';

// Variables
$mensaje = '';
$error = '';
$filtroEstado = $_GET['estado'] ?? 'revision';
$filtroCategoria = $_GET['categoria'] ?? '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $videoId = intval($_POST['video_id'] ?? 0);
    $accion = $_POST['accion'] ?? '';
    
    if ($videoId > 0) {
        switch ($accion) {
            case 'aprobar':
                $stmt = $db->prepare("UPDATE videos SET estado = 'aprobado' WHERE id = ?");
                $stmt->execute([$videoId]);
                $mensaje = "Video #$videoId aprobado para publicación.";
                break;
                
            case 'rechazar':
                $razon = $_POST['razon'] ?? 'No cumple con los estándares de calidad';
                $stmt = $db->prepare("UPDATE videos SET estado = 'rechazado', notas = ? WHERE id = ?");
                $stmt->execute([$razon, $videoId]);
                $mensaje = "Video #$videoId rechazado.";
                break;
                
            case 'revisar':
                $stmt = $db->prepare("UPDATE videos SET estado = 'revision' WHERE id = ?");
                $stmt->execute([$videoId]);
                $mensaje = "Video #$videoId enviado a revisión.";
                break;
                
            case 'eliminar':
                // Eliminar archivos asociados si existen
                $stmt = $db->prepare("SELECT video_local_path FROM videos WHERE id = ?");
                $stmt->execute([$videoId]);
                $video = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($video && $video['video_local_path']) {
                    $filePath = ROOT_PATH . '/' . $video['video_local_path'];
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
                
                // Eliminar registro
                $stmt = $db->prepare("DELETE FROM videos WHERE id = ?");
                $stmt->execute([$videoId]);
                $mensaje = "Video #$videoId eliminado permanentemente.";
                break;
        }
    }
}

// Construir consulta con filtros
$sql = "SELECT v.*, 
        (SELECT COUNT(*) FROM calendario WHERE video_id = v.id) as programaciones
        FROM videos v 
        WHERE 1=1";
$params = [];

if ($filtroEstado) {
    $sql .= " AND v.estado = ?";
    $params[] = $filtroEstado;
}

if ($filtroCategoria) {
    $sql .= " AND v.categoria = ?";
    $params[] = $filtroCategoria;
}

$sql .= " ORDER BY 
          CASE v.estado 
            WHEN 'revision' THEN 1 
            WHEN 'generando' THEN 2
            WHEN 'error' THEN 3
            WHEN 'aprobado' THEN 4
            ELSE 5 
          END,
          v.creado DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas
$stats = $db->query("
    SELECT 
        estado,
        COUNT(*) as total
    FROM videos
    GROUP BY estado
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Obtener categorías
$categorias = $db->query("
    SELECT DISTINCT categoria 
    FROM videos 
    WHERE categoria IS NOT NULL 
    ORDER BY categoria
")->fetchAll(PDO::FETCH_COLUMN);

require_once 'includes/header.php';
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Revisor de Videos</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?php echo ADMIN_URL; ?>">Inicio</a></li>
                    <li class="breadcrumb-item active">Revisor</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        
        <?php if ($mensaje): ?>
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="row">
            <div class="col-lg-2 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo $stats['revision'] ?? 0; ?></h3>
                        <p>En Revisión</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <a href="?estado=revision" class="small-box-footer">
                        Ver <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-lg-2 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo $stats['generando'] ?? 0; ?></h3>
                        <p>Generando</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-spinner"></i>
                    </div>
                    <a href="?estado=generando" class="small-box-footer">
                        Ver <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-lg-2 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo $stats['aprobado'] ?? 0; ?></h3>
                        <p>Aprobados</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <a href="?estado=aprobado" class="small-box-footer">
                        Ver <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-lg-2 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3><?php echo $stats['publicado'] ?? 0; ?></h3>
                        <p>Publicados</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-share"></i>
                    </div>
                    <a href="?estado=publicado" class="small-box-footer">
                        Ver <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-lg-2 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?php echo $stats['rechazado'] ?? 0; ?></h3>
                        <p>Rechazados</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-times"></i>
                    </div>
                    <a href="?estado=rechazado" class="small-box-footer">
                        Ver <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-lg-2 col-6">
                <div class="small-box bg-dark">
                    <div class="inner">
                        <h3><?php echo $stats['error'] ?? 0; ?></h3>
                        <p>Con Error</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <a href="?estado=error" class="small-box-footer">
                        Ver <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Filtros</h3>
            </div>
            <div class="card-body">
                <form method="GET" class="form-inline">
                    <div class="form-group mr-3">
                        <label class="mr-2">Estado:</label>
                        <select name="estado" class="form-control" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            <option value="revision" <?php echo $filtroEstado === 'revision' ? 'selected' : ''; ?>>En Revisión</option>
                            <option value="generando" <?php echo $filtroEstado === 'generando' ? 'selected' : ''; ?>>Generando</option>
                            <option value="aprobado" <?php echo $filtroEstado === 'aprobado' ? 'selected' : ''; ?>>Aprobados</option>
                            <option value="publicado" <?php echo $filtroEstado === 'publicado' ? 'selected' : ''; ?>>Publicados</option>
                            <option value="rechazado" <?php echo $filtroEstado === 'rechazado' ? 'selected' : ''; ?>>Rechazados</option>
                            <option value="error" <?php echo $filtroEstado === 'error' ? 'selected' : ''; ?>>Con Error</option>
                        </select>
                    </div>
                    
                    <div class="form-group mr-3">
                        <label class="mr-2">Categoría:</label>
                        <select name="categoria" class="form-control" onchange="this.form.submit()">
                            <option value="">Todas</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo $cat; ?>" <?php echo $filtroCategoria === $cat ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <a href="revisor.php" class="btn btn-secondary">
                        <i class="fas fa-sync"></i> Limpiar Filtros
                    </a>
                </form>
            </div>
        </div>

        <!-- Lista de Videos -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Videos para Revisar</h3>
            </div>
            <div class="card-body p-0">
                <?php if (empty($videos)): ?>
                    <div class="text-center py-5">
                        <p class="text-muted">No hay videos con los filtros seleccionados.</p>
                        <a href="generador.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Generar Videos
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="50">ID</th>
                                    <th>Título</th>
                                    <th>Categoría</th>
                                    <th>Tipo</th>
                                    <th>Score</th>
                                    <th>Estado</th>
                                    <th>Creado</th>
                                    <th width="200">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($videos as $video): ?>
                                    <tr>
                                        <td><?php echo $video['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars(substr($video['titulo'], 0, 50)); ?></strong>
                                            <?php if (strlen($video['titulo']) > 50): ?>...<?php endif; ?>
                                            <?php if ($video['descripcion']): ?>
                                                <br><small class="text-muted">
                                                    <?php echo htmlspecialchars(substr($video['descripcion'], 0, 60)); ?>...
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-secondary">
                                                <?php echo ucfirst($video['categoria'] ?? 'general'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo $video['tipo'] === 'short' ? 
                                                '<span class="badge badge-info">Short</span>' : 
                                                '<span class="badge badge-primary">Largo</span>'; ?>
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 15px; min-width: 60px;">
                                                <div class="progress-bar bg-success" 
                                                     style="width: <?php echo $video['viral_score']; ?>%">
                                                    <small><?php echo $video['viral_score']; ?>%</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo getStatusBadge($video['estado']); ?></td>
                                        <td>
                                            <small><?php echo timeAgo($video['creado']); ?></small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="revisar-video.php?id=<?php echo $video['id']; ?>" 
                                                   class="btn btn-primary" title="Revisar">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                <?php if ($video['estado'] === 'revision'): ?>
                                                    <button type="button" class="btn btn-success" 
                                                            onclick="aprobarVideo(<?php echo $video['id']; ?>)"
                                                            title="Aprobar">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-danger" 
                                                            onclick="rechazarVideo(<?php echo $video['id']; ?>)"
                                                            title="Rechazar">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($video['estado'] === 'aprobado'): ?>
                                                    <a href="calendario.php?video_id=<?php echo $video['id']; ?>" 
                                                       class="btn btn-info" title="Programar">
                                                        <i class="fas fa-calendar"></i>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if (in_array($video['estado'], ['rechazado', 'error'])): ?>
                                                    <button type="button" class="btn btn-warning" 
                                                            onclick="reintentarVideo(<?php echo $video['id']; ?>)"
                                                            title="Reintentar">
                                                        <i class="fas fa-redo"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</section>

<!-- Forms ocultos para acciones -->
<form id="formAccion" method="POST" style="display: none;">
    <input type="hidden" name="video_id" id="accion_video_id">
    <input type="hidden" name="accion" id="accion_tipo">
    <input type="hidden" name="razon" id="accion_razon">
</form>

<script>
function aprobarVideo(id) {
    if (confirm('¿Aprobar este video para publicación?')) {
        document.getElementById('accion_video_id').value = id;
        document.getElementById('accion_tipo').value = 'aprobar';
        document.getElementById('formAccion').submit();
    }
}

function rechazarVideo(id) {
    const razon = prompt('Razón del rechazo:', 'No cumple con los estándares de calidad');
    if (razon) {
        document.getElementById('accion_video_id').value = id;
        document.getElementById('accion_tipo').value = 'rechazar';
        document.getElementById('accion_razon').value = razon;
        document.getElementById('formAccion').submit();
    }
}

function reintentarVideo(id) {
    if (confirm('¿Enviar video a revisión nuevamente?')) {
        document.getElementById('accion_video_id').value = id;
        document.getElementById('accion_tipo').value = 'revisar';
        document.getElementById('formAccion').submit();
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
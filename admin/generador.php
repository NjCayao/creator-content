<?php
session_start();

// Fix temporal para sesión
require_once 'includes/session-fix.php';

require_once '../config/config.php';
require_once 'includes/auth_check.php';
require_once '../classes/core/Database.php';

// NO cargar ContentGenerator al inicio para evitar errores
// require_once '../classes/generators/ContentGenerator.php';

$db = Database::getInstance()->getConnection();
$pageTitle = 'Generador de Contenido';

// Variables
$mensaje = '';
$error = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['generar_individual'])) {
        try {
            // Cargar ContentGenerator solo cuando se necesite
            require_once '../classes/generators/ContentGenerator.php';
            $generator = new ContentGenerator();
            $result = $generator->generateFromTrend($_POST['trend_id'], [
                'tipo' => $_POST['tipo_video'],
                'duracion' => intval($_POST['duracion']),
                'calidad' => $_POST['calidad'],
                'template' => $_POST['template'],
                'auto_publish' => isset($_POST['auto_publish'])
            ]);
            
            if ($result['success']) {
                $mensaje = "Video generado exitosamente. ID: " . $result['video_id'];
            } else {
                $error = "Error al generar video: " . $result['error'];
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    } elseif (isset($_POST['generar_lote'])) {
        try {
            // Cargar ContentGenerator solo cuando se necesite
            require_once '../classes/generators/ContentGenerator.php';
            $generator = new ContentGenerator();
            $cantidad = intval($_POST['cantidad_videos']);
            $results = $generator->generateBatch($cantidad);
            
            $mensaje = "Generación en lote completada: {$results['success']} exitosos, {$results['errors']} errores de {$results['total']} total.";
        } catch (Exception $e) {
            $error = "Error en generación por lote: " . $e->getMessage();
        }
    }
}

// Obtener tendencias disponibles
$tendencias = $db->query("
    SELECT * FROM tendencias 
    WHERE usado = 0 
    AND score_viral >= 50
    AND (expira IS NULL OR expira > NOW())
    ORDER BY score_viral DESC 
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas
try {
    // Solo intentar si existe la clase
    if (file_exists('../classes/generators/ContentGenerator.php')) {
        require_once '../classes/generators/ContentGenerator.php';
        $generator = new ContentGenerator();
        $stats = $generator->getStats();
    } else {
        throw new Exception("ContentGenerator no encontrado");
    }
} catch (Exception $e) {
    // Estadísticas por defecto si falla
    $stats = [
        'hoy' => 0,
        'tendencias_disponibles' => 0,
        'por_estado' => []
    ];
}

// Obtener videos recientes
$videosRecientes = $db->query("
    SELECT * FROM videos 
    ORDER BY creado DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Generador de Contenido</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?php echo ADMIN_URL; ?>">Inicio</a></li>
                    <li class="breadcrumb-item active">Generador</li>
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
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo $stats['hoy']; ?></h3>
                        <p>Videos Generados Hoy</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-video"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo $stats['tendencias_disponibles']; ?></h3>
                        <p>Tendencias Disponibles</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-fire"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo $stats['por_estado']['revision'] ?? 0; ?></h3>
                        <p>En Revisión</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-eye"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?php echo $stats['por_estado']['error'] ?? 0; ?></h3>
                        <p>Con Errores</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Generación Individual -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-magic"></i> Generación Individual
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($tendencias)): ?>
                            <div class="alert alert-warning">
                                No hay tendencias disponibles. 
                                <a href="tendencias.php">Buscar tendencias</a>
                            </div>
                        <?php else: ?>
                            <form method="POST">
                                <div class="form-group">
                                    <label>Seleccionar Tendencia</label>
                                    <select name="trend_id" class="form-control" required>
                                        <option value="">-- Seleccionar --</option>
                                        <?php foreach ($tendencias as $trend): ?>
                                            <option value="<?php echo $trend['id']; ?>">
                                                <?php echo htmlspecialchars($trend['keyword']); ?> 
                                                (Score: <?php echo $trend['score_viral']; ?>%)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Tipo de Video</label>
                                            <select name="tipo_video" class="form-control">
                                                <option value="short">Short (< 60s)</option>
                                                <option value="largo">Largo (> 60s)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Duración (segundos)</label>
                                            <input type="number" name="duracion" class="form-control" 
                                                   value="60" min="15" max="600">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Calidad</label>
                                            <select name="calidad" class="form-control">
                                                <option value="720p">720p (HD)</option>
                                                <option value="1080p" selected>1080p (Full HD)</option>
                                                <option value="4k">4K (Ultra HD)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Template</label>
                                            <select name="template" class="form-control">
                                                <option value="viral">Viral</option>
                                                <option value="modern">Moderno</option>
                                                <option value="minimal">Minimalista</option>
                                                <option value="dynamic">Dinámico</option>
                                                <option value="elegant">Elegante</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" 
                                               id="auto_publish" name="auto_publish">
                                        <label class="custom-control-label" for="auto_publish">
                                            Aprobar automáticamente para publicación
                                        </label>
                                    </div>
                                </div>
                                
                                <button type="submit" name="generar_individual" class="btn btn-primary btn-block">
                                    <i class="fas fa-play"></i> Generar Video
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Generación por Lote -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-layer-group"></i> Generación por Lote
                        </h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-group">
                                <label>Cantidad de Videos</label>
                                <input type="number" name="cantidad_videos" class="form-control" 
                                       value="5" min="1" max="10">
                                <small class="form-text text-muted">
                                    Se generarán videos de las mejores tendencias disponibles
                                </small>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> 
                                La generación por lote puede tomar varios minutos. 
                                Los videos se generarán con configuración por defecto.
                            </div>
                            
                            <button type="submit" name="generar_lote" class="btn btn-success btn-block"
                                    onclick="return confirm('¿Iniciar generación por lote?')">
                                <i class="fas fa-rocket"></i> Generar Lote
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Videos Recientes -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-history"></i> Últimos Videos Generados
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Título</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($videosRecientes as $video): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(substr($video['titulo'], 0, 30)) . '...'; ?></td>
                                            <td>
                                                <?php echo getStatusBadge($video['estado']); ?>
                                            </td>
                                            <td>
                                                <a href="ver-video.php?id=<?php echo $video['id']; ?>" 
                                                   class="btn btn-xs btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($videosRecientes)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">
                                                No hay videos generados aún
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
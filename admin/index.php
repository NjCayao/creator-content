<?php
// Archivo: /creator/admin/index.php
// Propósito: Dashboard principal del sistema

$pageTitle = 'Dashboard';
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Obtener estadísticas para el dashboard
$db = Database::getInstance();

// Videos de hoy
$videosHoy = $db->query("
    SELECT COUNT(*) as total 
    FROM videos 
    WHERE DATE(creado) = CURDATE()
")->fetchColumn();

// Videos pendientes de revisión
$videosPendientes = $db->query("
    SELECT COUNT(*) as total 
    FROM videos 
    WHERE estado = 'revision'
")->fetchColumn();

// Videos publicados hoy
$videosPublicadosHoy = $db->query("
    SELECT COUNT(*) as total 
    FROM videos 
    WHERE DATE(publicado) = CURDATE()
")->fetchColumn();

// Total de views (simulado por ahora)
$totalViews = $db->query("
    SELECT SUM(views_total) as total 
    FROM videos
")->fetchColumn() ?? 0;

// Videos recientes
$videosRecientes = $db->query("
    SELECT * FROM videos 
    ORDER BY creado DESC 
    LIMIT 5
")->fetchAll();

// Próximas publicaciones
$proximasPublicaciones = $db->query("
    SELECT c.*, v.titulo 
    FROM calendario c 
    JOIN videos v ON c.video_id = v.id 
    WHERE c.publicado = 0 
    AND c.fecha_programada > NOW() 
    ORDER BY c.fecha_programada ASC 
    LIMIT 5
")->fetchAll();
?>
<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Dashboard</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?php echo ADMIN_URL; ?>">Inicio</a></li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        
        <!-- Info boxes -->
        <div class="row">
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-info elevation-1">
                        <i class="fas fa-video"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Videos Hoy</span>
                        <span class="info-box-number"><?php echo formatNumber($videosHoy); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-warning elevation-1">
                        <i class="fas fa-clock"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Pendientes Revisión</span>
                        <span class="info-box-number"><?php echo formatNumber($videosPendientes); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-success elevation-1">
                        <i class="fas fa-check-circle"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Publicados Hoy</span>
                        <span class="info-box-number"><?php echo formatNumber($videosPublicadosHoy); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-danger elevation-1">
                        <i class="fas fa-eye"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Views Totales</span>
                        <span class="info-box-number"><?php echo formatNumber($totalViews); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main row -->
        <div class="row">
            
            <!-- Videos Recientes -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-film"></i> Videos Recientes
                        </h3>
                        <div class="card-tools">
                            <a href="biblioteca.php" class="btn btn-sm btn-primary">
                                Ver todos
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Estado</th>
                                    <th>Categoría</th>
                                    <th>Creado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($videosRecientes)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No hay videos aún</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($videosRecientes as $video): ?>
                                        <tr>
                                            <td><?php echo truncateText($video['titulo'], 40); ?></td>
                                            <td><?php echo getStatusBadge($video['estado']); ?></td>
                                            <td>
                                                <span class="badge badge-secondary">
                                                    <?php echo $video['categoria'] ?? 'Sin categoría'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo timeAgo($video['creado']); ?></td>
                                            <td>
                                                <a href="ver-video.php?id=<?php echo $video['id']; ?>" 
                                                   class="btn btn-xs btn-info" 
                                                   data-toggle="tooltip" 
                                                   title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Próximas Publicaciones -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-calendar-alt"></i> Próximas Publicaciones
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <ul class="products-list product-list-in-card pl-2 pr-2">
                            <?php if (empty($proximasPublicaciones)): ?>
                                <li class="item text-center p-3">
                                    <span class="text-muted">No hay publicaciones programadas</span>
                                </li>
                            <?php else: ?>
                                <?php foreach ($proximasPublicaciones as $pub): ?>
                                    <li class="item">
                                        <div class="product-info">
                                            <a href="javascript:void(0)" class="product-title">
                                                <?php echo truncateText($pub['titulo'], 30); ?>
                                                <span class="badge badge-info float-right">
                                                    <i class="<?php echo getPlatformIcon($pub['plataforma']); ?>"></i>
                                                </span>
                                            </a>
                                            <span class="product-description">
                                                <i class="far fa-clock"></i> 
                                                <?php echo formatDate($pub['fecha_programada'], 'd/m H:i'); ?>
                                            </span>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="card-footer text-center">
                        <a href="calendario.php" class="uppercase">Ver Calendario Completo</a>
                    </div>
                </div>
            </div>
            
        </div>
        
        <!-- Acciones Rápidas -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-bolt"></i> Acciones Rápidas
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 col-sm-6">
                                <a href="generador.php" class="btn btn-block btn-primary btn-lg">
                                    <i class="fas fa-magic"></i><br>
                                    Generar Videos
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <a href="tendencias.php" class="btn btn-block btn-info btn-lg">
                                    <i class="fas fa-fire"></i><br>
                                    Ver Tendencias
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <a href="revisor.php" class="btn btn-block btn-warning btn-lg">
                                    <i class="fas fa-eye"></i><br>
                                    Revisar Videos
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <a href="publicador.php" class="btn btn-block btn-success btn-lg">
                                    <i class="fas fa-share-alt"></i><br>
                                    Publicar Ahora
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</section>

<?php
// Scripts adicionales para el dashboard
$additionalJS = [
    'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js'
];

require_once 'includes/footer.php';
?>

<script>
// Actualizar contadores del sidebar
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('videos-count').textContent = '<?php echo $videosHoy; ?>';
    document.getElementById('pending-count').textContent = '<?php echo $videosPendientes; ?>';
});
</script>
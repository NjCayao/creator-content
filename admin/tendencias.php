<?php
session_start();

// Fix temporal para sesión
require_once 'includes/session-fix.php';

require_once '../config/config.php';
require_once 'includes/auth_check.php';
require_once '../classes/core/Database.php';

$db = Database::getInstance()->getConnection();

// Definir título de página
$pageTitle = 'Tendencias';

// Variables para mensajes
$mensaje = '';
$error = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['buscar_ahora'])) {
        // Por ahora solo simular búsqueda
        $mensaje = "Función de búsqueda en desarrollo. Por favor, agrega tendencias manualmente.";
    } elseif (isset($_POST['agregar_manual'])) {
        // Agregar tendencia manualmente
        $keyword = trim($_POST['keyword']);
        $categoria = $_POST['categoria'];
        $score = intval($_POST['score_viral']);
        
        if (!empty($keyword)) {
            try {
                $stmt = $db->prepare("
                    INSERT INTO tendencias (keyword, keyword_clean, fuente, categoria, score_viral, volumen, pais, idioma, competencia)
                    VALUES (?, ?, 'manual', ?, ?, 5000, 'PE', 'es', 'media')
                ");
                
                $keywordClean = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $keyword));
                
                $stmt->execute([$keyword, $keywordClean, $categoria, $score]);
                $mensaje = "Tendencia agregada correctamente.";
            } catch (Exception $e) {
                $error = "Error al agregar tendencia: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['marcar_usada'])) {
        $id = intval($_POST['trend_id']);
        $stmt = $db->prepare("UPDATE tendencias SET usado = 1 WHERE id = ?");
        $stmt->execute([$id]);
        $mensaje = "Tendencia marcada como usada.";
    }
}

// Obtener filtros
$filtroCategoria = $_GET['categoria'] ?? '';
$filtroEstado = $_GET['estado'] ?? 'todas';

// Construir consulta
$sql = "SELECT * FROM tendencias WHERE 1=1";
$params = [];

if ($filtroCategoria) {
    $sql .= " AND categoria = ?";
    $params[] = $filtroCategoria;
}

if ($filtroEstado === 'disponibles') {
    $sql .= " AND usado = 0";
} elseif ($filtroEstado === 'usadas') {
    $sql .= " AND usado = 1";
}

$sql .= " ORDER BY score_viral DESC, detectado DESC LIMIT 100";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$tendencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas
$stats = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(usado = 0) as disponibles,
        SUM(usado = 1) as usadas
    FROM tendencias
")->fetch(PDO::FETCH_ASSOC);

// Obtener categorías para el select
$categorias_disponibles = ['tecnologia', 'entretenimiento', 'educacion', 'lifestyle', 'deportes', 'noticias', 'viral', 'misterios'];

$pageTitle = 'Tendencias';
require_once 'includes/header.php';
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Tendencias</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?php echo ADMIN_URL; ?>">Inicio</a></li>
                    <li class="breadcrumb-item active">Tendencias</li>
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
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="row">
            <div class="col-lg-4 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo $stats['total'] ?? 0; ?></h3>
                        <p>Total Tendencias</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo $stats['disponibles'] ?? 0; ?></h3>
                        <p>Disponibles</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo $stats['usadas'] ?? 0; ?></h3>
                        <p>Usadas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-video"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Agregar Tendencia Manual -->
        <div class="card collapsed-card">
            <div class="card-header">
                <h3 class="card-title">Agregar Tendencia Manual</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" class="form-inline">
                    <input type="hidden" name="agregar_manual" value="1">
                    <input type="text" name="keyword" class="form-control mr-2" placeholder="Palabra clave o tema" required>
                    <select name="categoria" class="form-control mr-2">
                        <?php foreach ($categorias_disponibles as $cat): ?>
                            <option value="<?php echo $cat; ?>"><?php echo ucfirst($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="score_viral" class="form-control mr-2" placeholder="Score (0-100)" min="0" max="100" value="50">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Agregar
                    </button>
                </form>
            </div>
        </div>

        <!-- Acciones -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Acciones</h3>
            </div>
            <div class="card-body">
                <form method="POST" class="d-inline">
                    <button type="submit" name="buscar_ahora" class="btn btn-primary">
                        <i class="fas fa-search"></i> Buscar Tendencias (En desarrollo)
                    </button>
                </form>
                
                <a href="?estado=todas" class="btn btn-secondary ml-2">
                    <i class="fas fa-sync"></i> Actualizar
                </a>
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
                            <option value="todas" <?php echo $filtroEstado === 'todas' ? 'selected' : ''; ?>>Todas</option>
                            <option value="disponibles" <?php echo $filtroEstado === 'disponibles' ? 'selected' : ''; ?>>Disponibles</option>
                            <option value="usadas" <?php echo $filtroEstado === 'usadas' ? 'selected' : ''; ?>>Usadas</option>
                        </select>
                    </div>
                    
                    <div class="form-group mr-3">
                        <label class="mr-2">Categoría:</label>
                        <select name="categoria" class="form-control" onchange="this.form.submit()">
                            <option value="">Todas</option>
                            <?php foreach ($categorias_disponibles as $cat): ?>
                                <option value="<?php echo $cat; ?>" <?php echo $filtroCategoria === $cat ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de tendencias -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Tendencias</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>Keyword</th>
                            <th>Fuente</th>
                            <th>Categoría</th>
                            <th>Score Viral</th>
                            <th>Competencia</th>
                            <th>Detectado</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tendencias)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <p class="text-muted">No hay tendencias registradas.</p>
                                    <p>Agrega tendencias manualmente usando el formulario arriba.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tendencias as $trend): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($trend['keyword']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-info"><?php echo ucfirst($trend['fuente']); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge badge-secondary"><?php echo ucfirst($trend['categoria'] ?? 'general'); ?></span>
                                    </td>
                                    <td>
                                        <div class="progress progress-xs">
                                            <div class="progress-bar bg-success" style="width: <?php echo $trend['score_viral']; ?>%"></div>
                                        </div>
                                        <small><?php echo $trend['score_viral']; ?>%</small>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $trend['competencia'] === 'baja' ? 'success' : ($trend['competencia'] === 'media' ? 'warning' : 'danger'); ?>">
                                            <?php echo ucfirst($trend['competencia']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?php echo date('d/m/Y', strtotime($trend['detectado'])); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($trend['usado']): ?>
                                            <span class="badge badge-warning">Usada</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">Disponible</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!$trend['usado']): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="trend_id" value="<?php echo $trend['id']; ?>">
                                                <button type="submit" name="marcar_usada" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
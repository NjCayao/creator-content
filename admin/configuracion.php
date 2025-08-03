<?php
// Archivo: /creator/admin/configuracion.php
// Propósito: Configuración general del sistema

$pageTitle = 'Configuración General';
require_once 'includes/header.php';
require_once 'includes/functions.php';

$db = Database::getInstance();
$message = '';
$error = '';

// Procesar formulario si se envió
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Actualizar cada configuración
        foreach ($_POST['config'] as $key => $value) {
            $db->query(
                "UPDATE configuracion SET valor = :valor WHERE clave = :clave",
                ['valor' => $value, 'clave' => $key]
            );
        }
        
        $message = 'Configuración actualizada correctamente.';
    } catch (Exception $e) {
        $error = 'Error al actualizar la configuración: ' . $e->getMessage();
    }
}

// Obtener configuraciones actuales
$configs = $db->query("
    SELECT * FROM configuracion 
    WHERE categoria IN ('produccion', 'sistema', 'email') 
    ORDER BY categoria, clave
")->fetchAll();

// Agrupar por categoría
$configsByCategory = [];
foreach ($configs as $config) {
    $configsByCategory[$config['categoria']][] = $config;
}
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Configuración General</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?php echo ADMIN_URL; ?>">Inicio</a></li>
                    <li class="breadcrumb-item">Sistema</li>
                    <li class="breadcrumb-item active">Configuración</li>
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
            <div class="row">
                
                <!-- Configuración de Producción -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-video"></i> Producción de Contenido
                            </h3>
                        </div>
                        <div class="card-body">
                            <?php foreach ($configsByCategory['produccion'] ?? [] as $config): ?>
                                <div class="form-group">
                                    <label for="config_<?php echo $config['clave']; ?>">
                                        <?php echo $config['descripcion']; ?>
                                    </label>
                                    
                                    <?php if ($config['tipo'] == 'number'): ?>
                                        <input type="number" 
                                               class="form-control" 
                                               id="config_<?php echo $config['clave']; ?>"
                                               name="config[<?php echo $config['clave']; ?>]"
                                               value="<?php echo htmlspecialchars($config['valor']); ?>"
                                               min="0">
                                               
                                    <?php elseif ($config['tipo'] == 'boolean'): ?>
                                        <select class="form-control" 
                                                id="config_<?php echo $config['clave']; ?>"
                                                name="config[<?php echo $config['clave']; ?>]">
                                            <option value="1" <?php echo $config['valor'] == '1' ? 'selected' : ''; ?>>Sí</option>
                                            <option value="0" <?php echo $config['valor'] == '0' ? 'selected' : ''; ?>>No</option>
                                        </select>
                                        
                                    <?php elseif ($config['clave'] == 'modo_revision'): ?>
                                        <select class="form-control" 
                                                id="config_<?php echo $config['clave']; ?>"
                                                name="config[<?php echo $config['clave']; ?>]">
                                            <option value="manual" <?php echo $config['valor'] == 'manual' ? 'selected' : ''; ?>>Manual</option>
                                            <option value="auto" <?php echo $config['valor'] == 'auto' ? 'selected' : ''; ?>>Automático</option>
                                            <option value="hibrido" <?php echo $config['valor'] == 'hibrido' ? 'selected' : ''; ?>>Híbrido</option>
                                        </select>
                                        
                                    <?php else: ?>
                                        <input type="text" 
                                               class="form-control" 
                                               id="config_<?php echo $config['clave']; ?>"
                                               name="config[<?php echo $config['clave']; ?>]"
                                               value="<?php echo htmlspecialchars($config['valor']); ?>">
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Configuración del Sistema -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-cog"></i> Sistema
                            </h3>
                        </div>
                        <div class="card-body">
                            <?php foreach ($configsByCategory['sistema'] ?? [] as $config): ?>
                                <div class="form-group">
                                    <label for="config_<?php echo $config['clave']; ?>">
                                        <?php echo $config['descripcion']; ?>
                                    </label>
                                    
                                    <?php if ($config['tipo'] == 'boolean'): ?>
                                        <select class="form-control" 
                                                id="config_<?php echo $config['clave']; ?>"
                                                name="config[<?php echo $config['clave']; ?>]">
                                            <option value="1" <?php echo $config['valor'] == '1' ? 'selected' : ''; ?>>Sí</option>
                                            <option value="0" <?php echo $config['valor'] == '0' ? 'selected' : ''; ?>>No</option>
                                        </select>
                                    <?php else: ?>
                                        <input type="text" 
                                               class="form-control" 
                                               id="config_<?php echo $config['clave']; ?>"
                                               name="config[<?php echo $config['clave']; ?>]"
                                               value="<?php echo htmlspecialchars($config['valor']); ?>"
                                               <?php echo $config['tipo'] == 'encrypted' ? 'readonly' : ''; ?>>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Configuración de Email -->
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-envelope"></i> Configuración de Email
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($configsByCategory['email'] ?? [] as $config): ?>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="config_<?php echo $config['clave']; ?>">
                                                <?php echo $config['descripcion']; ?>
                                            </label>
                                            
                                            <?php if ($config['tipo'] == 'encrypted' && strpos($config['clave'], 'pass') !== false): ?>
                                                <input type="password" 
                                                       class="form-control" 
                                                       id="config_<?php echo $config['clave']; ?>"
                                                       name="config[<?php echo $config['clave']; ?>]"
                                                       value="<?php echo htmlspecialchars($config['valor']); ?>"
                                                       placeholder="••••••••">
                                            <?php else: ?>
                                                <input type="<?php echo $config['tipo'] == 'number' ? 'number' : 'text'; ?>" 
                                                       class="form-control" 
                                                       id="config_<?php echo $config['clave']; ?>"
                                                       name="config[<?php echo $config['clave']; ?>]"
                                                       value="<?php echo htmlspecialchars($config['valor']); ?>">
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
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

<?php require_once 'includes/footer.php'; ?>
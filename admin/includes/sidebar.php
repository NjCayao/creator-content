<?php
// Archivo: /creator/admin/includes/sidebar.php
// Propósito: Menú lateral del admin

// Obtener página actual para marcar el menú activo
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>

<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="<?php echo ADMIN_URL; ?>" class="brand-link">
        <i class="fas fa-video brand-image" style="font-size: 30px; margin-left: 15px; margin-top: 5px; color: #fff;"></i>
        <span class="brand-text font-weight-light">Creator Content</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <i class="fas fa-user-circle fa-2x text-white"></i>
            </div>
            <div class="info">
                <a href="#" class="d-block"><?php echo htmlspecialchars($currentUser['nombre'] ?? 'Usuario'); ?></a>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

                <!-- Dashboard -->
                <li class="nav-item">
                    <a href="<?php echo ADMIN_URL; ?>/index.php" class="nav-link <?php echo $currentPage == 'index' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <!-- Contenido -->
                <li class="nav-header">CONTENIDO</li>

                <li class="nav-item">
                    <a href="<?php echo ADMIN_URL; ?>/tendencias.php" class="nav-link <?php echo $currentPage == 'tendencias' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-fire"></i>
                        <p>
                            Tendencias
                            <span class="badge badge-danger right">HOT</span>
                        </p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?php echo ADMIN_URL; ?>/generador.php" class="nav-link <?php echo $currentPage == 'generador' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-magic"></i>
                        <p>Generador</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?php echo ADMIN_URL; ?>/biblioteca.php" class="nav-link <?php echo $currentPage == 'biblioteca' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-video"></i>
                        <p>
                            Biblioteca
                            <span class="right badge badge-info" id="videos-count">0</span>
                        </p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?php echo ADMIN_URL; ?>/revisor.php" class="nav-link <?php echo $currentPage == 'revisor' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-eye"></i>
                        <p>
                            Revisor
                            <span class="right badge badge-warning" id="pending-count">0</span>
                        </p>
                    </a>
                </li>

                <!-- Publicación -->
                <li class="nav-header">PUBLICACIÓN</li>

                <li class="nav-item">
                    <a href="<?php echo ADMIN_URL; ?>/calendario.php" class="nav-link <?php echo $currentPage == 'calendario' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-calendar-alt"></i>
                        <p>Calendario</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?php echo ADMIN_URL; ?>/publicador.php" class="nav-link <?php echo $currentPage == 'publicador' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-share-alt"></i>
                        <p>Publicador</p>
                    </a>
                </li>

                <!-- Analytics -->
                <li class="nav-header">ANALYTICS</li>

                <li class="nav-item">
                    <a href="<?php echo ADMIN_URL; ?>/analytics.php" class="nav-link <?php echo $currentPage == 'analytics' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-chart-bar"></i>
                        <p>Estadísticas</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?php echo ADMIN_URL; ?>/monetizacion.php" class="nav-link <?php echo $currentPage == 'monetizacion' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-dollar-sign"></i>
                        <p>Monetización</p>
                    </a>
                </li>

                <!-- Sistema -->
                <li class="nav-header">SISTEMA</li>

                <li class="nav-item">
                    <a href="<?php echo ADMIN_URL; ?>/configuracion.php" class="nav-link <?php echo $currentPage == 'configuracion' ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i>
                        <span>Configuración</span>
                    </a>
                </li>

                <!-- AGREGAR ESTOS DOS ENLACES -->
                <li class="nav-item">
                    <a href="<?php echo ADMIN_URL; ?>/apis.php" class="nav-link <?php echo $currentPage == 'apis' ? 'active' : ''; ?>">
                        <i class="fas fa-key"></i>
                        <span>APIs</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?php echo ADMIN_URL; ?>/plataformas.php" class="nav-link <?php echo $currentPage == 'plataformas' ? 'active' : ''; ?>">
                        <i class="fas fa-share-nodes"></i>
                        <span>Plataformas</span>
                    </a>
                </li>


                <li class="nav-item">
                    <a href="<?php echo ADMIN_URL; ?>/plantillas.php" class="nav-link <?php echo $currentPage == 'plantillas' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-file-alt"></i>
                        <p>Plantillas</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?php echo ADMIN_URL; ?>/logs.php" class="nav-link <?php echo $currentPage == 'logs' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-history"></i>
                        <p>Logs</p>
                    </a>
                </li>

            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>
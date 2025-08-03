<?php
// Archivo: /creator/public/login.php (ACTUALIZADO)
// Propósito: Formulario de login con funcionalidad completa

// Definir acceso seguro
define('SECURE_ACCESS', true);

// Cargar configuración
require_once __DIR__ . '/../config/config.php';
require_once CLASSES_PATH . '/core/Database.php';
require_once CLASSES_PATH . '/core/Auth.php';

// Iniciar auth
$auth = new Auth();

// Si ya está logueado, redirigir
if ($auth->isAuthenticated()) {
    header('Location: ' . ADMIN_URL);
    exit;
}

$error = '';
$success = '';

// Verificar si viene de logout
if (isset($_GET['logout'])) {
    $success = 'Has cerrado sesión correctamente.';
}

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validar CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Token de seguridad inválido.';
    } else {
        // Intentar login
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        $result = $auth->login($email, $password, $remember);
        
        if ($result['success']) {
            // Redirigir al admin o a la página guardada
            $redirect = $_SESSION['redirect_after_login'] ?? ADMIN_URL;
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

// Generar CSRF token
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Creator Content System</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="../admin/assets/dist/css/adminlte.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .login-box {
            width: 400px;
            margin: 7% auto;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .login-logo {
            font-size: 2.5rem;
            color: white;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            margin-bottom: 30px;
            text-align: center;
        }
        .login-logo i {
            font-size: 3rem;
            display: block;
            margin-bottom: 10px;
        }
    </style>
</head>
<body class="hold-transition login-page">
    <div class="login-box">
        <div class="login-logo">
            <i class="fas fa-video"></i>
            <b>Creator</b> Content
        </div>
        
        <div class="card">
            <div class="card-body login-card-body">
                <p class="login-box-msg">Inicia sesión para acceder al sistema</p>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form action="" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    
                    <div class="input-group mb-3">
                        <input type="email" name="email" class="form-control" placeholder="Email" 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                               required autofocus>
                        <div class="input-group-text">
                            <span class="fas fa-envelope"></span>
                        </div>
                    </div>
                    
                    <div class="input-group mb-3">
                        <input type="password" name="password" class="form-control" placeholder="Contraseña" required>
                        <div class="input-group-text">
                            <span class="fas fa-lock"></span>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-8">
                            <div class="form-check">
                                <input type="checkbox" name="remember" class="form-check-input" id="remember">
                                <label class="form-check-label" for="remember">
                                    Recordarme
                                </label>
                            </div>
                        </div>
                        <div class="col-4">
                            <button type="submit" class="btn btn-primary btn-block w-100">
                                Ingresar
                            </button>
                        </div>
                    </div>
                </form>
                
                <hr>
                
                <p class="mb-1 text-center">
                    <a href="forgot-password.php">Olvidé mi contraseña</a>
                </p>
                
                <div class="text-center mt-3">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt"></i> Conexión segura
                    </small>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <small class="text-white">
                &copy; <?php echo date('Y'); ?> Creator Content System
            </small>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
// Archivo: /creator/classes/core/Auth.php
// Propósito: Clase para manejar autenticación

class Auth {
    private $db;
    private $sessionTimeout = SESSION_LIFETIME;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->initSession();
    }
    
    // Inicializar sesión
    private function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => SESSION_PATH,
                'httponly' => true,
                'secure' => isset($_SERVER['HTTPS']),
                'samesite' => 'Lax'
            ]);
            session_start();
        }
    }
    
    // Intentar login
    public function login($email, $password, $remember = false) {
        // Limpiar email
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        
        // Verificar si el usuario está bloqueado
        if ($this->isBlocked($email)) {
            return ['success' => false, 'message' => 'Usuario bloqueado temporalmente por múltiples intentos fallidos.'];
        }
        
        // Buscar usuario
        $sql = "SELECT * FROM usuario WHERE email = :email AND activo = 1";
        $this->db->query($sql, ['email' => $email]);
        $user = $this->db->fetch();
        
        if (!$user) {
            $this->recordFailedAttempt($email);
            return ['success' => false, 'message' => 'Credenciales incorrectas.'];
        }
        
        // Verificar contraseña
        if (!password_verify($password, $user['password'])) {
            $this->recordFailedAttempt($email);
            return ['success' => false, 'message' => 'Credenciales incorrectas.'];
        }
        
        // Login exitoso
        $this->createSession($user);
        $this->clearFailedAttempts($email);
        $this->updateLastAccess($user['id']);
        
        // Manejar "recordarme"
        if ($remember) {
            $this->createRememberToken($user['id']);
        }
        
        return ['success' => true, 'message' => 'Login exitoso.'];
    }
    
    // Crear sesión de usuario
    private function createSession($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['nombre'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        // Regenerar ID de sesión por seguridad
        session_regenerate_id(true);
    }
    
    // Verificar si está autenticado
    public function isAuthenticated() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Verificar timeout
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > $this->sessionTimeout) {
                $this->logout();
                return false;
            }
        }
        
        // Actualizar última actividad
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    // Cerrar sesión
    public function logout() {
        // Limpiar todas las variables de sesión
        $_SESSION = [];
        
        // Destruir cookie de sesión
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, SESSION_PATH);
        }
        
        // Destruir sesión
        session_destroy();
        
        return true;
    }
    
    // Verificar si usuario está bloqueado
    private function isBlocked($email) {
        $sql = "SELECT bloqueado_hasta FROM usuario WHERE email = :email";
        $this->db->query($sql, ['email' => $email]);
        $result = $this->db->fetch();
        
        if ($result && $result['bloqueado_hasta']) {
            if (strtotime($result['bloqueado_hasta']) > time()) {
                return true;
            }
        }
        
        return false;
    }
    
    // Registrar intento fallido
    private function recordFailedAttempt($email) {
        $sql = "UPDATE usuario SET 
                intentos_fallidos = intentos_fallidos + 1,
                bloqueado_hasta = CASE 
                    WHEN intentos_fallidos >= :max_attempts - 1 
                    THEN DATE_ADD(NOW(), INTERVAL :block_time SECOND)
                    ELSE bloqueado_hasta
                END
                WHERE email = :email";
        
        $this->db->query($sql, [
            'email' => $email,
            'max_attempts' => MAX_LOGIN_ATTEMPTS,
            'block_time' => BLOCK_TIME
        ]);
    }
    
    // Limpiar intentos fallidos
    private function clearFailedAttempts($email) {
        $sql = "UPDATE usuario SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE email = :email";
        $this->db->query($sql, ['email' => $email]);
    }
    
    // Actualizar último acceso
    private function updateLastAccess($userId) {
        $sql = "UPDATE usuario SET ultimo_acceso = NOW() WHERE id = :id";
        $this->db->query($sql, ['id' => $userId]);
    }
    
    // Obtener usuario actual
    public function getCurrentUser() {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        $sql = "SELECT id, email, nombre FROM usuario WHERE id = :id";
        $this->db->query($sql, ['id' => $_SESSION['user_id']]);
        return $this->db->fetch();
    }
    
    // Crear token para recordar sesión (opcional)
    private function createRememberToken($userId) {
        // Implementar si necesitas función "recordarme"
        // Esto requeriría una tabla adicional para tokens
    }
}
?>
<?php
/**
 * Clase para manejar seguridad y encriptación
 */
class Security {
    
    /**
     * Encriptar datos
     */
    public function encrypt($data) {
        if (empty($data)) return '';
        
        $key = ENCRYPTION_KEY;
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($data, ENCRYPTION_METHOD, $key, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }
    
    /**
     * Desencriptar datos
     */
    public function decrypt($data) {
        if (empty($data)) return '';
        
        $key = ENCRYPTION_KEY;
        list($encrypted_data, $iv) = array_pad(explode('::', base64_decode($data), 2), 2, null);
        
        if (!$iv) return '';
        
        return openssl_decrypt($encrypted_data, ENCRYPTION_METHOD, $key, 0, $iv);
    }
    
    /**
     * Generar token seguro
     */
    public function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Hash de contraseña
     */
    public function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Verificar contraseña
     */
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Sanitizar input
     */
    public function sanitize($input, $type = 'string') {
        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_SANITIZE_EMAIL);
            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
            case 'float':
                return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            case 'url':
                return filter_var($input, FILTER_SANITIZE_URL);
            default:
                return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        }
    }
    
    /**
     * Validar CSRF token
     */
    public function validateCSRF($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Generar CSRF token
     */
    public function generateCSRF() {
        $token = $this->generateToken();
        $_SESSION['csrf_token'] = $token;
        return $token;
    }
}
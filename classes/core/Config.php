<?php
// Archivo: /creator/classes/core/Config.php
// Propósito: Clase para manejar configuraciones

class Config {
    private $db;
    private $cache = [];
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->loadAll();
    }
    
    // Cargar todas las configuraciones
    private function loadAll() {
        $configs = $this->db->query("SELECT clave, valor FROM configuracion")->fetchAll();
        
        foreach ($configs as $config) {
            $this->cache[$config['clave']] = $config['valor'];
        }
    }
    
    // Obtener valor de configuración
    public function get($key, $default = null) {
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }
        
        // Si no está en cache, buscar en BD
        $result = $this->db->query(
            "SELECT valor FROM configuracion WHERE clave = ?", 
            [$key]
        )->fetch();
        
        if ($result) {
            $this->cache[$key] = $result['valor'];
            return $result['valor'];
        }
        
        return $default;
    }
    
    // Establecer valor de configuración
    public function set($key, $value) {
        // Verificar si existe
        if ($this->exists($key)) {
            // Actualizar
            $this->db->query(
                "UPDATE configuracion SET valor = ? WHERE clave = ?",
                [$value, $key]
            );
        } else {
            // Insertar
            $this->db->query(
                "INSERT INTO configuracion (clave, valor) VALUES (?, ?)",
                [$key, $value]
            );
        }
        
        // Actualizar cache
        $this->cache[$key] = $value;
        
        return true;
    }
    
    // Verificar si existe una configuración
    public function exists($key) {
        return isset($this->cache[$key]) || 
               $this->db->exists('configuracion', 'clave = ?', [$key]);
    }
    
    // Obtener todas las configuraciones de una categoría
    public function getByCategory($category) {
        return $this->db->query(
            "SELECT * FROM configuracion WHERE categoria = ? ORDER BY clave",
            [$category]
        )->fetchAll();
    }
    
    // Obtener configuración como entero
    public function getInt($key, $default = 0) {
        return (int) $this->get($key, $default);
    }
    
    // Obtener configuración como boolean
    public function getBool($key, $default = false) {
        $value = $this->get($key, $default);
        return $value === '1' || $value === 'true' || $value === true;
    }
    
    // Recargar configuraciones
    public function reload() {
        $this->cache = [];
        $this->loadAll();
    }
}
?>
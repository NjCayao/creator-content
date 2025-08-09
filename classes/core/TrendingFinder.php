<?php
/**
 * Clase principal para buscar tendencias
 * Coordina todas las fuentes de tendencias
 */
class TrendingFinder {
    private $db;
    private $sources = [];
    private $config;
    
    public function __construct() {
        require_once __DIR__ . '/../core/Database.php';
        $this->db = Database::getInstance()->getConnection();
        $this->loadConfig();
        $this->initializeSources();
    }
    
    /**
     * Cargar configuración del sistema
     */
    private function loadConfig() {
        $this->config = [
            'pais' => $this->getConfigValue('pais_principal', 'PE'),
            'idioma' => $this->getConfigValue('idioma_principal', 'es'),
            'categorias' => json_decode($this->getConfigValue('categorias_activas', '[]'), true)
        ];
    }
    
    /**
     * Obtener valor de configuración
     */
    private function getConfigValue($key, $default = '') {
        $stmt = $this->db->prepare("SELECT valor FROM configuracion WHERE clave = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['valor'] : $default;
    }
    
    /**
     * Inicializar fuentes de tendencias
     */
    private function initializeSources() {
        $sourcesDir = __DIR__ . '/../sources/';
        
        // Cargar Google Trends
        if (file_exists($sourcesDir . 'GoogleTrends.php')) {
            require_once $sourcesDir . 'GoogleTrends.php';
            $this->sources['google'] = new GoogleTrends($this->config);
        }
        
        // Cargar YouTube Trends
        if (file_exists($sourcesDir . 'YouTubeTrends.php')) {
            require_once $sourcesDir . 'YouTubeTrends.php';
            $this->sources['youtube'] = new YouTubeTrends($this->config);
        }
        
        // Cargar Twitter Trends (X)
        if (file_exists($sourcesDir . 'TwitterTrends.php')) {
            require_once $sourcesDir . 'TwitterTrends.php';
            $this->sources['twitter'] = new TwitterTrends($this->config);
        }
    }
    
    /**
     * Buscar tendencias en todas las fuentes
     */
    public function searchAllSources() {
        $allTrends = [];
        $errors = [];
        
        foreach ($this->sources as $sourceName => $source) {
            try {
                echo "Buscando en $sourceName...\n";
                $trends = $source->fetchTrends();
                
                foreach ($trends as $trend) {
                    // Agregar fuente al trend
                    $trend['fuente'] = $sourceName;
                    $allTrends[] = $trend;
                }
                
                echo "Encontradas " . count($trends) . " tendencias en $sourceName\n";
                
            } catch (Exception $e) {
                $errors[$sourceName] = $e->getMessage();
                echo "Error en $sourceName: " . $e->getMessage() . "\n";
            }
        }
        
        // Procesar y guardar tendencias
        $saved = $this->processTrends($allTrends);
        
        // Registrar en logs
        $this->logSearch(count($allTrends), $saved, $errors);
        
        return [
            'total' => count($allTrends),
            'saved' => $saved,
            'errors' => $errors
        ];
    }
    
    /**
     * Procesar y guardar tendencias
     */
    private function processTrends($trends) {
        $saved = 0;
        
        foreach ($trends as $trend) {
            // Limpiar keyword
            $keywordClean = $this->cleanKeyword($trend['keyword']);
            
            // Verificar si ya existe
            if (!$this->trendExists($keywordClean, $trend['fuente'])) {
                // Calcular score viral
                $viralScore = $this->calculateViralScore($trend);
                
                // Guardar en BD
                if ($this->saveTrend($trend, $keywordClean, $viralScore)) {
                    $saved++;
                }
            }
        }
        
        return $saved;
    }
    
    /**
     * Limpiar keyword
     */
    private function cleanKeyword($keyword) {
        // Remover caracteres especiales
        $clean = preg_replace('/[^a-zA-Z0-9\s\-áéíóúñÁÉÍÓÚÑ]/u', '', $keyword);
        // Convertir a minúsculas
        $clean = mb_strtolower($clean, 'UTF-8');
        // Remover espacios extras
        $clean = trim(preg_replace('/\s+/', ' ', $clean));
        
        return $clean;
    }
    
    /**
     * Verificar si tendencia existe
     */
    private function trendExists($keywordClean, $fuente) {
        $stmt = $this->db->prepare("
            SELECT id FROM tendencias 
            WHERE keyword_clean = ? AND fuente = ? 
            AND detectado > DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $stmt->execute([$keywordClean, $fuente]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Calcular score viral
     */
    private function calculateViralScore($trend) {
        $score = 50; // Base
        
        // Volumen
        if (isset($trend['volumen'])) {
            if ($trend['volumen'] > 10000) $score += 20;
            elseif ($trend['volumen'] > 5000) $score += 10;
            elseif ($trend['volumen'] > 1000) $score += 5;
        }
        
        // Competencia
        if (isset($trend['competencia'])) {
            if ($trend['competencia'] == 'baja') $score += 15;
            elseif ($trend['competencia'] == 'media') $score += 5;
            else $score -= 5;
        }
        
        // Categoría popular
        if (isset($trend['categoria']) && in_array($trend['categoria'], ['entretenimiento', 'viral', 'misterios'])) {
            $score += 10;
        }
        
        // Limitar entre 0 y 100
        return max(0, min(100, $score));
    }
    
    /**
     * Guardar tendencia en BD
     */
    private function saveTrend($trend, $keywordClean, $viralScore) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO tendencias (
                    keyword, keyword_clean, fuente, volumen, pais, idioma,
                    categoria, score_viral, competencia, data_adicional, expira
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            // Fecha de expiración (7 días por defecto)
            $expira = date('Y-m-d H:i:s', strtotime('+7 days'));
            
            $stmt->execute([
                $trend['keyword'],
                $keywordClean,
                $trend['fuente'],
                $trend['volumen'] ?? 0,
                $trend['pais'] ?? $this->config['pais'],
                $trend['idioma'] ?? $this->config['idioma'],
                $trend['categoria'] ?? null,
                $viralScore,
                $trend['competencia'] ?? 'media',
                isset($trend['data_adicional']) ? json_encode($trend['data_adicional']) : null,
                $expira
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log("Error guardando tendencia: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registrar búsqueda en logs
     */
    private function logSearch($total, $saved, $errors) {
        require_once __DIR__ . '/Logger.php';
        $logger = new Logger();
        
        $detalles = [
            'tendencias_encontradas' => $total,
            'tendencias_guardadas' => $saved,
            'errores' => $errors
        ];
        
        $logger->log(
            'info',
            'tendencias',
            'busqueda_completada',
            "Búsqueda completada: $saved de $total tendencias guardadas",
            $detalles
        );
    }
    
    /**
     * Obtener tendencias no usadas
     */
    public function getUnusedTrends($limit = 10, $minScore = 50) {
        $stmt = $this->db->prepare("
            SELECT * FROM tendencias 
            WHERE usado = 0 
            AND score_viral >= ? 
            AND (expira IS NULL OR expira > NOW())
            ORDER BY score_viral DESC, detectado DESC 
            LIMIT ?
        ");
        $stmt->execute([$minScore, $limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Marcar tendencia como usada
     */
    public function markAsUsed($trendId) {
        $stmt = $this->db->prepare("UPDATE tendencias SET usado = 1 WHERE id = ?");
        return $stmt->execute([$trendId]);
    }
    
    /**
     * Limpiar tendencias expiradas
     */
    public function cleanExpiredTrends() {
        $stmt = $this->db->prepare("
            DELETE FROM tendencias 
            WHERE expira < NOW() 
            AND usado = 0
        ");
        return $stmt->execute();
    }
}
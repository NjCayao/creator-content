<?php
/**
 * Generador principal de contenido
 * Coordina la generación de guiones, videos y metadatos
 */
class ContentGenerator {
    private $db;
    private $openai;
    private $invideo;
    private $scriptGenerator;
    private $metadataGenerator;
    private $logger;
    
    public function __construct() {
        require_once __DIR__ . '/../core/Database.php';
        require_once __DIR__ . '/../core/Logger.php';
        require_once __DIR__ . '/OpenAI.php';
        require_once __DIR__ . '/InVideo.php';
        require_once __DIR__ . '/ScriptGenerator.php';
        require_once __DIR__ . '/MetadataGenerator.php';
        
        $this->db = Database::getInstance()->getConnection();
        $this->logger = new Logger();
        
        try {
            $this->openai = new OpenAI();
            $this->invideo = new InVideo();
            $this->scriptGenerator = new ScriptGenerator($this->openai);
            $this->metadataGenerator = new MetadataGenerator($this->openai);
        } catch (Exception $e) {
            $this->logger->error('content_generator', 'init', 'Error inicializando generadores: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Generar contenido a partir de una tendencia
     */
    public function generateFromTrend($trendId, $options = []) {
        try {
            // Obtener tendencia
            $trend = $this->getTrend($trendId);
            if (!$trend) {
                throw new Exception("Tendencia no encontrada");
            }
            
            // Opciones por defecto
            $defaultOptions = [
                'tipo' => 'short',
                'duracion' => 60,
                'calidad' => '1080p',
                'template' => 'viral',
                'auto_publish' => false
            ];
            $options = array_merge($defaultOptions, $options);
            
            // Crear registro de video
            $videoId = $this->createVideoRecord($trend, $options);
            
            // Actualizar estado
            $this->updateVideoStatus($videoId, 'generando');
            
            // 1. Generar guion
            $this->logger->info('content_generator', 'generate', "Generando guion para: {$trend['keyword']}");
            $script = $this->scriptGenerator->generate($trend['keyword'], $options['duracion'], $options['tipo']);
            
            // 2. Generar metadatos
            $this->logger->info('content_generator', 'generate', "Generando metadatos");
            $metadata = $this->metadataGenerator->generate($trend['keyword'], $script);
            
            // 3. Actualizar registro con guion y metadatos
            $this->updateVideoData($videoId, $script, $metadata);
            
            // 4. Generar video con InVideo
            $this->logger->info('content_generator', 'generate', "Generando video con InVideo");
            $videoResult = $this->invideo->createVideo($script, [
                'title' => $metadata['titulo'],
                'duration' => $options['duracion'],
                'resolution' => $options['calidad'],
                'template' => $options['template']
            ]);
            
            // 5. Descargar video localmente
            $localPath = $this->downloadVideo($videoResult['download_url'], $videoId);
            
            // 6. Actualizar registro con URLs del video
            $this->updateVideoUrls($videoId, $videoResult, $localPath);
            
            // 7. Marcar tendencia como usada
            $this->markTrendAsUsed($trendId);
            
            // 8. Actualizar estado final
            $status = $options['auto_publish'] ? 'aprobado' : 'revision';
            $this->updateVideoStatus($videoId, $status);
            
            $this->logger->success('content_generator', 'generate', "Video generado exitosamente: ID $videoId");
            
            return [
                'success' => true,
                'video_id' => $videoId,
                'message' => 'Video generado exitosamente'
            ];
            
        } catch (Exception $e) {
            $this->logger->error('content_generator', 'generate', $e->getMessage());
            
            if (isset($videoId)) {
                $this->updateVideoStatus($videoId, 'error');
            }
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generar múltiples videos
     */
    public function generateBatch($limit = 5) {
        $results = [
            'total' => 0,
            'success' => 0,
            'errors' => 0,
            'details' => []
        ];
        
        // Obtener tendencias no usadas
        $trends = $this->getUnusedTrends($limit);
        $results['total'] = count($trends);
        
        foreach ($trends as $trend) {
            $result = $this->generateFromTrend($trend['id']);
            
            if ($result['success']) {
                $results['success']++;
            } else {
                $results['errors']++;
            }
            
            $results['details'][] = [
                'trend' => $trend['keyword'],
                'result' => $result
            ];
            
            // Pausa entre generaciones para no sobrecargar APIs
            sleep(5);
        }
        
        return $results;
    }
    
    /**
     * Obtener tendencia
     */
    private function getTrend($trendId) {
        $stmt = $this->db->prepare("SELECT * FROM tendencias WHERE id = ?");
        $stmt->execute([$trendId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener tendencias no usadas
     */
    private function getUnusedTrends($limit) {
        $stmt = $this->db->prepare("
            SELECT * FROM tendencias 
            WHERE usado = 0 
            AND score_viral >= 50
            AND (expira IS NULL OR expira > NOW())
            ORDER BY score_viral DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Crear registro de video
     */
    private function createVideoRecord($trend, $options) {
        $stmt = $this->db->prepare("
            INSERT INTO videos (
                titulo, tipo, categoria, calidad, idioma, estado, 
                viral_score, creado
            ) VALUES (?, ?, ?, ?, ?, 'ideacion', ?, NOW())
        ");
        
        $stmt->execute([
            $trend['keyword'], // Título temporal
            $options['tipo'],
            $trend['categoria'],
            $options['calidad'],
            'es',
            $trend['score_viral']
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Actualizar estado del video
     */
    private function updateVideoStatus($videoId, $status) {
        $stmt = $this->db->prepare("UPDATE videos SET estado = ? WHERE id = ?");
        $stmt->execute([$status, $videoId]);
    }
    
    /**
     * Actualizar datos del video
     */
    private function updateVideoData($videoId, $script, $metadata) {
        $scriptText = is_array($script) ? json_encode($script) : $script;
        
        $stmt = $this->db->prepare("
            UPDATE videos SET 
                titulo = ?,
                descripcion = ?,
                guion = ?,
                hashtags = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $metadata['titulo'],
            $metadata['descripcion'],
            $scriptText,
            implode(' ', $metadata['hashtags']),
            $videoId
        ]);
    }
    
    /**
     * Actualizar URLs del video
     */
    private function updateVideoUrls($videoId, $videoResult, $localPath) {
        $stmt = $this->db->prepare("
            UPDATE videos SET 
                video_url = ?,
                video_local_path = ?,
                thumbnail_url = ?,
                duracion = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $videoResult['download_url'],
            $localPath,
            $videoResult['preview_url'] ?? '',
            $videoResult['duration'] ?? 60,
            $videoId
        ]);
    }
    
    /**
     * Descargar video localmente
     */
    private function downloadVideo($url, $videoId) {
        $uploadDir = __DIR__ . '/../../storage/videos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $filename = $videoId . '_' . date('Ymd_His') . '.mp4';
        $localPath = $uploadDir . $filename;
        
        $this->invideo->downloadVideo($url, $localPath);
        
        return 'storage/videos/' . $filename;
    }
    
    /**
     * Marcar tendencia como usada
     */
    private function markTrendAsUsed($trendId) {
        $stmt = $this->db->prepare("UPDATE tendencias SET usado = 1 WHERE id = ?");
        $stmt->execute([$trendId]);
    }
    
    /**
     * Obtener estadísticas de generación
     */
    public function getStats() {
        $stats = [];
        
        // Videos generados hoy
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total FROM videos 
            WHERE DATE(creado) = CURDATE()
        ");
        $stats['hoy'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Videos por estado
        $stmt = $this->db->prepare("
            SELECT estado, COUNT(*) as total 
            FROM videos 
            GROUP BY estado
        ");
        $stats['por_estado'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Tendencias disponibles
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total FROM tendencias 
            WHERE usado = 0 AND (expira IS NULL OR expira > NOW())
        ");
        $stats['tendencias_disponibles'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        return $stats;
    }
}
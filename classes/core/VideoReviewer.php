<?php
/**
 * Clase para revisar y evaluar videos
 */
class VideoReviewer {
    private $db;
    private $logger;
    
    public function __construct() {
        require_once __DIR__ . '/Database.php';
        require_once __DIR__ . '/Logger.php';
        
        $this->db = Database::getInstance()->getConnection();
        $this->logger = new Logger();
    }
    
    /**
     * Evaluar calidad del video
     */
    public function evaluateQuality($videoId) {
        $video = $this->getVideo($videoId);
        if (!$video) {
            return false;
        }
        
        $score = 0;
        $criterios = [];
        
        // Evaluar título
        $tituloScore = $this->evaluateTitle($video['titulo']);
        $score += $tituloScore * 0.25;
        $criterios['titulo'] = $tituloScore;
        
        // Evaluar descripción
        $descripcionScore = $this->evaluateDescription($video['descripcion']);
        $score += $descripcionScore * 0.15;
        $criterios['descripcion'] = $descripcionScore;
        
        // Evaluar hashtags
        $hashtagsScore = $this->evaluateHashtags($video['hashtags']);
        $score += $hashtagsScore * 0.20;
        $criterios['hashtags'] = $hashtagsScore;
        
        // Evaluar guion
        $guionScore = $this->evaluateScript($video['guion']);
        $score += $guionScore * 0.40;
        $criterios['guion'] = $guionScore;
        
        // Actualizar score en BD
        $stmt = $this->db->prepare("UPDATE videos SET calidad_score = ? WHERE id = ?");
        $stmt->execute([round($score), $videoId]);
        
        return [
            'score_total' => round($score),
            'criterios' => $criterios,
            'aprobado' => $score >= 60
        ];
    }
    
    /**
     * Evaluar título
     */
    private function evaluateTitle($titulo) {
        $score = 50; // Base
        
        // Longitud ideal (40-70 caracteres)
        $length = mb_strlen($titulo);
        if ($length >= 40 && $length <= 70) {
            $score += 20;
        } elseif ($length < 30 || $length > 100) {
            $score -= 20;
        }
        
        // Contiene emojis
        if (preg_match('/[\x{1F300}-\x{1F9FF}]/u', $titulo)) {
            $score += 10;
        }
        
        // Contiene números
        if (preg_match('/\d+/', $titulo)) {
            $score += 10;
        }
        
        // Palabras poderosas
        $powerWords = ['increíble', 'sorprendente', 'secreto', 'nunca', 'único', 'mejor', 'peor'];
        foreach ($powerWords as $word) {
            if (stripos($titulo, $word) !== false) {
                $score += 5;
                break;
            }
        }
        
        // Mayúsculas excesivas (penalizar)
        if (mb_strtoupper($titulo) === $titulo) {
            $score -= 20;
        }
        
        return max(0, min(100, $score));
    }
    
    /**
     * Evaluar descripción
     */
    private function evaluateDescription($descripcion) {
        if (empty($descripcion)) {
            return 0;
        }
        
        $score = 50;
        
        // Longitud ideal (100-300 caracteres)
        $length = mb_strlen($descripcion);
        if ($length >= 100 && $length <= 300) {
            $score += 30;
        } elseif ($length < 50) {
            $score -= 20;
        }
        
        // Contiene call to action
        $ctas = ['suscrib', 'comenta', 'comparte', 'sígueme', 'dale like'];
        foreach ($ctas as $cta) {
            if (stripos($descripcion, $cta) !== false) {
                $score += 10;
                break;
            }
        }
        
        // Contiene emojis
        if (preg_match('/[\x{1F300}-\x{1F9FF}]/u', $descripcion)) {
            $score += 10;
        }
        
        return max(0, min(100, $score));
    }
    
    /**
     * Evaluar hashtags
     */
    private function evaluateHashtags($hashtags) {
        if (empty($hashtags)) {
            return 0;
        }
        
        $hashtagArray = explode(' ', $hashtags);
        $hashtagArray = array_filter($hashtagArray, function($tag) {
            return substr($tag, 0, 1) === '#';
        });
        
        $count = count($hashtagArray);
        $score = 50;
        
        // Cantidad ideal (15-30)
        if ($count >= 15 && $count <= 30) {
            $score += 30;
        } elseif ($count < 10) {
            $score -= 20;
        } elseif ($count > 40) {
            $score -= 10;
        }
        
        // Verificar hashtags virales
        $viralTags = ['#viral', '#fyp', '#parati', '#foryou', '#trending'];
        $hasViral = false;
        foreach ($viralTags as $tag) {
            if (in_array($tag, $hashtagArray)) {
                $hasViral = true;
                break;
            }
        }
        
        if ($hasViral) {
            $score += 20;
        }
        
        return max(0, min(100, $score));
    }
    
    /**
     * Evaluar guion
     */
    private function evaluateScript($guion) {
        if (empty($guion)) {
            return 0;
        }
        
        $score = 50;
        
        // Si es JSON, evaluar estructura
        if (substr($guion, 0, 1) === '{') {
            $guionArray = json_decode($guion, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                // Tiene hook
                if (isset($guionArray['hook']) && !empty($guionArray['hook'])) {
                    $score += 20;
                }
                
                // Tiene desarrollo
                if (isset($guionArray['desarrollo']) && is_array($guionArray['desarrollo'])) {
                    $score += 15;
                    if (count($guionArray['desarrollo']) >= 3) {
                        $score += 10;
                    }
                }
                
                // Tiene climax
                if (isset($guionArray['climax']) && !empty($guionArray['climax'])) {
                    $score += 10;
                }
                
                // Tiene call to action
                if (isset($guionArray['call_to_action']) && !empty($guionArray['call_to_action'])) {
                    $score += 15;
                }
            }
        } else {
            // Evaluar texto plano
            $length = mb_strlen($guion);
            if ($length >= 200 && $length <= 1000) {
                $score += 30;
            }
        }
        
        return max(0, min(100, $score));
    }
    
    /**
     * Aprobar video
     */
    public function approve($videoId, $userId = null) {
        $stmt = $this->db->prepare("UPDATE videos SET estado = 'aprobado' WHERE id = ?");
        $result = $stmt->execute([$videoId]);
        
        if ($result) {
            $this->logger->info('reviewer', 'approve', "Video #$videoId aprobado", [
                'video_id' => $videoId,
                'user_id' => $userId
            ]);
        }
        
        return $result;
    }
    
    /**
     * Rechazar video
     */
    public function reject($videoId, $razon = '', $userId = null) {
        $stmt = $this->db->prepare("UPDATE videos SET estado = 'rechazado', notas = ? WHERE id = ?");
        $result = $stmt->execute([$razon, $videoId]);
        
        if ($result) {
            $this->logger->warning('reviewer', 'reject', "Video #$videoId rechazado", [
                'video_id' => $videoId,
                'razon' => $razon,
                'user_id' => $userId
            ]);
        }
        
        return $result;
    }
    
    /**
     * Obtener videos para revisar
     */
    public function getVideosForReview($limit = 10) {
        $stmt = $this->db->prepare("
            SELECT * FROM videos 
            WHERE estado = 'revision' 
            ORDER BY viral_score DESC, creado ASC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener estadísticas de revisión
     */
    public function getReviewStats() {
        $stats = [];
        
        // Videos por estado
        $stmt = $this->db->query("
            SELECT estado, COUNT(*) as total 
            FROM videos 
            GROUP BY estado
        ");
        $stats['por_estado'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Promedio de calidad
        $stmt = $this->db->query("
            SELECT AVG(calidad_score) as promedio 
            FROM videos 
            WHERE calidad_score > 0
        ");
        $stats['calidad_promedio'] = round($stmt->fetch(PDO::FETCH_ASSOC)['promedio'] ?? 0);
        
        // Videos revisados hoy
        $stmt = $this->db->query("
            SELECT COUNT(*) as total 
            FROM videos 
            WHERE estado IN ('aprobado', 'rechazado') 
            AND DATE(actualizado) = CURDATE()
        ");
        $stats['revisados_hoy'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        return $stats;
    }
    
    /**
     * Obtener video
     */
    private function getVideo($videoId) {
        $stmt = $this->db->prepare("SELECT * FROM videos WHERE id = ?");
        $stmt->execute([$videoId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Auto-aprobar videos de alta calidad
     */
    public function autoApproveHighQuality($minScore = 80) {
        $stmt = $this->db->prepare("
            UPDATE videos 
            SET estado = 'aprobado' 
            WHERE estado = 'revision' 
            AND viral_score >= ?
            AND calidad_score >= ?
        ");
        
        $result = $stmt->execute([$minScore, $minScore]);
        $affected = $stmt->rowCount();
        
        if ($affected > 0) {
            $this->logger->info('reviewer', 'auto_approve', 
                "Auto-aprobados $affected videos con score >= $minScore");
        }
        
        return $affected;
    }
}
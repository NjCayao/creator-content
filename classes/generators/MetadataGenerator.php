<?php
/**
 * Generador de metadatos para videos
 * Títulos, descripciones, hashtags, miniaturas
 */
class MetadataGenerator {
    private $openai;
    private $hashtagLibrary;
    
    public function __construct($openai) {
        $this->openai = $openai;
        $this->loadHashtagLibrary();
    }
    
    /**
     * Cargar biblioteca de hashtags populares
     */
    private function loadHashtagLibrary() {
        $this->hashtagLibrary = [
            'general' => [
                '#viral', '#fyp', '#parati', '#foryou', '#trending',
                '#viralvideo', '#explorepage', '#foryoupage', '#trend'
            ],
            'español' => [
                '#videoviral', '#virales', '#viralestiktok', '#videosvirales',
                '#españa', '#mexico', '#argentina', '#colombia', '#peru'
            ],
            'categorias' => [
                'educacion' => ['#aprender', '#educacion', '#datoscuriosos', '#sabíasque', '#cultura'],
                'entretenimiento' => ['#humor', '#comedia', '#divertido', '#entretenimiento', '#risas'],
                'misterio' => ['#misterio', '#terror', '#paranormal', '#inexplicable', '#miedo'],
                'tecnologia' => ['#tecnologia', '#tech', '#innovacion', '#futuro', '#apps'],
                'lifestyle' => ['#lifestyle', '#vida', '#motivacion', '#salud', '#bienestar']
            ]
        ];
    }
    
    /**
     * Generar todos los metadatos
     */
    public function generate($topic, $script) {
        $metadata = [
            'titulo' => $this->generateTitle($topic, $script),
            'descripcion' => $this->generateDescription($topic, $script),
            'hashtags' => $this->generateHashtags($topic, $script),
            'miniatura' => $this->generateThumbnailIdeas($topic, $script)
        ];
        
        return $metadata;
    }
    
    /**
     * Generar título optimizado
     */
    public function generateTitle($topic, $script) {
        // Generar múltiples opciones
        $titles = $this->openai->generateTitle($topic, 5);
        
        // Seleccionar el mejor basado en criterios
        $bestTitle = $this->selectBestTitle($titles);
        
        // Asegurar que no exceda límites de plataforma
        return $this->optimizeTitleLength($bestTitle);
    }
    
    /**
     * Generar descripción SEO
     */
    public function generateDescription($topic, $script) {
        $hookText = is_array($script) && isset($script['hook']) ? $script['hook'] : '';
        
        $description = $this->openai->generateDescription($topic, $hookText);
        
        // Agregar emojis relevantes
        $description = $this->addEmojis($description);
        
        // Agregar links o CTAs si es necesario
        $description .= "\n\n▶️ Sígueme para más contenido";
        
        return $description;
    }
    
    /**
     * Generar hashtags optimizados
     */
    public function generateHashtags($topic, $script) {
        // Obtener hashtags específicos del tema
        $topicHashtags = $this->openai->generateHashtags($topic, 15);
        
        // Detectar categoría
        $category = $this->detectCategory($topic);
        
        // Combinar con hashtags de biblioteca
        $hashtags = [];
        
        // 30% hashtags generales virales
        $hashtags = array_merge($hashtags, array_slice($this->hashtagLibrary['general'], 0, 9));
        
        // 20% hashtags en español
        $hashtags = array_merge($hashtags, array_slice($this->hashtagLibrary['español'], 0, 6));
        
        // 30% hashtags de categoría
        if (isset($this->hashtagLibrary['categorias'][$category])) {
            $hashtags = array_merge($hashtags, $this->hashtagLibrary['categorias'][$category]);
        }
        
        // 20% hashtags específicos del tema
        $hashtags = array_merge($hashtags, array_slice($topicHashtags, 0, 6));
        
        // Eliminar duplicados y limitar a 30
        $hashtags = array_unique($hashtags);
        $hashtags = array_slice($hashtags, 0, 30);
        
        return $hashtags;
    }
    
    /**
     * Generar ideas para miniatura
     */
    public function generateThumbnailIdeas($topic, $script) {
        $ideas = [
            'texto_principal' => $this->generateThumbnailText($topic),
            'colores_sugeridos' => $this->suggestColors($topic),
            'elementos_visuales' => $this->suggestVisualElements($topic),
            'estilo' => $this->suggestStyle($topic)
        ];
        
        return $ideas;
    }
    
    /**
     * Seleccionar mejor título
     */
    private function selectBestTitle($titles) {
        if (empty($titles)) {
            return "Video Increíble";
        }
        
        // Criterios de selección
        $scores = [];
        
        foreach ($titles as $title) {
            $score = 0;
            
            // Longitud ideal (40-60 caracteres)
            $length = mb_strlen($title);
            if ($length >= 40 && $length <= 60) {
                $score += 10;
            }
            
            // Contiene emoji
            if (preg_match('/[\x{1F300}-\x{1F9FF}]/u', $title)) {
                $score += 5;
            }
            
            // Contiene números
            if (preg_match('/\d+/', $title)) {
                $score += 5;
            }
            
            // Palabras poderosas
            $powerWords = ['increíble', 'sorprendente', 'nunca', 'secreto', 'revelado'];
            foreach ($powerWords as $word) {
                if (stripos($title, $word) !== false) {
                    $score += 3;
                }
            }
            
            $scores[$title] = $score;
        }
        
        // Ordenar por puntuación
        arsort($scores);
        
        return array_key_first($scores);
    }
    
    /**
     * Optimizar longitud del título
     */
    private function optimizeTitleLength($title) {
        // YouTube: máximo 100 caracteres
        // TikTok: máximo 150 caracteres
        // Ideal: 50-70 caracteres
        
        if (mb_strlen($title) > 100) {
            $title = mb_substr($title, 0, 97) . '...';
        }
        
        return $title;
    }
    
    /**
     * Agregar emojis relevantes
     */
    private function addEmojis($text) {
        $emojis = [
            'asombroso' => '😱',
            'increíble' => '🤯',
            'secreto' => '🤫',
            'dinero' => '💰',
            'amor' => '❤️',
            'triste' => '😢',
            'feliz' => '😊',
            'importante' => '⚠️',
            'nuevo' => '🆕',
            'gratis' => '🆓'
        ];
        
        foreach ($emojis as $word => $emoji) {
            if (stripos($text, $word) !== false && strpos($text, $emoji) === false) {
                $text = str_ireplace($word, $word . ' ' . $emoji, $text);
            }
        }
        
        return $text;
    }
    
    /**
     * Detectar categoría
     */
    private function detectCategory($topic) {
        $topic_lower = mb_strtolower($topic);
        
        $categories = [
            'educacion' => ['aprender', 'historia', 'ciencia', 'datos', 'curiosidad'],
            'entretenimiento' => ['divertido', 'gracioso', 'comedia', 'humor', 'juego'],
            'misterio' => ['misterio', 'terror', 'miedo', 'paranormal', 'extraño'],
            'tecnologia' => ['tecnología', 'app', 'internet', 'computadora', 'móvil'],
            'lifestyle' => ['vida', 'salud', 'fitness', 'comida', 'viaje']
        ];
        
        foreach ($categories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($topic_lower, $keyword) !== false) {
                    return $category;
                }
            }
        }
        
        return 'general';
    }
    
    /**
     * Generar texto para miniatura
     */
    private function generateThumbnailText($topic) {
        // Texto corto e impactante
        $words = explode(' ', $topic);
        
        if (count($words) > 3) {
            // Tomar las palabras más importantes
            $important = array_slice($words, 0, 3);
            return mb_strtoupper(implode(' ', $important));
        }
        
        return mb_strtoupper($topic);
    }
    
    /**
     * Sugerir colores para miniatura
     */
    private function suggestColors($topic) {
        $colorSchemes = [
            'misterio' => ['#000000', '#8B0000', '#4B0082'],
            'tecnologia' => ['#0066CC', '#00AA44', '#FF6600'],
            'educacion' => ['#FFC107', '#2196F3', '#4CAF50'],
            'entretenimiento' => ['#FF1744', '#FFEB3B', '#00BCD4'],
            'default' => ['#FF0000', '#FFFF00', '#000000']
        ];
        
        $category = $this->detectCategory($topic);
        
        return $colorSchemes[$category] ?? $colorSchemes['default'];
    }
    
    /**
     * Sugerir elementos visuales
     */
    private function suggestVisualElements($topic) {
        return [
            'flechas' => true,
            'circulos' => true,
            'texto_grande' => true,
            'contraste_alto' => true,
            'emoji_grande' => true
        ];
    }
    
    /**
     * Sugerir estilo de miniatura
     */
    private function suggestStyle($topic) {
        $styles = [
            'clickbait' => 'Texto grande, colores brillantes, expresiones exageradas',
            'minimalista' => 'Fondo limpio, texto centrado, pocos elementos',
            'informativo' => 'Datos destacados, gráficos simples, claridad',
            'misterioso' => 'Oscuro, sombras, texto intrigante'
        ];
        
        $category = $this->detectCategory($topic);
        
        switch ($category) {
            case 'misterio':
                return $styles['misterioso'];
            case 'educacion':
                return $styles['informativo'];
            default:
                return $styles['clickbait'];
        }
    }
}
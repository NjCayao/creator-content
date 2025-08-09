<?php
/**
 * Fuente de tendencias: YouTube
 * Usa la API de YouTube Data v3
 */
class YouTubeTrends {
    private $config;
    private $apiKey;
    private $baseUrl = 'https://www.googleapis.com/youtube/v3/';
    
    public function __construct($config) {
        $this->config = $config;
        $this->loadApiKey();
    }
    
    /**
     * Cargar API Key
     */
    private function loadApiKey() {
        // Por ahora usar una API key genérica o dejar vacío
        // YouTube Data API v3 requiere key válida
        $this->apiKey = ''; // Dejar vacío hasta configurar
        
        // TODO: Cuando se configure en admin/apis.php
        // require_once __DIR__ . '/../core/Database.php';
        // $db = Database::getInstance()->getConnection();
        // $stmt = $db->prepare("SELECT valor FROM configuracion WHERE clave = 'youtube_api_key'");
        // $stmt->execute();
        // $result = $stmt->fetch(PDO::FETCH_ASSOC);
        // if ($result && !empty($result['valor'])) {
        //     require_once __DIR__ . '/../core/Security.php';
        //     $security = new Security();
        //     $this->apiKey = $security->decrypt($result['valor']);
        // }
    }
    
    /**
     * Obtener tendencias
     */
    public function fetchTrends() {
        $trends = [];
        
        if (empty($this->apiKey)) {
            throw new Exception("YouTube API Key no configurada");
        }
        
        try {
            // Obtener videos en tendencia
            $trendingVideos = $this->getTrendingVideos();
            
            // Obtener búsquedas populares
            $popularSearches = $this->getPopularSearches();
            
            // Combinar resultados
            $trends = array_merge($trendingVideos, $popularSearches);
            
        } catch (Exception $e) {
            throw new Exception("Error obteniendo YouTube Trends: " . $e->getMessage());
        }
        
        return $trends;
    }
    
    /**
     * Obtener videos en tendencia
     */
    private function getTrendingVideos() {
        $trends = [];
        
        $params = [
            'part' => 'snippet,statistics',
            'chart' => 'mostPopular',
            'regionCode' => strtoupper($this->config['pais']),
            'maxResults' => 25,
            'key' => $this->apiKey
        ];
        
        $url = $this->baseUrl . 'videos?' . http_build_query($params);
        $response = $this->makeRequest($url);
        
        if (isset($response['items'])) {
            foreach ($response['items'] as $video) {
                // Extraer tags y título como tendencias
                $keywords = $this->extractKeywords($video);
                
                foreach ($keywords as $keyword) {
                    $trends[] = [
                        'keyword' => $keyword,
                        'volumen' => $this->calculateVolume($video['statistics']),
                        'categoria' => $this->mapCategory($video['snippet']['categoryId'] ?? ''),
                        'competencia' => $this->calculateCompetition($video['statistics']),
                        'pais' => $this->config['pais'],
                        'idioma' => $this->config['idioma'],
                        'data_adicional' => [
                            'video_id' => $video['id'],
                            'video_title' => $video['snippet']['title'],
                            'channel' => $video['snippet']['channelTitle'],
                            'views' => $video['statistics']['viewCount'] ?? 0
                        ]
                    ];
                }
            }
        }
        
        return $trends;
    }
    
    /**
     * Obtener búsquedas populares
     */
    private function getPopularSearches() {
        $trends = [];
        
        // YouTube no proporciona directamente búsquedas populares
        // Usamos videos populares por categoría para inferir tendencias
        $categories = ['1', '10', '15', '17', '19', '20', '22', '23', '24', '25', '26', '27', '28'];
        
        foreach ($categories as $categoryId) {
            $params = [
                'part' => 'snippet',
                'chart' => 'mostPopular',
                'videoCategoryId' => $categoryId,
                'regionCode' => strtoupper($this->config['pais']),
                'maxResults' => 5,
                'key' => $this->apiKey
            ];
            
            $url = $this->baseUrl . 'videos?' . http_build_query($params);
            
            try {
                $response = $this->makeRequest($url);
                
                if (isset($response['items'])) {
                    foreach ($response['items'] as $video) {
                        // Extraer tema principal del título
                        $mainTopic = $this->extractMainTopic($video['snippet']['title']);
                        
                        if ($mainTopic && strlen($mainTopic) > 3) {
                            $trends[] = [
                                'keyword' => $mainTopic,
                                'volumen' => rand(5000, 50000), // Estimado
                                'categoria' => $this->mapCategory($categoryId),
                                'competencia' => 'media',
                                'pais' => $this->config['pais'],
                                'idioma' => $this->config['idioma'],
                                'data_adicional' => [
                                    'source' => 'category_analysis',
                                    'category_id' => $categoryId
                                ]
                            ];
                        }
                    }
                }
            } catch (Exception $e) {
                // Continuar con otras categorías si una falla
                continue;
            }
        }
        
        // Eliminar duplicados
        $unique = [];
        foreach ($trends as $trend) {
            $key = mb_strtolower($trend['keyword'], 'UTF-8');
            if (!isset($unique[$key])) {
                $unique[$key] = $trend;
            }
        }
        
        return array_values($unique);
    }
    
    /**
     * Hacer petición HTTP
     */
    private function makeRequest($url) {
        $options = [
            'http' => [
                'method' => 'GET',
                'header' => [
                    'Accept: application/json',
                    'Accept-Language: ' . $this->config['idioma']
                ],
                'timeout' => 10
            ]
        ];
        
        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new Exception("Error en petición a YouTube API");
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['error'])) {
            throw new Exception("YouTube API Error: " . $data['error']['message']);
        }
        
        return $data;
    }
    
    /**
     * Extraer keywords de un video
     */
    private function extractKeywords($video) {
        $keywords = [];
        
        // Tags del video
        if (isset($video['snippet']['tags'])) {
            foreach ($video['snippet']['tags'] as $tag) {
                if (strlen($tag) > 3 && strlen($tag) < 50) {
                    $keywords[] = $tag;
                }
            }
        }
        
        // Extraer del título
        $titleWords = $this->extractMainTopic($video['snippet']['title']);
        if ($titleWords) {
            $keywords[] = $titleWords;
        }
        
        // Limitar a 3 keywords por video
        return array_slice($keywords, 0, 3);
    }
    
    /**
     * Extraer tema principal del título
     */
    private function extractMainTopic($title) {
        // Eliminar emojis y caracteres especiales
        $title = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $title);
        $title = preg_replace('/[\x{1F300}-\x{1F5FF}]/u', '', $title);
        $title = preg_replace('/[\x{1F680}-\x{1F6FF}]/u', '', $title);
        
        // Eliminar palabras comunes
        $stopwords = ['el', 'la', 'de', 'en', 'y', 'a', 'los', 'las', 'del', 'con', 'por', 'para', 'es', 'un', 'una'];
        
        $words = preg_split('/\s+/', mb_strtolower($title, 'UTF-8'));
        $filtered = array_filter($words, function($word) use ($stopwords) {
            return strlen($word) > 2 && !in_array($word, $stopwords);
        });
        
        // Tomar las primeras 3-4 palabras significativas
        $mainWords = array_slice($filtered, 0, 4);
        
        return implode(' ', $mainWords);
    }
    
    /**
     * Calcular volumen basado en estadísticas
     */
    private function calculateVolume($stats) {
        $views = intval($stats['viewCount'] ?? 0);
        
        // Estimar volumen de búsqueda basado en vistas
        if ($views > 1000000) return 50000;
        if ($views > 500000) return 25000;
        if ($views > 100000) return 10000;
        if ($views > 50000) return 5000;
        
        return 1000;
    }
    
    /**
     * Calcular competencia
     */
    private function calculateCompetition($stats) {
        $views = intval($stats['viewCount'] ?? 0);
        $likes = intval($stats['likeCount'] ?? 0);
        
        // Ratio de engagement
        $engagement = $views > 0 ? ($likes / $views) * 100 : 0;
        
        if ($engagement > 5) return 'baja';
        if ($engagement > 2) return 'media';
        
        return 'alta';
    }
    
    /**
     * Mapear categoría de YouTube
     */
    private function mapCategory($categoryId) {
        $map = [
            '1' => 'entretenimiento',    // Film & Animation
            '2' => 'entretenimiento',    // Autos & Vehicles
            '10' => 'entretenimiento',   // Music
            '15' => 'entretenimiento',   // Pets & Animals
            '17' => 'deportes',          // Sports
            '19' => 'entretenimiento',   // Travel & Events
            '20' => 'entretenimiento',   // Gaming
            '22' => 'entretenimiento',   // People & Blogs
            '23' => 'entretenimiento',   // Comedy
            '24' => 'entretenimiento',   // Entertainment
            '25' => 'noticias',          // News & Politics
            '26' => 'entretenimiento',   // Howto & Style
            '27' => 'educacion',         // Education
            '28' => 'ciencia'            // Science & Technology
        ];
        
        return $map[$categoryId] ?? 'general';
    }
}
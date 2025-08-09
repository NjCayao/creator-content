<?php
/**
 * Fuente de tendencias: Twitter/X
 * Versión simplificada sin API (scraping básico)
 */
class TwitterTrends {
    private $config;
    
    public function __construct($config) {
        $this->config = $config;
    }
    
    /**
     * Obtener tendencias
     */
    public function fetchTrends() {
        $trends = [];
        
        try {
            // Por ahora usamos tendencias hardcodeadas o de fuentes alternativas
            // Twitter API v2 requiere autenticación compleja
            $trends = $this->getAlternativeTrends();
            
        } catch (Exception $e) {
            throw new Exception("Error obteniendo Twitter Trends: " . $e->getMessage());
        }
        
        return $trends;
    }
    
    /**
     * Obtener tendencias de fuentes alternativas
     */
    private function getAlternativeTrends() {
        $trends = [];
        
        // Opción 1: Usar trends.24ht.com.vn o similar
        $trendingSites = [
            'https://trends24.in/' . strtolower($this->config['pais']) . '/',
            'https://getdaytrends.com/' . strtolower($this->config['pais']) . '/'
        ];
        
        foreach ($trendingSites as $site) {
            try {
                $html = $this->fetchPage($site);
                if ($html) {
                    $extracted = $this->extractTrendsFromHTML($html);
                    $trends = array_merge($trends, $extracted);
                }
            } catch (Exception $e) {
                continue;
            }
        }
        
        // Si no hay resultados, usar tendencias genéricas
        if (empty($trends)) {
            $trends = $this->getGenericTrends();
        }
        
        return $trends;
    }
    
    /**
     * Obtener página web
     */
    private function fetchPage($url) {
        $options = [
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept: text/html,application/xhtml+xml',
                    'Accept-Language: ' . $this->config['idioma']
                ],
                'timeout' => 10
            ]
        ];
        
        $context = stream_context_create($options);
        $content = @file_get_contents($url, false, $context);
        
        return $content;
    }
    
    /**
     * Extraer tendencias del HTML
     */
    private function extractTrendsFromHTML($html) {
        $trends = [];
        
        // Buscar hashtags o trending topics
        preg_match_all('/#([a-zA-Z0-9áéíóúñÁÉÍÓÚÑ_]+)/u', $html, $matches);
        
        if (!empty($matches[1])) {
            foreach (array_unique($matches[1]) as $hashtag) {
                if (strlen($hashtag) > 3) {
                    $trends[] = [
                        'keyword' => '#' . $hashtag,
                        'volumen' => rand(1000, 10000),
                        'categoria' => $this->guessCategory($hashtag),
                        'competencia' => 'media',
                        'pais' => $this->config['pais'],
                        'idioma' => $this->config['idioma'],
                        'data_adicional' => [
                            'source' => 'web_scraping',
                            'type' => 'hashtag'
                        ]
                    ];
                }
            }
        }
        
        // Limitar resultados
        return array_slice($trends, 0, 10);
    }
    
    /**
     * Obtener tendencias genéricas
     */
    private function getGenericTrends() {
        // Tendencias genéricas basadas en eventos actuales
        $genericTrends = [
            [
                'keyword' => 'IA 2025',
                'volumen' => 15000,
                'categoria' => 'tecnologia',
                'competencia' => 'alta'
            ],
            [
                'keyword' => 'recetas saludables',
                'volumen' => 8000,
                'categoria' => 'lifestyle',
                'competencia' => 'media'
            ],
            [
                'keyword' => 'trucos caseros',
                'volumen' => 12000,
                'categoria' => 'lifestyle',
                'competencia' => 'baja'
            ],
            [
                'keyword' => 'datos curiosos animales',
                'volumen' => 10000,
                'categoria' => 'educacion',
                'competencia' => 'baja'
            ],
            [
                'keyword' => 'misterios sin resolver',
                'volumen' => 20000,
                'categoria' => 'misterios',
                'competencia' => 'media'
            ]
        ];
        
        $trends = [];
        foreach ($genericTrends as $trend) {
            $trend['pais'] = $this->config['pais'];
            $trend['idioma'] = $this->config['idioma'];
            $trend['data_adicional'] = ['source' => 'generic'];
            $trends[] = $trend;
        }
        
        return $trends;
    }
    
    /**
     * Adivinar categoría
     */
    private function guessCategory($keyword) {
        $keyword = mb_strtolower($keyword, 'UTF-8');
        
        $categorias = [
            'tecnologia' => ['tech', 'ia', 'ai', 'app', 'crypto', 'bitcoin', 'programacion'],
            'entretenimiento' => ['cine', 'serie', 'musica', 'concierto', 'festival'],
            'deportes' => ['futbol', 'nba', 'tenis', 'mundial', 'liga'],
            'viral' => ['challenge', 'meme', 'viral', 'trend'],
            'noticias' => ['breaking', 'urgente', 'noticia', 'politica'],
            'lifestyle' => ['receta', 'moda', 'belleza', 'salud', 'fitness']
        ];
        
        foreach ($categorias as $categoria => $palabras) {
            foreach ($palabras as $palabra) {
                if (stripos($keyword, $palabra) !== false) {
                    return $categoria;
                }
            }
        }
        
        return 'general';
    }
    
    /**
     * Método para cuando tengamos API de Twitter
     */
    public function fetchTrendsWithAPI() {
        // Placeholder para futura implementación con API
        // Requiere Bearer Token y autenticación OAuth 2.0
        
        $trends = [];
        
        /*
        $bearerToken = $this->getBearerToken();
        
        $params = [
            'id' => $this->getWOEID($this->config['pais'])
        ];
        
        $url = 'https://api.twitter.com/2/trends/place?' . http_build_query($params);
        
        $options = [
            'http' => [
                'method' => 'GET',
                'header' => [
                    'Authorization: Bearer ' . $bearerToken,
                    'Accept: application/json'
                ],
                'timeout' => 10
            ]
        ];
        
        // Hacer petición y procesar resultados
        */
        
        return $trends;
    }
    
    /**
     * Obtener WOEID del país
     */
    private function getWOEID($countryCode) {
        $woeids = [
            'PE' => 23424919,  // Perú
            'MX' => 23424900,  // México
            'ES' => 23424950,  // España
            'AR' => 23424747,  // Argentina
            'CO' => 23424787,  // Colombia
            'CL' => 23424782,  // Chile
            'US' => 23424977   // Estados Unidos
        ];
        
        return $woeids[strtoupper($countryCode)] ?? 1; // 1 = Mundial
    }
}
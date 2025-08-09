<?php
/**
 * Fuente de tendencias: Google Trends
 * Usa scraping básico ya que no hay API oficial gratuita
 */
class GoogleTrends {
    private $config;
    private $baseUrl = 'https://trends.google.com/trends/trendingsearches/daily/rss';
    
    public function __construct($config) {
        $this->config = $config;
    }
    
    /**
     * Obtener tendencias
     */
    public function fetchTrends() {
        $trends = [];
        
        try {
            // URL con parámetro de país
            $url = $this->baseUrl . '?geo=' . strtoupper($this->config['pais']);
            
            // Obtener contenido RSS
            $xml = $this->fetchRSS($url);
            
            if ($xml) {
                $trends = $this->parseRSS($xml);
            }
            
        } catch (Exception $e) {
            throw new Exception("Error obteniendo Google Trends: " . $e->getMessage());
        }
        
        return $trends;
    }
    
    /**
     * Obtener RSS
     */
    private function fetchRSS($url) {
        $options = [
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept: application/rss+xml, application/xml',
                    'Accept-Language: ' . $this->config['idioma'] . '-' . strtoupper($this->config['pais'])
                ],
                'timeout' => 10
            ]
        ];
        
        $context = stream_context_create($options);
        $content = @file_get_contents($url, false, $context);
        
        if ($content === false) {
            throw new Exception("No se pudo obtener el RSS de Google Trends");
        }
        
        return simplexml_load_string($content);
    }
    
    /**
     * Parsear RSS
     */
    private function parseRSS($xml) {
        $trends = [];
        
        if (isset($xml->channel->item)) {
            foreach ($xml->channel->item as $item) {
                $trend = [
                    'keyword' => (string) $item->title,
                    'volumen' => $this->extractVolume($item),
                    'categoria' => $this->guessCategory((string) $item->title),
                    'competencia' => 'media', // Google Trends no proporciona competencia
                    'pais' => $this->config['pais'],
                    'idioma' => $this->config['idioma'],
                    'data_adicional' => [
                        'link' => (string) $item->link,
                        'pubDate' => (string) $item->pubDate,
                        'description' => (string) $item->description
                    ]
                ];
                
                $trends[] = $trend;
            }
        }
        
        return $trends;
    }
    
    /**
     * Extraer volumen aproximado
     */
    private function extractVolume($item) {
        // Google Trends RSS incluye tráfico aproximado en la descripción
        $description = (string) $item->description;
        
        if (preg_match('/(\d+)[kKmM]?\+?\s*(searches|búsquedas)/i', $description, $matches)) {
            $volume = intval($matches[1]);
            
            // Convertir K o M a número
            if (stripos($matches[1], 'k') !== false) {
                $volume *= 1000;
            } elseif (stripos($matches[1], 'm') !== false) {
                $volume *= 1000000;
            }
            
            return $volume;
        }
        
        // Valor por defecto si no se encuentra
        return 5000;
    }
    
    /**
     * Adivinar categoría basada en keywords
     */
    private function guessCategory($keyword) {
        $keyword = mb_strtolower($keyword, 'UTF-8');
        
        $categorias = [
            'tecnologia' => ['tech', 'app', 'iphone', 'android', 'google', 'microsoft', 'ia', 'ai', 'robot'],
            'entretenimiento' => ['película', 'serie', 'netflix', 'disney', 'música', 'cantante', 'actor', 'actriz'],
            'deportes' => ['fútbol', 'futbol', 'nba', 'tenis', 'mundial', 'copa', 'liga', 'equipo'],
            'ciencia' => ['nasa', 'espacio', 'planeta', 'descubrimiento', 'estudio', 'investigación'],
            'viral' => ['challenge', 'trend', 'viral', 'meme', 'tiktok', 'instagram'],
            'noticias' => ['presidente', 'ministro', 'política', 'elecciones', 'gobierno', 'ley'],
            'misterios' => ['ovni', 'ufo', 'misterio', 'enigma', 'paranormal', 'extraño']
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
     * Método alternativo usando web scraping (backup)
     */
    public function fetchTrendsAlternative() {
        $trends = [];
        
        try {
            // URL de tendencias diarias
            $url = 'https://trends.google.com/trends/trendingsearches/daily?geo=' . strtoupper($this->config['pais']);
            
            $options = [
                'http' => [
                    'method' => 'GET',
                    'header' => [
                        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                        'Accept: text/html,application/xhtml+xml',
                        'Accept-Language: ' . $this->config['idioma'] . '-' . strtoupper($this->config['pais'])
                    ],
                    'timeout' => 10
                ]
            ];
            
            $context = stream_context_create($options);
            $html = @file_get_contents($url, false, $context);
            
            if ($html) {
                // Buscar JSON embebido en la página
                if (preg_match('/window\.trendsData\s*=\s*({.+?});/s', $html, $matches)) {
                    $data = json_decode($matches[1], true);
                    // Procesar datos si se encuentran
                    // Este es un método de respaldo, implementar según estructura actual
                }
            }
            
        } catch (Exception $e) {
            // Silenciar error del método alternativo
        }
        
        return $trends;
    }
}
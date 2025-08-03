<?php
// Archivo: /creator/classes/generators/InVideo.php
// Propósito: Clase para crear videos con InVideo API

class InVideo {
    private $apiKey;
    private $apiUrl = 'https://api.invideo.io/v2';
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->apiKey = $this->getApiKey();
    }
    
    /**
     * Obtener API Key desde la base de datos
     */
    private function getApiKey() {
        $result = $this->db->query("SELECT valor FROM configuracion WHERE clave = 'invideo_api_key'")->fetch();
        
        if (!$result || empty($result['valor'])) {
            throw new Exception('InVideo API Key no configurada');
        }
        
        // Desencriptar el API Key
        return $this->decrypt($result['valor']);
    }
    
    /**
     * Método para desencriptar
     */
    private function decrypt($data) {
        if (empty($data)) return '';
        
        $key = ENCRYPTION_KEY;
        $list = explode('::', base64_decode($data), 2);
        
        if (count($list) !== 2) {
            return $data;
        }
        
        list($encrypted_data, $iv) = $list;
        
        return openssl_decrypt($encrypted_data, ENCRYPTION_METHOD, $key, 0, $iv);
    }
    
    /**
     * Crear video a partir de un guion
     */
    public function createVideo($script, $options = []) {
        $endpoint = $this->apiUrl . '/videos';
        
        // Configuración por defecto
        $defaultOptions = [
            'template_id' => $this->getTemplateId($options['template'] ?? 'modern'),
            'duration' => $options['duration'] ?? 60,
            'resolution' => $options['resolution'] ?? '1080p',
            'language' => $options['language'] ?? 'es',
            'voice' => $options['voice'] ?? 'es-ES-Standard-A',
            'music' => $options['music'] ?? 'upbeat'
        ];
        
        $options = array_merge($defaultOptions, $options);
        
        // Preparar datos para la API
        $data = [
            'template_id' => $options['template_id'],
            'title' => $options['title'] ?? 'Video generado automáticamente',
            'story' => $this->prepareStory($script, $options),
            'media_type' => 'video',
            'duration' => $options['duration'],
            'resolution' => $options['resolution'],
            'settings' => [
                'voice_over' => [
                    'enabled' => true,
                    'voice' => $options['voice'],
                    'speed' => 1.0
                ],
                'music' => [
                    'enabled' => true,
                    'track' => $options['music'],
                    'volume' => 0.3
                ],
                'transitions' => true,
                'captions' => true
            ]
        ];
        
        // Hacer la petición
        $response = $this->makeRequest('POST', $endpoint, $data);
        
        if (isset($response['video_id'])) {
            // Esperar a que el video esté listo
            return $this->waitForVideo($response['video_id']);
        }
        
        throw new Exception('Error al crear video con InVideo');
    }
    
    /**
     * Preparar el guion para InVideo
     */
    private function prepareStory($script, $options) {
        $story = [];
        
        // Si el script es un array (de OpenAI)
        if (is_array($script)) {
            if (isset($script['hook'])) {
                $story[] = [
                    'text' => $script['hook'],
                    'duration' => 3,
                    'media' => 'relevant'
                ];
            }
            
            if (isset($script['desarrollo']) && is_array($script['desarrollo'])) {
                foreach ($script['desarrollo'] as $punto) {
                    $story[] = [
                        'text' => $punto,
                        'duration' => 5,
                        'media' => 'relevant'
                    ];
                }
            }
            
            if (isset($script['climax'])) {
                $story[] = [
                    'text' => $script['climax'],
                    'duration' => 5,
                    'media' => 'relevant'
                ];
            }
            
            if (isset($script['call_to_action'])) {
                $story[] = [
                    'text' => $script['call_to_action'],
                    'duration' => 3,
                    'media' => 'cta'
                ];
            }
        } else {
            // Si es texto plano, dividir por párrafos
            $paragraphs = explode("\n", $script);
            foreach ($paragraphs as $paragraph) {
                if (trim($paragraph)) {
                    $story[] = [
                        'text' => trim($paragraph),
                        'duration' => 5,
                        'media' => 'relevant'
                    ];
                }
            }
        }
        
        return $story;
    }
    
    /**
     * Obtener ID de template según el tipo
     */
    private function getTemplateId($templateName) {
        $templates = [
            'modern' => 'tmp_modern_001',
            'minimal' => 'tmp_minimal_001',
            'dynamic' => 'tmp_dynamic_001',
            'elegant' => 'tmp_elegant_001',
            'viral' => 'tmp_viral_001'
        ];
        
        return $templates[$templateName] ?? $templates['modern'];
    }
    
    /**
     * Esperar a que el video esté listo
     */
    private function waitForVideo($videoId) {
        $maxAttempts = 60; // Máximo 10 minutos
        $attempt = 0;
        
        while ($attempt < $maxAttempts) {
            sleep(10); // Esperar 10 segundos entre intentos
            
            $status = $this->checkVideoStatus($videoId);
            
            if ($status['status'] === 'completed') {
                return [
                    'success' => true,
                    'video_id' => $videoId,
                    'download_url' => $status['download_url'],
                    'preview_url' => $status['preview_url'],
                    'duration' => $status['duration']
                ];
            } elseif ($status['status'] === 'failed') {
                throw new Exception('Error al generar video: ' . ($status['error'] ?? 'Error desconocido'));
            }
            
            $attempt++;
        }
        
        throw new Exception('Timeout esperando la generación del video');
    }
    
    /**
     * Verificar estado del video
     */
    private function checkVideoStatus($videoId) {
        $endpoint = $this->apiUrl . '/videos/' . $videoId . '/status';
        
        $response = $this->makeRequest('GET', $endpoint);
        
        return $response;
    }
    
    /**
     * Obtener lista de templates disponibles
     */
    public function getTemplates() {
        $endpoint = $this->apiUrl . '/templates';
        
        return $this->makeRequest('GET', $endpoint);
    }
    
    /**
     * Obtener lista de voces disponibles
     */
    public function getVoices($language = 'es') {
        $endpoint = $this->apiUrl . '/voices?language=' . $language;
        
        return $this->makeRequest('GET', $endpoint);
    }
    
    /**
     * Hacer petición HTTP a la API
     */
    private function makeRequest($method, $endpoint, $data = null) {
        $ch = curl_init();
        
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('Error CURL: ' . $error);
        }
        
        $responseData = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $errorMessage = $responseData['message'] ?? $responseData['error'] ?? 'Error desconocido';
            throw new Exception('Error InVideo API (' . $httpCode . '): ' . $errorMessage);
        }
        
        return $responseData;
    }
    
    /**
     * Test de conexión
     */
    public function testConnection() {
        try {
            // Intentar obtener templates como prueba
            $templates = $this->getTemplates();
            
            if (is_array($templates)) {
                return [
                    'success' => true,
                    'message' => 'Conexión exitosa. ' . count($templates) . ' templates disponibles.'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Respuesta inesperada de la API'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Descargar video a servidor local
     */
    public function downloadVideo($downloadUrl, $localPath) {
        $ch = curl_init($downloadUrl);
        $fp = fopen($localPath, 'wb');
        
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        
        $result = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        fclose($fp);
        
        if ($error) {
            unlink($localPath);
            throw new Exception('Error al descargar video: ' . $error);
        }
        
        return $result;
    }
}
?>
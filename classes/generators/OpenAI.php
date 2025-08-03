<?php
// Archivo: /creator/classes/generators/OpenAI.php
// Propósito: Clase para interactuar con la API de ChatGPT

class OpenAI
{
    private $apiKey;
    private $apiUrl = 'https://api.openai.com/v1';
    private $model = 'gpt-3.5-turbo';
    private $maxTokens = 2000;

    public function __construct()
    {
        $this->apiKey = $this->getApiKey();
    }

    /**
     * Obtener API Key desde la base de datos
     */
    private function getApiKey()
    {
        $db = Database::getInstance();
        $result = $db->query("SELECT valor FROM configuracion WHERE clave = 'openai_api_key'")->fetch();

        if (!$result || empty($result['valor'])) {
            throw new Exception('OpenAI API Key no configurada');
        }

        // IMPORTANTE: Desencriptar el API Key
        $encryptedKey = $result['valor'];
        $decryptedKey = $this->decrypt($encryptedKey);

        if (empty($decryptedKey)) {
            throw new Exception('Error al desencriptar OpenAI API Key');
        }

        return $decryptedKey;
    }
    /**
     * Método para desencriptar
     */
    private function decrypt($data)
    {
        if (empty($data)) return '';

        $key = ENCRYPTION_KEY;
        $list = explode('::', base64_decode($data), 2);

        if (count($list) !== 2) {
            return $data; // Devolver tal cual si no está encriptado
        }

        list($encrypted_data, $iv) = $list;

        return openssl_decrypt($encrypted_data, ENCRYPTION_METHOD, $key, 0, $iv);
    }

    /**
     * Generar contenido usando ChatGPT
     */
    public function generateContent($prompt, $temperature = 0.8)
    {
        $endpoint = $this->apiUrl . '/chat/completions';

        $data = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Eres un experto en crear contenido viral para redes sociales. Creas guiones atractivos, con ganchos poderosos y finales que enganchan.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => $temperature,
            'max_tokens' => $this->maxTokens
        ];

        $response = $this->makeRequest($endpoint, $data);

        if (isset($response['choices'][0]['message']['content'])) {
            return $response['choices'][0]['message']['content'];
        }

        throw new Exception('Error al generar contenido con OpenAI');
    }

    /**
     * Generar guion para video
     */
    public function generateVideoScript($topic, $duration = 60, $type = 'short')
    {
        $durationText = $duration <= 60 ? "$duration segundos" : round($duration / 60, 1) . " minutos";

        $prompt = "Crea un guion para un video viral de $durationText sobre: $topic\n\n";
        $prompt .= "Estructura requerida:\n";

        if ($type == 'short') {
            $prompt .= "1. Hook (0-3 segundos): Una frase o pregunta impactante que capture la atención inmediatamente\n";
            $prompt .= "2. Desarrollo (3-" . ($duration - 15) . " segundos): Información fascinante, datos curiosos o historia\n";
            $prompt .= "3. Climax (" . ($duration - 15) . "-" . ($duration - 5) . " segundos): El punto más interesante o revelador\n";
            $prompt .= "4. Call to Action (" . ($duration - 5) . "-$duration segundos): Invitación a seguir, comentar o compartir\n\n";
        } else {
            $prompt .= "1. Introducción (0-20 segundos): Presentación del tema y lo que aprenderán\n";
            $prompt .= "2. Desarrollo (20 segundos-" . ($duration - 40) . " segundos): Contenido principal dividido en puntos claros\n";
            $prompt .= "3. Conclusión (" . ($duration - 40) . "-" . ($duration - 20) . " segundos): Resumen de puntos clave\n";
            $prompt .= "4. Call to Action (" . ($duration - 20) . "-$duration segundos): Qué hacer después\n\n";
        }

        $prompt .= "Formato de respuesta en JSON con esta estructura:\n";
        $prompt .= '{"hook": "...", "desarrollo": ["punto1", "punto2", ...], "climax": "...", "call_to_action": "...", "hashtags": ["#hashtag1", "#hashtag2", ...]}';

        $content = $this->generateContent($prompt, 0.7);

        // Intentar parsear como JSON
        $json = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }

        // Si no es JSON válido, devolver como texto
        return ['script_text' => $content];
    }

    /**
     * Generar título para video
     */
    public function generateTitle($topic, $variations = 3)
    {
        $prompt = "Genera $variations títulos virales y atractivos para un video sobre: $topic\n\n";
        $prompt .= "Requisitos:\n";
        $prompt .= "- Máximo 60 caracteres\n";
        $prompt .= "- Debe generar curiosidad\n";
        $prompt .= "- Usar palabras poderosas\n";
        $prompt .= "- Puede incluir emojis\n";
        $prompt .= "- Formato: Un título por línea";

        $content = $this->generateContent($prompt, 0.9);
        $titles = explode("\n", trim($content));

        // Limpiar y filtrar títulos
        $titles = array_filter(array_map('trim', $titles));
        $titles = array_slice($titles, 0, $variations);

        return $titles;
    }

    /**
     * Generar descripción para video
     */
    public function generateDescription($topic, $title)
    {
        $prompt = "Genera una descripción optimizada para SEO para un video titulado: '$title' sobre el tema: $topic\n\n";
        $prompt .= "Requisitos:\n";
        $prompt .= "- Entre 150-300 caracteres\n";
        $prompt .= "- Incluir palabras clave relevantes\n";
        $prompt .= "- Generar intriga sin revelar todo\n";
        $prompt .= "- Terminar con una pregunta o llamada a la acción";

        return $this->generateContent($prompt, 0.7);
    }

    /**
     * Generar hashtags
     */
    public function generateHashtags($topic, $count = 30)
    {
        $prompt = "Genera exactamente $count hashtags relevantes y virales para un video sobre: $topic\n\n";
        $prompt .= "Requisitos:\n";
        $prompt .= "- Mezclar hashtags populares y específicos\n";
        $prompt .= "- Incluir hashtags en español\n";
        $prompt .= "- Algunos hashtags de tendencia general\n";
        $prompt .= "- Formato: #hashtag1 #hashtag2 (separados por espacios)";

        $content = $this->generateContent($prompt, 0.8);

        // Extraer hashtags
        preg_match_all('/#\w+/u', $content, $matches);
        $hashtags = array_unique($matches[0]);

        return array_slice($hashtags, 0, $count);
    }

    /**
     * Analizar tendencia y sugerir contenido
     */
    public function analyzeTrend($trend)
    {
        $prompt = "Analiza esta tendencia: '$trend' y sugiere:\n";
        $prompt .= "1. Por qué es viral\n";
        $prompt .= "2. Ángulo único para abordarla\n";
        $prompt .= "3. Tipo de contenido que funcionaría mejor\n";
        $prompt .= "4. Audiencia objetivo\n";
        $prompt .= "5. Mejor momento para publicar\n\n";
        $prompt .= "Responde de forma concisa y práctica.";

        return $this->generateContent($prompt, 0.7);
    }

    /**
     * Hacer petición a la API
     */
    private function makeRequest($endpoint, $data)
    {
        $ch = curl_init($endpoint);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ],
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception('Error CURL: ' . $error);
        }

        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMessage = $errorData['error']['message'] ?? 'Error desconocido';
            throw new Exception('Error OpenAI API: ' . $errorMessage);
        }

        return json_decode($response, true);
    }

    /**
     * Verificar si la API está funcionando
     */
    public function testConnection()
    {
        try {
            $response = $this->generateContent('Di "Hola, la API funciona"', 0.1);
            return ['success' => true, 'message' => $response];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

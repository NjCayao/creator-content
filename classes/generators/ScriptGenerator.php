<?php
/**
 * Generador de guiones para videos
 */
class ScriptGenerator {
    private $openai;
    private $templates;
    
    public function __construct($openai) {
        $this->openai = $openai;
        $this->loadTemplates();
    }
    
    /**
     * Cargar templates de guiones
     */
    private function loadTemplates() {
        $this->templates = [
            'datos_curiosos' => [
                'estructura' => [
                    'hook' => 'Pregunta o dato impactante',
                    'desarrollo' => '3-5 datos fascinantes',
                    'climax' => 'El dato más sorprendente',
                    'cta' => 'Invitación a seguir para más'
                ],
                'estilo' => 'informativo y asombroso'
            ],
            'historia' => [
                'estructura' => [
                    'hook' => 'Inicio intrigante',
                    'desarrollo' => 'Desarrollo de la historia',
                    'climax' => 'Momento culminante',
                    'cta' => 'Reflexión o pregunta final'
                ],
                'estilo' => 'narrativo y emocional'
            ],
            'tutorial' => [
                'estructura' => [
                    'hook' => 'Problema o necesidad',
                    'desarrollo' => 'Pasos claros',
                    'climax' => 'Resultado final',
                    'cta' => 'Más trucos en el perfil'
                ],
                'estilo' => 'práctico y directo'
            ],
            'misterio' => [
                'estructura' => [
                    'hook' => 'Pregunta misteriosa',
                    'desarrollo' => 'Pistas y teorías',
                    'climax' => 'Revelación impactante',
                    'cta' => '¿Qué piensas tú?'
                ],
                'estilo' => 'intrigante y misterioso'
            ]
        ];
    }
    
    /**
     * Generar guion
     */
    public function generate($topic, $duration = 60, $type = 'short') {
        // Detectar categoría del tema
        $category = $this->detectCategory($topic);
        $template = $this->templates[$category] ?? $this->templates['datos_curiosos'];
        
        // Generar guion con OpenAI
        $script = $this->openai->generateVideoScript($topic, $duration, $type);
        
        // Si el script no tiene la estructura esperada, reformatearlo
        if (!isset($script['hook'])) {
            $script = $this->parseScriptText($script);
        }
        
        // Optimizar para la duración
        $script = $this->optimizeForDuration($script, $duration);
        
        // Agregar metadata
        $script['metadata'] = [
            'categoria' => $category,
            'duracion' => $duration,
            'tipo' => $type,
            'fecha_generacion' => date('Y-m-d H:i:s')
        ];
        
        return $script;
    }
    
    /**
     * Generar múltiples variaciones
     */
    public function generateVariations($topic, $count = 3, $duration = 60) {
        $variations = [];
        
        for ($i = 0; $i < $count; $i++) {
            $variations[] = $this->generate($topic, $duration);
            // Pequeña pausa para evitar rate limiting
            usleep(500000); // 0.5 segundos
        }
        
        return $variations;
    }
    
    /**
     * Detectar categoría del tema
     */
    private function detectCategory($topic) {
        $topic_lower = mb_strtolower($topic);
        
        $keywords = [
            'datos_curiosos' => ['dato', 'curiosidad', 'sabías', 'increíble', 'sorprendente'],
            'historia' => ['historia', 'cuento', 'relato', 'sucedió', 'pasó'],
            'tutorial' => ['cómo', 'tutorial', 'paso', 'hacer', 'trucos'],
            'misterio' => ['misterio', 'enigma', 'inexplicable', 'paranormal', 'extraño']
        ];
        
        foreach ($keywords as $category => $words) {
            foreach ($words as $word) {
                if (strpos($topic_lower, $word) !== false) {
                    return $category;
                }
            }
        }
        
        return 'datos_curiosos'; // Por defecto
    }
    
    /**
     * Parsear texto de guion
     */
    private function parseScriptText($scriptData) {
        if (is_array($scriptData) && isset($scriptData['script_text'])) {
            $text = $scriptData['script_text'];
        } else {
            $text = is_string($scriptData) ? $scriptData : '';
        }
        
        // Intentar dividir en secciones
        $lines = explode("\n", $text);
        $script = [
            'hook' => '',
            'desarrollo' => [],
            'climax' => '',
            'call_to_action' => ''
        ];
        
        // Lógica simple para dividir el texto
        if (count($lines) >= 4) {
            $script['hook'] = $lines[0];
            $script['desarrollo'] = array_slice($lines, 1, -2);
            $script['climax'] = $lines[count($lines) - 2];
            $script['call_to_action'] = $lines[count($lines) - 1];
        } else {
            $script['hook'] = $text;
            $script['desarrollo'] = [$text];
        }
        
        return $script;
    }
    
    /**
     * Optimizar guion para duración
     */
    private function optimizeForDuration($script, $duration) {
        // Calcular tiempo aproximado por sección
        $timings = [
            'hook' => min(5, $duration * 0.1),
            'desarrollo' => $duration * 0.6,
            'climax' => $duration * 0.2,
            'cta' => $duration * 0.1
        ];
        
        // Ajustar cantidad de contenido según duración
        if ($duration <= 30) {
            // Video muy corto: solo lo esencial
            if (isset($script['desarrollo']) && count($script['desarrollo']) > 2) {
                $script['desarrollo'] = array_slice($script['desarrollo'], 0, 2);
            }
        } elseif ($duration <= 60) {
            // Short estándar
            if (isset($script['desarrollo']) && count($script['desarrollo']) > 4) {
                $script['desarrollo'] = array_slice($script['desarrollo'], 0, 4);
            }
        }
        
        // Agregar información de timing
        $script['timings'] = $timings;
        
        return $script;
    }
    
    /**
     * Validar guion
     */
    public function validate($script) {
        $errors = [];
        
        // Verificar estructura básica
        if (!isset($script['hook']) || empty($script['hook'])) {
            $errors[] = 'Falta el hook inicial';
        }
        
        if (!isset($script['desarrollo']) || !is_array($script['desarrollo']) || empty($script['desarrollo'])) {
            $errors[] = 'Falta el desarrollo del contenido';
        }
        
        if (!isset($script['call_to_action']) || empty($script['call_to_action'])) {
            $errors[] = 'Falta el call to action';
        }
        
        // Verificar longitud
        if (isset($script['hook']) && strlen($script['hook']) > 200) {
            $errors[] = 'Hook demasiado largo';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Mejorar guion existente
     */
    public function improve($script, $feedback = '') {
        $prompt = "Mejora este guion de video haciéndolo más viral y atractivo:\n\n";
        $prompt .= "Hook actual: " . ($script['hook'] ?? '') . "\n";
        $prompt .= "Desarrollo: " . implode("\n", $script['desarrollo'] ?? []) . "\n";
        
        if ($feedback) {
            $prompt .= "\nFeedback específico: $feedback\n";
        }
        
        $prompt .= "\nMantén la misma estructura pero hazlo más impactante, emocional y viral.";
        
        $improved = $this->openai->generateContent($prompt);
        
        return $this->parseScriptText($improved);
    }
}
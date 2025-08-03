<?php
// Archivo: /creator/admin/includes/functions.php
// Propósito: Funciones helper para el admin

/**
 * Formatear número con separadores de miles
 */
function formatNumber($number) {
    return number_format($number, 0, ',', '.');
}

/**
 * Formatear número como dinero
 */
function formatMoney($amount, $symbol = '$') {
    return $symbol . number_format($amount, 2, ',', '.');
}

/**
 * Formatear fecha en español
 */
function formatDate($date, $format = 'd/m/Y H:i') {
    if (empty($date)) return '-';
    
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date($format, $timestamp);
}

/**
 * Formatear fecha relativa (hace X tiempo)
 */
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'hace ' . $diff . ' segundos';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return 'hace ' . $mins . ' minuto' . ($mins > 1 ? 's' : '');
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return 'hace ' . $hours . ' hora' . ($hours > 1 ? 's' : '');
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return 'hace ' . $days . ' día' . ($days > 1 ? 's' : '');
    } else {
        return formatDate($datetime, 'd/m/Y');
    }
}

/**
 * Obtener icono para plataforma
 */
function getPlatformIcon($platform) {
    $icons = [
        'youtube' => 'fab fa-youtube text-danger',
        'tiktok' => 'fab fa-tiktok',
        'instagram' => 'fab fa-instagram text-purple',
        'facebook' => 'fab fa-facebook text-primary',
        'twitter' => 'fab fa-twitter text-info'
    ];
    
    return $icons[$platform] ?? 'fas fa-globe';
}

/**
 * Obtener color de badge según estado
 */
function getStatusBadge($status) {
    $badges = [
        'ideacion' => '<span class="badge badge-secondary">Ideación</span>',
        'generando' => '<span class="badge badge-info">Generando</span>',
        'revision' => '<span class="badge badge-warning">Revisión</span>',
        'aprobado' => '<span class="badge badge-success">Aprobado</span>',
        'publicado' => '<span class="badge badge-primary">Publicado</span>',
        'rechazado' => '<span class="badge badge-danger">Rechazado</span>',
        'error' => '<span class="badge badge-dark">Error</span>'
    ];
    
    return $badges[$status] ?? '<span class="badge badge-secondary">' . $status . '</span>';
}

/**
 * Truncar texto
 */
function truncateText($text, $length = 50, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length) . $suffix;
}

/**
 * Generar URL de video preview
 */
function getVideoPreviewUrl($videoUrl) {
    // Si es URL de YouTube, extraer ID y generar thumbnail
    if (strpos($videoUrl, 'youtube.com') !== false || strpos($videoUrl, 'youtu.be') !== false) {
        preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?#]+)/', $videoUrl, $matches);
        if (isset($matches[1])) {
            return 'https://img.youtube.com/vi/' . $matches[1] . '/maxresdefault.jpg';
        }
    }
    
    // Para otros casos, devolver URL por defecto o la misma URL
    return $videoUrl;
}

/**
 * Calcular porcentaje
 */
function calculatePercentage($value, $total, $decimals = 1) {
    if ($total == 0) return 0;
    return round(($value / $total) * 100, $decimals);
}

/**
 * Generar mensaje de alerta
 */
function showAlert($message, $type = 'info', $dismissible = true) {
    $icon = [
        'success' => 'check-circle',
        'danger' => 'exclamation-circle',
        'warning' => 'exclamation-triangle',
        'info' => 'info-circle'
    ];
    
    $html = '<div class="alert alert-' . $type . ($dismissible ? ' alert-dismissible' : '') . ' fade show" role="alert">';
    $html .= '<i class="fas fa-' . ($icon[$type] ?? 'info-circle') . '"></i> ';
    $html .= $message;
    
    if ($dismissible) {
        $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Verificar si una configuración está activa
 */
function isConfigActive($key) {
    global $db;
    
    $result = $db->query("SELECT valor FROM configuracion WHERE clave = ?", [$key])->fetch();
    return $result && ($result['valor'] == '1' || $result['valor'] == 'true');
}

/**
 * Obtener valor de configuración
 */
function getConfig($key, $default = '') {
    global $db;
    
    $result = $db->query("SELECT valor FROM configuracion WHERE clave = ?", [$key])->fetch();
    return $result ? $result['valor'] : $default;
}
?>
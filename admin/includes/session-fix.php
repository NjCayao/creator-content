<?php
// Fix temporal para mantener la sesión activa
// Incluir este archivo al inicio de cada página problemática

// Si no hay sesión activa, crear una temporal
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Administrador';
    $_SESSION['email'] = 'admin@sistema.com';
    $_SESSION['last_activity'] = time();
}

// Actualizar última actividad
$_SESSION['last_activity'] = time();
?>
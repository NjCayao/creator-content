<?php
// Archivo: /creator/public/index.php
// Propósito: Redireccionar al login o al admin según el estado de sesión

session_start();

// Si ya está logueado, ir al admin
if (isset($_SESSION['user_id'])) {
    header('Location: ../admin/');
    exit;
}

// Si no, ir al login
header('Location: login.php');
exit;
?>
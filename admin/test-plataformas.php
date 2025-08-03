<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h3>Test de Plataformas</h3>";

// Test 1: Verificar includes
echo "1. Verificando includes...<br>";
require_once 'includes/header.php';
echo "✓ Header cargado<br>";

require_once 'includes/functions.php';
echo "✓ Functions cargado<br>";

// Test 2: Verificar base de datos
echo "<br>2. Verificando base de datos...<br>";
$db = Database::getInstance();
echo "✓ Conexión a BD<br>";

// Test 3: Verificar tabla oauth_tokens
echo "<br>3. Verificando tabla oauth_tokens...<br>";
try {
    $test = $db->query("SELECT COUNT(*) as total FROM oauth_tokens")->fetch();
    echo "✓ Tabla oauth_tokens existe. Registros: " . $test['total'] . "<br>";
} catch (Exception $e) {
    echo "✗ Error con tabla oauth_tokens: " . $e->getMessage() . "<br>";
}

// Test 4: Verificar configuraciones
echo "<br>4. Verificando configuraciones...<br>";
try {
    $test = $db->query("SELECT clave FROM configuracion WHERE clave LIKE '%facebook%' OR clave LIKE '%tiktok%'")->fetchAll();
    echo "✓ Configuraciones encontradas: <br>";
    foreach($test as $config) {
        echo " - " . $config['clave'] . "<br>";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
}

echo "<br><a href='plataformas.php'>Volver a plataformas</a>";
?>
<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'u148394603_teamtalks');
define('DB_USER', 'u148394603_teamtalks');
define('DB_PASS', 'TeamTalks2901879');


// Conexión a la base de datos
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>

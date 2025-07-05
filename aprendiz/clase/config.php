<?php
// Detectar si estás en entorno local (XAMPP) o en producción (Hostinger)
$isLocal = (
    strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || 
    strpos($_SERVER['DOCUMENT_ROOT'], 'htdocs') !== false
);

// Configuración según entorno
if ($isLocal) {
    // Entorno local - XAMPP
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'u148394603_teamtalks'); // el nombre de tu base local
    define('DB_USER', 'root');
    define('DB_PASS', '');
} else {
    // Producción - Hostinger
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'u148394603_teamtalks'); // nombre real en Hostinger
    define('DB_USER', 'u148394603_teamtalks');       // usuario real de Hostinger
    define('DB_PASS', 'TeamTalks2901879');  // contraseña real
}

// Conexión a la base de datos
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

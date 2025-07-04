<?php
// Detectar entorno y cargar rutas
if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['DOCUMENT_ROOT'], 'htdocs') !== false) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/rutas.php';
} else {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/rutas.php';
}

// Cargar clase de conexión
require_once BASE_PATH . '/conexion/conexion.php';
$conexion = new Database();
$conex = $conexion->connect();

// Iniciar sesión si aún no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

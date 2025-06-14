<?php
// Iniciar sesión (si aún no está iniciada)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Detecta si estás en local o producción, y define las rutas base
$local = strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['DOCUMENT_ROOT'], 'htdocs') !== false;
$ruta_rutas = $local 
    ? $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/rutas.php' 
    : $_SERVER['DOCUMENT_ROOT'] . '/conexion/rutas.php';

require_once $ruta_rutas;

// Conexión a la base de datos
require_once BASE_PATH . '/conexion/conexion.php';
$conexion = new database();
$conex = $conexion->connect();

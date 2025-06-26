<?php

// Carga las rutas base (define BASE_PATH y BASE_URL)
require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';

// Conexión a la base de datos
require_once BASE_PATH . '/conexion/conexion.php';
$conexion = new database();
$conex = $conexion->connect();

// Iniciar sesión (si aún no está iniciada)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

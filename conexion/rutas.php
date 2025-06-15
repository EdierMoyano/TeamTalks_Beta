<?php
// Detecta si estás en entorno local o en producción
$isLocal = false;


// Detectar por nombre del host
if (
    strpos($_SERVER['HTTP_HOST'], 'localhost') !== false ||  // localhost
    strpos($_SERVER['DOCUMENT_ROOT'], 'htdocs') !== false    // XAMPP
) {
    $isLocal = true;
}

// Configurar rutas según el entorno
if ($isLocal) {
    // Local: carpeta "teamtalks" dentro de htdocs
    define('BASE_PATH', $_SERVER['DOCUMENT_ROOT'] . '/teamtalks');
    define('BASE_URL', '/teamtalks');
} else {
    // Producción: carpeta raíz ya es public_html
    define('BASE_PATH', $_SERVER['DOCUMENT_ROOT']);
    define('BASE_URL', '');
}

// Agregar carpeta includes al path para poder hacer includes sin rutas relativas
set_include_path(get_include_path() . PATH_SEPARATOR . BASE_PATH . '/includes');

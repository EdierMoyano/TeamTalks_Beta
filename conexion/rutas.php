<?php
// Detectar si estás en local o producción
$isLocal = strpos($_SERVER['HTTP_HOST'], 'localhost') !== false ||
           strpos($_SERVER['DOCUMENT_ROOT'], 'htdocs') !== false;

// Definir rutas base
if ($isLocal) {
    define('BASE_PATH', $_SERVER['DOCUMENT_ROOT'] . '/teamtalks');
    define('BASE_URL', '/teamtalks');
} else {
    define('BASE_PATH', $_SERVER['DOCUMENT_ROOT']);
    define('BASE_URL', '');
}

// Agrega la carpeta /includes al path global para includes sin rutas relativas
set_include_path(get_include_path() . PATH_SEPARATOR . BASE_PATH . '/includes');

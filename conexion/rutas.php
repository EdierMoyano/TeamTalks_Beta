<?php
// Detecta si estás en local (Windows o Linux) o en producción
if (strpos($_SERVER['DOCUMENT_ROOT'], 'htdocs') !== false) {
    // Entorno local con carpeta teamtalks dentro de htdocs
    define('BASE_PATH', $_SERVER['DOCUMENT_ROOT'] . '/teamtalks');
    define('BASE_URL', '/teamtalks');
} else {
    // Producción (hosting) donde teamtalks es la raíz del proyecto
    define('BASE_PATH', $_SERVER['DOCUMENT_ROOT']);
    define('BASE_URL', '');
}

// Agrega carpeta includes para usar include sin ../
set_include_path(get_include_path() . PATH_SEPARATOR . BASE_PATH . '/includes');

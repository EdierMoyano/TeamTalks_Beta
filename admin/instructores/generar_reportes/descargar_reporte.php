<?php
session_start();

// Verificar sesión y rol de administrador
if (!isset($_SESSION['documento']) || $_SESSION['rol'] !== 2) {
    header('Location: ../../../includes/exit.php');
    exit;
}

$archivo = $_GET['archivo'] ?? '';

if (empty($archivo)) {
    die('Archivo no especificado');
}

// Validar que el archivo existe y está en el directorio correcto
$rutaArchivo = __DIR__ . '/' . basename($archivo);

if (!file_exists($rutaArchivo)) {
    die('Archivo no encontrado');
}

// Validar extensión del archivo
$extension = pathinfo($rutaArchivo, PATHINFO_EXTENSION);
if ($extension !== 'xlsx') {
    die('Tipo de archivo no válido');
}

// Configurar headers para descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . basename($archivo) . '"');
header('Content-Length: ' . filesize($rutaArchivo));
header('Cache-Control: must-revalidate');
header('Pragma: public');

// Leer y enviar el archivo
readfile($rutaArchivo);

// Opcional: eliminar el archivo después de la descarga
// unlink($rutaArchivo);

exit;
?>

<?php
if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['DOCUMENT_ROOT'], 'htdocs') !== false) {
  require_once $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php';
} else {
  require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';
}include 'session.php';
if ($_SESSION['rol'] !== 3 && $_SESSION['rol'] !== 5) {
    header('Location:' . BASE_URL . '/includes/exit.php?motivo=acceso-denegado');
    exit;
}

$id = $_POST['id_actividad'] ?? 0;
$num = $_POST['archivo_num'] ?? 0;

if ($id && in_array($num, [1, 2, 3])) {
    $columna = "archivo$num";

    // Obtener archivo actual
    $stmt = $conex->prepare("SELECT $columna FROM actividades WHERE id_actividad = ?");
    $stmt->execute([$id]);
    $archivo = $stmt->fetchColumn();

    if ($archivo) {
        // Eliminar archivo del disco
        $ruta = $_SERVER['DOCUMENT_ROOT'] . "/teamtalks/uploads/$archivo";
        if (file_exists($ruta)) {
            unlink($ruta);
        }

        // Eliminar en BD
        $stmt = $conex->prepare("UPDATE actividades SET $columna = NULL WHERE id_actividad = ?");
        $stmt->execute([$id]);

        echo "ok";
    } else {
        echo "Archivo no encontrado.";
    }
} else {
    echo "Datos inválidos.";
}

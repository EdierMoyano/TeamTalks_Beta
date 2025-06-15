<?php
session_start();
require_once 'functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$id_actividad = $_POST['id_actividad'] ?? null;
$id_usuario = $_POST['id_usuario'] ?? null;

if (!$id_actividad || !$id_usuario) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

try {
    // Verificar si la actividad está vencida
    if (actividadEstaVencida($id_actividad)) {
        echo json_encode(['success' => false, 'message' => 'No se puede cancelar la entrega de una actividad vencida']);
        exit;
    }

    // Verificar si existe una entrega
    $entregaExistente = verificarEntregaExistente($id_actividad, $id_usuario);
    
    if (!$entregaExistente) {
        echo json_encode(['success' => false, 'message' => 'No hay entrega para cancelar']);
        exit;
    }
    
    // Obtener información de la entrega para eliminar archivos
    $entrega = obtenerEntregaUsuario($id_actividad, $id_usuario);
    
    // Eliminar archivos físicos si existen
    if (isset($entrega['archivo']) && $entrega['archivo']) {
        $archivos = json_decode($entrega['archivo'], true);
        if ($archivos && is_array($archivos)) {
            foreach ($archivos as $archivo) {
                $rutaArchivo = 'uploads/entregas/' . $id_actividad . '/' . $id_usuario . '/' . $archivo['nombre_archivo'];
                if (file_exists($rutaArchivo)) {
                    unlink($rutaArchivo);
                }
            }
        }
    }
    
    // Eliminar directorio del usuario si está vacío
    $directorioUsuario = 'uploads/entregas/' . $id_actividad . '/' . $id_usuario . '/';
    if (is_dir($directorioUsuario) && count(scandir($directorioUsuario)) == 2) {
        rmdir($directorioUsuario);
    }
    
    // Eliminar la entrega de la base de datos
    $resultado = eliminarEntregaActividad($id_actividad, $id_usuario);
    
    if ($resultado['success']) {
        echo json_encode([
            'success' => true, 
            'message' => 'Entrega cancelada exitosamente'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => $resultado['message']]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage()]);
}
?>

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
$contenido = $_POST['contenido'] ?? '';

if (!$id_actividad || !$id_usuario) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

try {
    // Verificar si ya existe una entrega para esta actividad y usuario
    $entregaExistente = verificarEntregaExistente($id_actividad, $id_usuario);
    
    if ($entregaExistente) {
        echo json_encode(['success' => false, 'message' => 'Ya has entregado esta actividad']);
        exit;
    }
    
    // Crear directorio para archivos si no existe
    $directorioBase = 'uploads/entregas/';
    $directorioActividad = $directorioBase . $id_actividad . '/';
    $directorioUsuario = $directorioActividad . $id_usuario . '/';
    
    if (!file_exists($directorioUsuario)) {
        mkdir($directorioUsuario, 0755, true);
    }
    
    $archivosSubidos = [];
    
    // Procesar archivos subidos
    if (isset($_FILES['archivos']) && !empty($_FILES['archivos']['name'][0])) {
        $archivos = $_FILES['archivos'];
        $totalArchivos = count($archivos['name']);
        
        for ($i = 0; $i < $totalArchivos; $i++) {
            if ($archivos['error'][$i] === UPLOAD_ERR_OK) {
                $nombreOriginal = $archivos['name'][$i];
                $tipoArchivo = $archivos['type'][$i];
                $tamanoArchivo = $archivos['size'][$i];
                $archivoTemporal = $archivos['tmp_name'][$i];
                
                // Validar tamaño (10MB máximo)
                if ($tamanoArchivo > 10 * 1024 * 1024) {
                    echo json_encode(['success' => false, 'message' => "El archivo {$nombreOriginal} excede el tamaño máximo de 10MB"]);
                    exit;
                }
                
                // Validar extensión
                $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
                $extensionesPermitidas = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png', 'gif', 'zip', 'rar'];
                
                if (!in_array($extension, $extensionesPermitidas)) {
                    echo json_encode(['success' => false, 'message' => "El archivo {$nombreOriginal} tiene una extensión no permitida"]);
                    exit;
                }
                
                // Generar nombre único para el archivo
                $nombreArchivo = time() . '_' . $i . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $nombreOriginal);
                $rutaCompleta = $directorioUsuario . $nombreArchivo;
                
                if (move_uploaded_file($archivoTemporal, $rutaCompleta)) {
                    $archivosSubidos[] = [
                        'nombre_original' => $nombreOriginal,
                        'nombre_archivo' => $nombreArchivo,
                        'ruta' => $rutaCompleta,
                        'tipo' => $tipoArchivo,
                        'tamano' => $tamanoArchivo
                    ];
                }
            }
        }
    }
    
    // Guardar la entrega en la base de datos
    $resultado = guardarEntregaActividad($id_actividad, $id_usuario, $contenido, $archivosSubidos);
    
    if ($resultado['success']) {
        echo json_encode([
            'success' => true, 
            'message' => 'Actividad entregada exitosamente',
            'archivos_subidos' => count($archivosSubidos)
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => $resultado['message']]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage()]);
}
?>

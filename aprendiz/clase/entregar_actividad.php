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

// Debug: Log de datos recibidos
error_log("Datos recibidos - ID Actividad: $id_actividad, ID Usuario: $id_usuario");
error_log("Archivos recibidos: " . (isset($_FILES['archivos']) ? count($_FILES['archivos']['name']) : 0));

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

    // Crear directorio por usuario si no existe
    $directorioBase = 'uploads/';
    $directorioUsuario = $directorioBase . $id_usuario . '/';

    if (!file_exists($directorioUsuario)) {
        mkdir($directorioUsuario, 0755, true);
    }

    $archivosSubidos = [];

    // Procesar archivos subidos (máximo 3) - CORREGIDO
    if (isset($_FILES['archivos']) && !empty($_FILES['archivos']['name'][0])) {
        $archivos = $_FILES['archivos'];
        $totalArchivos = count($archivos['name']);

        error_log("Total de archivos a procesar: $totalArchivos");

        // Limitar a 3 archivos máximo
        $totalArchivos = min($totalArchivos, 3);

        for ($i = 0; $i < $totalArchivos; $i++) {
            if ($archivos['error'][$i] === UPLOAD_ERR_OK) {
                $nombreOriginal = $archivos['name'][$i];
                $tipoArchivo = $archivos['type'][$i];
                $tamanoArchivo = $archivos['size'][$i];
                $archivoTemporal = $archivos['tmp_name'][$i];

                error_log("📎 Procesando archivo $i: $nombreOriginal ($tamanoArchivo bytes)");

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

                // Generar nombre único para evitar conflictos
                $timestamp = time();
                $nombreArchivo = $timestamp . "_actividad{$id_actividad}_" . ($i + 1) . "_" . preg_replace('/[^a-zA-Z0-9._-]/', '', $nombreOriginal);
                $rutaCompleta = $directorioUsuario . $nombreArchivo;

                if (move_uploaded_file($archivoTemporal, $rutaCompleta)) {
                    $archivosSubidos[] = $rutaCompleta;
                    error_log("Archivo guardado: $rutaCompleta");
                } else {
                    error_log("Error al mover archivo: $nombreOriginal");
                    echo json_encode(['success' => false, 'message' => "Error al subir el archivo {$nombreOriginal}"]);
                    exit;
                }
            } else {
                error_log("Error en archivo $i: " . $archivos['error'][$i]);
                echo json_encode(['success' => false, 'message' => "Error en el archivo: " . $archivos['name'][$i]]);
                exit;
            }
        }
    }

    error_log("Total archivos subidos: " . count($archivosSubidos));

    // Guardar la entrega en la base de datos
    $resultado = guardarEntregaActividad($id_actividad, $id_usuario, $contenido, $archivosSubidos);

    if ($resultado['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Actividad entregada exitosamente',
            'archivos_subidos' => count($archivosSubidos)
        ]);
    } else {
        // Si hay error, eliminar archivos subidos
        foreach ($archivosSubidos as $archivo) {
            if (file_exists($archivo)) {
                unlink($archivo);
            }
        }
        echo json_encode(['success' => false, 'message' => $resultado['message']]);
    }
} catch (Exception $e) {
    error_log("Excepción: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage()]);
}

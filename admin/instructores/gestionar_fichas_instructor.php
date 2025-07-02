<?php
session_start();

// Verificar sesión y rol de administrador
if (!isset($_SESSION['documento']) || $_SESSION['rol'] !== 2) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

require_once '../../conexion/conexion.php';

$db = new Database();
$conexion = $db->connect();

if (!$conexion || !($conexion instanceof PDO)) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit;
}

$accion = $_POST['accion'] ?? '';
$id_ficha = $_POST['id_ficha'] ?? '';
$id_instructor_origen = $_POST['id_instructor_origen'] ?? '';

if (empty($accion) || empty($id_ficha) || empty($id_instructor_origen)) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

try {
    $conexion->beginTransaction();

    if ($accion === 'transferir') {
        $id_instructor_destino = $_POST['id_instructor_destino'] ?? '';
        
        if (empty($id_instructor_destino)) {
            throw new Exception('Debe seleccionar un instructor destino');
        }

        // Verificar que ambos instructores existen
        $stmt = $conexion->prepare("SELECT COUNT(*) FROM usuarios WHERE id IN (?, ?) AND id_rol = 3 AND id_estado = 1");
        $stmt->execute([$id_instructor_origen, $id_instructor_destino]);
        
        if ($stmt->fetchColumn() != 2) {
            throw new Exception('Uno o ambos instructores no son válidos');
        }

        // Transferir todas las materias de la ficha
        $stmt = $conexion->prepare("UPDATE materia_ficha SET id_instructor = ? WHERE id_ficha = ? AND id_instructor = ?");
        $result = $stmt->execute([$id_instructor_destino, $id_ficha, $id_instructor_origen]);

        if (!$result) {
            throw new Exception('Error al transferir la ficha');
        }

        $mensaje = "Ficha {$id_ficha} transferida exitosamente";

    } elseif ($accion === 'dejar_administrar') {
        $id_nuevo_instructor = $_POST['id_nuevo_instructor'] ?? '';
        
        if (empty($id_nuevo_instructor)) {
            throw new Exception('Debe seleccionar un nuevo instructor');
        }

        // Verificar que el nuevo instructor existe
        $stmt = $conexion->prepare("SELECT COUNT(*) FROM usuarios WHERE id = ? AND id_rol = 3 AND id_estado = 1");
        $stmt->execute([$id_nuevo_instructor]);
        
        if ($stmt->fetchColumn() == 0) {
            throw new Exception('El nuevo instructor no es válido');
        }

        // Cambiar instructor de todas las materias de la ficha
        $stmt = $conexion->prepare("UPDATE materia_ficha SET id_instructor = ? WHERE id_ficha = ? AND id_instructor = ?");
        $result = $stmt->execute([$id_nuevo_instructor, $id_ficha, $id_instructor_origen]);

        if (!$result) {
            throw new Exception('Error al cambiar el administrador de la ficha');
        }

        $mensaje = "Administración de ficha {$id_ficha} transferida exitosamente";
    } else {
        throw new Exception('Acción no válida');
    }

    $conexion->commit();

    echo json_encode([
        'success' => true,
        'message' => $mensaje
    ]);

} catch (Exception $e) {
    $conexion->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

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

$id_instructor = $_POST['id_instructor'] ?? '';
$nuevo_estado = $_POST['nuevo_estado'] ?? '';

if (empty($id_instructor) || empty($nuevo_estado)) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

try {
    $conexion->beginTransaction();

    // Verificar que el instructor existe
    $stmt = $conexion->prepare("SELECT nombres, apellidos FROM usuarios WHERE id = ? AND id_rol IN (3, 5)");
    $stmt->execute([$id_instructor]);
    $instructor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$instructor) {
        throw new Exception('Instructor no encontrado');
    }

    // Actualizar estado
    $stmt = $conexion->prepare("UPDATE usuarios SET id_estado = ? WHERE id = ?");
    $result = $stmt->execute([$nuevo_estado, $id_instructor]);

    if (!$result) {
        throw new Exception('Error al actualizar el estado del instructor');
    }

    $conexion->commit();

    $estado_texto = ($nuevo_estado == 1) ? 'Activo' : 'Inactivo';
    
    echo json_encode([
        'success' => true,
        'message' => "Estado del instructor {$instructor['nombres']} {$instructor['apellidos']} cambiado a {$estado_texto}",
        'nuevo_estado' => $nuevo_estado
    ]);

} catch (Exception $e) {
    $conexion->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

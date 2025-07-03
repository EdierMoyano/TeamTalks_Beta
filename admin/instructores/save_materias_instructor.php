<?php
session_start();

// Verificar sesión y rol de administrador
if (!isset($_SESSION['documento']) || $_SESSION['rol'] !== 2) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

// Establecer encabezados para respuesta JSON
header('Content-Type: application/json');

// Verificar si la solicitud es POST
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

// Obtener datos del formulario
$id_instructor = $_POST['id_instructor'] ?? '';
$materia_seleccionada = $_POST['materia_seleccionada'] ?? '';

// Validar datos
if (empty($id_instructor) || !is_numeric($id_instructor)) {
    echo json_encode(['success' => false, 'message' => 'ID de instructor inválido']);
    exit;
}

if (empty($materia_seleccionada) || !is_numeric($materia_seleccionada)) {
    echo json_encode(['success' => false, 'message' => 'Debe seleccionar una materia']);
    exit;
}

// Iniciar transacción
$conexion->beginTransaction();

try {
    // Verificar que el instructor existe
    $stmt_instructor = $conexion->prepare("SELECT id, nombres, apellidos FROM usuarios WHERE id = ? AND id_rol IN (3, 5) AND id_estado = 1");
    $stmt_instructor->execute([$id_instructor]);
    $instructor = $stmt_instructor->fetch(PDO::FETCH_ASSOC);

    if (!$instructor) {
        throw new Exception('Instructor no encontrado');
    }

    // Verificar que la materia existe
    $stmt_materia = $conexion->prepare("SELECT id_materia, materia FROM materias WHERE id_materia = ?");
    $stmt_materia->execute([$materia_seleccionada]);
    $materia = $stmt_materia->fetch(PDO::FETCH_ASSOC);

    if (!$materia) {
        throw new Exception('La materia seleccionada no existe');
    }

    // Verificar si el instructor ya tiene una materia asignada
    $stmt_check = $conexion->prepare("SELECT id_detalles_instructor FROM materia_instructor WHERE id_instructor = ?");
    $stmt_check->execute([$id_instructor]);
    $asignacion_existente = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if ($asignacion_existente) {
        // Actualizar la materia existente
        $stmt_update = $conexion->prepare("UPDATE materia_instructor SET id_materia = ? WHERE id_instructor = ?");
        $result = $stmt_update->execute([$materia_seleccionada, $id_instructor]);
        
        if (!$result) {
            throw new Exception('Error al actualizar la materia del instructor');
        }
        
        $accion = 'actualizada';
    } else {
        // Insertar nueva asignación
        $stmt_insert = $conexion->prepare("INSERT INTO materia_instructor (id_instructor, id_materia) VALUES (?, ?)");
        $result = $stmt_insert->execute([$id_instructor, $materia_seleccionada]);
        
        if (!$result) {
            throw new Exception('Error al asignar la materia al instructor');
        }
        
        $accion = 'asignada';
    }

    // Confirmar transacción
    $conexion->commit();

    // Respuesta exitosa
    $nombre_instructor = $instructor['nombres'] . ' ' . $instructor['apellidos'];
    
    echo json_encode([
        'success' => true,
        'message' => "Materia {$accion} correctamente a {$nombre_instructor}",
        'materia_asignada' => $materia['materia'],
        'accion' => $accion,
        'instructor' => $nombre_instructor
    ]);

} catch (Exception $e) {
    // En caso de error, realizar rollback
    $conexion->rollBack();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    // Cerrar la conexión
    $conexion = null;
}
?>

<?php
session_start();

// Verificar sesión y rol de administrador
if (!isset($_SESSION['documento']) || $_SESSION['rol'] !== 2) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

require_once '../../conexion/conexion.php';

$db = new Database();
$conexion = $db->connect();

if (!$conexion || !($conexion instanceof PDO)) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$id_instructor = $_POST['id_instructor'] ?? '';
$materias_seleccionadas = $_POST['materias'] ?? [];

// Debug: Log de datos recibidos
error_log("ID Instructor: " . $id_instructor);
error_log("Materias seleccionadas: " . print_r($materias_seleccionadas, true));

if (empty($id_instructor)) {
    echo json_encode(['success' => false, 'message' => 'ID de instructor requerido']);
    exit;
}

// Validar que el ID del instructor sea numérico
if (!is_numeric($id_instructor)) {
    echo json_encode(['success' => false, 'message' => 'ID de instructor inválido']);
    exit;
}

try {
    // Verificar que el instructor existe y obtener su NIT
    $stmt = $conexion->prepare("
        SELECT u.id, u.nit 
        FROM usuarios u 
        WHERE u.id = ? AND u.id_rol IN (3, 5) AND u.id_estado = 1
    ");
    $stmt->execute([$id_instructor]);
    $instructor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$instructor) {
        echo json_encode(['success' => false, 'message' => 'Instructor no encontrado o inactivo']);
        exit;
    }

    // Verificar que el usuario logueado tiene acceso a este instructor (mismo NIT)
    $stmt = $conexion->prepare("SELECT nit FROM usuarios WHERE id = ? AND id_estado = 1");
    $stmt->execute([$_SESSION['documento']]);
    $usuario_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario_data || $usuario_data['nit'] != $instructor['nit']) {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para modificar este instructor']);
        exit;
    }

    // Iniciar transacción
    $conexion->beginTransaction();

    // Eliminar asignaciones existentes
    $stmt = $conexion->prepare("DELETE FROM materia_instructor WHERE id_instructor = ?");
    $result = $stmt->execute([$id_instructor]);
    
    error_log("Eliminación de materias existentes: " . ($result ? 'exitosa' : 'falló'));

    // Insertar nuevas asignaciones
    $materias_insertadas = 0;
    $materias_validas = [];
    
    if (!empty($materias_seleccionadas) && is_array($materias_seleccionadas)) {
        // Preparar statement para inserción
        $stmt_insert = $conexion->prepare("INSERT INTO materia_instructor (id_instructor, id_materia) VALUES (?, ?)");
        
        // Preparar statement para verificar materias
        $stmt_check = $conexion->prepare("SELECT id_materia, materia FROM materias WHERE id_materia = ?");

        foreach ($materias_seleccionadas as $id_materia) {
            // Validar que el ID de materia sea numérico
            if (!is_numeric($id_materia)) {
                error_log("ID de materia inválido: $id_materia");
                continue;
            }

            // Verificar que la materia existe
            $stmt_check->execute([$id_materia]);
            $materia = $stmt_check->fetch(PDO::FETCH_ASSOC);
            
            if ($materia) {
                $result = $stmt_insert->execute([$id_instructor, $id_materia]);
                if ($result) {
                    $materias_insertadas++;
                    $materias_validas[] = $materia['materia'];
                    error_log("Materia {$materia['materia']} (ID: $id_materia) insertada correctamente");
                } else {
                    error_log("Error al insertar materia $id_materia");
                }
            } else {
                error_log("Materia $id_materia no existe en la base de datos");
            }
        }
    }

    // Confirmar transacción
    $conexion->commit();

    // Obtener información del instructor para el mensaje
    $stmt = $conexion->prepare("SELECT nombres, apellidos FROM usuarios WHERE id = ?");
    $stmt->execute([$id_instructor]);
    $instructor_info = $stmt->fetch(PDO::FETCH_ASSOC);
    $nombre_instructor = $instructor_info ? $instructor_info['nombres'] . ' ' . $instructor_info['apellidos'] : 'Instructor';

    echo json_encode([
        'success' => true,
        'message' => "Materias asignadas correctamente a $nombre_instructor",
        'total_materias' => $materias_insertadas,
        'materias_asignadas' => $materias_validas,
        'debug' => [
            'id_instructor' => $id_instructor,
            'materias_recibidas' => count($materias_seleccionadas ?? []),
            'materias_insertadas' => $materias_insertadas,
            'instructor' => $nombre_instructor
        ]
    ]);

} catch (PDOException $e) {
    // Revertir transacción en caso de error
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    
    error_log("Error PDO: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage(),
        'debug' => [
            'id_instructor' => $id_instructor,
            'materias_recibidas' => count($materias_seleccionadas ?? [])
        ]
    ]);
} catch (Exception $e) {
    // Revertir transacción en caso de error
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    
    error_log("Error general: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Error inesperado: ' . $e->getMessage()
    ]);
}
?>

<?php
session_start();
require_once '../../conexion/conexion.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['documento'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$db = new Database();
$conexion = $db->connect();

// Verificar que la conexión sea válida
if (!$conexion || !($conexion instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_fichas':
            $stmt = $conexion->query("
                SELECT f.id_ficha, fo.nombre as nombre_programa, tf.tipo_formacion
                FROM fichas f
                JOIN formacion fo ON f.id_formacion = fo.id_formacion
                JOIN tipo_formacion tf ON fo.id_tipo_formacion = tf.id_tipo_formacion
                WHERE f.id_estado = 1
                ORDER BY f.id_ficha
            ");
            if ($stmt) {
                $fichas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($fichas);
            } else {
                echo json_encode([]);
            }
            break;

        case 'get_trimestres':
            $id_ficha = $_GET['id_ficha'] ?? 0;
            if (!$id_ficha) {
                echo json_encode([]);
                break;
            }
            
            // Obtener el tipo de formación de la ficha
            $stmt = $conexion->prepare("
                SELECT tf.tipo_formacion
                FROM fichas f
                JOIN formacion fo ON f.id_formacion = fo.id_formacion
                JOIN tipo_formacion tf ON fo.id_tipo_formacion = tf.id_tipo_formacion
                WHERE f.id_ficha = ?
            ");
            $stmt->execute([$id_ficha]);
            $ficha_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($ficha_info) {
                // Determinar cuántos trimestres según el tipo de formación
                $max_trimestres = 6; // Por defecto
                if (stripos($ficha_info['tipo_formacion'], 'tecnico') !== false) {
                    $max_trimestres = 4;
                } elseif (stripos($ficha_info['tipo_formacion'], 'tecnologo') !== false) {
                    $max_trimestres = 6;
                }
                
                $stmt = $conexion->prepare("
                    SELECT id_trimestre, trimestre
                    FROM trimestre 
                    WHERE id_trimestre <= ?
                    ORDER BY id_trimestre
                ");
                $stmt->execute([$max_trimestres]);
                if ($stmt) {
                    $trimestres = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode($trimestres);
                } else {
                    echo json_encode([]);
                }
            } else {
                echo json_encode([]);
            }
            break;

        case 'get_materias_ficha':
            $id_ficha = $_GET['id_ficha'] ?? 0;
            if (!$id_ficha) {
                echo json_encode([]);
                break;
            }
            
            $stmt = $conexion->prepare("
                SELECT mf.id_materia_ficha, m.materia
                FROM materia_ficha mf
                JOIN materias m ON mf.id_materia = m.id_materia
                WHERE mf.id_ficha = ?
                ORDER BY m.materia
            ");
            $stmt->execute([$id_ficha]);
            if ($stmt) {
                $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($materias);
            } else {
                echo json_encode([]);
            }
            break;

        case 'get_bloques_jornada':
            $id_jornada = $_GET['id_jornada'] ?? 0;
            if (!$id_jornada) {
                echo json_encode([]);
                break;
            }
            
            $stmt = $conexion->prepare("
                SELECT * FROM bloques_horario 
                WHERE id_jornada = ? AND nombre_bloque != 'Jornada Completa'
                ORDER BY orden_bloque
            ");
            $stmt->execute([$id_jornada]);
            if ($stmt) {
                $bloques = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($bloques);
            } else {
                echo json_encode([]);
            }
            break;

        default:
            echo json_encode(['error' => 'Acción no válida']);
            break;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>

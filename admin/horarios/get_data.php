<?php
session_start();
require_once '../../conexion/conexion.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['documento'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Crear instancia de la conexión
$db = new Database();
$conexion = $db->connect();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_fichas':
        try {
            $stmt = $conexion->query("
                SELECT f.id_ficha, fo.nombre as nombre_programa, tf.tipo_formacion
                FROM fichas f
                LEFT JOIN formacion fo ON f.id_formacion = fo.id_formacion
                LEFT JOIN tipo_formacion tf ON fo.id_tipo_formacion = tf.id_tipo_formacion
                WHERE f.id_estado = 1
                ORDER BY f.id_ficha
            ");
            $fichas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($fichas);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al cargar fichas: ' . $e->getMessage()]);
        }
        break;

    case 'get_materias_ficha':
        $idFicha = $_GET['id_ficha'] ?? null;
        if (!$idFicha) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de ficha requerido']);
            exit;
        }

        try {
            $stmt = $conexion->prepare("
                SELECT mf.id_materia_ficha, m.materia
                FROM materia_ficha mf
                JOIN materias m ON mf.id_materia = m.id_materia
                WHERE mf.id_ficha = :id_ficha
                ORDER BY m.materia
            ");
            $stmt->bindParam(':id_ficha', $idFicha, PDO::PARAM_INT);
            $stmt->execute();
            $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($materias);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al cargar materias: ' . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Acción no válida']);
        break;
}

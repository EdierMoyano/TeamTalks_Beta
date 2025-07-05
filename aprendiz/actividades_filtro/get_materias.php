<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../conexion/init.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    if (!isset($conex)) {
        throw new Exception('Error: No hay conexión a la base de datos');
    }
    
    $user_id = null;
    $session_method = '';
    
    if (isset($_SESSION['documento']) && !empty($_SESSION['documento'])) {
        $user_id = $_SESSION['documento'];
        $session_method = 'SESSION[documento]';
    }
    elseif (isset($_SESSION['id']) && !empty($_SESSION['id'])) {
        $user_id = $_SESSION['id'];
        $session_method = 'SESSION[id]';
    }
    elseif (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $session_method = 'SESSION[user_id]';
    }

    if (!$user_id) {
        throw new Exception('No se pudo obtener el ID de usuario para materias. Sesión requerida: documento');
    }

    $query = "
        SELECT DISTINCT m.id_materia, m.materia
        FROM materias m
        INNER JOIN materia_ficha mf ON m.id_materia = mf.id_materia
        INNER JOIN fichas f ON mf.id_ficha = f.id_ficha
        INNER JOIN user_ficha uf ON f.id_ficha = uf.id_ficha
        WHERE uf.id_user = :user_id AND uf.id_estado = 1
        ORDER BY m.materia ASC
    ";

    $stmt = $conex->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_STR);
    $stmt->execute();
    
    $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'materias' => $materias,
        'debug' => [
            'user_id' => $user_id,
            'session_method' => $session_method,
            'materias_count' => count($materias),
            'query_executed' => true,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'user_id' => $user_id ?? null,
            'session_method' => $session_method ?? 'none',
            'error_details' => $e->getMessage(),
            'session_status' => session_status(),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
}
?>

<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../conexion/init.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

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
    elseif (isset($_SESSION['id_user']) && !empty($_SESSION['id_user'])) {
        $user_id = $_SESSION['id_user'];
        $session_method = 'SESSION[id_user]';
    }
    
    if (!$user_id) {
        throw new Exception('Sesión no iniciada correctamente. No se encontró $_SESSION[documento].');
    }

    $user_check = "SELECT id, nombres, apellidos, id AS documento, id_rol FROM usuarios WHERE id = :user_id";
    $user_check_stmt = $conex->prepare($user_check);
    $user_check_stmt->bindParam(':user_id', $user_id, PDO::PARAM_STR);
    $user_check_stmt->execute();
    $user_exists = $user_check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user_exists) {
        throw new Exception('Usuario no encontrado en la base de datos con documento/ID: ' . $user_id);
    }

    $query = "
        SELECT 
            a.id_actividad,
            a.titulo,
            a.descripcion,
            a.fecha_entrega,
            m.materia,
            m.id_materia,
            COALESCE(t.trimestre, 'Sin trimestre') as trimestre,
            COALESCE(t.mes_inicio, 1) as trimestre_inicio,
            COALESCE(t.mes_fin, 12) as trimestre_fin,
            CASE 
                WHEN au.id_estado_actividad = 8 THEN 'entregada'
                WHEN au.id_estado_actividad = 10 THEN 'vencida'
                WHEN au.id_estado_actividad = 9 AND a.fecha_entrega < CURDATE() THEN 'vencida'
                WHEN au.id_estado_actividad = 9 THEN 'pendiente'
                WHEN au.id_estado_actividad IS NULL AND a.fecha_entrega < CURDATE() THEN 'vencida'
                WHEN au.id_estado_actividad IS NULL THEN 'pendiente'
                ELSE 'pendiente'
            END as estado,
            au.fecha_entrega as fecha_entrega_usuario,
            au.nota,
            au.comentario_inst,
            COALESCE(f.id_ficha, 0) as id_ficha,
            CONCAT('Ficha ', f.id_ficha) as numero_ficha,
            COALESCE(fo.nombre, 'Sin formación') as formacion_nombre
        FROM actividades a
        INNER JOIN materia_ficha mf ON a.id_materia_ficha = mf.id_materia_ficha
        INNER JOIN materias m ON mf.id_materia = m.id_materia
        LEFT JOIN trimestre t ON mf.id_trimestre = t.id_trimestre
        INNER JOIN fichas f ON mf.id_ficha = f.id_ficha
        LEFT JOIN formacion fo ON f.id_formacion = fo.id_formacion
        INNER JOIN user_ficha uf ON f.id_ficha = uf.id_ficha
        LEFT JOIN actividades_user au ON a.id_actividad = au.id_actividad AND au.id_user = :user_id
        WHERE uf.id_user = :user_id2 AND uf.id_estado = 1
        ORDER BY a.fecha_entrega ASC
    ";

    $stmt = $conex->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_STR);
    $stmt->bindParam(':user_id2', $user_id, PDO::PARAM_STR);
    $stmt->execute();
    
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $user_query = "
        SELECT u.id, u.nombres, u.apellidos, u.id AS documento, r.rol
        FROM usuarios u
        LEFT JOIN roles r ON u.id_rol = r.id_rol
        WHERE u.id = :user_id
    ";
    $user_stmt = $conex->prepare($user_query);
    $user_stmt->bindParam(':user_id', $user_id, PDO::PARAM_STR);
    $user_stmt->execute();
    $user_info = $user_stmt->fetch(PDO::FETCH_ASSOC);

    $ficha_query = "
        SELECT f.id_ficha, 
               CONCAT('Ficha ', f.id_ficha) as numero_ficha,
               COALESCE(fo.nombre, 'Sin formación') as formacion_nombre, 
               uf.id_estado
        FROM user_ficha uf
        INNER JOIN fichas f ON uf.id_ficha = f.id_ficha
        LEFT JOIN formacion fo ON f.id_formacion = fo.id_formacion
        WHERE uf.id_user = :user_id
    ";
    $ficha_stmt = $conex->prepare($ficha_query);
    $ficha_stmt->bindParam(':user_id', $user_id, PDO::PARAM_STR);
    $ficha_stmt->execute();
    $user_fichas = $ficha_stmt->fetchAll(PDO::FETCH_ASSOC);

    $response = [
        'success' => true,
        'activities' => $activities,
        'debug' => [
            'user_id' => $user_id,
            'session_method' => $session_method,
            'user_info' => $user_info,
            'user_fichas' => $user_fichas,
            'activities_count' => count($activities),
            'query_executed' => true,
            'session_active' => true,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    $error_response = [
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'user_id' => $user_id ?? null,
            'session_method' => $session_method ?? 'none',
            'error_line' => $e->getLine(),
            'error_file' => $e->getFile(),
            'session_status' => session_status(),
            'session_id' => session_id(),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];
    
    echo json_encode($error_response);
}
?>

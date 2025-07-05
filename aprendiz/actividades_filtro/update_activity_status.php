<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../../conexion/init.php';
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['documento']) || empty($_SESSION['documento'])) {
        throw new Exception('Usuario no autenticado - Sesión no iniciada correctamente');
    }

    $user_id = $_SESSION['documento'];

    $input = json_decode(file_get_contents('php://input'), true);
    $id_actividad = $input['id_actividad'] ?? null;
    $estado = $input['estado'] ?? null;

    if (!$id_actividad || !$estado) {
        throw new Exception('Datos incompletos: se requiere id_actividad y estado');
    }

    $estado_map = [
        'pendiente' => 9,
        'entregada' => 8,
        'vencida' => 10
    ];

    $id_estado = $estado_map[$estado] ?? null;
    if (!$id_estado) {
        throw new Exception('Estado no válido: ' . $estado);
    }

    $check_query = "SELECT id_actividad_user FROM actividades_user WHERE id_actividad = :id_actividad AND id_user = :user_id";
    $check_stmt = $conex->prepare($check_query);
    $check_stmt->bindParam(':id_actividad', $id_actividad, PDO::PARAM_INT);
    $check_stmt->bindParam(':user_id', $user_id, PDO::PARAM_STR);
    $check_stmt->execute();
    
    $existing_record = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing_record) {
        $update_query = "
            UPDATE actividades_user 
            SET id_estado_actividad = :id_estado, fecha_entrega = NOW() 
            WHERE id_actividad = :id_actividad AND id_user = :user_id
        ";
        $update_stmt = $conex->prepare($update_query);
        $update_stmt->bindParam(':id_estado', $id_estado, PDO::PARAM_INT);
        $update_stmt->bindParam(':id_actividad', $id_actividad, PDO::PARAM_INT);
        $update_stmt->bindParam(':user_id', $user_id, PDO::PARAM_STR);
        $update_stmt->execute();
    } else {
        $insert_query = "
            INSERT INTO actividades_user (id_actividad, id_user, id_estado_actividad, fecha_entrega) 
            VALUES (:id_actividad, :user_id, :id_estado, NOW())
        ";
        $insert_stmt = $conex->prepare($insert_query);
        $insert_stmt->bindParam(':id_actividad', $id_actividad, PDO::PARAM_INT);
        $insert_stmt->bindParam(':user_id', $user_id, PDO::PARAM_STR);
        $insert_stmt->bindParam(':id_estado', $id_estado, PDO::PARAM_INT);
        $insert_stmt->execute();
    }

    echo json_encode([
        'success' => true,
        'message' => 'Estado actualizado correctamente',
        'debug' => [
            'user_id' => $user_id,
            'action' => $existing_record ? 'updated' : 'created',
            'id_actividad' => $id_actividad,
            'nuevo_estado' => $estado
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'user_id' => $user_id ?? null,
            'error_details' => $e->getMessage(),
            'session_exists' => isset($_SESSION['documento']) ? 'yes' : 'no'
        ]
    ]);
}
?>

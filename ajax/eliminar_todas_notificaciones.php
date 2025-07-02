<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';

// Verificar que sea una petición AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Petición inválida']));
}

// Verificar que el usuario esté logueado
if (!isset($_SESSION['documento'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'No autorizado']));
}

$id_usuario = $_SESSION['documento'];

try {
    // Eliminar todas las notificaciones del usuario
    $stmt = $conex->prepare("DELETE FROM notificaciones WHERE id_usuario = ?");
    $result = $stmt->execute([$id_usuario]);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Todas las notificaciones han sido eliminadas'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Error al eliminar las notificaciones'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error al eliminar notificaciones: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor'
    ]);
}
?>
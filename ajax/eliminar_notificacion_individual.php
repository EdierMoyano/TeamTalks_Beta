<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Petición inválida']));
}

if (!isset($_SESSION['documento'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'No autorizado']));
}

$id_usuario = $_SESSION['documento'];
$id_notificacion = $_POST['id_notificacion'] ?? null;

if (!$id_notificacion) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'ID de notificación requerido']));
}

try {
    $stmt = $conex->prepare("DELETE FROM notificaciones WHERE id_notificacion = ? AND id_usuario = ?");
    $result = $stmt->execute([$id_notificacion, $id_usuario]);

    if ($result && $stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Notificación eliminada correctamente'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No se pudo eliminar la notificación'
        ]);
    }
} catch (Exception $e) {
    error_log("Error al eliminar notificación: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}

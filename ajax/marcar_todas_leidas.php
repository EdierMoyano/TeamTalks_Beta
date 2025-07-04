<?php
if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['DOCUMENT_ROOT'], 'htdocs') !== false) {
  require_once $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php';
} else {
  require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';
}include 'session.php';

header('Content-Type: application/json');

$id_usuario = $_SESSION['documento'] ?? null;

if ($id_usuario) {
    $stmt = $conex->prepare("UPDATE notificaciones SET leido = 1 WHERE id_usuario = ?");
    $stmt->execute([$id_usuario]);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
}

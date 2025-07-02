<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';

$id_actividad_user = $_POST['id_actividad_user'];
$nota = floatval($_POST['nota']);
$comentario = $_POST['comentario_inst'] ?? null;

// Determinar nuevo estado según la nota
if ($nota >= 3.0) {
    $nuevo_estado = 3; // Aprobado
} elseif ($nota > 0.0) {
    $nuevo_estado = 4; // Desaprobado
} else {
    $nuevo_estado = 9; // Pendiente o sin calificar
}

// Actualizar la nota, comentario y estado
$stmt = $conex->prepare("UPDATE actividades_user SET nota = ?, comentario_inst = ?, id_estado_actividad = ? WHERE id_actividad_user = ?");
$exito = $stmt->execute([$nota, $comentario, $nuevo_estado, $id_actividad_user]);

// Obtener el nombre del estado
$estado_stmt = $conex->prepare("SELECT estado FROM estado WHERE id_estado = ?");
$estado_stmt->execute([$nuevo_estado]);
$estado_nombre = $estado_stmt->fetchColumn();

echo json_encode([
    'success' => true,
    'message' => 'Aprendiz Calificado.',
    'nuevo_estado' => $estado_nombre
]);


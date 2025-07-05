<?php
session_start();
require_once '../../conexion/conexion.php';

if (!isset($_SESSION['documento']) || $_SESSION['rol'] !== 2) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$id_materia = $_GET['id_materia'] ?? 0;

if (!$id_materia) {
    echo json_encode(['success' => false, 'error' => 'ID de materia no válido']);
    exit;
}

$db = new Database();
$conexion = $db->connect();

// Aquí se asume que existe una tabla materia_instructor con los campos id_instructor, id_materia
try {
    $stmt = $conexion->prepare("
        SELECT u.id, CONCAT(u.nombres, ' ', u.apellidos) AS nombre_completo
        FROM usuarios u
        INNER JOIN materia_instructor mi ON u.id = mi.id_instructor
        WHERE mi.id_materia = ? AND u.id_estado = 1
        ORDER BY u.nombres
    ");
    $stmt->execute([$id_materia]);
    $instructores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'instructores' => $instructores
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

<?php
session_start();
require_once '../clase/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$id_usuario = $_POST['id_usuario'] ?? null;
$id_ficha = $_POST['id_ficha'] ?? null;
$id_trimestre = $_POST['id_trimestre'] ?? null;

if (!$id_usuario || !$id_ficha || !$id_trimestre) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

try {
    // Obtener información del trimestre
    $stmt_trimestre = $pdo->prepare("SELECT trimestre FROM trimestre WHERE id_trimestre = ?");
    $stmt_trimestre->execute([$id_trimestre]);
    $trimestre_info = $stmt_trimestre->fetch();
    
    if (!$trimestre_info) {
        echo json_encode(['success' => false, 'message' => 'Trimestre no encontrado']);
        exit;
    }
    
    // Consulta corregida para obtener materias del trimestre específico
    $stmt = $pdo->prepare("
        SELECT 
            m.id_materia,
            m.materia as nombre,
            mf.id_materia_ficha,
            AVG(CASE WHEN au.nota IS NOT NULL AND au.nota > 0 AND au.id_estado_actividad = 8 
                     THEN au.nota ELSE NULL END) as promedio_final,
            COUNT(CASE WHEN au.nota IS NOT NULL AND au.nota > 0 AND au.id_estado_actividad = 8 
                       THEN au.id_actividad ELSE NULL END) as total_actividades_calificadas,
            COUNT(DISTINCT a.id_actividad) as total_actividades_asignadas
        FROM materia_ficha mf
        JOIN materias m ON mf.id_materia = m.id_materia
        LEFT JOIN actividades a ON mf.id_materia_ficha = a.id_materia_ficha
        LEFT JOIN actividades_user au ON a.id_actividad = au.id_actividad AND au.id_user = ?
        WHERE mf.id_ficha = ? AND mf.id_trimestre = ?
        GROUP BY m.id_materia, m.materia, mf.id_materia_ficha
        ORDER BY m.materia
    ");
    
    $stmt->execute([$id_usuario, $id_ficha, $id_trimestre]);
    $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Contar totales
    $total_materias = count($materias);
    $materias_con_calificacion = 0;
    $total_actividades = 0;
    
    foreach ($materias as $materia) {
        if ($materia['promedio_final'] !== null) {
            $materias_con_calificacion++;
        }
        $total_actividades += $materia['total_actividades_calificadas'];
    }
    
    // Debug info
    $debug_info = [
        'id_usuario' => $id_usuario,
        'id_ficha' => $id_ficha,
        'id_trimestre' => $id_trimestre,
        'consulta_materias' => $materias,
        'total_encontradas' => count($materias)
    ];
    
    echo json_encode([
        'success' => true,
        'total_materias' => $total_materias,
        'materias_con_calificacion' => $materias_con_calificacion,
        'total_actividades' => $total_actividades,
        'trimestre_nombre' => $trimestre_info['trimestre'] . ' Trimestre',
        'materias' => $materias,
        'debug' => $debug_info
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener boletín: ' . $e->getMessage()
    ]);
}
?>

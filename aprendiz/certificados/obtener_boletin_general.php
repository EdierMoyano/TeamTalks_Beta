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

if (!$id_usuario || !$id_ficha) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

try {
    // Obtener trimestre actual basado en el mes
    $mes_actual = date('n');
    $stmt_trimestre = $pdo->prepare("
        SELECT id_trimestre, trimestre 
        FROM trimestre 
        WHERE ? BETWEEN mes_inicio AND mes_fin
        LIMIT 1
    ");
    $stmt_trimestre->execute([$mes_actual]);
    $trimestre_actual = $stmt_trimestre->fetch();
    
    $id_trimestre_filtro = $trimestre_actual ? $trimestre_actual['id_trimestre'] : 1;
    
    // Obtener solo las materias del trimestre actual que está cursando el aprendiz
    $stmt = $pdo->prepare("
        SELECT 
            m.id_materia,
            m.materia as nombre,
            AVG(au.nota) as promedio_final,
            COUNT(au.nota) as total_actividades_calificadas,
            t.trimestre as nombre_trimestre
        FROM materia_ficha mf
        JOIN materias m ON mf.id_materia = m.id_materia
        JOIN trimestre t ON mf.id_trimestre = t.id_trimestre
        LEFT JOIN actividades a ON mf.id_materia_ficha = a.id_materia_ficha
        LEFT JOIN actividades_user au ON a.id_actividad = au.id_actividad 
            AND au.id_user = ? 
            AND au.nota IS NOT NULL 
            AND au.nota > 0
            AND au.id_estado_actividad = 8
        WHERE mf.id_ficha = ? AND mf.id_trimestre = ?
        GROUP BY m.id_materia, m.materia, t.trimestre
        HAVING COUNT(a.id_actividad) > 0
        ORDER BY m.materia
    ");
    
    $stmt->execute([$id_usuario, $id_ficha, $id_trimestre_filtro]);
    $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Contar totales
    $total_materias = count($materias);
    $materias_con_calificacion = 0;
    $total_actividades = 0;
    
    foreach ($materias as $materia) {
        if ($materia['promedio_final']) {
            $materias_con_calificacion++;
        }
        $total_actividades += $materia['total_actividades_calificadas'];
    }
    
    echo json_encode([
        'success' => true,
        'total_materias' => $total_materias,
        'materias_con_calificacion' => $materias_con_calificacion,
        'total_actividades' => $total_actividades,
        'trimestre_actual' => $trimestre_actual ? $trimestre_actual['trimestre'] . ' Trimestre' : 'Trimestre Actual',
        'materias' => $materias
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener boletín: ' . $e->getMessage()
    ]);
}
?>

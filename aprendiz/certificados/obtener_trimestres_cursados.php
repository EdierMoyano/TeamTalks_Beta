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
    $stmt_trimestre_actual = $pdo->prepare("
        SELECT id_trimestre, trimestre 
        FROM trimestre 
        WHERE ? BETWEEN mes_inicio AND mes_fin
        LIMIT 1
    ");
    $stmt_trimestre_actual->execute([$mes_actual]);
    $trimestre_actual = $stmt_trimestre_actual->fetch();
    
    // Si no se encuentra, usar trimestre 3 como fallback
    if (!$trimestre_actual) {
        $trimestre_actual = ['id_trimestre' => 3, 'trimestre' => 'Tercer'];
    }
    
    $id_trimestre_actual = $trimestre_actual['id_trimestre'];
    
    // Obtener TODOS los trimestres que tienen materias asignadas a esta ficha
    // EXCLUYENDO el trimestre actual
    $stmt_todos = $pdo->prepare("
        SELECT DISTINCT 
            t.id_trimestre, 
            t.trimestre, 
            t.mes_inicio, 
            t.mes_fin,
            COUNT(DISTINCT mf.id_materia_ficha) as total_materias
        FROM trimestre t
        JOIN materia_ficha mf ON t.id_trimestre = mf.id_trimestre
        WHERE mf.id_ficha = ? AND t.id_trimestre < ?
        GROUP BY t.id_trimestre, t.trimestre, t.mes_inicio, t.mes_fin
        HAVING total_materias > 0
        ORDER BY t.id_trimestre
    ");
    $stmt_todos->execute([$id_ficha, $id_trimestre_actual]);
    $todos_trimestres = $stmt_todos->fetchAll(PDO::FETCH_ASSOC);
    
    $trimestres_procesados = [];
    
    foreach ($todos_trimestres as $trimestre) {
        // Para cada trimestre, obtener estadísticas de calificaciones
        $stmt_stats = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT mf.id_materia_ficha) as total_materias,
                COUNT(DISTINCT CASE 
                    WHEN au.nota IS NOT NULL AND au.nota > 0 AND au.id_estado_actividad = 8
                    THEN mf.id_materia_ficha 
                END) as materias_calificadas,
                COUNT(DISTINCT CASE 
                    WHEN au.nota IS NOT NULL AND au.nota > 0 AND au.id_estado_actividad = 8
                    THEN au.id_actividad 
                END) as actividades_calificadas
            FROM materia_ficha mf
            LEFT JOIN actividades a ON mf.id_materia_ficha = a.id_materia_ficha
            LEFT JOIN actividades_user au ON a.id_actividad = au.id_actividad AND au.id_user = ?
            WHERE mf.id_ficha = ? AND mf.id_trimestre = ?
        ");
        $stmt_stats->execute([$id_usuario, $id_ficha, $trimestre['id_trimestre']]);
        $stats = $stmt_stats->fetch();
        
        $total_materias = $stats['total_materias'] ?? 0;
        $materias_calificadas = $stats['materias_calificadas'] ?? 0;
        $actividades_calificadas = $stats['actividades_calificadas'] ?? 0;
        
        // Un trimestre está completado si ya pasó su mes final
        $esta_completado = ($mes_actual > $trimestre['mes_fin']);
        
        // Todas las materias están calificadas si hay materias y todas tienen calificación
        $todas_calificadas = $total_materias > 0 && $total_materias == $materias_calificadas;
        
        // Solo mostrar trimestres que ya terminaron (no el actual)
        if ($total_materias > 0) {
            $trimestres_procesados[] = [
                'id_trimestre' => $trimestre['id_trimestre'],
                'nombre' => $trimestre['trimestre'] . ' Trimestre',
                'total_materias' => $total_materias,
                'materias_calificadas' => $materias_calificadas,
                'actividades_calificadas' => $actividades_calificadas,
                'completado' => $esta_completado,
                'todas_calificadas' => $todas_calificadas,
                'puede_descargar' => $esta_completado && $todas_calificadas
            ];
        }
    }
    
    // Debug info
    $debug_info = [
        'mes_actual' => $mes_actual,
        'trimestre_actual_detectado' => $trimestre_actual,
        'id_ficha' => $id_ficha,
        'todos_trimestres_encontrados' => count($todos_trimestres),
        'trimestres_procesados' => count($trimestres_procesados)
    ];
    
    echo json_encode([
        'success' => true,
        'trimestres' => $trimestres_procesados,
        'debug' => $debug_info
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener trimestres: ' . $e->getMessage()
    ]);
}
?>

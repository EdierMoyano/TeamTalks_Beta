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
    
    // Obtener promedio general del trimestre actual
    $stmt = $pdo->prepare("
        SELECT AVG(au.nota) as promedio_general
        FROM actividades_user au
        JOIN actividades a ON au.id_actividad = a.id_actividad
        JOIN materia_ficha mf ON a.id_materia_ficha = mf.id_materia_ficha
        WHERE au.id_user = ? 
        AND mf.id_ficha = ? 
        AND mf.id_trimestre = ?
        AND au.nota IS NOT NULL 
        AND au.nota > 0
        AND au.id_estado_actividad = 8
    ");
    
    $stmt->execute([$id_usuario, $id_ficha, $id_trimestre_filtro]);
    $resultado = $stmt->fetch();
    
    $promedio = $resultado['promedio_general'] ? round($resultado['promedio_general'], 2) : 0;
    
    echo json_encode([
        'success' => true,
        'promedio' => $promedio,
        'trimestre' => $trimestre_actual ? $trimestre_actual['trimestre'] . ' Trimestre' : 'Trimestre Actual'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener promedio: ' . $e->getMessage()
    ]);
}
?>

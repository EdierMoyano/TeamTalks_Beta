<?php
session_start();

// Verificar sesión y rol de administrador
if (!isset($_SESSION['documento']) || $_SESSION['rol'] !== 2) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

require_once '../../../conexion/conexion.php';

header('Content-Type: application/json');

$db = new Database();
$conexion = $db->connect();

if (!$conexion || !($conexion instanceof PDO)) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit;
}

$id_instructor = $_GET['id_instructor'] ?? '';

if (empty($id_instructor) || !is_numeric($id_instructor)) {
    echo json_encode(['success' => false, 'message' => 'ID de instructor inválido']);
    exit;
}

try {
    // Obtener trimestres donde el instructor tiene horarios asignados
    // Flujo correcto: instructor -> materia_ficha -> horario (que tiene id_trimestre directamente)
    $stmt = $conexion->prepare("
        SELECT DISTINCT
            t.id_trimestre,
            t.trimestre,
            COUNT(DISTINCT h.id_horario) as total_horarios,
            COUNT(DISTINCT h.id_ficha) as fichas_en_trimestre,
            SUM(TIMESTAMPDIFF(MINUTE, h.hora_inicio, h.hora_fin)) / 60 as total_horas_semanales
        FROM trimestre t
        INNER JOIN horario h ON t.id_trimestre = h.id_trimestre
        INNER JOIN materia_ficha mf ON h.id_materia_ficha = mf.id_materia_ficha
        WHERE mf.id_instructor = ? 
        AND h.id_estado = 1
        GROUP BY t.id_trimestre, t.trimestre
        ORDER BY t.id_trimestre
    ");
    $stmt->execute([$id_instructor]);
    $trimestres = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'trimestres' => $trimestres
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener trimestres: ' . $e->getMessage()
    ]);
}
?>

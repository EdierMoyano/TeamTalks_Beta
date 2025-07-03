<?php
session_start();

// Verificar sesión y rol de administrador
if (!isset($_SESSION['documento']) || $_SESSION['rol'] !== 2) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

require_once '../../conexion/conexion.php';

$db = new Database();
$conexion = $db->connect();

if (!$conexion || !($conexion instanceof PDO)) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

// Obtener NIT del usuario logueado
$nit_usuario = '';
try {
    $stmt = $conexion->prepare("SELECT nit FROM usuarios WHERE id = ? AND id_estado = 1");
    $stmt->execute([$_SESSION['documento']]);
    $usuario_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario_data && !empty($usuario_data['nit'])) {
        $nit_usuario = $usuario_data['nit'];
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo obtener el NIT del usuario']);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error al obtener datos del usuario: ' . $e->getMessage()]);
    exit;
}

$id_aprendiz = $_GET['id_aprendiz'] ?? '';

if (empty($id_aprendiz)) {
    echo json_encode(['success' => false, 'message' => 'ID de aprendiz requerido']);
    exit;
}

try {
    // Obtener datos del aprendiz
    $stmt = $conexion->prepare("
        SELECT 
            u.id,
            u.nombres,
            u.apellidos,
            u.correo,
            u.telefono,
            u.fecha_registro,
            u.id_estado,
            e.estado,
            uf.id_ficha,
            f.id_ficha as ficha_numero,
            fo.nombre as programa_formacion,
            tf.tipo_formacion,
            j.jornada
        FROM usuarios u
        LEFT JOIN user_ficha uf ON u.id = uf.id_user AND uf.id_estado = 1
        LEFT JOIN fichas f ON uf.id_ficha = f.id_ficha
        LEFT JOIN formacion fo ON f.id_formacion = fo.id_formacion
        LEFT JOIN tipo_formacion tf ON fo.id_tipo_formacion = tf.id_tipo_formacion
        LEFT JOIN jornada j ON f.id_jornada = j.id_jornada
        LEFT JOIN estado e ON u.id_estado = e.id_estado
        WHERE u.id = ? AND u.id_rol = 4 AND u.nit = ?
    ");
    $stmt->execute([$id_aprendiz, $nit_usuario]);
    $aprendiz = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$aprendiz) {
        echo json_encode(['success' => false, 'message' => 'Aprendiz no encontrado']);
        exit;
    }

    // Obtener notas por trimestre
    $stmt = $conexion->prepare("
        SELECT 
            t.trimestre,
            t.id_trimestre,
            AVG(au.nota) as promedio_trimestre,
            COUNT(au.id_actividad_user) as total_actividades,
            SUM(CASE WHEN au.nota >= 4.0 THEN 1 ELSE 0 END) as actividades_aprobadas,
            CASE 
                WHEN AVG(au.nota) >= 4.0 THEN 'Aprobado'
                WHEN AVG(au.nota) IS NULL THEN 'Sin calificar'
                ELSE 'Reprobado'
            END as estado_trimestre
        FROM trimestre t
        LEFT JOIN materia_ficha mf ON t.id_trimestre = mf.id_trimestre AND mf.id_ficha = ?
        LEFT JOIN actividades a ON mf.id_materia_ficha = a.id_materia_ficha
        LEFT JOIN actividades_user au ON a.id_actividad = au.id_actividad AND au.id_user = ?
        GROUP BY t.id_trimestre, t.trimestre
        ORDER BY t.id_trimestre
    ");
    $stmt->execute([$aprendiz['id_ficha'], $id_aprendiz]);
    $notas_trimestre = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener actividades recientes
    $stmt = $conexion->prepare("
        SELECT 
            a.titulo,
            a.fecha_entrega,
            au.nota,
            au.fecha_entrega as fecha_entrega_estudiante,
            au.comentario_inst,
            m.materia,
            CASE 
                WHEN au.nota IS NULL THEN 'Sin calificar'
                WHEN au.nota >= 4.0 THEN 'Aprobado'
                ELSE 'Reprobado'
            END as estado_actividad
        FROM actividades a
        LEFT JOIN actividades_user au ON a.id_actividad = au.id_actividad AND au.id_user = ?
        LEFT JOIN materia_ficha mf ON a.id_materia_ficha = mf.id_materia_ficha
        LEFT JOIN materias m ON mf.id_materia = m.id_materia
        WHERE mf.id_ficha = ?
        ORDER BY a.fecha_entrega DESC
        LIMIT 10
    ");
    $stmt->execute([$id_aprendiz, $aprendiz['id_ficha']]);
    $actividades_recientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcular estadísticas generales
    $stmt = $conexion->prepare("
        SELECT 
            AVG(au.nota) as promedio_general,
            COUNT(au.id_actividad_user) as total_actividades,
            SUM(CASE WHEN au.nota >= 4.0 THEN 1 ELSE 0 END) as actividades_aprobadas,
            CASE 
                WHEN COUNT(au.id_actividad_user) > 0 
                THEN ROUND((SUM(CASE WHEN au.nota >= 4.0 THEN 1 ELSE 0 END) * 100.0 / COUNT(au.id_actividad_user)), 2)
                ELSE 0 
            END as porcentaje_aprobacion
        FROM actividades_user au
        LEFT JOIN actividades a ON au.id_actividad = a.id_actividad
        LEFT JOIN materia_ficha mf ON a.id_materia_ficha = mf.id_materia_ficha
        WHERE au.id_user = ? AND mf.id_ficha = ? AND au.nota IS NOT NULL
    ");
    $stmt->execute([$id_aprendiz, $aprendiz['id_ficha']]);
    $estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);

    // Generar HTML
    ob_start();
    ?>
    <div class="row">
        <!-- Información del aprendiz -->
        <div class="col-md-4">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">
                        <i class="bi bi-person-badge"></i> Información del Aprendiz
                    </h6>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <i class="bi bi-person-circle display-1 text-primary"></i>
                        <h5 class="mt-2"><?php echo htmlspecialchars($aprendiz['nombres'] . ' ' . $aprendiz['apellidos']); ?></h5>
                        <span class="badge bg-<?php echo ($aprendiz['id_estado'] == 1) ? 'success' : (($aprendiz['id_estado'] == 6) ? 'warning' : 'danger'); ?>">
                            <?php echo htmlspecialchars($aprendiz['estado']); ?>
                        </span>
                    </div>
                    
                    <hr>
                    
                    <p><strong><i class="bi bi-person-vcard"></i> Documento:</strong><br><?php echo $aprendiz['id']; ?></p>
                    <p><strong><i class="bi bi-envelope"></i> Correo:</strong><br><?php echo htmlspecialchars($aprendiz['correo']); ?></p>
                    <p><strong><i class="bi bi-telephone"></i> Teléfono:</strong><br><?php echo htmlspecialchars($aprendiz['telefono'] ?? 'No registrado'); ?></p>
                    <p><strong><i class="bi bi-calendar"></i> Fecha de registro:</strong><br><?php echo date('d/m/Y', strtotime($aprendiz['fecha_registro'])); ?></p>
                    
                    <?php if ($aprendiz['ficha_numero']): ?>
                        <p><strong><i class="bi bi-folder"></i> Ficha:</strong><br><?php echo $aprendiz['ficha_numero']; ?></p>
                        <p><strong><i class="bi bi-book"></i> Programa:</strong><br><?php echo htmlspecialchars($aprendiz['programa_formacion']); ?></p>
                        <p><strong><i class="bi bi-clock"></i> Jornada:</strong><br><?php echo htmlspecialchars($aprendiz['jornada']); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Estadísticas generales -->
            <div class="card mt-3">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">
                        <i class="bi bi-graph-up"></i> Estadísticas Generales
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <h4 class="text-primary"><?php echo number_format($estadisticas['promedio_general'] ?? 0, 2); ?></h4>
                            <small class="text-muted">Promedio General</small>
                        </div>
                        <div class="col-6">
                            <h4 class="text-success"><?php echo number_format($estadisticas['porcentaje_aprobacion'] ?? 0, 1); ?>%</h4>
                            <small class="text-muted">% Aprobación</small>
                        </div>
                    </div>
                    <hr>
                    <div class="row text-center">
                        <div class="col-6">
                            <h5 class="text-info"><?php echo $estadisticas['total_actividades'] ?? 0; ?></h5>
                            <small class="text-muted">Total Actividades</small>
                        </div>
                        <div class="col-6">
                            <h5 class="text-success"><?php echo $estadisticas['actividades_aprobadas'] ?? 0; ?></h5>
                            <small class="text-muted">Aprobadas</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notas por trimestre -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="bi bi-calendar3"></i> Rendimiento por Trimestre
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($notas_trimestre)): ?>
                        <?php foreach ($notas_trimestre as $trimestre): ?>
                            <div class="card mb-3 border-<?php echo ($trimestre['estado_trimestre'] == 'Aprobado') ? 'success' : (($trimestre['estado_trimestre'] == 'Sin calificar') ? 'secondary' : 'danger'); ?>">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($trimestre['trimestre']); ?> Trimestre</h6>
                                        <span class="badge bg-<?php echo ($trimestre['estado_trimestre'] == 'Aprobado') ? 'success' : (($trimestre['estado_trimestre'] == 'Sin calificar') ? 'secondary' : 'danger'); ?>">
                                            <?php echo $trimestre['estado_trimestre']; ?>
                                        </span>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-6">
                                            <small class="text-muted">Promedio:</small>
                                            <div class="fw-bold text-<?php echo ($trimestre['promedio_trimestre'] >= 4.0) ? 'success' : 'danger'; ?>">
                                                <?php echo $trimestre['promedio_trimestre'] ? number_format($trimestre['promedio_trimestre'], 2) : 'N/A'; ?>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Actividades:</small>
                                            <div class="fw-bold">
                                                <?php echo $trimestre['actividades_aprobadas']; ?>/<?php echo $trimestre['total_actividades']; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-calendar-x display-4 text-muted"></i>
                            <p class="text-muted mt-2">No hay datos de trimestres disponibles</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Actividades recientes -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0">
                        <i class="bi bi-list-task"></i> Actividades Recientes
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($actividades_recientes)): ?>
                        <div style="max-height: 400px; overflow-y: auto;">
                            <?php foreach ($actividades_recientes as $actividad): ?>
                                <div class="card mb-2 border-<?php echo ($actividad['estado_actividad'] == 'Aprobado') ? 'success' : (($actividad['estado_actividad'] == 'Sin calificar') ? 'secondary' : 'danger'); ?>">
                                    <div class="card-body p-2">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1 fs-6"><?php echo htmlspecialchars($actividad['titulo']); ?></h6>
                                                <small class="text-muted">
                                                    <i class="bi bi-book"></i> <?php echo htmlspecialchars($actividad['materia']); ?>
                                                </small>
                                            </div>
                                            <span class="badge bg-<?php echo ($actividad['estado_actividad'] == 'Aprobado') ? 'success' : (($actividad['estado_actividad'] == 'Sin calificar') ? 'secondary' : 'danger'); ?>">
                                                <?php echo $actividad['nota'] ? number_format($actividad['nota'], 1) : 'N/A'; ?>
                                            </span>
                                        </div>
                                        <div class="mt-1">
                                            <small class="text-muted">
                                                <i class="bi bi-calendar"></i> Entrega: <?php echo date('d/m/Y', strtotime($actividad['fecha_entrega'])); ?>
                                            </small>
                                        </div>
                                        <?php if ($actividad['comentario_inst']): ?>
                                            <div class="mt-1">
                                                <small class="text-info">
                                                    <i class="bi bi-chat-text"></i> <?php echo htmlspecialchars($actividad['comentario_inst']); ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-list-task display-4 text-muted"></i>
                            <p class="text-muted mt-2">No hay actividades registradas</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    $html = ob_get_clean();
    
    echo json_encode([
        'success' => true,
        'html' => $html
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar detalles: ' . $e->getMessage()
    ]);
}
?>

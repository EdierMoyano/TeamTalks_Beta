<?php
session_start();
require_once '../../conexion/conexion.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['documento'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$db = new Database();
$conexion = $db->connect();

$id_ficha = $_GET['id_ficha'] ?? 0;

if (!$id_ficha) {
    echo json_encode(['success' => false, 'error' => 'ID de ficha no válido']);
    exit;
}

try {
    // Obtener información básica de la ficha
    $stmt = $conexion->prepare("
        SELECT 
            f.id_ficha,
            f.fecha_creac,
            fo.nombre as programa,
            fo.descripcion as descripcion_programa,
            tf.tipo_formacion,
            tf.Duracion,
            j.jornada,
            e.estado,
            CONCAT(u.nombres, ' ', u.apellidos) as instructor_lider,
            u.correo as correo_instructor,
            u.telefono as telefono_instructor
        FROM fichas f
        LEFT JOIN formacion fo ON f.id_formacion = fo.id_formacion
        LEFT JOIN tipo_formacion tf ON fo.id_tipo_formacion = tf.id_tipo_formacion
        LEFT JOIN jornada j ON f.id_jornada = j.id_jornada
        LEFT JOIN estado e ON f.id_estado = e.id_estado
        LEFT JOIN usuarios u ON f.id_instructor = u.id
        WHERE f.id_ficha = ?
    ");
    $stmt->execute([$id_ficha]);
    $ficha = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ficha) {
        echo json_encode(['success' => false, 'error' => 'Ficha no encontrada']);
        exit;
    }

    // Obtener materias asignadas
    $stmt = $conexion->prepare("
        SELECT 
            mf.id_materia_ficha,
            m.materia,
            m.descripcion as descripcion_materia,
            t.trimestre,
            CONCAT(u.nombres, ' ', u.apellidos) as instructor_materia,
            u.id as id_instructor,
            u.correo as correo_instructor_materia
        FROM materia_ficha mf
        JOIN materias m ON mf.id_materia = m.id_materia
        LEFT JOIN trimestre t ON mf.id_trimestre = t.id_trimestre
        LEFT JOIN usuarios u ON mf.id_instructor = u.id
        WHERE mf.id_ficha = ?
        ORDER BY t.id_trimestre, m.materia
    ");
    $stmt->execute([$id_ficha]);
    $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener aprendices asignados
    $stmt = $conexion->prepare("
        SELECT 
            u.id,
            CONCAT(u.nombres, ' ', u.apellidos) as nombre_completo,
            u.correo,
            u.telefono,
            uf.fecha_asig,
            e.estado
        FROM user_ficha uf
        JOIN usuarios u ON uf.id_user = u.id
        LEFT JOIN estado e ON uf.id_estado = e.id_estado
        WHERE uf.id_ficha = ? AND uf.id_estado = 1
        ORDER BY u.nombres, u.apellidos
    ");
    $stmt->execute([$id_ficha]);
    $aprendices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener horarios asignados
    $stmt = $conexion->prepare("
        SELECT 
            h.nombre_horario,
            h.descripcion,
            h.dia_semana,
            h.hora_inicio,
            h.hora_fin,
            m.materia,
            t.trimestre
        FROM horario h
        LEFT JOIN materia_ficha mf ON h.id_materia_ficha = mf.id_materia_ficha
        LEFT JOIN materias m ON mf.id_materia = m.id_materia
        LEFT JOIN trimestre t ON h.id_trimestre = t.id_trimestre
        WHERE h.id_ficha = ?
        ORDER BY 
            CASE h.dia_semana 
                WHEN 'Lunes' THEN 1 
                WHEN 'Martes' THEN 2 
                WHEN 'Miércoles' THEN 3 
                WHEN 'Jueves' THEN 4 
                WHEN 'Viernes' THEN 5 
                WHEN 'Sábado' THEN 6 
            END, h.hora_inicio
    ");
    $stmt->execute([$id_ficha]);
    $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generar HTML
    $html = '
    <div class="row">
        <!-- Información básica de la ficha -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-folder"></i> Información de la Ficha</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><td><strong>Número de Ficha:</strong></td><td>' . $ficha['id_ficha'] . '</td></tr>
                        <tr><td><strong>Programa:</strong></td><td>' . htmlspecialchars($ficha['programa']) . '</td></tr>
                        <tr><td><strong>Tipo:</strong></td><td>' . htmlspecialchars($ficha['tipo_formacion']) . '</td></tr>
                        <tr><td><strong>Duración:</strong></td><td>' . htmlspecialchars($ficha['Duracion']) . '</td></tr>
                        <tr><td><strong>Jornada:</strong></td><td>' . htmlspecialchars($ficha['jornada']) . '</td></tr>
                        <tr><td><strong>Estado:</strong></td><td><span class="badge bg-' . (($ficha['estado'] == 'Activo') ? 'success' : 'secondary') . '">' . htmlspecialchars($ficha['estado']) . '</span></td></tr>
                        <tr><td><strong>Fecha Creación:</strong></td><td>' . date('d/m/Y', strtotime($ficha['fecha_creac'])) . '</td></tr>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Instructor líder -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-person-badge"></i> Instructor Líder</h5>
                </div>
                <div class="card-body">';
    
    if ($ficha['instructor_lider']) {
        $html .= '
                    <table class="table table-sm">
                        <tr><td><strong>Nombre:</strong></td><td>' . htmlspecialchars($ficha['instructor_lider']) . '</td></tr>
                        <tr><td><strong>Correo:</strong></td><td>' . htmlspecialchars($ficha['correo_instructor']) . '</td></tr>
                        <tr><td><strong>Teléfono:</strong></td><td>' . htmlspecialchars($ficha['telefono_instructor']) . '</td></tr>
                    </table>';
    } else {
        $html .= '<p class="text-muted">No hay instructor líder asignado</p>';
    }
    
    $html .= '
                </div>
            </div>
        </div>
    </div>

    <!-- Materias asignadas -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-book"></i> Materias Asignadas (' . count($materias) . ')</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="materias-table">
                            <thead>
                                <tr>
                                    <th>Materia</th>
                                    <th>Trimestre</th>
                                    <th>Instructor</th>
                                    <th>Contacto</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>';
    
    if (!empty($materias)) {
        foreach ($materias as $materia) {
            $html .= '
                                <tr class="materia-row">
                                    <td>
                                        <strong>' . htmlspecialchars($materia['materia']) . '</strong><br>
                                        <small class="text-muted">' . htmlspecialchars($materia['descripcion_materia'] ?? '') . '</small>
                                    </td>
                                    <td><span class="badge bg-primary">' . htmlspecialchars($materia['trimestre'] ?? 'No definido') . '</span></td>
                                    <td>' . htmlspecialchars($materia['instructor_materia'] ?? 'Sin asignar') . '</td>
                                    <td>' . htmlspecialchars($materia['correo_instructor_materia'] ?? '-') . '</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-warning actualizar-instructor" 
                                                    data-id="' . $materia['id_materia_ficha'] . '"
                                                    data-materia="' . htmlspecialchars($materia['materia']) . '"
                                                    data-instructor="' . ($materia['id_instructor'] ?? '') . '"
                                                    title="Cambiar instructor">
                                                <i class="bi bi-person-gear"></i>
                                            </button>
                                            <button class="btn btn-outline-danger eliminar-asignacion" 
                                                    data-id="' . $materia['id_materia_ficha'] . '"
                                                    data-materia="' . htmlspecialchars($materia['materia']) . '"
                                                    title="Eliminar asignación">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>';
        }
    } else {
        $html .= '<tr><td colspan="5" class="text-center text-muted p-3">No hay materias asignadas a esta ficha</td></tr>';
    }
    
    $html .= '
                            </tbody>
                        </table>
                    </div>';
    
    // Paginación para materias
    if (count($materias) > 6) {
        $html .= '
                    <div class="d-flex justify-content-center align-items-center p-3 border-top">
                        <div class="me-3">
                            <small class="text-muted" id="materias-info">Mostrando 1-6 de ' . count($materias) . ' registros</small>
                        </div>
                        <nav aria-label="Paginación materias">
                            <ul class="pagination pagination-sm mb-0" id="materias-pagination">
                                <li class="page-item disabled">
                                    <button class="page-link" onclick="cambiarPaginaMaterias(materiasCurrentPage - 1)">Anterior</button>
                                </li>
                                <li class="page-item disabled">
                                    <button class="page-link" onclick="cambiarPaginaMaterias(materiasCurrentPage + 1)">Siguiente</button>
                                </li>
                            </ul>
                        </nav>
                    </div>';
    }
    
    $html .= '
                </div>
            </div>
        </div>
    </div>

    <!-- Aprendices asignados -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-people"></i> Aprendices Asignados (' . count($aprendices) . ')</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="aprendices-table">
                            <thead>
                                <tr>
                                    <th>Documento</th>
                                    <th>Nombre Completo</th>
                                    <th>Correo</th>
                                    <th>Teléfono</th>
                                    <th>Fecha Asignación</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>';
    
    if (!empty($aprendices)) {
        foreach ($aprendices as $aprendiz) {
            $html .= '
                                <tr class="aprendiz-row">
                                    <td>' . $aprendiz['id'] . '</td>
                                    <td>' . htmlspecialchars($aprendiz['nombre_completo']) . '</td>
                                    <td>' . htmlspecialchars($aprendiz['correo']) . '</td>
                                    <td>' . htmlspecialchars($aprendiz['telefono']) . '</td>
                                    <td>' . date('d/m/Y', strtotime($aprendiz['fecha_asig'])) . '</td>
                                    <td><span class="badge bg-success">' . htmlspecialchars($aprendiz['estado']) . '</span></td>
                                </tr>';
        }
    } else {
        $html .= '<tr><td colspan="6" class="text-center text-muted p-3">No hay aprendices asignados a esta ficha</td></tr>';
    }
    
    $html .= '
                            </tbody>
                        </table>
                    </div>';
    
    // Paginación para aprendices
    if (count($aprendices) > 6) {
        $html .= '
                    <div class="d-flex justify-content-center align-items-center p-3 border-top">
                        <div class="me-3">
                            <small class="text-muted" id="aprendices-info">Mostrando 1-6 de ' . count($aprendices) . ' registros</small>
                        </div>
                        <nav aria-label="Paginación aprendices">
                            <ul class="pagination pagination-sm mb-0" id="aprendices-pagination">
                                <li class="page-item disabled">
                                    <button class="page-link" onclick="cambiarPaginaAprendices(aprendicesCurrentPage - 1)">Anterior</button>
                                </li>
                                <li class="page-item disabled">
                                    <button class="page-link" onclick="cambiarPaginaAprendices(aprendicesCurrentPage + 1)">Siguiente</button>
                                </li>
                            </ul>
                        </nav>
                    </div>';
    }
    
    $html .= '
                </div>
            </div>
        </div>
    </div>';

    // Horarios asignados
    if (!empty($horarios)) {
        $html .= '
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-calendar-week"></i> Horarios Asignados</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="horarios-table">
                                <thead>
                                    <tr>
                                        <th>Nombre Horario</th>
                                        <th>Día</th>
                                        <th>Hora</th>
                                        <th>Materia</th>
                                        <th>Trimestre</th>
                                    </tr>
                                </thead>
                                <tbody>';
        
        foreach ($horarios as $horario) {
            $html .= '
                                    <tr class="horario-row">
                                        <td>' . htmlspecialchars($horario['nombre_horario'] ?? 'Sin nombre') . '</td>
                                        <td>' . htmlspecialchars($horario['dia_semana'] ?? '-') . '</td>
                                        <td>' . ($horario['hora_inicio'] ? date('H:i', strtotime($horario['hora_inicio'])) . ' - ' . date('H:i', strtotime($horario['hora_fin'])) : '-') . '</td>
                                        <td>' . htmlspecialchars($horario['materia'] ?? 'Sin materia') . '</td>
                                        <td>' . htmlspecialchars($horario['trimestre'] ?? 'Sin trimestre') . '</td>
                                    </tr>';
        }
        
        $html .= '
                                </tbody>
                            </table>
                        </div>';
        
        // Paginación para horarios
        if (count($horarios) > 6) {
            $html .= '
                        <div class="d-flex justify-content-center align-items-center p-3 border-top">
                            <div class="me-3">
                                <small class="text-muted" id="horarios-info">Mostrando 1-6 de ' . count($horarios) . ' registros</small>
                            </div>
                            <nav aria-label="Paginación horarios">
                                <ul class="pagination pagination-sm mb-0" id="horarios-pagination">
                                    <li class="page-item disabled">
                                        <button class="page-link" onclick="cambiarPaginaHorarios(horariosCurrentPage - 1)">Anterior</button>
                                    </li>
                                    <li class="page-item disabled">
                                        <button class="page-link" onclick="cambiarPaginaHorarios(horariosCurrentPage + 1)">Siguiente</button>
                                    </li>
                                </ul>
                            </nav>
                        </div>';
        }
        
        $html .= '
                    </div>
                </div>
            </div>
        </div>';
    }

    echo json_encode([
        'success' => true, 
        'html' => $html
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>

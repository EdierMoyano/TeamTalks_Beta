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

// Obtener NIT del usuario logueado desde la base de datos
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

$id_instructor = $_GET['id_instructor'] ?? '';

if (empty($id_instructor)) {
    echo json_encode(['success' => false, 'message' => 'ID de instructor requerido']);
    exit;
}

try {
    // Obtener datos del instructor
    $stmt = $conexion->prepare("
        SELECT 
            u.id,
            u.nombres,
            u.apellidos,
            u.correo,
            u.telefono,
            u.id_rol,
            r.rol,
            u.fecha_registro,
            u.id_estado,
            e.estado as estado_nombre
        FROM usuarios u
        LEFT JOIN roles r ON u.id_rol = r.id_rol
        LEFT JOIN estado e ON u.id_estado = e.id_estado
        WHERE u.id = ? AND u.id_rol IN (3, 5) AND u.nit = ?
    ");
    $stmt->execute([$id_instructor, $nit_usuario]);
    $instructor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$instructor) {
        echo json_encode(['success' => false, 'message' => 'Instructor no encontrado']);
        exit;
    }

    // Obtener fichas asignadas con paginación
    $pagina = isset($_GET['pagina_fichas']) ? (int)$_GET['pagina_fichas'] : 1;
    $fichas_por_pagina = 5;
    $offset = ($pagina - 1) * $fichas_por_pagina;

    // Contar total de fichas
    $stmt = $conexion->prepare("
        SELECT COUNT(DISTINCT f.id_ficha) as total
        FROM fichas f
        INNER JOIN materia_ficha mf ON f.id_ficha = mf.id_ficha
        WHERE mf.id_instructor = ? AND f.id_estado = 1
    ");
    $stmt->execute([$id_instructor]);
    $total_fichas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_paginas_fichas = ceil($total_fichas / $fichas_por_pagina);

    // Obtener fichas con paginación y cálculo de horas del instructor en cada ficha (corregido)
    $stmt = $conexion->prepare("
        SELECT
            f.id_ficha,
            fo.nombre as programa,
            tf.tipo_formacion,
            j.jornada,
            f.fecha_creac,
            COUNT(DISTINCT mf2.id_materia) as materias_asignadas,
            (
              SELECT COUNT(DISTINCT uf2.id_user)
              FROM user_ficha uf2
              WHERE uf2.id_ficha = f.id_ficha AND uf2.id_estado = 1
            ) as aprendices_asignados,
            (
              SELECT COALESCE(SUM(TIMESTAMPDIFF(MINUTE, h2.hora_inicio, h2.hora_fin)), 0)
              FROM materia_ficha mf3
              LEFT JOIN horario h2 ON mf3.id_materia_ficha = h2.id_materia_ficha AND h2.id_estado = 1
              WHERE mf3.id_ficha = f.id_ficha AND mf3.id_instructor = ?
            ) / 60 as horas_semanales
        FROM fichas f
        INNER JOIN materia_ficha mf ON f.id_ficha = mf.id_ficha AND mf.id_instructor = ?
        LEFT JOIN formacion fo ON f.id_formacion = fo.id_formacion
        LEFT JOIN tipo_formacion tf ON fo.id_tipo_formacion = tf.id_tipo_formacion
        LEFT JOIN jornada j ON f.id_jornada = j.id_jornada
        LEFT JOIN materia_ficha mf2 ON f.id_ficha = mf2.id_ficha AND mf2.id_instructor = ?
        WHERE f.id_estado = 1
        GROUP BY f.id_ficha, fo.nombre, tf.tipo_formacion, j.jornada, f.fecha_creac
        ORDER BY f.id_ficha DESC
        LIMIT $fichas_por_pagina OFFSET $offset
    ");
    $stmt->execute([$id_instructor, $id_instructor, $id_instructor]);
    $fichas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcular total de horas del trimestre actual
    $stmt = $conexion->prepare("
        SELECT 
            COALESCE(SUM(TIMESTAMPDIFF(MINUTE, h.hora_inicio, h.hora_fin)), 0) / 60 as total_horas_trimestre
        FROM horario h
        INNER JOIN materia_ficha mf ON h.id_materia_ficha = mf.id_materia_ficha
        INNER JOIN trimestre t ON h.id_trimestre = t.id_trimestre
        WHERE mf.id_instructor = ? 
        AND h.id_estado = 1
        AND MONTH(CURDATE()) BETWEEN t.mes_inicio AND t.mes_fin
    ");
    $stmt->execute([$id_instructor]);
    $horas_trimestre = $stmt->fetch(PDO::FETCH_ASSOC)['total_horas_trimestre'] ?? 0;

    // Obtener materias especializadas
    $stmt = $conexion->prepare("
        SELECT m.materia, m.descripcion
        FROM materia_instructor mi
        INNER JOIN materias m ON mi.id_materia = m.id_materia
        WHERE mi.id_instructor = ?
        ORDER BY m.materia
    ");
    $stmt->execute([$id_instructor]);
    $materias_especializadas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generar HTML
    ob_start();
    ?>
    <div class="row">
    <!-- Información del instructor -->
    <div class="col-md-4">
        <div class="card border-primary">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0">
                    <i class="bi bi-person-badge"></i> Información del Instructor
                </h6>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <i class="bi bi-person-circle display-1 text-primary"></i>
                    <h5 class="mt-2"><?php echo htmlspecialchars($instructor['nombres'] . ' ' . $instructor['apellidos']); ?></h5>
                    <span class="badge bg-primary">
                        <?php echo ($instructor['id_rol'] == 3) ? 'Instructor Normal' : 'Instructor Transversal'; ?>
                    </span>
                    <br>
                    <span class="badge bg-<?php echo ($instructor['id_estado'] == 1) ? 'success' : 'danger'; ?> mt-2">
                        <?php echo $instructor['estado_nombre']; ?>
                    </span>
                </div>
                
                <hr>
                
                <p><strong><i class="bi bi-person-vcard"></i> Documento:</strong><br><?php echo $instructor['id']; ?></p>
                <p><strong><i class="bi bi-envelope"></i> Correo:</strong><br><?php echo htmlspecialchars($instructor['correo']); ?></p>
                <p><strong><i class="bi bi-telephone"></i> Teléfono:</strong><br><?php echo htmlspecialchars($instructor['telefono'] ?? 'No registrado'); ?></p>
                <p><strong><i class="bi bi-calendar"></i> Fecha de registro:</strong><br><?php echo date('d/m/Y', strtotime($instructor['fecha_registro'])); ?></p>
                <p><strong><i class="bi bi-clock"></i> Horas trimestre actual:</strong><br><span class="badge bg-info"><?php echo number_format($horas_trimestre, 1); ?> horas</span></p>
                
                <div class="d-grid gap-2 mt-3">
                    <button class="btn btn-warning editar-instructor" 
                            data-id="<?php echo $instructor['id']; ?>"
                            data-nombre="<?php echo htmlspecialchars($instructor['nombres'] . ' ' . $instructor['apellidos']); ?>"
                            data-correo="<?php echo htmlspecialchars($instructor['correo']); ?>"
                            data-telefono="<?php echo htmlspecialchars($instructor['telefono'] ?? ''); ?>">
                        <i class="bi bi-pencil"></i> Editar Datos
                    </button>
                    
                    <button class="btn btn-info cambiar-estado-instructor" 
                            data-id="<?php echo $instructor['id']; ?>"
                            data-nombre="<?php echo htmlspecialchars($instructor['nombres'] . ' ' . $instructor['apellidos']); ?>"
                            data-estado="<?php echo $instructor['id_estado']; ?>">
                        <i class="bi bi-toggle-on"></i> Cambiar Estado
                    </button>
                    
                    <?php if ($instructor['id_rol'] == 3 && $total_fichas > 0): ?>
                    <button class="btn btn-secondary gestionar-fichas" 
                            data-id="<?php echo $instructor['id']; ?>"
                            data-nombre="<?php echo htmlspecialchars($instructor['nombres'] . ' ' . $instructor['apellidos']); ?>">
                        <i class="bi bi-folder-symlink"></i> Gestionar Fichas
                    </button>
                    <?php endif; ?>
                    
                    <button class="btn btn-success generar-reportes" 
                            data-id="<?php echo $instructor['id']; ?>"
                            data-nombre="<?php echo htmlspecialchars($instructor['nombres'] . ' ' . $instructor['apellidos']); ?>">
                        <i class="bi bi-file-earmark-text"></i> Reportes
                    </button>
                </div>
            </div>
        </div>

        <!-- Materias especializadas -->
        <?php if (!empty($materias_especializadas)): ?>
        <div class="card mt-3">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0">
                    <i class="bi bi-book"></i> Materias Especializadas
                </h6>
            </div>
            <div class="card-body">
                <?php foreach ($materias_especializadas as $materia): ?>
                    <div class="mb-2">
                        <span class="badge bg-primary"><?php echo htmlspecialchars($materia['materia']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Fichas asignadas -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0">
                    <i class="bi bi-folder-check"></i> Fichas Asignadas (<?php echo $total_fichas; ?>)
                </h6>
            </div>
            <div class="card-body">
                <?php if (!empty($fichas)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Ficha</th>
                                    <th>Programa</th>
                                    <th>Tipo</th>
                                    <th>Jornada</th>
                                    <th>Materias</th>
                                    <th>Aprendices</th>
                                    <th>Horas/Sem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fichas as $ficha): ?>
                                    <tr>
                                        <td><strong><?php echo $ficha['id_ficha']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($ficha['programa']); ?></td>
                                        <td>
                                            <span class="badge bg-primary">
                                                <?php echo htmlspecialchars($ficha['tipo_formacion']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($ficha['jornada']); ?></td>
                                        <td>
                                            <span class="badge bg-success"><?php echo $ficha['materias_asignadas']; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo $ficha['aprendices_asignados']; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo number_format($ficha['horas_semanales'], 1); ?>h</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación de fichas -->
                    <?php if ($total_paginas_fichas > 1): ?>
                        <nav aria-label="Paginación de fichas">
                            <ul class="pagination pagination-sm justify-content-center">
                                <?php if ($pagina > 1): ?>
                                    <li class="page-item">
                                        <button class="page-link" onclick="cambiarPaginaFichas(<?php echo $id_instructor; ?>, <?php echo ($pagina - 1); ?>)">
                                            <i class="bi bi-chevron-left"></i>
                                        </button>
                                    </li>
                                <?php endif; ?>

                                <?php
                                $inicio_pag = max(1, $pagina - 2);
                                $fin_pag = min($total_paginas_fichas, $inicio_pag + 4);
                                
                                if ($fin_pag - $inicio_pag < 4) {
                                    $inicio_pag = max(1, $fin_pag - 4);
                                }

                                for ($i = $inicio_pag; $i <= $fin_pag; $i++): ?>
                                    <li class="page-item <?php echo ($i == $pagina) ? 'active' : ''; ?>">
                                        <button class="page-link" onclick="cambiarPaginaFichas(<?php echo $id_instructor; ?>, <?php echo $i; ?>)">
                                            <?php echo $i; ?>
                                        </button>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($pagina < $total_paginas_fichas): ?>
                                    <li class="page-item">
                                        <button class="page-link" onclick="cambiarPaginaFichas(<?php echo $id_instructor; ?>, <?php echo ($pagina + 1); ?>)">
                                            <i class="bi bi-chevron-right"></i>
                                        </button>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-folder-x display-4 text-muted"></i>
                        <p class="text-muted mt-2">No hay fichas asignadas a este instructor</p>
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

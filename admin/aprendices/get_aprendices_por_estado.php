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

$estado = $_GET['estado'] ?? '';

if (empty($estado)) {
    echo json_encode(['success' => false, 'message' => 'Estado requerido']);
    exit;
}

try {
    // Obtener aprendices por estado
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
            j.jornada,
            COALESCE(AVG(au.nota), 0) as promedio_general,
            COUNT(au.id_actividad_user) as total_actividades,
            SUM(CASE WHEN au.nota >= 4.0 THEN 1 ELSE 0 END) as actividades_aprobadas,
            CASE 
                WHEN COUNT(au.id_actividad_user) > 0 
                THEN ROUND((SUM(CASE WHEN au.nota >= 4.0 THEN 1 ELSE 0 END) * 100.0 / COUNT(au.id_actividad_user)), 2)
                ELSE 0 
            END as porcentaje_aprobacion
        FROM usuarios u
        LEFT JOIN user_ficha uf ON u.id = uf.id_user AND uf.id_estado = 1
        LEFT JOIN fichas f ON uf.id_ficha = f.id_ficha
        LEFT JOIN formacion fo ON f.id_formacion = fo.id_formacion
        LEFT JOIN tipo_formacion tf ON fo.id_tipo_formacion = tf.id_tipo_formacion
        LEFT JOIN jornada j ON f.id_jornada = j.id_jornada
        LEFT JOIN estado e ON u.id_estado = e.id_estado
        LEFT JOIN actividades_user au ON u.id = au.id_user AND au.nota IS NOT NULL
        WHERE u.id_rol = 4 AND u.id_estado = ? AND u.nit = ?
        GROUP BY u.id, u.nombres, u.apellidos, u.correo, u.telefono, u.fecha_registro, 
                 u.id_estado, e.estado, uf.id_ficha, f.id_ficha, fo.nombre, tf.tipo_formacion, j.jornada
        ORDER BY u.nombres, u.apellidos
    ");
    $stmt->execute([$estado, $nit_usuario]);
    $aprendices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($aprendices)) {
        $mensaje = '';
        $icono = '';
        $color = '';

        switch ($estado) {
            case '1':
                $mensaje = 'No hay aprendices activos registrados.';
                $icono = 'bi-person-check';
                $color = 'success';
                break;
            case '6':
                $mensaje = 'No hay aprendices suspendidos.';
                $icono = 'bi-person-dash';
                $color = 'warning';
                break;
            case '7':
                $mensaje = 'No hay aprendices expulsados.';
                $icono = 'bi-person-x';
                $color = 'danger';
                break;
            default:
                $mensaje = 'No hay aprendices en este estado.';
                $icono = 'bi-person';
                $color = 'info';
        }

        echo json_encode([  
            'success' => true,
            'html' => "<div class=\"alert alert-{$color} text-center border-{$color}\">
                        <i class=\"bi {$icono} display-4 text-{$color}\"></i>
                        <h5 class=\"mt-3 text-{$color}\">Sin registros</h5>
                        <p class=\"text-muted\">{$mensaje}</p>
                       </div>"
        ]);
        exit;
    }

    // Generar HTML
    ob_start();
?>
    <div class="row">
        <?php foreach ($aprendices as $aprendiz): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 border-<?php echo ($estado == '1') ? 'success' : (($estado == '6') ? 'warning' : 'danger'); ?> shadow-sm aprendiz-card">
                    <div class="card-header bg-<?php echo ($estado == '1') ? 'success' : (($estado == '6') ? 'warning' : 'danger'); ?> text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold">
                                <i class="bi bi-person-badge"></i>
                                <?php echo htmlspecialchars($aprendiz['nombres'] . ' ' . $aprendiz['apellidos']); ?>
                            </h6>
                            <span class="badge bg-light text-dark">
                                <?php echo htmlspecialchars($aprendiz['estado']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="aprendiz-info">
                            <div class="info-item mb-2">
                                <i class="bi bi-person-vcard text-primary"></i>
                                <strong>Documento:</strong>
                                <span class="text-muted"><?php echo $aprendiz['id']; ?></span>
                            </div>
                            <div class="info-item mb-2">
                                <i class="bi bi-envelope text-primary"></i>
                                <strong>Correo:</strong>
                                <span class="text-muted"><?php echo htmlspecialchars($aprendiz['correo']); ?></span>
                            </div>
                            <div class="info-item mb-2">
                                <i class="bi bi-telephone text-primary"></i>
                                <strong>Teléfono:</strong>
                                <span class="text-muted"><?php echo htmlspecialchars($aprendiz['telefono'] ?? 'No registrado'); ?></span>
                            </div>
                            <div class="info-item mb-2">
                                <i class="bi bi-folder text-primary"></i>
                                <strong>Ficha:</strong>
                                <span class="text-muted"><?php echo $aprendiz['ficha_numero'] ?? 'Sin asignar'; ?></span>
                            </div>
                            <?php if ($aprendiz['programa_formacion']): ?>
                                <div class="info-item mb-3">
                                    <i class="bi bi-book text-primary"></i>
                                    <strong>Programa:</strong>
                                    <span class="text-muted"><?php echo htmlspecialchars($aprendiz['programa_formacion']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="row text-center mt-3">
                            <div class="col-6">
                                <div class="border-end">
                                    <h5 class="text-primary mb-0"><?php echo number_format($aprendiz['promedio_general'], 2); ?></h5>
                                    <small class="text-muted">Promedio</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <h5 class="text-success mb-0"><?php echo number_format($aprendiz['porcentaje_aprobacion'], 1); ?>%</h5>
                                <small class="text-muted">Aprobación</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <div class="d-grid gap-2">
                            <div class="row g-2">
                                <div class="col-6">
                                    <button class="btn btn-outline-primary btn-sm ver-detalles w-100"
                                        data-aprendiz="<?php echo $aprendiz['id']; ?>">
                                        <i class="bi bi-eye"></i> Detalles
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button class="btn btn-outline-success btn-sm generar-reporte-individual w-100"
                                        data-aprendiz="<?php echo $aprendiz['id']; ?>"
                                        data-nombre="<?php echo htmlspecialchars($aprendiz['nombres'] . ' ' . $aprendiz['apellidos']); ?>">
                                        <i class="bi bi-file-earmark-excel"></i> Excel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-4">
        <div class="alert alert-<?php echo ($estado == '1') ? 'success' : (($estado == '6') ? 'warning' : 'danger'); ?> border-<?php echo ($estado == '1') ? 'success' : (($estado == '6') ? 'warning' : 'danger'); ?>">
            <div class="d-flex align-items-center">
                <i class="bi bi-info-circle text-<?php echo ($estado == '1') ? 'success' : (($estado == '6') ? 'warning' : 'danger'); ?> me-2"></i>
                <div>
                    <strong class="text-<?php echo ($estado == '1') ? 'success' : (($estado == '6') ? 'warning' : 'danger'); ?>">Total:</strong>
                    <span class="text-muted"><?php echo count($aprendices); ?> aprendiz(es) en estado "<?php echo htmlspecialchars($aprendices[0]['estado']); ?>".</span>
                </div>
            </div>
        </div>
    </div>

    <style>
        .aprendiz-card {
            transition: all 0.3s ease;
        }

        .aprendiz-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(14, 74, 134, 0.15) !important;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9em;
        }

        .info-item i {
            width: 16px;
            flex-shrink: 0;
        }

        .aprendiz-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
    </style>
<?php
    $html = ob_get_clean();

    echo json_encode([
        'success' => true,
        'html' => $html
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar aprendices: ' . $e->getMessage()
    ]);
}
?>
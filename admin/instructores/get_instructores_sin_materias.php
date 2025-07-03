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

try {
    // Obtener instructores sin materias asignadas usando LEFT JOIN
    $stmt = $conexion->prepare("
        SELECT 
            u.id,
            u.nombres,
            u.apellidos,
            u.correo,
            u.telefono,
            u.id_rol,
            r.rol,
            u.fecha_registro
        FROM usuarios u
        LEFT JOIN roles r ON u.id_rol = r.id_rol
        LEFT JOIN materia_instructor mi ON u.id = mi.id_instructor
        WHERE u.id_rol IN (3, 5) 
        AND u.id_estado = 1 
        AND u.nit = ?
        AND mi.id_instructor IS NULL
        ORDER BY u.nombres, u.apellidos
    ");
    $stmt->execute([$nit_usuario]);
    $instructores_sin_materias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($instructores_sin_materias)) {
        echo json_encode([
            'success' => true,
            'html' => '<div class="alert alert-info text-center border-primary">
                        <i class="bi bi-check-circle display-4 text-primary"></i>
                        <h5 class="mt-3 text-primary">¡Excelente!</h5>
                        <p class="text-muted">Todos los instructores tienen materias asignadas.</p>
                       </div>'
        ]);
        exit;
    }

    // Generar HTML
    ob_start();
?>
    <div class="row">
        <?php foreach ($instructores_sin_materias as $instructor): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-70 border-primary shadow-sm instructor-card">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold">
                                <i class="bi bi-person-exclamation"></i>
                                <?php echo htmlspecialchars($instructor['nombres'] . ' ' . $instructor['apellidos']); ?>
                            </h6>
                            <span class="badge bg-light text-primary">
                                <?php echo ($instructor['id_rol'] == 3) ? 'Normal' : 'Transversal'; ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="instructor-info">
                            <div class="info-item mb-2">
                                <i class="bi bi-person-vcard text-primary"></i>
                                <strong>Documento:</strong>
                                <span class="text-muted"><?php echo $instructor['id']; ?></span>
                            </div>
                            <div class="info-item mb-2">
                                <i class="bi bi-envelope text-primary"></i>
                                <strong>Correo:</strong>
                                <span class="text-muted"><?php echo htmlspecialchars($instructor['correo']); ?></span>
                            </div>
                            <div class="info-item mb-2">
                                <i class="bi bi-telephone text-primary"></i>
                                <strong>Teléfono:</strong>
                                <span class="text-muted"><?php echo htmlspecialchars($instructor['telefono'] ?? 'No registrado'); ?></span>
                            </div>
                            <div class="info-item mb-3">
                                <i class="bi bi-calendar text-primary"></i>
                                <strong>Registro:</strong>
                                <span class="text-muted"><?php echo date('d/m/Y', strtotime($instructor['fecha_registro'])); ?></span>
                            </div>
                        </div>

                        <div class="alert alert-warning py-2 mb-3 border-warning">
                            <small>
                                <i class="bi bi-exclamation-triangle text-warning"></i>
                                <strong>Sin materias especializadas</strong>
                            </small>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary btn-sm asignar-materias"
                                data-instructor="<?php echo $instructor['id']; ?>"
                                data-nombre="<?php echo htmlspecialchars($instructor['nombres'] . ' ' . $instructor['apellidos']); ?>">
                                <i class="bi bi-plus-circle"></i> Asignar Materias
                            </button>
                            <button class="btn btn-outline-primary btn-sm ver-detalles"
                                data-instructor="<?php echo $instructor['id']; ?>">
                                <i class="bi bi-eye"></i> Ver Detalles
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-4">
        <div class="alert alert-primary border-primary">
            <div class="d-flex align-items-center">
                <i class="bi bi-info-circle text-primary me-2"></i>
                <div>
                    <strong class="text-primary">Total:</strong>
                    <span class="text-muted"><?php echo count($instructores_sin_materias); ?> instructor(es) sin materias asignadas.</span>
                </div>
            </div>
        </div>
    </div>

    <style>
        .instructor-card {
            transition: all 0.3s ease;
        }

        .instructor-card:hover {
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

        .instructor-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .btn-primary {
            background-color: #0e4a86;
            border-color: #0e4a86;
        }

        .btn-primary:hover {
            background-color: #1765b4;
            border-color: #1765b4;
        }

        .btn-outline-primary {
            color: #0e4a86;
            border-color: #0e4a86;
        }

        .btn-outline-primary:hover {
            background-color: #0e4a86;
            border-color: #0e4a86;
        }

        .text-primary {
            color: #0e4a86 !important;
        }

        .bg-primary {
            background-color: #0e4a86 !important;
        }

        .border-primary {
            border-color: #0e4a86 !important;
        }

        .alert-primary {
            background-color: rgba(14, 74, 134, 0.1);
            border-color: #0e4a86;
            color: #0e4a86;
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
        'message' => 'Error al cargar instructores: ' . $e->getMessage()
    ]);
}
?>
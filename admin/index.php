<?php
session_start();
if ($_SESSION['rol'] !== 2) {
    header('Location: includes/exit.php?motivo=acceso-denegado');
    exit;
}

require_once '../conexion/conexion.php';
$db = new Database();
$conexion = $db->connect();

if (!$conexion || !($conexion instanceof PDO)) {
    die("Error de conexión a la base de datos.");
}

// --------- Indicadores principales ---------
$total_usuarios = $conexion->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$total_aprendices = $conexion->query("SELECT COUNT(*) FROM usuarios WHERE id_rol = 4")->fetchColumn();
$total_instructores = $conexion->query("SELECT COUNT(*) FROM usuarios WHERE id_rol IN (3,5)")->fetchColumn();
$total_fichas = $conexion->query("SELECT COUNT(*) FROM fichas WHERE id_estado = 1")->fetchColumn();
$total_materias = $conexion->query("SELECT COUNT(*) FROM materias")->fetchColumn();
$total_ambientes = $conexion->query("SELECT COUNT(*) FROM ambientes WHERE ambiente IS NOT NULL AND ambiente <> ''")->fetchColumn();

// --------- Últimos logs ---------
$ultimos_logs = $conexion->query("
    SELECT 
        logs.*, 
        u.id AS usuario_id, 
        u.nombres, 
        u.apellidos, 
        u.id_rol, 
        r.rol AS nombre_rol
    FROM logs_acciones logs
    LEFT JOIN usuarios u ON logs.usuario_accion = u.id
    LEFT JOIN roles r ON u.id_rol = r.id_rol
    ORDER BY logs.fecha DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// --------- Últimos usuarios creados ---------
$ultimos_usuarios = $conexion->query("
    SELECT u.id, u.nombres, u.apellidos, u.correo, r.rol AS nombre_rol, u.fecha_registro
    FROM usuarios u
    LEFT JOIN roles r ON u.id_rol = r.id_rol
    ORDER BY u.fecha_registro DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Panel Administrativo - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/main.css">
    <link rel="stylesheet" href="styles/sidebard.css">
</head>

<body>
    <div class="wrapper">
        <?php include 'includes/sidebard.php'; ?>
        <main class="main-content">
            <header class="content-header">
                <h1>Dashboard</h1>
            </header>
            <div class="content">
                <!-- Tarjetas principales -->
                <div class="row dashboard-cards">
                    <div class="col-md-2 mb-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h6 class="card-title">Usuarios</h6>
                                <p class="display-6 fw-bold"><?php echo $total_usuarios; ?></p>
                                <i class="bi bi-people-fill text-primary fs-2"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h6 class="card-title">Aprendices</h6>
                                <p class="display-6 fw-bold"><?php echo $total_aprendices; ?></p>
                                <i class="bi bi-person-badge-fill text-success fs-2"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h6 class="card-title">Instructores</h6>
                                <p class="display-6 fw-bold"><?php echo $total_instructores; ?></p>
                                <i class="bi bi-person-video2 text-info fs-2"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h6 class="card-title">Fichas activas</h6>
                                <p class="display-6 fw-bold"><?php echo $total_fichas; ?></p>
                                <i class="bi bi-folder-symlink text-warning fs-2"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h6 class="card-title">Materias</h6>
                                <p class="display-6 fw-bold"><?php echo $total_materias; ?></p>
                                <i class="bi bi-journal-code text-secondary fs-2"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h6 class="card-title">Ambientes</h6>
                                <p class="display-6 fw-bold"><?php echo $total_ambientes; ?></p>
                                <i class="bi bi-building text-danger fs-2"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Últimos logs del sistema -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                <span>Últimos 10 movimientos del sistema</span>
                                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#exportLogsModal">
                                    <i class="bi bi-download"></i> Exportar Excel
                                </button>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover table-bordered align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Usuario (ID)</th>
                                                <th>Rol</th>
                                                <th>Acción</th>
                                                <th>Entidad</th>
                                                <th>Descripción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($ultimos_logs as $log): ?>
                                                <tr>
                                                    <td><?php echo date('Y-m-d H:i:s', strtotime($log['fecha'])); ?></td>
                                                    <td>
                                                        <?php echo htmlspecialchars($log['nombres'] . ' ' . $log['apellidos']); ?>
                                                        <span class="text-muted">(<?php echo $log['usuario_id']; ?>)</span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info"><?php echo htmlspecialchars($log['nombre_rol']); ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?php
                                                                                switch ($log['accion']) {
                                                                                    case 'CREAR':
                                                                                        echo 'success';
                                                                                        break;
                                                                                    case 'EDITAR':
                                                                                        echo 'warning';
                                                                                        break;
                                                                                    case 'ELIMINAR':
                                                                                        echo 'danger';
                                                                                        break;
                                                                                    default:
                                                                                        echo 'secondary';
                                                                                }
                                                                                ?>">
                                                            <?php echo htmlspecialchars($log['accion']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($log['entidad']); ?></td>
                                                    <td><?php echo htmlspecialchars($log['descripcion']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    <?php if (empty($ultimos_logs)): ?>
                                        <div class="text-center text-muted p-3">No hay movimientos recientes.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Últimos usuarios creados -->
                <div class="row">
                    <div class="col-12">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white fw-bold">
                                Últimos usuarios creados
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Fecha</th>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Correo</th>
                                            <th>Rol</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($ultimos_usuarios as $u): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y H:i', strtotime($u['fecha_registro'])); ?></td>
                                                <td><?php echo htmlspecialchars($u['id']); ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($u['nombres'] . ' ' . $u['apellidos']); ?>
                                                </td>
                                                <td>
                                                    <span class="text-muted small"><?php echo htmlspecialchars($u['correo']); ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo htmlspecialchars($u['nombre_rol']); ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($ultimos_usuarios)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">No hay usuarios recientes.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MODAL: Exportar logs -->
                <div class="modal fade" id="exportLogsModal" tabindex="-1" aria-labelledby="exportLogsModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <form action="exportar_logs.php" method="get" target="_blank" class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title" id="exportLogsModalLabel">Exportar logs a Excel</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="fecha_inicio" class="form-label">Fecha inicio</label>
                                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" required>
                                </div>
                                <div class="mb-3">
                                    <label for="fecha_fin" class="form-label">Fecha fin</label>
                                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-success"><i class="bi bi-file-earmark-excel"></i> Exportar Excel</button>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- FIN MODAL -->

            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
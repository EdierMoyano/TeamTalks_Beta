<?php
session_start();

// Verificar sesión y rol de administrador
if (!isset($_SESSION['documento']) || $_SESSION['rol'] !== 2) {
    header('Location: ../../includes/exit.php');
    exit;
}

require_once '../../conexion/conexion.php';
require_once '../../includes/functions.php';

// Crear instancia de la conexión
$db = new Database();
$conexion = $db->connect();

if (!$conexion || !($conexion instanceof PDO)) {
    die("Error: No se pudo establecer la conexión a la base de datos");
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
        die("Error: No se pudo obtener el NIT del usuario. Contacte al administrador.");
    }
} catch (PDOException $e) {
    die("Error al obtener datos del usuario: " . $e->getMessage());
}

// Inicializar mensaje de alerta
$alertMessage = '';
$alertType = '';

// Procesar actualización de datos del instructor
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'actualizar_instructor') {
    $id_instructor = $_POST['id_instructor'];
    $correo = $_POST['correo'];
    $telefono = $_POST['telefono'];

    try {
        $stmt = $conexion->prepare("UPDATE usuarios SET correo = ?, telefono = ? WHERE id = ? AND id_rol IN (3, 5)");
        $stmt->execute([$correo, $telefono, $id_instructor]);

        $alertMessage = "Datos del instructor actualizados correctamente";
        $alertType = "success";
    } catch (PDOException $e) {
        $alertMessage = "Error al actualizar instructor: " . $e->getMessage();
        $alertType = "danger";
    }
}

// Obtener parámetros de filtro y paginación
$filtro_rol = isset($_GET['filtro_rol']) ? $_GET['filtro_rol'] : '';
$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$instructores_por_pagina = 6;
$offset = ($pagina_actual - 1) * $instructores_por_pagina;

// Construir consulta con filtros
$where_conditions = ["u.id_rol IN (3, 5)", "u.id_estado = 1", "u.nit = ?"];
$params = [$nit_usuario];

if (!empty($filtro_rol)) {
    $where_conditions[] = "u.id_rol = ?";
    $params[] = $filtro_rol;
}

if (!empty($busqueda)) {
    $where_conditions[] = "(u.nombres LIKE ? OR u.apellidos LIKE ? OR u.correo LIKE ? OR u.id LIKE ?)";
    $busqueda_param = "%$busqueda%";
    $params = array_merge($params, [$busqueda_param, $busqueda_param, $busqueda_param, $busqueda_param]);
}

$where_clause = implode(" AND ", $where_conditions);

// Obtener total de instructores para paginación
try {
    $count_query = "SELECT COUNT(*) as total FROM usuarios u WHERE $where_clause";
    $stmt = $conexion->prepare($count_query);
    $stmt->execute($params);
    $total_instructores = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_paginas = ceil($total_instructores / $instructores_por_pagina);
} catch (PDOException $e) {
    $total_instructores = 0;
    $total_paginas = 0;
}

// Obtener instructores con paginación
$instructores = [];
try {
    $query = "
        SELECT 
            u.id,
            u.nombres,
            u.apellidos,
            u.correo,
            u.telefono,
            u.id_rol,
            r.rol,
            COALESCE(fichas_count.total, 0) as fichas_asignadas,
            COALESCE(materias_count.total, 0) as materias_especializadas
        FROM usuarios u
        LEFT JOIN roles r ON u.id_rol = r.id_rol
        LEFT JOIN (
            SELECT id_instructor, COUNT(DISTINCT id_ficha) as total
            FROM materia_ficha
            GROUP BY id_instructor
        ) fichas_count ON u.id = fichas_count.id_instructor
        LEFT JOIN (
            SELECT id_instructor, COUNT(DISTINCT id_materia) as total
            FROM materia_instructor
            GROUP BY id_instructor
        ) materias_count ON u.id = materias_count.id_instructor
        WHERE $where_clause
        ORDER BY u.nombres, u.apellidos
        LIMIT $instructores_por_pagina OFFSET $offset
    ";

    $stmt = $conexion->prepare($query);
    $stmt->execute($params);
    $instructores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $alertMessage = "Error al cargar instructores: " . $e->getMessage();
    $alertType = "danger";
    $instructores = []; // Asegura que siempre sea un array
}

// Estadísticas
$stats = [
    'total_instructores' => 0,
    'instructores_normales' => 0,
    'instructores_transversales' => 0,
    'instructores_sin_materias' => 0,
    'fichas_activas' => 0
];

try {
    // Total instructores
    $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM usuarios WHERE id_rol IN (3, 5) AND id_estado = 1 AND nit = ?");
    $stmt->execute([$nit_usuario]);
    $stats['total_instructores'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Instructores normales
    $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM usuarios WHERE id_rol = 3 AND id_estado = 1 AND nit = ?");
    $stmt->execute([$nit_usuario]);
    $stats['instructores_normales'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Instructores transversales
    $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM usuarios WHERE id_rol = 5 AND id_estado = 1 AND nit = ?");
    $stmt->execute([$nit_usuario]);
    $stats['instructores_transversales'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Instructores sin materias asignadas
    $stmt = $conexion->prepare("
        SELECT COUNT(*) as total 
        FROM usuarios u 
        WHERE u.id_rol IN (3, 5) 
        AND u.id_estado = 1 
        AND u.nit = ? 
        AND u.id NOT IN (SELECT DISTINCT id_instructor FROM materia_instructor WHERE id_instructor IS NOT NULL)
    ");
    $stmt->execute([$nit_usuario]);
    $stats['instructores_sin_materias'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Fichas activas
    $stmt = $conexion->query("SELECT COUNT(*) as total FROM fichas WHERE id_estado = 1");
    $stats['fichas_activas'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
} catch (PDOException $e) {
    // Mantener valores por defecto
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Instructores - TeamTalks</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../styles/sidebard.css">
    <link rel="stylesheet" href="../styles/main.css">
</head>

<body>
    <div class="wrapper">
        <?php include '../includes/sidebard.php'; ?>
        <div class="main-content">
            <div class="container mt-4">
                <!-- Dashboard de estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-3">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="bi bi-people display-6 mb-2"></i>
                                            <h3 class="mb-0"><?php echo $stats['total_instructores']; ?></h3>
                                            <small>Total Instructores</small>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="bi bi-person-badge display-6 mb-2"></i>
                                            <h3 class="mb-0"><?php echo $stats['instructores_normales']; ?></h3>
                                            <small>Normales</small>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="bi bi-person-gear display-6 mb-2"></i>
                                            <h3 class="mb-0"><?php echo $stats['instructores_transversales']; ?></h3>
                                            <small>Transversales</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="bi bi-person-exclamation display-6 mb-2"></i>
                                            <h3 class="mb-0"><?php echo $stats['instructores_sin_materias']; ?></h3>
                                            <small>Sin Materias</small>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="bi bi-folder-check display-6 mb-2"></i>
                                            <h3 class="mb-0"><?php echo $stats['fichas_activas']; ?></h3>
                                            <small>Fichas</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones de navegación -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex gap-3 justify-content-center">
                                    <button class="btn btn-primary active" id="btnTodosInstructores" onclick="mostrarSeccion('todos')">
                                        <i class="bi bi-people"></i> Todos los Instructores
                                    </button>
                                    <button class="btn btn-outline-primary" id="btnSinMaterias" onclick="mostrarSeccion('sin-materias')">
                                        <i class="bi bi-person-exclamation"></i> Sin Materias Asignadas (<?php echo $stats['instructores_sin_materias']; ?>)
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sección: Todos los instructores -->
                <div id="seccion-todos" class="seccion-instructores">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0">
                                <i class="bi bi-person-lines-fill"></i> Gestión de Instructores
                            </h4>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($alertMessage)): ?>
                                <div class="alert alert-<?php echo $alertType; ?> alert-dismissible fade show" role="alert">
                                    <?php echo $alertMessage; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <!-- Filtros y buscador -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                                        <input type="text" class="form-control" id="buscarInstructor"
                                            placeholder="Buscar por nombre, documento o correo..."
                                            value="<?php echo htmlspecialchars($busqueda); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select" id="filtroRol">
                                        <option value="">Todos los tipos</option>
                                        <option value="3" <?php echo ($filtro_rol == '3') ? 'selected' : ''; ?>>Instructor Normal</option>
                                        <option value="5" <?php echo ($filtro_rol == '5') ? 'selected' : ''; ?>>Instructor Transversal</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-primary w-100" onclick="limpiarFiltros()">
                                        <i class="bi bi-arrow-clockwise"></i> Limpiar
                                    </button>
                                </div>
                            </div>

                            <!-- Lista de instructores -->
                            <div class="row" id="instructoresContainer">
                                <?php foreach ($instructores as $instructor): ?>
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card h-100 border-<?php echo ($instructor['id_rol'] == 3) ? 'primary' : 'success'; ?>">
                                            <div class="card-header bg-<?php echo ($instructor['id_rol'] == 3) ? 'primary' : 'success'; ?> text-white">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <h6 class="mb-0 fw-bold">
                                                        <i class="bi bi-person-badge"></i>
                                                        <?php echo htmlspecialchars($instructor['nombres'] . ' ' . $instructor['apellidos']); ?>
                                                    </h6>
                                                    <span class="badge bg-light text-dark">
                                                        <?php echo ($instructor['id_rol'] == 3) ? 'Normal' : 'Transversal'; ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <p class="card-text">
                                                    <small class="text-muted">
                                                        <i class="bi bi-person-vcard"></i> <strong>Documento:</strong> <?php echo $instructor['id']; ?><br>
                                                        <i class="bi bi-envelope"></i> <strong>Correo:</strong> <?php echo htmlspecialchars($instructor['correo']); ?><br>
                                                        <i class="bi bi-telephone"></i> <strong>Teléfono:</strong> <?php echo htmlspecialchars($instructor['telefono'] ?? 'No registrado'); ?>
                                                    </small>
                                                </p>

                                                <div class="row text-center mt-3">
                                                    <div class="col-6">
                                                        <div class="border-end">
                                                            <h5 class="text-primary mb-0"><?php echo $instructor['fichas_asignadas']; ?></h5>
                                                            <small class="text-muted">Fichas</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <h5 class="text-success mb-0"><?php echo $instructor['materias_especializadas']; ?></h5>
                                                        <small class="text-muted">Materias</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-footer bg-transparent">
                                                <div class="d-grid">
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

                            <!-- Paginación -->
                            <?php if ($total_paginas > 1): ?>
                                <nav aria-label="Paginación de instructores">
                                    <ul class="pagination justify-content-center">
                                        <!-- Botón anterior -->
                                        <?php if ($pagina_actual > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?pagina=<?php echo ($pagina_actual - 1); ?>&filtro_rol=<?php echo $filtro_rol; ?>&busqueda=<?php echo urlencode($busqueda); ?>">
                                                    <i class="bi bi-chevron-left"></i> Anterior
                                                </a>
                                            </li>
                                        <?php endif; ?>

                                        <!-- Números de página con límite de 5 -->
                                        <?php
                                        $inicio_pag = max(1, $pagina_actual - 2);
                                        $fin_pag = min($total_paginas, $inicio_pag + 4);

                                        // Ajustar inicio si estamos cerca del final
                                        if ($fin_pag - $inicio_pag < 4) {
                                            $inicio_pag = max(1, $fin_pag - 4);
                                        }

                                        for ($i = $inicio_pag; $i <= $fin_pag; $i++): ?>
                                            <li class="page-item <?php echo ($i == $pagina_actual) ? 'active' : ''; ?>">
                                                <a class="page-link" href="?pagina=<?php echo $i; ?>&filtro_rol=<?php echo $filtro_rol; ?>&busqueda=<?php echo urlencode($busqueda); ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>

                                        <!-- Botón siguiente -->
                                        <?php if ($pagina_actual < $total_paginas): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?pagina=<?php echo ($pagina_actual + 1); ?>&filtro_rol=<?php echo $filtro_rol; ?>&busqueda=<?php echo urlencode($busqueda); ?>">
                                                    Siguiente <i class="bi bi-chevron-right"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>

                            <?php if (empty($instructores)): ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-person-x display-1 text-muted"></i>
                                    <h5 class="text-muted mt-3">No se encontraron instructores</h5>
                                    <p class="text-muted">Intenta ajustar los filtros de búsqueda</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Sección: Instructores sin materias -->
                <div id="seccion-sin-materias" class="seccion-instructores" style="display: none;">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0">
                                <i class="bi bi-person-exclamation"></i> Instructores Sin Materias Asignadas
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-primary border-primary">
                                <i class="bi bi-info-circle"></i>
                                <strong>Información:</strong> Estos instructores no tienen materias especializadas asignadas en el sistema.
                            </div>
                            
                            <div id="instructoresSinMateriasContainer">
                                <!-- Se cargará dinámicamente -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para ver detalles del instructor -->
    <div class="modal fade" id="detallesInstructorModal" tabindex="-1" aria-labelledby="detallesInstructorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="detallesInstructorModalLabel">Detalles del Instructor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detallesInstructorContent">
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para editar instructor -->
    <div class="modal fade" id="editarInstructorModal" tabindex="-1" aria-labelledby="editarInstructorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editarInstructorModalLabel">Editar Datos del Instructor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST" id="editarInstructorForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="actualizar_instructor">
                        <input type="hidden" name="id_instructor" id="edit_id_instructor">

                        <div class="mb-3">
                            <label class="form-label">Instructor:</label>
                            <p class="fw-bold" id="edit_instructor_nombre"></p>
                        </div>

                        <div class="mb-3">
                            <label for="edit_correo" class="form-label">Correo Electrónico *</label>
                            <input type="email" class="form-control" id="edit_correo" name="correo" required>
                        </div>

                        <div class="mb-3">
                            <label for="edit_telefono" class="form-label">Número de Teléfono</label>
                            <input type="tel" class="form-control" id="edit_telefono" name="telefono">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar Datos</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para cambiar estado -->
    <div class="modal fade" id="cambiarEstadoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">Cambiar Estado del Instructor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="formCambiarEstado">
                    <div class="modal-body">
                        <input type="hidden" id="estado_id_instructor" name="id_instructor">
                        
                        <div class="mb-3">
                            <label class="form-label">Instructor:</label>
                            <p class="fw-bold" id="estado_instructor_nombre"></p>
                        </div>

                        <div class="mb-3">
                            <label for="nuevo_estado" class="form-label">Nuevo Estado:</label>
                            <select class="form-select" id="nuevo_estado" name="nuevo_estado" required>
                                <option value="">Seleccionar estado...</option>
                                <option value="1">Activo</option>
                                <option value="2">Inactivo</option>
                            </select>
                        </div>

                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Atención:</strong> Cambiar el estado del instructor puede afectar sus asignaciones actuales.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-info">Cambiar Estado</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para gestionar fichas -->
    <div class="modal fade" id="gestionarFichasModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title" id="gestionarFichasModalLabel">Gestionar Fichas del Instructor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="gestionarFichasContent">
                    <!-- Se cargará dinámicamente -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para reportes -->
    <div class="modal fade" id="reportesModal" tabindex="-1" aria-labelledby="reportesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" id="reportesModalContent">
            <!-- Se cargará dinámicamente -->
        </div>
    </div>
</div>

    <!-- Modal para asignar materias -->
    <div class="modal fade" id="asignarMateriasModal" tabindex="-1" aria-labelledby="asignarMateriasModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="asignarMateriasModalLabel">Asignar Materias al Instructor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="asignarMateriasContent">
                    <!-- El contenido se cargará dinámicamente -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/sidebard.js"></script>

    <!-- Agregar el manejador de materias -->
    <script src="materias-handler.js"></script>
    <!-- Agregar el nuevo manejador de gestión de instructores -->
    <script src="instructor-management-handler.js"></script>

    <script>
        let instructoresData = [];
        let paginaActual = 1;
        const instructoresPorPagina = 6;

        // Utilidad: limpiar backdrop huérfano si queda (por modales dinámicos)
        function limpiarBackdrop() {
            setTimeout(() => {
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                document.body.classList.remove('modal-open');
                document.body.style = '';
            }, 350);
        }

        // Cargar datos iniciales
        document.addEventListener("DOMContentLoaded", function() {
            cargarInstructoresData();

            // Event listeners para filtros en tiempo real
            const buscarInput = document.getElementById('buscarInstructor');
            if (buscarInput) {
                buscarInput.addEventListener('input', filtrarInstructoresDebounce);
            }
            const filtroRol = document.getElementById('filtroRol');
            if (filtroRol) {
                filtroRol.addEventListener('change', filtrarInstructoresDebounce);
            }

            // Inicializar tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Fix: Asociar limpieza de backdrop al cerrar cualquier modal
            document.querySelectorAll('.modal').forEach(modalEl => {
                modalEl.addEventListener('hidden.bs.modal', limpiarBackdrop);
            });
        });

        // Mostrar sección específica
        function mostrarSeccion(seccion) {
            document.querySelectorAll('.seccion-instructores').forEach(el => {
                el.style.display = 'none';
            });
            document.querySelectorAll('#btnTodosInstructores, #btnSinMaterias').forEach(btn => {
                btn.classList.remove('active');
                btn.classList.add('btn-outline-primary');
                btn.classList.remove('btn-primary');
            });

            if (seccion === 'todos') {
                document.getElementById('seccion-todos').style.display = 'block';
                document.getElementById('btnTodosInstructores').classList.add('active', 'btn-primary');
                document.getElementById('btnTodosInstructores').classList.remove('btn-outline-primary');
            } else if (seccion === 'sin-materias') {
                document.getElementById('seccion-sin-materias').style.display = 'block';
                document.getElementById('btnSinMaterias').classList.add('active', 'btn-primary');
                document.getElementById('btnSinMaterias').classList.remove('btn-outline-primary');
                cargarInstructoresSinMaterias();
            }
        }

        // Cargar instructores sin materias
        async function cargarInstructoresSinMaterias() {
            try {
                const response = await fetch('get_instructores_sin_materias.php');
                const data = await response.json();

                if (data.success) {
                    document.getElementById('instructoresSinMateriasContainer').innerHTML = data.html;
                    asignarEventListenersDinamicos();
                } else {
                    document.getElementById('instructoresSinMateriasContainer').innerHTML =
                        '<div class="alert alert-info">No hay instructores sin materias asignadas</div>';
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('instructoresSinMateriasContainer').innerHTML =
                    '<div class="alert alert-danger">Error al cargar instructores sin materias</div>';
            }
        }

        // NUEVA FUNCIÓN: Asignar event listeners a elementos dinámicos
        function asignarEventListenersDinamicos() {
            

            document.querySelectorAll('.asignar-materias').forEach(button => {
                button.removeEventListener('click', handleAsignarMaterias);
                button.addEventListener('click', handleAsignarMaterias);
            });

            document.querySelectorAll('.cambiar-estado-instructor').forEach(button => {
                button.removeEventListener('click', handleCambiarEstado);
                button.addEventListener('click', handleCambiarEstado);
            });

            document.querySelectorAll('.gestionar-fichas').forEach(button => {
                button.removeEventListener('click', handleGestionarFichas);
                button.addEventListener('click', handleGestionarFichas);
            });

            document.querySelectorAll('.generar-reportes').forEach(button => {
                button.removeEventListener('click', handleGenerarReportes);
                button.addEventListener('click', handleGenerarReportes);
            });
        }

        function handleVerDetalles(event) {
            const button = event.currentTarget;
            const idInstructor = button.getAttribute('data-instructor');
            cargarDetallesInstructor(idInstructor);
        }

        function handleAsignarMaterias(event) {
            const button = event.currentTarget;
            const idInstructor = button.getAttribute('data-instructor');
            const nombre = button.getAttribute('data-nombre');
            cargarFormularioMaterias(idInstructor, nombre);
        }

        function handleCambiarEstado(event) {
            const button = event.currentTarget;
            const idInstructor = button.getAttribute('data-id');
            const nombre = button.getAttribute('data-nombre');
            const estadoActual = button.getAttribute('data-estado');
            
            document.getElementById('estado_id_instructor').value = idInstructor;
            document.getElementById('estado_instructor_nombre').textContent = nombre;
            document.getElementById('nuevo_estado').value = estadoActual == 1 ? '2' : '1';
            
            const modal = new bootstrap.Modal(document.getElementById('cambiarEstadoModal'));
            modal.show();
        }

        function handleGestionarFichas(event) {
            const button = event.currentTarget;
            const idInstructor = button.getAttribute('data-id');
            const nombre = button.getAttribute('data-nombre');
            cargarGestionFichas(idInstructor, nombre);
        }

        function handleGenerarReportes(event) {
            const button = event.currentTarget; // <-- Cambia aquí
            const idInstructor = button.getAttribute('data-id');
            const nombre = button.getAttribute('data-nombre');
            cargarOpcionesReporte(idInstructor, nombre);
        }

        function cargarInstructoresData() {
            const instructorCards = document.querySelectorAll('#instructoresContainer .col-md-6');
            instructoresData = Array.from(instructorCards).map(card => {
                const nombre = card.querySelector('.card-header h6').textContent.trim();
                const documento = card.querySelector('.card-text').textContent.match(/Documento:\s*(\d+)/)?.[1] || '';
                const correo = card.querySelector('.card-text').textContent.match(/Correo:\s*([^\n]+)/)?.[1] || '';
                const rol = card.querySelector('.badge').textContent.trim();

                return {
                    element: card,
                    nombre: nombre.toLowerCase(),
                    documento: documento,
                    correo: correo.toLowerCase(),
                    rol: rol === 'Normal' ? '3' : '5',
                    visible: true
                };
            });
        }

        let debounceTimer = null;

        function filtrarInstructoresDebounce() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const busqueda = document.getElementById('buscarInstructor').value.trim();
                const filtroRol = document.getElementById('filtroRol').value;
                const params = new URLSearchParams();
                if (busqueda) params.append('busqueda', busqueda);
                if (filtroRol) params.append('filtro_rol', filtroRol);
                params.append('pagina', '1');
                window.location.search = params.toString();
            }, 400);
        }

        function limpiarFiltros() {
            document.getElementById('buscarInstructor').value = '';
            document.getElementById('filtroRol').value = '';
            window.location.search = '';
        }

        // Event listeners para elementos estáticos (sección "todos")
        document.addEventListener('click', function(event) {
            if (event.target.closest('.ver-detalles')) {
                const button = event.target.closest('.ver-detalles');
                const idInstructor = button.getAttribute('data-instructor');
                cargarDetallesInstructor(idInstructor);
            }

            if (event.target.closest('.editar-instructor')) {
                const button = event.target.closest('.editar-instructor');
                const idInstructor = button.getAttribute('data-id');
                const nombre = button.getAttribute('data-nombre');
                const correo = button.getAttribute('data-correo');
                const telefono = button.getAttribute('data-telefono');

                document.getElementById('edit_id_instructor').value = idInstructor;
                document.getElementById('edit_instructor_nombre').textContent = nombre;
                document.getElementById('edit_correo').value = correo;
                document.getElementById('edit_telefono').value = telefono || '';

                const modalEl = document.getElementById('editarInstructorModal');
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            }

            if (event.target.closest('.cambiar-estado-instructor')) {
                handleCambiarEstado(event);
            }

            if (event.target.closest('.gestionar-fichas')) {
                handleGestionarFichas(event);
            }

            if (event.target.closest('.generar-reportes')) {
                handleGenerarReportes(event);
            }
        });

        // Función para cargar detalles del instructor
        async function cargarDetallesInstructor(idInstructor, paginaFichas = 1) {
            try {
                const response = await fetch(`get_instructor_details.php?id_instructor=${idInstructor}&pagina_fichas=${paginaFichas}`);
                const data = await response.json();

                if (data.success) {
                    document.getElementById('detallesInstructorContent').innerHTML = data.html;
                    const modalEl = document.getElementById('detallesInstructorModal');
                    const modal = new bootstrap.Modal(modalEl);
                    modal.show();
                    
                    // Asignar event listeners a los nuevos elementos
                    asignarEventListenersDinamicos();
                } else {
                    Swal.fire('Error', 'Error al cargar los detalles del instructor', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'Error al cargar los detalles del instructor', 'error');
            }
        }

        // Cargar formulario de asignación de materias
        async function cargarFormularioMaterias(idInstructor, nombre) {
            try {
                const response = await fetch(`get_materias.php?id_instructor=${encodeURIComponent(idInstructor)}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                if (data.success) {
                    document.getElementById('asignarMateriasContent').innerHTML = data.html;
                    document.getElementById('asignarMateriasModalLabel').textContent = `Asignar Materias - ${nombre}`;

                    const modalEl = document.getElementById('asignarMateriasModal');
                    const modal = new bootstrap.Modal(modalEl);
                    modal.show();
                } else {
                    Swal.fire('Error', 'Error al cargar el formulario de materias: ' + (data.message || 'Error desconocido'), 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'Error al cargar el formulario de materias', 'error');
            }
        }

        // Cargar gestión de fichas
        async function cargarGestionFichas(idInstructor, nombre) {
            try {
                const response = await fetch(`get_fichas_instructor.php?id_instructor=${idInstructor}`);
                const data = await response.json();

                if (data.success) {
                    document.getElementById('gestionarFichasContent').innerHTML = data.html;
                    document.getElementById('gestionarFichasModalLabel').textContent = `Gestionar Fichas - ${nombre}`;
                    const modalEl = document.getElementById('gestionarFichasModal');
                    const modal = new bootstrap.Modal(modalEl);
                    modal.show();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'Error al cargar gestión de fichas', 'error');
            }
        }

        // Cargar opciones de reporte
        async function cargarOpcionesReporte(idInstructor, nombre) {
            try {
                const response = await fetch(`generar_reporte_instructor.php?id_instructor=${idInstructor}`);
                const data = await response.json();

                if (data.success) {
                    document.getElementById('reportesModalContent').innerHTML = data.html;

                    const modalEl = document.getElementById('reportesModal');
                    const modal = new bootstrap.Modal(modalEl);
                    modal.show();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'Error al cargar opciones de reporte', 'error');
            }
        }

        function cambiarPaginaFichas(idInstructor, pagina) {
            cargarDetallesInstructor(idInstructor, pagina);
        }

        // Función global para generar reportes (llamada desde el modal)
        async function generarReporte(tipo, idInstructor, idFicha = null) {
            try {
                let url = `procesar_reporte_instructor.php?id_instructor=${idInstructor}&tipo_reporte=${tipo}`;
                if (idFicha) {
                    url += `&id_ficha=${idFicha}`;
                }

                // Mostrar loading
                if (window.Swal) {
                    window.Swal.fire({
                        title: 'Generando reporte...',
                        text: 'Por favor espera',
                        allowOutsideClick: false,
                        didOpen: () => {
                            window.Swal.showLoading();
                        }
                    });
                }

                // Crear formulario temporal para forzar descarga
                const form = document.createElement('form');
                form.method = 'GET';
                form.action = url;
                form.target = '_blank';
                form.style.display = 'none';
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);

                // Cerrar loading después de un momento
                setTimeout(() => {
                    if (window.Swal) {
                        window.Swal.fire('Éxito', 'Reporte generado correctamente', 'success');
                    }
                }, 1000);
            } catch (error) {
                console.error('Error:', error);
                if (window.Swal) {
                    window.Swal.fire('Error', 'No se pudo generar el reporte', 'error');
                }
            }
        }

        // Función para generar reporte de ficha seleccionada
        function generarReporteFichaSeleccionada(idInstructor) {
            const selectFicha = document.getElementById('selectFichaIndividual');
            if (!selectFicha) {
                console.error('Select de ficha no encontrado');
                return;
            }
            
            const idFicha = selectFicha.value;
            
            if (!idFicha) {
                if (window.Swal) {
                    window.Swal.fire('Atención', 'Por favor selecciona una ficha', 'warning');
                } else {
                    alert('Por favor selecciona una ficha');
                }
                return;
            }
            
            generarReporte('ficha_individual', idInstructor, idFicha);
        }
    </script>

</body>
</html>

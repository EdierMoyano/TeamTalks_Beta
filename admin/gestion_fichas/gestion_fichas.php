<?php
session_start();

if ($_SESSION['rol'] !== 2) {
    header('Location: ../../includes/exit.php?');
    exit;
}
require_once '../../conexion/conexion.php';
require_once '../../includes/functions.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['documento'])) {
    header('Location: ../login/login.php');
    exit;
}

// Verificar rol de administrador
$user_role = $_SESSION['rol'] ?? '';
if (!in_array($user_role, [2])) {
    header('Location: ../index.php?error=acceso_denegado');
    $_SESSION['error_message'] = "Acceso denegado: No tienes permisos para acceder a esta sección.";
    exit;
}

// Inicializar mensaje de alerta
$alertMessage = '';
$alertType = '';

// Crear instancia de la conexión
$db = new Database();
$conexion = $db->connect();

// Verificar que la conexión sea válida
if (!$conexion || !($conexion instanceof PDO)) {
    die("Error: No se pudo establecer la conexión a la base de datos");
}

// Procesar asignación de materia a ficha
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'asignar_materia') {
    $id_ficha = $_POST['id_ficha'];
    $id_materia = $_POST['id_materia'];
    $id_instructor = !empty($_POST['id_instructor']) ? $_POST['id_instructor'] : null;
    $id_trimestre = $_POST['id_trimestre'];

    try {
        // Verificar si la materia ya está asignada a la ficha en el mismo trimestre
        $stmt = $conexion->prepare("
            SELECT id_materia_ficha FROM materia_ficha 
            WHERE id_ficha = ? AND id_materia = ? AND id_trimestre = ?
        ");
        $stmt->execute([$id_ficha, $id_materia, $id_trimestre]);

        if ($stmt->fetch()) {
            $alertMessage = "Esta materia ya está asignada a la ficha en este trimestre";
            $alertType = "warning";
        } else {
            // Insertar nueva asignación
            $stmt = $conexion->prepare("
                INSERT INTO materia_ficha (id_materia, id_ficha, id_instructor, id_trimestre) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$id_materia, $id_ficha, $id_instructor, $id_trimestre]);

            $alertMessage = "Materia asignada correctamente a la ficha";
            $alertType = "success";
        }
    } catch (PDOException $e) {
        $alertMessage = "Error al asignar materia: " . $e->getMessage();
        $alertType = "danger";
    }
}

// Procesar actualización de instructor
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'actualizar_instructor') {
    $id_materia_ficha = $_POST['id_materia_ficha'];
    $id_instructor = !empty($_POST['id_instructor']) ? $_POST['id_instructor'] : null;

    try {
        $stmt = $conexion->prepare("
            UPDATE materia_ficha SET id_instructor = ? WHERE id_materia_ficha = ?
        ");
        $stmt->execute([$id_instructor, $id_materia_ficha]);

        $alertMessage = "Instructor actualizado correctamente";
        $alertType = "success";
    } catch (PDOException $e) {
        $alertMessage = "Error al actualizar instructor: " . $e->getMessage();
        $alertType = "danger";
    }
}

// Procesar eliminación de asignación
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'eliminar_asignacion') {
    $id_materia_ficha = $_POST['id_materia_ficha'];

    try {
        $stmt = $conexion->prepare("DELETE FROM materia_ficha WHERE id_materia_ficha = ?");
        $stmt->execute([$id_materia_ficha]);

        $alertMessage = "Asignación eliminada correctamente";
        $alertType = "success";
    } catch (PDOException $e) {
        $alertMessage = "Error al eliminar asignación: " . $e->getMessage();
        $alertType = "danger";
    }
}

// Obtener estadísticas para el dashboard
$stats = [
    'fichas_activas' => 0,
    'materias_disponibles' => 0,
    'instructores' => 0,
    'asignaciones_totales' => 0,
    'aprendices_totales' => 0
];

try {
    // Contar fichas activas
    $stmt = $conexion->query("SELECT COUNT(*) as total FROM fichas WHERE id_estado = 1");
    if ($stmt) {
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['fichas_activas'] = $result['total'];
    }

    // Contar materias disponibles
    $stmt = $conexion->query("SELECT COUNT(*) as total FROM materias");
    if ($stmt) {
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['materias_disponibles'] = $result['total'];
    }

    // Contar instructores
    $stmt = $conexion->query("SELECT COUNT(*) as total FROM usuarios WHERE id_rol = 3 AND id_estado = 1");
    if ($stmt) {
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['instructores'] = $result['total'];
    }

    // Contar asignaciones totales
    $stmt = $conexion->query("SELECT COUNT(*) as total FROM materia_ficha");
    if ($stmt) {
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['asignaciones_totales'] = $result['total'];
    }

    // Contar aprendices totales
    $stmt = $conexion->query("SELECT COUNT(*) as total FROM usuarios WHERE id_rol = 4 AND id_estado = 1");
    if ($stmt) {
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['aprendices_totales'] = $result['total'];
    }
} catch (PDOException $e) {
    // En caso de error, mantener valores por defecto
}

// Obtener todas las fichas con información completa
$fichas = [];
try {
    $stmt = $conexion->query("
        SELECT 
            f.id_ficha,
            fo.nombre as programa,
            tf.tipo_formacion,
            j.jornada,
            e.estado,
            f.fecha_creac,
            CONCAT(u.nombres, ' ', u.apellidos) as instructor_lider,
            COUNT(DISTINCT mf.id_materia_ficha) as materias_asignadas,
            COUNT(DISTINCT uf.id_user) as aprendices_asignados
        FROM fichas f
        LEFT JOIN formacion fo ON f.id_formacion = fo.id_formacion
        LEFT JOIN tipo_formacion tf ON fo.id_tipo_formacion = tf.id_tipo_formacion
        LEFT JOIN jornada j ON f.id_jornada = j.id_jornada
        LEFT JOIN estado e ON f.id_estado = e.id_estado
        LEFT JOIN usuarios u ON f.id_instructor = u.id
        LEFT JOIN materia_ficha mf ON f.id_ficha = mf.id_ficha
        LEFT JOIN user_ficha uf ON f.id_ficha = uf.id_ficha AND uf.id_estado = 1
        WHERE f.id_estado = 1
        GROUP BY f.id_ficha, fo.nombre, tf.tipo_formacion, j.jornada, e.estado, f.fecha_creac, u.nombres, u.apellidos
        ORDER BY (COUNT(DISTINCT mf.id_materia_ficha) + COUNT(DISTINCT uf.id_user)) DESC, f.id_ficha DESC
    ");
    if ($stmt) {
        $fichas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $alertMessage = "Error al cargar fichas: " . $e->getMessage();
    $alertType = "danger";
}

// Obtener materias disponibles
$materias = [];
try {
    $stmt = $conexion->query("SELECT * FROM materias ORDER BY materia");
    if ($stmt) {
        $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $alertMessage = "Error al cargar materias: " . $e->getMessage();
    $alertType = "danger";
}

// Obtener instructores disponibles
$instructores = [];
try {
    $stmt = $conexion->query("
        SELECT id, CONCAT(nombres, ' ', apellidos) as nombre_completo 
        FROM usuarios 
        WHERE id_rol = 3 AND id_estado = 1 
        ORDER BY nombres
    ");
    if ($stmt) {
        $instructores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $alertMessage = "Error al cargar instructores: " . $e->getMessage();
    $alertType = "danger";
}

// Obtener trimestres
$trimestres = [];
try {
    $stmt = $conexion->query("SELECT * FROM trimestre ORDER BY id_trimestre");
    if ($stmt) {
        $trimestres = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // Trimestres por defecto si hay error
    $trimestres = [
        ['id_trimestre' => 1, 'trimestre' => 'Primer'],
        ['id_trimestre' => 2, 'trimestre' => 'Segundo'],
        ['id_trimestre' => 3, 'trimestre' => 'Tercer'],
        ['id_trimestre' => 4, 'trimestre' => 'Cuarto'],
        ['id_trimestre' => 5, 'trimestre' => 'Quinto'],
        ['id_trimestre' => 6, 'trimestre' => 'Sexto']
    ];
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Fichas - Asignación de Materias</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
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
                                    <div class="col-md-2">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="bi bi-folder-check display-6 mb-2"></i>
                                            <h3 class="mb-0"><?php echo $stats['fichas_activas']; ?></h3>
                                            <small>Fichas Activas</small>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="bi bi-book display-6 mb-2"></i>
                                            <h3 class="mb-0"><?php echo $stats['materias_disponibles']; ?></h3>
                                            <small>Materias Disponibles</small>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="bi bi-person-badge display-6 mb-2"></i>
                                            <h3 class="mb-0"><?php echo $stats['instructores']; ?></h3>
                                            <small>Instructores</small>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="bi bi-diagram-3 display-6 mb-2"></i>
                                            <h3 class="mb-0"><?php echo $stats['asignaciones_totales']; ?></h3>
                                            <small>Asignaciones Totales</small>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="bi bi-people display-6 mb-2"></i>
                                            <h3 class="mb-0"><?php echo $stats['aprendices_totales']; ?></h3>
                                            <small>Aprendices Registrados</small>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="bi bi-graph-up display-6 mb-2"></i>
                                            <h3 class="mb-0"><?php echo round(($stats['asignaciones_totales'] / max($stats['fichas_activas'], 1)), 1); ?></h3>
                                            <small>Promedio Materias/Ficha</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta principal -->
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="bi bi-folder-check"></i> Gestión de Fichas - Asignación de Materias y Docentes
                        </h4>
                        <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#asignarMateriaModal">
                            <i class="bi bi-plus-circle"></i> Asignar Materia
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($alertMessage)): ?>
                            <div class="alert alert-<?php echo $alertType; ?> alert-dismissible fade show" role="alert">
                                <?php echo $alertMessage; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Buscador de fichas -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control" id="buscarFicha"
                                        placeholder="Buscar por número de ficha, programa o instructor...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="filtroJornada">
                                    <option value="">Todas las jornadas</option>
                                    <option value="Mañana">Mañana</option>
                                    <option value="Tarde">Tarde</option>
                                    <option value="Noche">Noche</option>
                                    <option value="Mixta">Mixta</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="filtroTipoFormacion">
                                    <option value="">Todos los tipos</option>
                                    <option value="Tecnico">Técnico</option>
                                    <option value="Tecnologo">Tecnólogo</option>
                                </select>
                            </div>
                        </div>

                        <!-- Lista de fichas -->
                        <div class="row" id="fichasContainer">
                            <?php foreach ($fichas as $ficha): ?>
                                <div class="col-md-6 col-lg-4 mb-4 ficha-item"
                                    data-ficha="<?php echo $ficha['id_ficha']; ?>"
                                    data-programa="<?php echo strtolower($ficha['programa']); ?>"
                                    data-instructor="<?php echo strtolower($ficha['instructor_lider']); ?>"
                                    data-jornada="<?php echo $ficha['jornada']; ?>"
                                    data-tipo="<?php echo $ficha['tipo_formacion']; ?>">
                                    <div class="card h-100 border-primary">
                                        <div class="card-header bg-light">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0 fw-bold text-primary">
                                                    <i class="bi bi-folder"></i> Ficha <?php echo $ficha['id_ficha']; ?>
                                                </h6>
                                                <span class="badge bg-<?php echo ($ficha['estado'] == 'Activo') ? 'success' : 'secondary'; ?>">
                                                    <?php echo $ficha['estado']; ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <h6 class="card-title"><?php echo htmlspecialchars($ficha['programa']); ?></h6>
                                            <p class="card-text">
                                                <small class="text-muted">
                                                    <i class="bi bi-mortarboard"></i> <?php echo $ficha['tipo_formacion']; ?><br>
                                                    <i class="bi bi-clock"></i> <?php echo $ficha['jornada']; ?><br>
                                                    <i class="bi bi-person-badge"></i> <?php echo $ficha['instructor_lider'] ?? 'Sin asignar'; ?>
                                                </small>
                                            </p>

                                            <div class="row text-center">
                                                <div class="col-6">
                                                    <div class="border-end">
                                                        <h5 class="text-primary mb-0"><?php echo $ficha['materias_asignadas']; ?></h5>
                                                        <small class="text-muted">Materias</small>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <h5 class="text-success mb-0"><?php echo $ficha['aprendices_asignados']; ?></h5>
                                                    <small class="text-muted">Aprendices</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-footer bg-transparent">
                                            <div class="d-grid gap-2">
                                                <button class="btn btn-primary btn-sm ver-detalles"
                                                    data-ficha="<?php echo $ficha['id_ficha']; ?>">
                                                    <i class="bi bi-eye"></i> Ver Detalles
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Paginación -->
                        <nav>
                            <ul class="pagination justify-content-center" id="paginacionFichas"></ul>
                        </nav>

                        <?php if (empty($fichas)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-folder-x display-1 text-muted"></i>
                                <h5 class="text-muted mt-3">No hay fichas registradas</h5>
                                <p class="text-muted">Las fichas aparecerán aquí una vez que sean creadas</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para asignar materia -->
    <div class="modal fade" id="asignarMateriaModal" tabindex="-1" aria-labelledby="asignarMateriaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="asignarMateriaModalLabel">Asignar Materia a Ficha</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST" id="asignarMateriaForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="asignar_materia">

                        <div class="mb-3">
                            <label for="id_ficha" class="form-label">Ficha *</label>
                            <select class="form-select" id="id_ficha" name="id_ficha" required>
                                <option value="">Seleccione una ficha</option>
                                <?php foreach ($fichas as $ficha): ?>
                                    <option value="<?php echo $ficha['id_ficha']; ?>">
                                        Ficha <?php echo $ficha['id_ficha']; ?> - <?php echo htmlspecialchars($ficha['programa']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="id_materia" class="form-label">Materia *</label>
                            <select class="form-select" id="id_materia" name="id_materia" required>
                                <option value="">Seleccione una materia</option>
                                <?php foreach ($materias as $materia): ?>
                                    <option value="<?php echo $materia['id_materia']; ?>">
                                        <?php echo htmlspecialchars($materia['materia']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="id_trimestre" class="form-label">Trimestre *</label>
                            <select class="form-select" id="id_trimestre" name="id_trimestre" required>
                                <option value="">Seleccione un trimestre</option>
                                <?php foreach ($trimestres as $trimestre): ?>
                                    <option value="<?php echo $trimestre['id_trimestre']; ?>">
                                        <?php echo htmlspecialchars($trimestre['trimestre']); ?> Trimestre
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="id_instructor" class="form-label">Instructor (Opcional)</label>
                            <select class="form-select" id="id_instructor" name="id_instructor">
                                <option value="">Sin instructor asignado</option>
                                <?php foreach ($instructores as $instructor): ?>
                                    <option value="<?php echo $instructor['id']; ?>">
                                        <?php echo htmlspecialchars($instructor['nombre_completo']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Puede asignar o cambiar el instructor más tarde</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Asignar Materia</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para ver detalles de ficha -->
    <div class="modal fade" id="detallesFichaModal" tabindex="-1" aria-labelledby="detallesFichaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="detallesFichaModalLabel">Detalles de la Ficha</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detallesFichaContent">
                    <!-- El contenido se cargará dinámicamente -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para actualizar instructor -->
    <div class="modal fade" id="actualizarInstructorModal" tabindex="-1" aria-labelledby="actualizarInstructorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="actualizarInstructorModalLabel">Actualizar Instructor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST" id="actualizarInstructorForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="actualizar_instructor">
                        <input type="hidden" name="id_materia_ficha" id="update_id_materia_ficha">

                        <div class="mb-3">
                            <label class="form-label">Materia:</label>
                            <p class="fw-bold" id="update_materia_nombre"></p>
                        </div>

                        <div class="mb-3">
                            <label for="update_id_instructor" class="form-label">Nuevo Instructor</label>
                            <select class="form-select" id="update_id_instructor" name="id_instructor">
                                <option value="">Sin instructor asignado</option>
                                <?php foreach ($instructores as $instructor): ?>
                                    <option value="<?php echo $instructor['id']; ?>">
                                        <?php echo htmlspecialchars($instructor['nombre_completo']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Actualizar Instructor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para mostrar contacto de aprendiz -->
    <div class="modal fade" id="contactoAprendizModal" tabindex="-1" aria-labelledby="contactoAprendizModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="contactoAprendizModalLabel">Datos de Contacto del Aprendiz</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="card border-info">
                                <div class="card-body">
                                    <h6 class="card-title text-info" id="contacto_nombre"></h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong><i class="bi bi-person-badge"></i> Documento:</strong><br>
                                                <span id="contacto_documento"></span>
                                            </p>

                                            <p><strong><i class="bi bi-envelope"></i> Correo Electrónico:</strong><br>
                                                <a href="#" id="contacto_correo_link"><span id="contacto_correo"></span></a>
                                            </p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong><i class="bi bi-telephone"></i> Teléfono:</strong><br>
                                                <a href="#" id="contacto_telefono_link"><span id="contacto_telefono"></span></a>
                                            </p>

                                            <p><strong><i class="bi bi-geo-alt"></i> Dirección:</strong><br>
                                                <span id="contacto_direccion"></span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../gestion_fichas/js/modal-pagination.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/sidebard.js"></script>

    <script>
        // Variables para paginación
        let filasPorPagina = 6;
        let paginaActual = 1;

        // Función para obtener fichas filtradas
        function obtenerFichasFiltradas() {
            const busqueda = document.getElementById('buscarFicha').value.toLowerCase();
            const jornada = document.getElementById('filtroJornada').value;
            const tipoFormacion = document.getElementById('filtroTipoFormacion').value;

            const fichas = Array.from(document.querySelectorAll('.ficha-item'));

            return fichas.filter(ficha => {
                const fichaNum = ficha.dataset.ficha;
                const programa = ficha.dataset.programa;
                const instructor = ficha.dataset.instructor;
                const fichaJornada = ficha.dataset.jornada;
                const fichaTipo = ficha.dataset.tipo;

                const coincideBusqueda = fichaNum.includes(busqueda) ||
                    programa.includes(busqueda) ||
                    instructor.includes(busqueda);

                const coincideJornada = !jornada || fichaJornada === jornada;
                const coincideTipo = !tipoFormacion || fichaTipo.includes(tipoFormacion);

                return coincideBusqueda && coincideJornada && coincideTipo;
            });
        }

        // Función para mostrar página específica
        function mostrarPagina(pagina) {
            const fichasFiltradas = obtenerFichasFiltradas();
            const totalPaginas = Math.ceil(fichasFiltradas.length / filasPorPagina);

            if (pagina < 1) pagina = 1;
            if (pagina > totalPaginas) pagina = totalPaginas;

            // Ocultar todas las fichas
            document.querySelectorAll('.ficha-item').forEach(ficha => {
                ficha.style.display = 'none';
            });

            // Mostrar fichas de la página actual
            const inicio = (pagina - 1) * filasPorPagina;
            const fin = inicio + filasPorPagina;

            for (let i = inicio; i < fin && i < fichasFiltradas.length; i++) {
                fichasFiltradas[i].style.display = 'block';
            }

            // Actualizar paginación
            actualizarPaginacion(pagina, totalPaginas);
            paginaActual = pagina;
        }

        // Función para actualizar controles de paginación
        function actualizarPaginacion(paginaActual, totalPaginas) {
            const paginacion = document.getElementById('paginacionFichas');
            paginacion.innerHTML = '';

            if (totalPaginas <= 1) return;

            // Botón anterior
            paginacion.innerHTML += `
            <li class="page-item ${paginaActual === 1 ? 'disabled' : ''}">
                <button class="page-link" onclick="cambiarPagina(${paginaActual - 1})">Anterior</button>
            </li>
        `;

            // Números de página
            for (let i = 1; i <= totalPaginas; i++) {
                paginacion.innerHTML += `
                <li class="page-item ${paginaActual === i ? 'active' : ''}">
                    <button class="page-link" onclick="cambiarPagina(${i})">${i}</button>
                </li>
            `;
            }

            // Botón siguiente
            paginacion.innerHTML += `
            <li class="page-item ${paginaActual === totalPaginas ? 'disabled' : ''}">
                <button class="page-link" onclick="cambiarPagina(${paginaActual + 1})">Siguiente</button>
            </li>
        `;
        }

        // Función para cambiar página
        function cambiarPagina(nuevaPagina) {
            mostrarPagina(nuevaPagina);
        }

        // Función para filtrar fichas
        function filtrarFichas() {
            paginaActual = 1;
            mostrarPagina(paginaActual);
        }

        // Event listeners para filtros
        document.getElementById('buscarFicha').addEventListener('input', filtrarFichas);
        document.getElementById('filtroJornada').addEventListener('change', filtrarFichas);
        document.getElementById('filtroTipoFormacion').addEventListener('change', filtrarFichas);

        // Ver detalles de ficha
        document.addEventListener('click', function(event) {
            if (event.target.closest('.ver-detalles')) {
                const button = event.target.closest('.ver-detalles');
                const idFicha = button.getAttribute('data-ficha');
                cargarDetallesFicha(idFicha);
            }
        });

        // Función para cargar detalles de ficha
        async function cargarDetallesFicha(idFicha) {
            try {
                const response = await fetch(`get_ficha_details.php?id_ficha=${idFicha}`);
                const data = await response.json();

                if (data.success) {
                    document.getElementById('detallesFichaContent').innerHTML = data.html;
                    const modal = new bootstrap.Modal(document.getElementById('detallesFichaModal'));
                    modal.show();
                } else {
                    alert('Error al cargar los detalles de la ficha');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al cargar los detalles de la ficha');
            }
        }

        // Manejar actualización de instructor
        document.addEventListener('click', function(event) {
            if (event.target.closest('.actualizar-instructor')) {
                const button = event.target.closest('.actualizar-instructor');
                const idMateriaFicha = button.getAttribute('data-id');
                const materiaNombre = button.getAttribute('data-materia');
                const instructorActual = button.getAttribute('data-instructor');

                document.getElementById('update_id_materia_ficha').value = idMateriaFicha;
                document.getElementById('update_materia_nombre').textContent = materiaNombre;
                document.getElementById('update_id_instructor').value = instructorActual || '';

                const modal = new bootstrap.Modal(document.getElementById('actualizarInstructorModal'));
                modal.show();
            }
        });

        // Manejar eliminación de asignación
        document.addEventListener('click', function(event) {
            if (event.target.closest('.eliminar-asignacion')) {
                const button = event.target.closest('.eliminar-asignacion');
                const idMateriaFicha = button.getAttribute('data-id');
                const materiaNombre = button.getAttribute('data-materia');

                if (confirm(`¿Está seguro que desea eliminar la asignación de "${materiaNombre}"?`)) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                    <input type="hidden" name="action" value="eliminar_asignacion">
                    <input type="hidden" name="id_materia_ficha" value="${idMateriaFicha}">
                `;
                    document.body.appendChild(form);
                    form.submit();
                }
            }
        });

        // Manejar visualización de contacto de aprendiz
        document.addEventListener('click', function(event) {
            if (event.target.closest('.ver-contacto-aprendiz')) {
                const button = event.target.closest('.ver-contacto-aprendiz');
                const documento = button.getAttribute('data-id');
                const nombre = button.getAttribute('data-nombre');
                const correo = button.getAttribute('data-correo');
                const telefono = button.getAttribute('data-telefono') || 'No registrado';
                const direccion = button.getAttribute('data-direccion') || 'No registrada';

                document.getElementById('contacto_documento').textContent = documento;
                document.getElementById('contacto_nombre').textContent = nombre;
                document.getElementById('contacto_correo').textContent = correo;
                document.getElementById('contacto_correo_link').href = `mailto:${correo}`;
                document.getElementById('contacto_telefono').textContent = telefono;
                document.getElementById('contacto_telefono_link').href = `tel:${telefono}`;
                document.getElementById('contacto_direccion').textContent = direccion;

                const modal = new bootstrap.Modal(document.getElementById('contactoAprendizModal'));
                modal.show();
            }
        });

        // Inicializar al cargar la página
        document.addEventListener("DOMContentLoaded", function() {
            // Mostrar primera página
            mostrarPagina(1);

            // Inicializar tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>

    

</body>

</html>
<?php
session_start();

if ($_SESSION['rol'] !== 2) {
    header('Location: ../../includes/exit.php?');
    exit;
}



require_once $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php';

include 'functions.php';

// Mostrar mensaje después de redireccionar tras editar
if (isset($_SESSION['alertMessage'])) {
    $alertMessage = $_SESSION['alertMessage'];
    $alertType = $_SESSION['alertType'];
    unset($_SESSION['alertMessage'], $_SESSION['alertType']);
} else {
    $alertMessage = '';
    $alertType = '';
}

// Crear instancia de la conexión
$db = new Database();
$conexion = $db->connect();

// Procesar el formulario de creación/edición de ficha
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'crear') {
            // Recopilar datos del formulario
            $id_formacion = $_POST['id_formacion'];
            $id_instructor = $_POST['id_instructor'];
            $id_jornada = $_POST['id_jornada'];
            $id_tipo_ficha = $_POST['id_tipo_ficha'];
            $fecha_creac = $_POST['fecha_creac'];
            $id_estado = 1; // Por defecto activo

            if (empty($id_formacion) || empty($id_instructor) || empty($id_jornada) || empty($id_tipo_ficha)) {
                $alertMessage = "Todos los campos marcados con * son requeridos";
                $alertType = "danger";
            } else {
                try {
                    $stmt = $conexion->prepare("INSERT INTO fichas (id_formacion, id_instructor, id_jornada, id_tipo_ficha, fecha_creac, id_estado) VALUES (:id_formacion, :id_instructor, :id_jornada, :id_tipo_ficha, :fecha_creac, :id_estado)");
                    $stmt->bindParam(':id_formacion', $id_formacion, PDO::PARAM_INT);
                    $stmt->bindParam(':id_instructor', $id_instructor, PDO::PARAM_INT);
                    $stmt->bindParam(':id_jornada', $id_jornada, PDO::PARAM_INT);
                    $stmt->bindParam(':id_tipo_ficha', $id_tipo_ficha, PDO::PARAM_INT);
                    $stmt->bindParam(':fecha_creac', $fecha_creac, PDO::PARAM_STR);
                    $stmt->bindParam(':id_estado', $id_estado, PDO::PARAM_INT);

                    if ($stmt->execute()) {
                        $alertMessage = "Ficha creada exitosamente";
                        $alertType = "success";
                    } else {
                        $alertMessage = "Error al crear la ficha";
                        $alertType = "danger";
                    }
                } catch (PDOException $e) {
                    $alertMessage = "Error: " . $e->getMessage();
                    $alertType = "danger";
                }
            }
        }
        // --- EDICIÓN ---
        elseif ($_POST['action'] == 'editar') {
            $id = isset($_POST['id']) ? $_POST['id'] : null;
            $id_formacion = $_POST['id_formacion'];
            $id_instructor = $_POST['id_instructor'];
            $id_jornada = $_POST['id_jornada'];
            $id_tipo_ficha = $_POST['id_tipo_ficha'];
            $fecha_creac = $_POST['fecha_creac'];
            $id_estado = $_POST['id_estado']; // Ahora se puede cambiar el estado

            if (empty($id_formacion) || empty($id_instructor) || empty($id_jornada) || empty($id_tipo_ficha)) {
                $alertMessage = "Todos los campos marcados con * son requeridos";
                $alertType = "danger";
            } else {
                try {
                    $stmt = $conexion->prepare("UPDATE fichas SET id_formacion = :id_formacion, id_instructor = :id_instructor, id_jornada = :id_jornada, id_tipo_ficha = :id_tipo_ficha, fecha_creac = :fecha_creac, id_estado = :id_estado WHERE id_ficha = :id");
                    $stmt->bindParam(':id_formacion', $id_formacion, PDO::PARAM_INT);
                    $stmt->bindParam(':id_instructor', $id_instructor, PDO::PARAM_INT);
                    $stmt->bindParam(':id_jornada', $id_jornada, PDO::PARAM_INT);
                    $stmt->bindParam(':id_tipo_ficha', $id_tipo_ficha, PDO::PARAM_INT);
                    $stmt->bindParam(':fecha_creac', $fecha_creac, PDO::PARAM_STR);
                    $stmt->bindParam(':id_estado', $id_estado, PDO::PARAM_INT);
                    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

                    if ($stmt->execute()) {
                        // REDIRECT para limpiar ?edit=... y no reabrir el modal
                        $_SESSION['alertMessage'] = "Ficha actualizada exitosamente";
                        $_SESSION['alertType'] = "success";
                        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
                        exit;
                    } else {
                        $alertMessage = "Error al actualizar la ficha";
                        $alertType = "danger";
                    }
                } catch (PDOException $e) {
                    $alertMessage = "Error: " . $e->getMessage();
                    $alertType = "danger";
                }
            }
        }
        // --- ELIMINAR ---
        elseif ($_POST['action'] == 'eliminar') {
            $id = $_POST['id'];
            $stmt = $conexion->prepare("SELECT COUNT(*) as count FROM user_ficha WHERE id_ficha = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row['count'] > 0) {
                $alertMessage = "No se puede eliminar la ficha porque tiene aprendices asociados";
                $alertType = "danger";
            } else {
                try {
                    $stmt = $conexion->prepare("DELETE FROM fichas WHERE id_ficha = :id");
                    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

                    if ($stmt->execute()) {
                        $alertMessage = "Ficha eliminada exitosamente";
                        $alertType = "success";
                    } else {
                        $alertMessage = "Error al eliminar la ficha";
                        $alertType = "danger";
                    }
                } catch (PDOException $e) {
                    $alertMessage = "Error: " . $e->getMessage();
                    $alertType = "danger";
                }
            }
        }
    }
}

// Obtener todas las fichas con información relacionada y conteo de usuarios
$fichas = [];
try {
    $query = "SELECT f.id_ficha, fo.nombre as nombre_formacion, u.nombres as instructor_nombre, 
            j.jornada, tf.tipo_ficha, f.fecha_creac, f.id_formacion, f.id_instructor, f.id_jornada, 
            f.id_tipo_ficha, f.id_estado, e.estado as estado_nombre,
            COUNT(uf.id_user) as total_usuarios,
            COUNT(CASE WHEN uf.id_estado = 1 THEN 1 END) as usuarios_activos,
            COUNT(CASE WHEN uf.id_estado = 2 THEN 1 END) as usuarios_inactivos
            FROM fichas f
            LEFT JOIN formacion fo ON f.id_formacion = fo.id_formacion
            LEFT JOIN usuarios u ON f.id_instructor = u.id
            LEFT JOIN jornada j ON f.id_jornada = j.id_jornada
            LEFT JOIN tipo_ficha tf ON f.id_tipo_ficha = tf.id_tipo_ficha
            LEFT JOIN estado e ON f.id_estado = e.id_estado
            LEFT JOIN user_ficha uf ON f.id_ficha = uf.id_ficha
            GROUP BY f.id_ficha
            ORDER BY f.fecha_creac DESC";
    $stmt = $conexion->query($query);
    $fichas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $alertMessage = "Error al cargar fichas: " . $e->getMessage();
    $alertType = "danger";
}

// Obtener ficha para editar si se solicita
$fichaEdit = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    try {
        $stmt = $conexion->prepare("SELECT * FROM fichas WHERE id_ficha = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $fichaEdit = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $alertMessage = "Error al cargar la ficha: " . $e->getMessage();
        $alertType = "danger";
    }
}

// Obtener formaciones para el select
$formaciones = [];
try {
    $stmt = $conexion->query("SELECT id_formacion, nombre FROM formacion WHERE id_estado = 1 ORDER BY nombre");
    $formaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $alertMessage = "Error al cargar formaciones: " . $e->getMessage();
    $alertType = "danger";
}

// Obtener instructores para el select
$instructores = [];
try {
    $stmt = $conexion->query("SELECT id, nombres, apellidos FROM usuarios WHERE id_rol = 3 AND id_estado = 1 ORDER BY nombres"); // Rol 3 es instructor
    $instructores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $alertMessage = "Error al cargar instructores: " . $e->getMessage();
    $alertType = "danger";
}

// Obtener jornadas para el select
$jornadas = [];
try {
    $stmt = $conexion->query("SELECT id_jornada, jornada FROM jornada ORDER BY jornada");
    $jornadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $alertMessage = "Error al cargar jornadas: " . $e->getMessage();
    $alertType = "danger";
}

// Obtener tipos de ficha para el select
$tiposFicha = [];
try {
    $stmt = $conexion->query("SELECT id_tipo_ficha, tipo_ficha FROM tipo_ficha ORDER BY tipo_ficha");
    $tiposFicha = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $alertMessage = "Error al cargar tipos de ficha: " . $e->getMessage();
    $alertType = "danger";
}

// Obtener estados para el select
$estados = [];
try {
    $stmt = $conexion->query("SELECT id_estado, estado FROM estado ORDER BY estado");
    $estados = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $alertMessage = "Error al cargar estados: " . $e->getMessage();
    $alertType = "danger";
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Fichas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link rel="stylesheet" href="../styles/sidebard.css">
    <link rel="stylesheet" href="../styles/main.css">
</head>

<body>
    <div class="wrapper">
        <?php include '../includes/sidebard.php'; ?>
        <div class="main-content">
            <div class="container mt-4">
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Gestión de Fichas</h4>
                        <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#crearFichaModal">
                            <i class="bi bi-plus-circle"></i> Nueva Ficha
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($alertMessage)): ?>
                            <div class="alert alert-<?php echo $alertType; ?> alert-dismissible fade show" role="alert">
                                <?php echo $alertMessage; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Controles de paginación y búsqueda -->
                        <div class="d-flex justify-content-between align-items-center mt-3 mb-4">
                            <div>
                                <label for="filasPorPagina" class="form-label me-2">Mostrar:</label>
                                <select id="filasPorPagina" class="form-select form-select-sm d-inline-block w-auto" onchange="cambiarFilasPorPagina(this.value)">
                                    <option value="5" selected>5</option>
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                                <span class="ms-2 text-muted">registros por página</span>
                            </div>
                            <input
                                type="number"
                                min="0"
                                id="busquedaFicha"
                                class="form-control"
                                style="max-width: 350px;"
                                placeholder="Buscar por número de ficha..."
                                oninput="filtrarFicha()">
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover" id="tablaFichas">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Formación</th>
                                        <th>Instructor</th>
                                        <th>Jornada</th>
                                        <th>Tipo</th>
                                        <th>Estado</th>
                                        <th>Usuarios</th>
                                        <th>Fecha Creación</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($fichas)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center">No hay fichas registradas</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($fichas as $ficha): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($ficha['id_ficha']); ?></td>
                                                <td><?php echo htmlspecialchars($ficha['nombre_formacion'] ?? 'No asignada'); ?></td>
                                                <td><?php echo htmlspecialchars($ficha['instructor_nombre'] ?? 'No asignado'); ?></td>
                                                <td><?php echo htmlspecialchars($ficha['jornada'] ?? 'No asignada'); ?></td>
                                                <td><?php echo htmlspecialchars($ficha['tipo_ficha'] ?? 'No asignado'); ?></td>
                                                <td>
                                                    <span class="badge <?php echo ($ficha['id_estado'] == 1) ? 'bg-success' : 'bg-danger'; ?>">
                                                        <?php echo htmlspecialchars($ficha['estado_nombre'] ?? 'Sin estado'); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <span class="badge bg-primary mb-1">
                                                            Total: <?php echo $ficha['total_usuarios']; ?>
                                                        </span>
                                                        <?php if ($ficha['total_usuarios'] > 0): ?>
                                                            <small class="text-muted">
                                                                <span class="badge bg-success me-1">Activos: <?php echo $ficha['usuarios_activos']; ?></span>
                                                                <span class="badge bg-secondary">Inactivos: <?php echo $ficha['usuarios_inactivos']; ?></span>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($ficha['fecha_creac']))); ?></td>
                                                <td>
                                                    <a href="?edit=<?php echo $ficha['id_ficha']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-danger"
                                                        onclick="confirmarEliminar(<?php echo $ficha['id_ficha']; ?>, '<?php echo htmlspecialchars($ficha['nombre_formacion']); ?>')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <nav>
                                <ul class="pagination justify-content-center" id="paginacionFichas"></ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL CREAR FICHA (SEPARADO) -->
    <div class="modal fade" id="crearFichaModal" tabindex="-1" aria-labelledby="crearFichaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="" method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="crearFichaModalLabel">Nueva Ficha</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="crear">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="id_formacion_crear" class="form-label">Formación *</label>
                                <select class="form-select" id="id_formacion_crear" name="id_formacion" required>
                                    <option value="">Seleccione una formación</option>
                                    <?php foreach ($formaciones as $formacion): ?>
                                        <option value="<?php echo $formacion['id_formacion']; ?>">
                                            <?php echo htmlspecialchars($formacion['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($formaciones)): ?>
                                    <div class="form-text text-danger">
                                        No hay formaciones disponibles. <a href="formaciones.php" target="_blank">Crear formación</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="id_instructor_crear" class="form-label">Instructor Principal *</label>
                                <select class="form-select" id="id_instructor_crear" name="id_instructor" required>
                                    <option value="">Seleccione un instructor</option>
                                    <?php foreach ($instructores as $instructor): ?>
                                        <option value="<?php echo $instructor['id']; ?>">
                                            <?php echo htmlspecialchars($instructor['nombres'] . ' ' . $instructor['apellidos']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($instructores)): ?>
                                    <div class="form-text text-danger">
                                        No hay instructores disponibles. Registre instructores con rol de Instructor.
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="fecha_creac_crear" class="form-label">Fecha de Creación *</label>
                                <input type="date" class="form-control" id="fecha_creac_crear" name="fecha_creac" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="id_jornada_crear" class="form-label">Jornada *</label>
                                <select class="form-select" id="id_jornada_crear" name="id_jornada" required>
                                    <option value="">Seleccione una jornada</option>
                                    <?php foreach ($jornadas as $jornada): ?>
                                        <option value="<?php echo $jornada['id_jornada']; ?>">
                                            <?php echo htmlspecialchars($jornada['jornada']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($jornadas)): ?>
                                    <div class="form-text text-danger">
                                        No hay jornadas disponibles. Use el script SQL para agregar jornadas.
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="id_tipo_ficha_crear" class="form-label">Tipo de Ficha *</label>
                                <select class="form-select" id="id_tipo_ficha_crear" name="id_tipo_ficha" required>
                                    <option value="">Seleccione un tipo</option>
                                    <?php foreach ($tiposFicha as $tipo): ?>
                                        <option value="<?php echo $tipo['id_tipo_ficha']; ?>">
                                            <?php echo htmlspecialchars($tipo['tipo_ficha']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($tiposFicha)): ?>
                                    <div class="form-text text-danger">
                                        No hay tipos de ficha disponibles. Use el script SQL para agregar tipos.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" <?php echo (empty($formaciones) || empty($instructores) || empty($jornadas) || empty($tiposFicha)) ? 'disabled' : ''; ?>>
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL EDITAR FICHA (USA EL DE SIEMPRE) -->
    <div class="modal fade" id="fichaModal" tabindex="-1" aria-labelledby="fichaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="" method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="fichaModalLabel">
                            Editar Ficha
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="editar">
                        <input type="hidden" name="id" value="<?php echo $fichaEdit ? $fichaEdit['id_ficha'] : ''; ?>">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Formación *</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($formaciones[array_search($fichaEdit['id_formacion'], array_column($formaciones, 'id_formacion'))]['nombre'] ?? ''); ?>" readonly>
                                <input type="hidden" name="id_formacion" value="<?php echo $fichaEdit['id_formacion']; ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="id_instructor" class="form-label">Instructor Principal *</label>
                                <select class="form-select" id="id_instructor" name="id_instructor" required>
                                    <option value="">Seleccione un instructor</option>
                                    <?php foreach ($instructores as $instructor): ?>
                                        <option value="<?php echo $instructor['id']; ?>" <?php echo ($fichaEdit && $fichaEdit['id_instructor'] == $instructor['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($instructor['nombres'] . ' ' . $instructor['apellidos']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($instructores)): ?>
                                    <div class="form-text text-danger">
                                        No hay instructores disponibles. Registre instructores con rol de Instructor.
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fecha de Inicio *</label>
                                <input type="date" class="form-control" name="fecha_creac"
                                    value="<?php echo $fichaEdit ? $fichaEdit['fecha_creac'] : ''; ?>" readonly>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="id_jornada" class="form-label">Jornada *</label>
                                <select class="form-select" id="id_jornada" name="id_jornada" required>
                                    <option value="">Seleccione una jornada</option>
                                    <?php foreach ($jornadas as $jornada): ?>
                                        <option value="<?php echo $jornada['id_jornada']; ?>" <?php echo ($fichaEdit && $fichaEdit['id_jornada'] == $jornada['id_jornada']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($jornada['jornada']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($jornadas)): ?>
                                    <div class="form-text text-danger">
                                        No hay jornadas disponibles. Use el script SQL para agregar jornadas.
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="id_tipo_ficha" class="form-label">Tipo de Ficha *</label>
                                <select class="form-select" id="id_tipo_ficha" name="id_tipo_ficha" required>
                                    <option value="">Seleccione un tipo</option>
                                    <?php foreach ($tiposFicha as $tipo): ?>
                                        <option value="<?php echo $tipo['id_tipo_ficha']; ?>" <?php echo ($fichaEdit && $fichaEdit['id_tipo_ficha'] == $tipo['id_tipo_ficha']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($tipo['tipo_ficha']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($tiposFicha)): ?>
                                    <div class="form-text text-danger">
                                        No hay tipos de ficha disponibles. Use el script SQL para agregar tipos.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="id_estado" class="form-label">Estado *</label>
                                <select class="form-select" id="id_estado" name="id_estado" required>
                                    <?php foreach ($estados as $estado): ?>
                                        <option value="<?php echo $estado['id_estado']; ?>" <?php echo ($fichaEdit && $fichaEdit['id_estado'] == $estado['id_estado']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($estado['estado']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" <?php echo (empty($formaciones) || empty($instructores) || empty($jornadas) || empty($tiposFicha)) ? 'disabled' : ''; ?>>
                            Actualizar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación para eliminar -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    ¿Está seguro que desea eliminar la ficha <span id="ficha-nombre"></span>?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form action="" method="POST" id="deleteForm">
                        <input type="hidden" name="action" value="eliminar">
                        <input type="hidden" name="id" id="delete-id">
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/sidebard.js"></script>

    <script>
        $(document).ready(function() {
            // Select2 para los select del modal de crear
            $('#id_instructor_crear').select2({
                dropdownParent: $('#crearFichaModal'),
                dropdownAutoWidth: true,
                width: '100%',
                placeholder: "Seleccione un instructor",
                allowClear: true,
                dropdownPosition: 'below'
            });
            $('#id_formacion_crear').select2({
                dropdownParent: $('#crearFichaModal'),
                dropdownAutoWidth: true,
                width: '100%',
                placeholder: "Seleccione una formación",
                allowClear: true,
                dropdownPosition: 'below'
            });
            // Select2 para el modal de editar
            $('#id_instructor').select2({
                dropdownParent: $('#fichaModal'),
                dropdownAutoWidth: true,
                width: '100%',
                placeholder: "Seleccione un instructor",
                allowClear: true,
                dropdownPosition: 'below'
            });
        });
    </script>

    <!-- Script: Mostrar modal de edición automáticamente si hay una ficha a editar -->
    <script>
        <?php if ($fichaEdit): ?>
            document.addEventListener('DOMContentLoaded', function() {
                var fichaModal = new bootstrap.Modal(document.getElementById('fichaModal'));
                fichaModal.show();
            });
        <?php endif; ?>
    </script>

    <!-- Script: Función para abrir el modal de eliminar con los datos correctos -->
    <script>
        function confirmarEliminar(id, nombre) {
            document.getElementById('delete-id').value = id;
            document.getElementById('ficha-nombre').textContent = nombre;
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }
    </script>

    <!-- PAGINACIÓN Y FILTRO JS MEJORADO -->
    <script>
        let filasPorPaginaFichas = 5;
        let paginaActualFichas = 1;

        function obtenerFilasFichasFiltradas() {
            let filas = Array.from(document.querySelectorAll("#tablaFichas tbody tr"));
            let filtro = document.getElementById("busquedaFicha").value.trim();
            if (filtro === "") return filas;
            return filas.filter(fila => {
                let idFicha = fila.querySelector("td").textContent.trim();
                return idFicha.includes(filtro);
            });
        }

        function mostrarPaginaFichas(pagina) {
            let filas = obtenerFilasFichasFiltradas();
            let totalPaginas = Math.ceil(filas.length / filasPorPaginaFichas);

            if (pagina < 1) pagina = 1;
            if (pagina > totalPaginas) pagina = totalPaginas;

            // Ocultar todas las filas
            document.querySelectorAll("#tablaFichas tbody tr").forEach(fila => fila.style.display = "none");

            // Mostrar filas de la página actual
            let inicio = (pagina - 1) * filasPorPaginaFichas;
            let fin = inicio + filasPorPaginaFichas;
            for (let i = inicio; i < fin && i < filas.length; i++) {
                filas[i].style.display = "";
            }

            // Generar paginación inteligente
            generarPaginacionInteligenteFichas(pagina, totalPaginas);
            paginaActualFichas = pagina;
        }

        function generarPaginacionInteligenteFichas(paginaActual, totalPaginas) {
            let paginacion = document.getElementById("paginacionFichas");
            paginacion.innerHTML = "";

            if (totalPaginas <= 1) return;

            const maxPaginasVisibles = 5; // Máximo número de páginas a mostrar
            let paginaInicio, paginaFin;

            // Calcular rango de páginas a mostrar
            if (totalPaginas <= maxPaginasVisibles) {
                paginaInicio = 1;
                paginaFin = totalPaginas;
            } else {
                // Centrar la página actual en el rango visible
                let mitad = Math.floor(maxPaginasVisibles / 2);
                paginaInicio = Math.max(1, paginaActual - mitad);
                paginaFin = Math.min(totalPaginas, paginaInicio + maxPaginasVisibles - 1);

                // Ajustar si estamos cerca del final
                if (paginaFin - paginaInicio < maxPaginasVisibles - 1) {
                    paginaInicio = Math.max(1, paginaFin - maxPaginasVisibles + 1);
                }
            }

            // Botón "Primera" (solo si no estamos en la primera página)
            if (paginaActual > 1) {
                paginacion.innerHTML += `
            <li class="page-item">
                <button class="page-link" onclick="cambiarPaginaFichas(1)" title="Primera página">
                    <i class="bi bi-chevron-double-left"></i>
                </button>
            </li>`;
            }

            // Botón "Anterior"
            paginacion.innerHTML += `
        <li class="page-item ${paginaActual === 1 ? 'disabled' : ''}">
            <button class="page-link" onclick="cambiarPaginaFichas(${paginaActual - 1})" title="Página anterior">
                <i class="bi bi-chevron-left"></i>
            </button>
        </li>`;

            // Mostrar "..." si hay páginas antes del rango visible
            if (paginaInicio > 1) {
                paginacion.innerHTML += `
            <li class="page-item disabled">
                <span class="page-link">...</span>
            </li>`;
            }

            // Números de página
            for (let i = paginaInicio; i <= paginaFin; i++) {
                paginacion.innerHTML += `
            <li class="page-item ${paginaActual === i ? 'active' : ''}">
                <button class="page-link" onclick="cambiarPaginaFichas(${i})">${i}</button>
            </li>`;
            }

            // Mostrar "..." si hay páginas después del rango visible
            if (paginaFin < totalPaginas) {
                paginacion.innerHTML += `
            <li class="page-item disabled">
                <span class="page-link">...</span>
            </li>`;
            }

            // Botón "Siguiente"
            paginacion.innerHTML += `
        <li class="page-item ${paginaActual === totalPaginas ? 'disabled' : ''}">
            <button class="page-link" onclick="cambiarPaginaFichas(${paginaActual + 1})" title="Página siguiente">
                <i class="bi bi-chevron-right"></i>
            </button>
        </li>`;

            // Botón "Última" (solo si no estamos en la última página)
            if (paginaActual < totalPaginas) {
                paginacion.innerHTML += `
            <li class="page-item">
                <button class="page-link" onclick="cambiarPaginaFichas(${totalPaginas})" title="Última página">
                    <i class="bi bi-chevron-double-right"></i>
                </button>
            </li>`;
            }

            // Mostrar información de página actual
            mostrarInfoPaginacionFichas(paginaActual, totalPaginas, obtenerFilasFichasFiltradas().length);
        }

        function mostrarInfoPaginacionFichas(paginaActual, totalPaginas, totalRegistros) {
            // Crear o actualizar el elemento de información si no existe
            let infoPaginacion = document.getElementById("infoPaginacionFichas");
            if (!infoPaginacion) {
                infoPaginacion = document.createElement("div");
                infoPaginacion.id = "infoPaginacionFichas";
                infoPaginacion.className = "text-center mt-2 text-muted small";
                document.getElementById("paginacionFichas").parentNode.appendChild(infoPaginacion);
            }

            let registroInicio = ((paginaActual - 1) * filasPorPaginaFichas) + 1;
            let registroFin = Math.min(paginaActual * filasPorPaginaFichas, totalRegistros);

            infoPaginacion.innerHTML = `
        Mostrando ${registroInicio} a ${registroFin} de ${totalRegistros} registros 
        (Página ${paginaActual} de ${totalPaginas})
    `;
        }

        function cambiarPaginaFichas(nuevaPagina) {
            mostrarPaginaFichas(nuevaPagina);
        }

        function filtrarFicha() {
            paginaActualFichas = 1;
            mostrarPaginaFichas(paginaActualFichas);
        }

        // Función para cambiar el número de filas por página
        function cambiarFilasPorPagina(nuevasFilas) {
            filasPorPaginaFichas = parseInt(nuevasFilas);
            paginaActualFichas = 1;
            mostrarPaginaFichas(paginaActualFichas);
        }

        document.addEventListener("DOMContentLoaded", function() {
            mostrarPaginaFichas(paginaActualFichas);
        });
    </script>

    // Script para cerrar sesión al recargar la página
    <script>
        window.addEventListener('beforeunload', function() {
            // Aquí puedes enviar una solicitud AJAX para cerrar la sesión en el servidor
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '../../includes/exit.php', true);
            xhr.send();
        });
    </script>

</body>

</html>
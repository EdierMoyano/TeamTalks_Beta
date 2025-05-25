<?php
session_start();
require_once '../../conexion/conexion.php';
require_once '../../includes/functions.php';
// Verificar si el usuario está autenticado
if (!isset($_SESSION['documento'])) {
    header('Location: ../login/login.php');
    exit;
}

// Inicializar mensaje de alerta
$alertMessage = '';
$alertType = '';

// Crear instancia de la conexión
$db = new Database();
$conexion = $db->connect();

// Procesar el formulario de creación/edición de ficha
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'crear' || $_POST['action'] == 'editar') {
            // Recopilar datos del formulario
            $id = isset($_POST['id']) ? $_POST['id'] : null;
            $id_formacion = $_POST['id_formacion'];
            $id_instructor = $_POST['id_instructor'];
            $id_jornada = $_POST['id_jornada'];
            $id_tipo_ficha = $_POST['id_tipo_ficha'];
            $fecha_creac = $_POST['fecha_creac'];
            $id_estado = 1; // Por defecto activo
            
            // Validaciones básicas
            if (empty($id_formacion) || empty($id_instructor) || empty($id_jornada) || empty($id_tipo_ficha)) {
                $alertMessage = "Todos los campos marcados con * son requeridos";
                $alertType = "danger";
            } else {
                try {
                    if ($_POST['action'] == 'crear') {
                        // Crear nueva ficha - sin usar ficha_nom
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
                    } else {
                        // Actualizar ficha existente - sin usar ficha_nom
                        $stmt = $conexion->prepare("UPDATE fichas SET id_formacion = :id_formacion, id_instructor = :id_instructor, id_jornada = :id_jornada, id_tipo_ficha = :id_tipo_ficha, fecha_creac = :fecha_creac WHERE id_ficha = :id");
                        $stmt->bindParam(':id_formacion', $id_formacion, PDO::PARAM_INT);
                        $stmt->bindParam(':id_instructor', $id_instructor, PDO::PARAM_INT);
                        $stmt->bindParam(':id_jornada', $id_jornada, PDO::PARAM_INT);
                        $stmt->bindParam(':id_tipo_ficha', $id_tipo_ficha, PDO::PARAM_INT);
                        $stmt->bindParam(':fecha_creac', $fecha_creac, PDO::PARAM_STR);
                        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                        
                        if ($stmt->execute()) {
                            $alertMessage = "Ficha actualizada exitosamente";
                            $alertType = "success";
                        } else {
                            $alertMessage = "Error al actualizar la ficha";
                            $alertType = "danger";
                        }
                    }
                } catch (PDOException $e) {
                    $alertMessage = "Error: " . $e->getMessage();
                    $alertType = "danger";
                }
            }
        } elseif ($_POST['action'] == 'eliminar') {
            // Eliminar ficha
            $id = $_POST['id'];
            
            // Verificar si hay aprendices asociados a esta ficha
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

// Obtener todas las fichas con información relacionada
$fichas = [];
try {
    $query = "SELECT f.id_ficha, fo.nombre as nombre_formacion, u.nombres as instructor_nombre, 
            j.jornada, tf.tipo_ficha, f.fecha_creac
            FROM fichas f
            LEFT JOIN formacion fo ON f.id_formacion = fo.id_formacion
            LEFT JOIN usuarios u ON f.id_instructor = u.id
            LEFT JOIN jornada j ON f.id_jornada = j.id_jornada
            LEFT JOIN tipo_ficha tf ON f.id_tipo_ficha = tf.id_tipo_ficha
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
    $stmt = $conexion->query("SELECT id_formacion, nombre FROM formacion ORDER BY nombre");
    $formaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $alertMessage = "Error al cargar formaciones: " . $e->getMessage();
    $alertType = "danger";
}

// Obtener instructores para el select
$instructores = [];
try {
    $stmt = $conexion->query("SELECT id, nombres, apellidos FROM usuarios WHERE id_rol = 3 ORDER BY nombres"); // Rol 3 es instructor
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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Fichas</title>
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
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Gestión de Fichas</h4>
                        <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#fichaModal">
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
                        <div class="mb-3 d-flex justify-content-end">
                            <input 
                                type="number" 
                                min="0" 
                                id="busquedaFicha" 
                                class="form-control" 
                                style="max-width: 350px;" 
                                placeholder="Buscar por número de ficha..." 
                                oninput="filtrarFicha()"
                            >
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover" id="tablaFichas">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Instructor</th>
                                        <th>Jornada</th>
                                        <th>Tipo</th>
                                        <th>Fecha Creación</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($fichas)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No hay fichas registradas</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($fichas as $ficha): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($ficha['id_ficha']); ?></td>
                                                <td><?php echo htmlspecialchars($ficha['nombre_formacion'] ?? 'No asignada'); ?></td>
                                                <td><?php echo htmlspecialchars($ficha['instructor_nombre'] ?? 'No asignado'); ?></td>
                                                <td><?php echo htmlspecialchars($ficha['jornada'] ?? 'No asignada'); ?></td>
                                                <td><?php echo htmlspecialchars($ficha['tipo_ficha'] ?? 'No asignado'); ?></td>
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
                                <ul class="pagination justify-content-end" id="paginacionFichas"></ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para crear/editar ficha -->
    <div class="modal fade" id="fichaModal" tabindex="-1" aria-labelledby="fichaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="fichaModalLabel">
                        <?php echo $fichaEdit ? 'Editar Ficha' : 'Nueva Ficha'; ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="<?php echo $fichaEdit ? 'editar' : 'crear'; ?>">
                        <?php if ($fichaEdit): ?>
                            <input type="hidden" name="id" value="<?php echo $fichaEdit['id_ficha']; ?>">
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="id_formacion" class="form-label">Formación *</label>
                                <select class="form-select" id="id_formacion" name="id_formacion" required>
                                    <option value="">Seleccione una formación</option>
                                    <?php foreach ($formaciones as $formacion): ?>
                                        <option value="<?php echo $formacion['id_formacion']; ?>" 
                                                <?php echo ($fichaEdit && $fichaEdit['id_formacion'] == $formacion['id_formacion']) ? 'selected' : ''; ?>>
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
                                <label for="id_instructor" class="form-label">Instructor Principal *</label>
                                <select class="form-select" id="id_instructor" name="id_instructor" required>
                                    <option value="">Seleccione un instructor</option>
                                    <?php foreach ($instructores as $instructor): ?>
                                        <option value="<?php echo $instructor['id']; ?>" 
                                                <?php echo ($fichaEdit && $fichaEdit['id_instructor'] == $instructor['id']) ? 'selected' : ''; ?>>
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
                                <label for="fecha_creac" class="form-label">Fecha de Creación *</label>
                                <input type="date" class="form-control" id="fecha_creac" name="fecha_creac" 
                                       value="<?php echo $fichaEdit ? $fichaEdit['fecha_creac'] : date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="id_jornada" class="form-label">Jornada *</label>
                                <select class="form-select" id="id_jornada" name="id_jornada" required>
                                    <option value="">Seleccione una jornada</option>
                                    <?php foreach ($jornadas as $jornada): ?>
                                        <option value="<?php echo $jornada['id_jornada']; ?>" 
                                                <?php echo ($fichaEdit && $fichaEdit['id_jornada'] == $jornada['id_jornada']) ? 'selected' : ''; ?>>
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
                                        <option value="<?php echo $tipo['id_tipo_ficha']; ?>" 
                                                <?php echo ($fichaEdit && $fichaEdit['id_tipo_ficha'] == $tipo['id_tipo_ficha']) ? 'selected' : ''; ?>>
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
                            <?php echo $fichaEdit ? 'Actualizar' : 'Guardar'; ?>
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

<!-- Script: Filtro por número de ficha -->
<script>
// Cuando el usuario escribe en el input de búsqueda, filtra las filas (sin paginar todavía)
function filtrarFicha() {
    paginaActual = 1; // Reiniciar a la página 1 siempre que se filtre
    mostrarPagina(paginaActual);
}
</script>

<!-- Script: Paginación (10 fichas por página) -->
<script>
let filasPorPagina = 10;
let paginaActual = 1;

// Devuelve solo las filas que cumplen con el filtro actual
function obtenerFilasFiltradas() {
    let filas = Array.from(document.querySelectorAll("#tablaFichas tbody tr"));
    let filtro = document.getElementById("busquedaFicha").value.trim();
    if (filtro === "") return filas;
    return filas.filter(fila => {
        let idFicha = fila.querySelector("td").textContent.trim();
        return idFicha.includes(filtro);
    });
}

// Muestra la página indicada con sus fichas
function mostrarPagina(pagina) {
    let filas = obtenerFilasFiltradas();
    let totalPaginas = Math.ceil(filas.length / filasPorPagina);

    if (pagina < 1) pagina = 1;
    if (pagina > totalPaginas) pagina = totalPaginas;

    // Oculta todas
    document.querySelectorAll("#tablaFichas tbody tr").forEach(fila => fila.style.display = "none");
    // Muestra solo las de la página actual (de las filtradas)
    let inicio = (pagina - 1) * filasPorPagina;
    let fin = inicio + filasPorPagina;
    for (let i = inicio; i < fin && i < filas.length; i++) {
        filas[i].style.display = "";
    }

    // Actualiza los botones de paginación
    let paginacion = document.getElementById("paginacionFichas");
    paginacion.innerHTML = "";
    if (totalPaginas <= 1) return;

    paginacion.innerHTML += `<li class="page-item ${pagina === 1 ? 'disabled' : ''}">
        <button class="page-link" onclick="cambiarPagina(${pagina - 1})">Anterior</button>
    </li>`;

    for (let i = 1; i <= totalPaginas; i++) {
        paginacion.innerHTML += `<li class="page-item ${pagina === i ? 'active' : ''}">
            <button class="page-link" onclick="cambiarPagina(${i})">${i}</button>
        </li>`;
    }

    paginacion.innerHTML += `<li class="page-item ${pagina === totalPaginas ? 'disabled' : ''}">
        <button class="page-link" onclick="cambiarPagina(${pagina + 1})">Siguiente</button>
    </li>`;

    paginaActual = pagina;
}

// Cambia de página al número solicitado
function cambiarPagina(nuevaPagina) {
    mostrarPagina(nuevaPagina);
}

// Inicializa la paginación al cargar
document.addEventListener("DOMContentLoaded", function() {
    mostrarPagina(paginaActual);
});

// Hace que al escribir en el input de búsqueda se aplique el filtro y la paginación
document.getElementById('busquedaFicha').addEventListener('input', filtrarFicha);
</script>
    
</body>
</html>

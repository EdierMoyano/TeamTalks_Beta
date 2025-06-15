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

// Inicializar mensaje de alerta
$alertMessage = '';
$alertType = '';

// Crear instancia de la conexión
$db = new Database();
$conexion = $db->connect();

// Procesar el formulario de creación/edición de formación
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'crear') {
            // Crear nueva formación
            $nombre = trim($_POST['nombre']);
            $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
            $id_tipo_formacion = $_POST['id_tipo_formacion'];
            $id_estado = 1; // Por defecto activo

            if (empty($nombre) || empty($id_tipo_formacion)) {
                $alertMessage = "El nombre de la formación y el tipo son requeridos";
                $alertType = "danger";
            } else {
                try {
                    $stmt = $conexion->prepare("INSERT INTO formacion (nombre, descripcion, id_tipo_formacion, id_estado, fecha_creacion) VALUES (:nombre, :descripcion, :id_tipo_formacion, :id_estado, CURRENT_DATE)");
                    $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
                    $stmt->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
                    $stmt->bindParam(':id_tipo_formacion', $id_tipo_formacion, PDO::PARAM_INT);
                    $stmt->bindParam(':id_estado', $id_estado, PDO::PARAM_INT);

                    if ($stmt->execute()) {
                        $alertMessage = "Formación creada exitosamente";
                        $alertType = "success";
                    } else {
                        $alertMessage = "Error al crear la formación";
                        $alertType = "danger";
                    }
                } catch (PDOException $e) {
                    $alertMessage = "Error: " . $e->getMessage();
                    $alertType = "danger";
                }
            }
        } elseif ($_POST['action'] == 'editar') {
            // Editar formación existente
            $id = $_POST['id'];
            $id_estado = $_POST['id_estado'];

            try {
                $stmt = $conexion->prepare("UPDATE formacion SET id_estado = :id_estado WHERE id_formacion = :id");
                $stmt->bindParam(':id_estado', $id_estado, PDO::PARAM_INT);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    $_SESSION['alertMessage'] = "Estado de la formación actualizado exitosamente";
                    $_SESSION['alertType'] = "success";
                    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
                    exit;
                } else {
                    $alertMessage = "Error al actualizar la formación";
                    $alertType = "danger";
                }
            } catch (PDOException $e) {
                $alertMessage = "Error: " . $e->getMessage();
                $alertType = "danger";
            }
        } elseif ($_POST['action'] == 'eliminar') {
            // Eliminar formación
            $id = $_POST['id'];

            // Verificar si hay fichas asociadas a esta formación
            $stmt = $conexion->prepare("SELECT COUNT(*) as count FROM fichas WHERE id_formacion = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row['count'] > 0) {
                $alertMessage = "No se puede eliminar la formación porque tiene fichas asociadas";
                $alertType = "danger";
            } else {
                try {
                    $stmt = $conexion->prepare("DELETE FROM formacion WHERE id_formacion = :id");
                    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

                    if ($stmt->execute()) {
                        $alertMessage = "Formación eliminada exitosamente";
                        $alertType = "success";
                    } else {
                        $alertMessage = "Error al eliminar la formación";
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

// Mostrar mensaje después de redireccionar tras editar
if (isset($_SESSION['alertMessage'])) {
    $alertMessage = $_SESSION['alertMessage'];
    $alertType = $_SESSION['alertType'];
    unset($_SESSION['alertMessage'], $_SESSION['alertType']);
}

// Obtener todas las formaciones con información relacionada y conteo de fichas
$formaciones = [];
try {
    $stmt = $conexion->query("SELECT f.*, tf.tipo_formacion, e.estado, 
                             CASE 
                                WHEN tf.id_tipo_formacion = 1 THEN 3 
                                WHEN tf.id_tipo_formacion = 2 THEN 7 
                                ELSE 0 
                             END as duracion_trimestres,
                             COUNT(fi.id_ficha) as total_fichas,
                             COUNT(CASE WHEN fi.id_estado = 1 THEN 1 END) as fichas_activas,
                             COUNT(CASE WHEN fi.id_estado = 2 THEN 1 END) as fichas_inactivas
                             FROM formacion f
                             LEFT JOIN tipo_formacion tf ON f.id_tipo_formacion = tf.id_tipo_formacion
                             LEFT JOIN estado e ON f.id_estado = e.id_estado
                             LEFT JOIN fichas fi ON f.id_formacion = fi.id_formacion
                             GROUP BY f.id_formacion
                             ORDER BY f.nombre");
    $formaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $alertMessage = "Error al cargar formaciones: " . $e->getMessage();
    $alertType = "danger";
}

// Obtener formación para editar si se solicita
$formacionEdit = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    try {
        $stmt = $conexion->prepare("SELECT f.*, tf.tipo_formacion, e.estado,
                                   CASE 
                                      WHEN tf.id_tipo_formacion = 1 THEN 3 
                                      WHEN tf.id_tipo_formacion = 2 THEN 7 
                                      ELSE 0 
                                   END as duracion_trimestres
                                   FROM formacion f
                                   LEFT JOIN tipo_formacion tf ON f.id_tipo_formacion = tf.id_tipo_formacion
                                   LEFT JOIN estado e ON f.id_estado = e.id_estado
                                   WHERE f.id_formacion = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $formacionEdit = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $alertMessage = "Error al cargar la formación: " . $e->getMessage();
        $alertType = "danger";
    }
}

// Obtener tipos de formación
$tiposFormacion = [];
try {
    $stmt = $conexion->query("SELECT * FROM tipo_formacion ORDER BY tipo_formacion");
    $tiposFormacion = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $alertMessage = "Error al cargar tipos de formación: " . $e->getMessage();
    $alertType = "danger";
}

// Obtener estados
$estados = [];
try {
    $stmt = $conexion->query("SELECT * FROM estado ORDER BY estado");
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
    <title>Gestión de Formaciones</title>
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
                        <h4 class="mb-0">Gestión de Formaciones</h4>
                        <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#formacionModal">
                            <i class="bi bi-plus-circle"></i> Nueva Formación
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
                                type="text"
                                id="busquedaFormacion"
                                class="form-control"
                                style="max-width: 350px;"
                                placeholder="Buscar formación (nombre, tipo...)"
                                oninput="filtrarFormacion()">
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover" id="tablaFormaciones">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Tipo</th>
                                        <th>Duración</th>
                                        <th>Estado</th>
                                        <th>Fichas</th>
                                        <th>Fecha Creación</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($formaciones)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No hay formaciones registradas</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($formaciones as $formacion): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($formacion['id_formacion']); ?></td>
                                                <td><?php echo htmlspecialchars($formacion['nombre']); ?></td>
                                                <td><?php echo htmlspecialchars($formacion['tipo_formacion']); ?></td>
                                                <td><?php echo htmlspecialchars($formacion['duracion_trimestres']); ?> trimestres</td>
                                                <td>
                                                    <span class="badge <?php echo ($formacion['id_estado'] == 1) ? 'bg-success' : 'bg-danger'; ?>">
                                                        <?php echo htmlspecialchars($formacion['estado']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <span class="badge bg-primary mb-1">
                                                            Total: <?php echo $formacion['total_fichas']; ?>
                                                        </span>
                                                        <?php if ($formacion['total_fichas'] > 0): ?>
                                                            <small class="text-muted">
                                                                <span class="badge bg-success me-1">Activas: <?php echo $formacion['fichas_activas']; ?></span>
                                                                <span class="badge bg-secondary">Inactivas: <?php echo $formacion['fichas_inactivas']; ?></span>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($formacion['fecha_creacion']))); ?></td>
                                                <td>
                                                    <a href="?edit=<?php echo $formacion['id_formacion']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-danger"
                                                        onclick="confirmarEliminar(<?php echo $formacion['id_formacion']; ?>, '<?php echo htmlspecialchars($formacion['nombre']); ?>')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <nav>
                                <ul class="pagination justify-content-center" id="paginacionFormaciones"></ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para crear formación -->
    <div class="modal fade" id="formacionModal" tabindex="-1" aria-labelledby="formacionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="formacionModalLabel">Nueva Formación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="crear">

                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre de la Formación *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>

                        <div class="mb-3">
                            <label for="id_tipo_formacion" class="form-label">Tipo de Formación *</label>
                            <select class="form-select" id="id_tipo_formacion" name="id_tipo_formacion" required>
                                <option value="">Seleccione un tipo</option>
                                <?php foreach ($tiposFormacion as $tipo): ?>
                                    <option value="<?php echo $tipo['id_tipo_formacion']; ?>"
                                        data-duracion="<?php echo ($tipo['id_tipo_formacion'] == 1) ? 3 : 7; ?>">
                                        <?php echo htmlspecialchars($tipo['tipo_formacion']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="duracion" class="form-label">Duración</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="duracion" readonly value="">
                                <span class="input-group-text">trimestres</span>
                            </div>
                            <div class="form-text">La duración se establece automáticamente según el tipo de formación</div>
                        </div>

                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para editar formación -->
    <div class="modal fade" id="editFormacionModal" tabindex="-1" aria-labelledby="editFormacionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editFormacionModalLabel">Editar Formación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="editar">
                        <input type="hidden" name="id" id="edit-id" value="<?php echo $formacionEdit ? $formacionEdit['id_formacion'] : ''; ?>">

                        <div class="mb-3">
                            <label for="edit-nombre" class="form-label">Nombre de la Formación</label>
                            <input type="text" class="form-control" id="edit-nombre" value="<?php echo $formacionEdit ? htmlspecialchars($formacionEdit['nombre']) : ''; ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="edit-tipo" class="form-label">Tipo de Formación</label>
                            <input type="text" class="form-control" id="edit-tipo" value="<?php echo $formacionEdit ? htmlspecialchars($formacionEdit['tipo_formacion']) : ''; ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="edit-duracion" class="form-label">Duración</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="edit-duracion" value="<?php echo $formacionEdit ? htmlspecialchars($formacionEdit['duracion_trimestres']) : ''; ?>" readonly>
                                <span class="input-group-text">trimestres</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="id_estado" class="form-label">Estado *</label>
                            <select class="form-select" id="id_estado" name="id_estado" required>
                                <?php foreach ($estados as $estado): ?>
                                    <option value="<?php echo $estado['id_estado']; ?>"
                                        <?php echo ($formacionEdit && $formacionEdit['id_estado'] == $estado['id_estado']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($estado['estado']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="edit-descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="edit-descripcion" rows="3" readonly><?php echo $formacionEdit ? htmlspecialchars($formacionEdit['descripcion']) : ''; ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar</button>
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
                    ¿Está seguro que desea eliminar la formación <span id="formacion-nombre"></span>?
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

    <!-- Script: Mostrar modal de edición automáticamente si hay una formación a editar -->
    <script>
        <?php if ($formacionEdit): ?>
            document.addEventListener('DOMContentLoaded', function() {
                var editFormacionModal = new bootstrap.Modal(document.getElementById('editFormacionModal'));
                editFormacionModal.show();
            });
        <?php endif; ?>

        // Función para confirmar eliminación
        function confirmarEliminar(id, nombre) {
            document.getElementById('delete-id').value = id;
            document.getElementById('formacion-nombre').textContent = nombre;
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }

        // Actualizar duración automáticamente según el tipo de formación seleccionado
        document.getElementById('id_tipo_formacion').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value !== '') {
                const duracion = selectedOption.getAttribute('data-duracion');
                document.getElementById('duracion').value = duracion;
            } else {
                document.getElementById('duracion').value = '';
            }
        });
    </script>

    <!-- PAGINACIÓN Y FILTRO JS MEJORADO -->
    <script>
        let filasPorPaginaFormaciones = 5;
        let paginaActualFormaciones = 1;

        function obtenerFilasFormacionesFiltradas() {
            let filas = Array.from(document.querySelectorAll("#tablaFormaciones tbody tr"));
            let filtro = document.getElementById("busquedaFormacion").value.trim().toLowerCase();
            if (filtro === "") return filas;
            return filas.filter(fila => {
                let texto = fila.innerText.toLowerCase();
                return texto.includes(filtro);
            });
        }

        function mostrarPaginaFormaciones(pagina) {
            let filas = obtenerFilasFormacionesFiltradas();
            let totalPaginas = Math.ceil(filas.length / filasPorPaginaFormaciones);

            if (pagina < 1) pagina = 1;
            if (pagina > totalPaginas) pagina = totalPaginas;

            // Ocultar todas las filas
            document.querySelectorAll("#tablaFormaciones tbody tr").forEach(fila => fila.style.display = "none");

            // Mostrar filas de la página actual
            let inicio = (pagina - 1) * filasPorPaginaFormaciones;
            let fin = inicio + filasPorPaginaFormaciones;
            for (let i = inicio; i < fin && i < filas.length; i++) {
                filas[i].style.display = "";
            }

            // Generar paginación inteligente
            generarPaginacionInteligenteFormaciones(pagina, totalPaginas);
            paginaActualFormaciones = pagina;
        }

        function generarPaginacionInteligenteFormaciones(paginaActual, totalPaginas) {
            let paginacion = document.getElementById("paginacionFormaciones");
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
                    <button class="page-link" onclick="cambiarPaginaFormaciones(1)" title="Primera página">
                        <i class="bi bi-chevron-double-left"></i>
                    </button>
                </li>`;
            }

            // Botón "Anterior"
            paginacion.innerHTML += `
            <li class="page-item ${paginaActual === 1 ? 'disabled' : ''}">
                <button class="page-link" onclick="cambiarPaginaFormaciones(${paginaActual - 1})" title="Página anterior">
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
                    <button class="page-link" onclick="cambiarPaginaFormaciones(${i})">${i}</button>
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
                <button class="page-link" onclick="cambiarPaginaFormaciones(${paginaActual + 1})" title="Página siguiente">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </li>`;

            // Botón "Última" (solo si no estamos en la última página)
            if (paginaActual < totalPaginas) {
                paginacion.innerHTML += `
                <li class="page-item">
                    <button class="page-link" onclick="cambiarPaginaFormaciones(${totalPaginas})" title="Última página">
                        <i class="bi bi-chevron-double-right"></i>
                    </button>
                </li>`;
            }

            // Mostrar información de página actual
            mostrarInfoPaginacionFormaciones(paginaActual, totalPaginas, obtenerFilasFormacionesFiltradas().length);
        }

        function mostrarInfoPaginacionFormaciones(paginaActual, totalPaginas, totalRegistros) {
            // Crear o actualizar el elemento de información si no existe
            let infoPaginacion = document.getElementById("infoPaginacionFormaciones");
            if (!infoPaginacion) {
                infoPaginacion = document.createElement("div");
                infoPaginacion.id = "infoPaginacionFormaciones";
                infoPaginacion.className = "text-center mt-2 text-muted small";
                document.getElementById("paginacionFormaciones").parentNode.appendChild(infoPaginacion);
            }

            let registroInicio = ((paginaActual - 1) * filasPorPaginaFormaciones) + 1;
            let registroFin = Math.min(paginaActual * filasPorPaginaFormaciones, totalRegistros);

            infoPaginacion.innerHTML = `
            Mostrando ${registroInicio} a ${registroFin} de ${totalRegistros} registros 
            (Página ${paginaActual} de ${totalPaginas})
        `;
        }

        function cambiarPaginaFormaciones(nuevaPagina) {
            mostrarPaginaFormaciones(nuevaPagina);
        }

        function filtrarFormacion() {
            paginaActualFormaciones = 1;
            mostrarPaginaFormaciones(paginaActualFormaciones);
        }

        // Función para cambiar el número de filas por página
        function cambiarFilasPorPagina(nuevasFilas) {
            filasPorPaginaFormaciones = parseInt(nuevasFilas);
            paginaActualFormaciones = 1;
            mostrarPaginaFormaciones(paginaActualFormaciones);
        }

        document.addEventListener("DOMContentLoaded", function() {
            mostrarPaginaFormaciones(paginaActualFormaciones);
        });
    </script>


    
</body>

</html>
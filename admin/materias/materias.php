<?php
session_start();

if ($_SESSION['rol'] !== 2) {
    header('Location: ../../includes/exit.php?');
    exit;
}

require_once '../../conexion/conexion.php';
require_once '../../includes/functions.php';

// Inicializar mensaje de alerta
$alertMessage = '';
$alertType = '';
$modalMessage = '';
$modalType = '';

// Crear instancia de la conexión
$db = new Database();
$conexion = $db->connect();

// Procesar eliminación de materia
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'eliminar') {
    $id_materia = $_POST['id_materia'];
    
    try {
        // Verificar si la materia está en uso
        $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM materia_ficha WHERE id_materia = :id_materia");
        $stmt->bindParam(':id_materia', $id_materia, PDO::PARAM_INT);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado['total'] > 0) {
            $alertMessage = "No se puede eliminar la materia porque está asignada a " . $resultado['total'] . " ficha(s)";
            $alertType = "danger";
        } else {
            // Eliminar la materia
            $stmt = $conexion->prepare("DELETE FROM materias WHERE id_materia = :id_materia");
            $stmt->bindParam(':id_materia', $id_materia, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $alertMessage = "Materia eliminada correctamente";
                $alertType = "success";
            } else {
                $alertMessage = "Error al eliminar la materia";
                $alertType = "danger";
            }
        }
    } catch (PDOException $e) {
        $alertMessage = "Error: " . $e->getMessage();
        $alertType = "danger";
    }
}

// Procesar formulario de registro/edición manual de materia
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'manual') {
    $materia = trim($_POST['materia']);
    $descripcion = trim($_POST['descripcion']);
    $id_materia = !empty($_POST['id_materia']) ? $_POST['id_materia'] : null;

    // Validar datos básicos
    if (empty($materia)) {
        $alertMessage = "El nombre de la materia es obligatorio";
        $alertType = "danger";
    } else {
        try {
            if ($id_materia) {
                // Editar materia existente
                $stmt = $conexion->prepare("
                    UPDATE materias SET materia = :materia, descripcion = :descripcion 
                    WHERE id_materia = :id_materia
                ");
                $stmt->bindParam(':materia', $materia, PDO::PARAM_STR);
                $stmt->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
                $stmt->bindParam(':id_materia', $id_materia, PDO::PARAM_INT);
                
                if ($stmt->execute()) {
                    $alertMessage = "Materia actualizada correctamente";
                    $alertType = "success";
                } else {
                    $alertMessage = "Error al actualizar la materia";
                    $alertType = "danger";
                }
            } else {
                // Verificar si la materia ya existe
                $stmt = $conexion->prepare("SELECT id_materia FROM materias WHERE LOWER(materia) = LOWER(:materia)");
                $stmt->bindParam(':materia', $materia, PDO::PARAM_STR);
                $stmt->execute();
                $materiaExistente = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($materiaExistente) {
                    $alertMessage = "Ya existe una materia con ese nombre";
                    $alertType = "danger";
                } else {
                    // Crear nueva materia
                    $stmt = $conexion->prepare("
                        INSERT INTO materias (materia, descripcion) 
                        VALUES (:materia, :descripcion)
                    ");
                    $stmt->bindParam(':materia', $materia, PDO::PARAM_STR);
                    $stmt->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
                    
                    if ($stmt->execute()) {
                        $alertMessage = "Materia registrada correctamente";
                        $alertType = "success";
                    } else {
                        $alertMessage = "Error al registrar la materia";
                        $alertType = "danger";
                    }
                }
            }
        } catch (PDOException $e) {
            $alertMessage = "Error: " . $e->getMessage();
            $alertType = "danger";
        }
    }
}

// Procesar registro masivo por CSV
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'masivo') {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $nombreArchivo = $_FILES['csv_file']['tmp_name'];
        
        // Validar extensión
        $fileExtension = pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION);
        if ($fileExtension != 'csv') {
            $alertMessage = "Solo se permiten archivos CSV";
            $alertType = "danger";
        } else {
            $resultados = [
                'exitosos' => 0,
                'errores' => 0,
                'duplicados' => 0
            ];
            
            $erroresDetalle = [];
            
            // Leer archivo CSV
            if (($handle = fopen($nombreArchivo, "r")) !== FALSE) {
                $esPrimera = true;

                // Iniciar transacción
                $conexion->beginTransaction();
                try {
                    while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                        if ($esPrimera) { 
                            $esPrimera = false; 
                            continue;
                        }

                        if (count($data) < 1) {
                            $erroresDetalle[] = "Formato incorrecto en línea: " . implode(';', $data);
                            $resultados['errores']++;
                            continue;
                        }
                        
                        // Extraer datos
                        $materia = trim($data[0]);
                        $descripcion = isset($data[1]) ? trim($data[1]) : '';

                        // Validar datos básicos
                        if (empty($materia)) {
                            $erroresDetalle[] = "Nombre de materia vacío en línea: " . implode(';', $data);
                            $resultados['errores']++;
                            continue;
                        }

                        // Verificar si la materia ya existe
                        $stmt = $conexion->prepare("SELECT id_materia FROM materias WHERE LOWER(materia) = LOWER(:materia)");
                        $stmt->bindParam(':materia', $materia, PDO::PARAM_STR);
                        $stmt->execute();
                        $materiaExistente = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($materiaExistente) {
                            $erroresDetalle[] = "Materia duplicada: " . $materia;
                            $resultados['duplicados']++;
                            continue;
                        }
                        
                        // Crear nueva materia
                        $stmt = $conexion->prepare("
                            INSERT INTO materias (materia, descripcion) 
                            VALUES (:materia, :descripcion)
                        ");
                        $stmt->bindParam(':materia', $materia, PDO::PARAM_STR);
                        $stmt->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
                        
                        if ($stmt->execute()) {
                            $resultados['exitosos']++;
                        } else {
                            $erroresDetalle[] = "Error al crear materia: " . $materia;
                            $resultados['errores']++;
                        }
                    }
                    
                    // Commit de la transacción
                    $conexion->commit();
                    
                    // Preparar mensaje de resultado
                    $mensajeResultado = "Procesamiento completado: <br>";
                    $mensajeResultado .= "- Materias creadas: " . $resultados['exitosos'] . "<br>";
                    $mensajeResultado .= "- Materias duplicadas: " . $resultados['duplicados'] . "<br>";
                    $mensajeResultado .= "- Errores: " . $resultados['errores'] . "<br>";
                    
                    if (count($erroresDetalle) > 0) {
                        $mensajeResultado .= "<hr><strong>Detalle de errores:</strong><br>";
                        $mensajeResultado .= implode("<br>", array_slice($erroresDetalle, 0, 10));
                        if (count($erroresDetalle) > 10) {
                            $mensajeResultado .= "<br>... y " . (count($erroresDetalle) - 10) . " errores más.";
                        }
                    }
                    
                    $modalMessage = $mensajeResultado;
                    $modalType = ($resultados['exitosos'] > 0) ? "success" : "warning";
                    
                } catch (PDOException $e) {
                    // Rollback en caso de error
                    $conexion->rollBack();
                    $alertMessage = "Error en la transacción: " . $e->getMessage();
                    $alertType = "danger";
                }
                
                fclose($handle);
            } else {
                $alertMessage = "No se pudo abrir el archivo";
                $alertType = "danger";
            }
        }
    } else {
        $alertMessage = "No se seleccionó ningún archivo o hubo un error al subirlo";
        $alertType = "danger";
    }
}

// Obtener lista de materias con información de uso
$materias = [];
try {
    $stmt = $conexion->query("
        SELECT m.id_materia, m.materia, m.descripcion,
               COUNT(mf.id_materia_ficha) as fichas_asignadas
        FROM materias m
        LEFT JOIN materia_ficha mf ON m.id_materia = mf.id_materia
        GROUP BY m.id_materia, m.materia, m.descripcion
        ORDER BY m.id_materia DESC
    ");
    $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $alertMessage = "Error al cargar materias: " . $e->getMessage();
    $alertType = "danger";
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Materias</title>
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
                <!-- Tarjeta para Registro de materias -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Gestión de Materias</h4>
                        <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#materiaModal">
                            <i class="bi bi-journal-plus"></i> Nueva Materia
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($alertMessage)): ?>
                            <div class="alert alert-<?php echo $alertType; ?> alert-dismissible fade show" role="alert">
                                <?php echo $alertMessage; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        <form action="" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="masivo">
                            <div class="row align-items-end">
                                <div class="col-md-7 mb-3">
                                    <label for="csv_file" class="form-label">Archivo CSV con materias:</label>
                                    <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                                    <div class="form-text">
                                        Formato: Materia;Descripcion<br>
                                        Ejemplo: Matemáticas;Matemáticas básicas y aplicadas
                                    </div>
                                </div>
                                <div class="col-md-5 mb-3">
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-cloud-upload"></i> Cargar Materias
                                        </button>
                                        <a href="plantillas/plantilla_materias.csv" download class="btn btn-outline-secondary">
                                            <i class="bi bi-download"></i> Descargar Plantilla
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tarjeta para listar materias -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Lista de Materias (<?php echo count($materias); ?> registradas)</h4>
                    </div>
                    <div class="table-responsive">
                        <div class="d-flex justify-content-end mt-3 mb-4">
                            <input 
                                type="text" 
                                id="busquedaMateria" 
                                class="form-control me-3" 
                                style="max-width: 350px;" 
                                placeholder="Buscar materia..." 
                                oninput="filtrarMateria()"
                            >
                        </div>
                        <table class="table table-hover" id="tablaMaterias">
                            <thead>
                                <tr>
                                    
                                    <th>Materia</th>
                                    <th>Descripción</th>
                                    <th>Fichas Asignadas</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($materias)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No hay materias registradas</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($materias as $materia): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($materia['materia']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($materia['descripcion'] ?? 'Sin descripción'); ?></td>
                                            <td>
                                                <?php if ($materia['fichas_asignadas'] > 0): ?>
                                                    <span class="badge bg-info"><?php echo $materia['fichas_asignadas']; ?> ficha(s)</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Sin asignar</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($materia['fichas_asignadas'] > 0): ?>
                                                    <span class="badge bg-success">En uso</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Disponible</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-primary edit-materia" 
                                                            data-id="<?php echo $materia['id_materia']; ?>"
                                                            data-materia="<?php echo htmlspecialchars($materia['materia']); ?>"
                                                            data-descripcion="<?php echo htmlspecialchars($materia['descripcion'] ?? ''); ?>"
                                                            data-bs-toggle="tooltip" 
                                                            title="Editar materia">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <?php if ($materia['fichas_asignadas'] == 0): ?>
                                                        <button class="btn btn-sm btn-outline-danger delete-materia" 
                                                                data-id="<?php echo $materia['id_materia']; ?>"
                                                                data-materia="<?php echo htmlspecialchars($materia['materia']); ?>"
                                                                data-bs-toggle="tooltip" 
                                                                title="Eliminar materia">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-outline-secondary" 
                                                                disabled
                                                                data-bs-toggle="tooltip" 
                                                                title="No se puede eliminar - Materia en uso">
                                                            <i class="bi bi-lock"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <nav>
                            <ul class="pagination justify-content-center" id="paginacionMaterias"></ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para registro/edición manual -->
    <div class="modal fade" id="materiaModal" tabindex="-1" aria-labelledby="materiaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="materiaModalLabel">Nueva Materia</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST" id="materiaForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="manual">
                        <input type="hidden" name="id_materia" id="id_materia">
                        
                        <div class="mb-3">
                            <label for="materia" class="form-label">Nombre de la Materia *</label>
                            <input type="text" class="form-control" id="materia" name="materia" required 
                                   placeholder="Ej: Matemáticas, Inglés, Programación...">
                        </div>
                        
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3" 
                                      placeholder="Descripción opcional de la materia"></textarea>
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

    <!-- Modal de confirmación para eliminar -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro que desea eliminar la materia <strong id="deleteMateriaNombre"></strong>?</p>
                    <p class="text-muted">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <form action="" method="POST" id="deleteForm">
                        <input type="hidden" name="action" value="eliminar">
                        <input type="hidden" name="id_materia" id="deleteIdMateria">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para mostrar resultados de carga masiva -->
    <div class="modal fade" id="resultadosModal" tabindex="-1" aria-labelledby="resultadosModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" id="resultadosModalHeader">
                    <h5 class="modal-title" id="resultadosModalLabel">Resultados de Carga</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="resultadosModalBody">
                    <!-- Aquí se insertarán los resultados -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/sidebard.js"></script>

    <!-- PAGINACIÓN Y FILTRO JS -->
    <script>
    let filasPorPaginaMaterias = 8;
    let paginaActualMaterias = 1;

    function obtenerFilasMateriasFiltradas() {
        let filas = Array.from(document.querySelectorAll("#tablaMaterias tbody tr"));
        let filtro = document.getElementById("busquedaMateria").value.trim().toLowerCase();
        if (filtro === "") return filas;
        return filas.filter(fila => {
            let texto = fila.innerText.toLowerCase();
            return texto.includes(filtro);
        });
    }

    function mostrarPaginaMaterias(pagina) {
        let filas = obtenerFilasMateriasFiltradas();
        let totalPaginas = Math.ceil(filas.length / filasPorPaginaMaterias);
        if (pagina < 1) pagina = 1;
        if (pagina > totalPaginas) pagina = totalPaginas;

        document.querySelectorAll("#tablaMaterias tbody tr").forEach(fila => fila.style.display = "none");
        let inicio = (pagina - 1) * filasPorPaginaMaterias;
        let fin = inicio + filasPorPaginaMaterias;
        for (let i = inicio; i < fin && i < filas.length; i++) {
            filas[i].style.display = "";
        }

        let paginacion = document.getElementById("paginacionMaterias");
        paginacion.innerHTML = "";
        if (totalPaginas <= 1) return;

        paginacion.innerHTML += `<li class="page-item ${pagina === 1 ? 'disabled' : ''}">
            <button class="page-link" onclick="cambiarPaginaMaterias(${pagina - 1})">Anterior</button>
        </li>`;

        for (let i = 1; i <= totalPaginas; i++) {
            paginacion.innerHTML += `<li class="page-item ${pagina === i ? 'active' : ''}">
                <button class="page-link" onclick="cambiarPaginaMaterias(${i})">${i}</button>
            </li>`;
        }

        paginacion.innerHTML += `<li class="page-item ${pagina === totalPaginas ? 'disabled' : ''}">
            <button class="page-link" onclick="cambiarPaginaMaterias(${pagina + 1})">Siguiente</button>
        </li>`;

        paginaActualMaterias = pagina;
    }

    function cambiarPaginaMaterias(nuevaPagina) {
        mostrarPaginaMaterias(nuevaPagina);
    }

    function filtrarMateria() {
        paginaActualMaterias = 1;
        mostrarPaginaMaterias(paginaActualMaterias);
    }

    document.addEventListener("DOMContentLoaded", function() {
        mostrarPaginaMaterias(paginaActualMaterias);

        <?php if (!empty($modalMessage)): ?>
            const resultadosModal = new bootstrap.Modal(document.getElementById('resultadosModal'));
            const resultadosModalHeader = document.getElementById('resultadosModalHeader');
            const resultadosModalBody = document.getElementById('resultadosModalBody');
            resultadosModalHeader.className = 'modal-header <?php echo ($modalType == "success") ? "bg-success" : "bg-warning"; ?> text-white';
            resultadosModalBody.innerHTML = `<?php echo $modalMessage; ?>`;
            resultadosModal.show();
        <?php endif; ?>

        // Inicializar tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Resetear formulario cuando se cierra el modal
        document.getElementById('materiaModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('materiaForm').reset();
            document.getElementById('materiaModalLabel').textContent = 'Nueva Materia';
            document.getElementById('id_materia').value = '';
        });

        // Manejar edición de materias
        const editButtons = document.querySelectorAll('.edit-materia');
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const materiaId = this.getAttribute('data-id');
                const materiaNombre = this.getAttribute('data-materia');
                const materiaDescripcion = this.getAttribute('data-descripcion');
                
                document.getElementById('id_materia').value = materiaId;
                document.getElementById('materia').value = materiaNombre;
                document.getElementById('descripcion').value = materiaDescripcion;
                document.getElementById('materiaModalLabel').textContent = 'Editar Materia';
                
                const materiaModal = new bootstrap.Modal(document.getElementById('materiaModal'));
                materiaModal.show();
            });
        });

        // Manejar eliminación de materias
        const deleteButtons = document.querySelectorAll('.delete-materia');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const materiaId = this.getAttribute('data-id');
                const materiaNombre = this.getAttribute('data-materia');
                
                document.getElementById('deleteIdMateria').value = materiaId;
                document.getElementById('deleteMateriaNombre').textContent = materiaNombre;
                
                const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                deleteModal.show();
            });
        });
    });
    </script>

    // Script para cerrar sesión al recargar la página
<script>
window.addEventListener('beforeunload', function () {
    // Aquí puedes enviar una solicitud AJAX para cerrar la sesión en el servidor
    var xhr = new XMLHttpRequest();
    xhr.open('GET', '../../includes/exit.php', true);
    xhr.send();
});
</script>
</body>
</html>

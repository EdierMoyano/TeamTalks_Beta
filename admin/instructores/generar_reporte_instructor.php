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

$id_instructor = $_GET['id_instructor'] ?? '';
$tipo_reporte = $_GET['tipo_reporte'] ?? '';

if (empty($id_instructor)) {
    echo json_encode(['success' => false, 'message' => 'ID de instructor requerido']);
    exit;
}

try {
    // Obtener datos básicos del instructor
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
        WHERE u.id = ? AND u.id_rol IN (3, 5)
    ");
    $stmt->execute([$id_instructor]);
    $instructor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$instructor) {
        echo json_encode(['success' => false, 'message' => 'Instructor no encontrado']);
        exit;
    }

    ob_start();
    ?>
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="bi bi-file-earmark-text"></i> Generar Reporte - 
                    <?php echo htmlspecialchars($instructor['nombres'] . ' ' . $instructor['apellidos']); ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <h6 class="text-success mb-3">Selecciona el tipo de reporte:</h6>
                        
                        <div class="d-grid gap-3">
                            <button class="btn btn-outline-success btn-lg text-start" onclick="generarReporte('general', <?php echo $id_instructor; ?>)">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-file-earmark-person display-6 me-3"></i>
                                    <div>
                                        <h6 class="mb-1">Reporte General Completo</h6>
                                        <small class="text-muted">Datos personales, fichas asignadas, horarios y cálculo de horas del trimestre</small>
                                    </div>
                                </div>
                            </button>

                            <button class="btn btn-outline-success btn-lg text-start" onclick="generarReporte('personal', <?php echo $id_instructor; ?>)">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-person-vcard display-6 me-3"></i>
                                    <div>
                                        <h6 class="mb-1">Reporte de Datos Personales</h6>
                                        <small class="text-muted">Información personal y de contacto del instructor</small>
                                    </div>
                                </div>
                            </button>

                            <button class="btn btn-outline-success btn-lg text-start" onclick="generarReporte('fichas', <?php echo $id_instructor; ?>)">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-folder-check display-6 me-3"></i>
                                    <div>
                                        <h6 class="mb-1">Reporte de Fichas Asignadas</h6>
                                        <small class="text-muted">Fichas con información de aprendices y materias</small>
                                    </div>
                                </div>
                            </button>

                            <?php
                            // Obtener fichas para reporte individual
                            $stmt = $conexion->prepare("
                                SELECT DISTINCT f.id_ficha, fo.nombre as programa
                                FROM fichas f
                                INNER JOIN materia_ficha mf ON f.id_ficha = mf.id_ficha
                                LEFT JOIN formacion fo ON f.id_formacion = fo.id_formacion
                                WHERE mf.id_instructor = ? AND f.id_estado = 1
                                ORDER BY f.id_ficha DESC
                            ");
                            $stmt->execute([$id_instructor]);
                            $fichas_disponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            ?>

                            <?php if (!empty($fichas_disponibles)): ?>
                                <div class="border-top pt-3">
                                    <h6 class="text-success mb-3">Reporte por Ficha Individual:</h6>
                                    <div class="row">
                                        <div class="col-md-8">
                                            <select class="form-select" id="selectFichaIndividual">
                                                <option value="">Seleccionar ficha...</option>
                                                <?php foreach ($fichas_disponibles as $ficha): ?>
                                                    <option value="<?php echo $ficha['id_ficha']; ?>">
                                                        Ficha <?php echo $ficha['id_ficha']; ?> - <?php echo htmlspecialchars($ficha['programa']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <button class="btn btn-info w-100" onclick="generarReporteFichaSeleccionada(<?php echo $id_instructor; ?>)">
                                                <i class="bi bi-file-earmark-excel"></i> Generar Reporte
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>

    <script>
        async function generarReporte(tipo, idInstructor, idFicha = null) {
            try {
                let url = `procesar_reporte_instructor.php?id_instructor=${idInstructor}&tipo_reporte=${tipo}`;
                if (idFicha) {
                    url += `&id_ficha=${idFicha}`;
                }

                // Mostrar loading
                Swal.fire({
                    title: 'Generando reporte...',
                    text: 'Por favor espera',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Crear formulario temporal para forzar descarga
                const form = document.createElement('form');
                form.method = 'GET';
                form.action = url;
                form.target = '_blank';
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);

                // Cerrar loading después de un momento
                setTimeout(() => {
                    Swal.fire('Éxito', 'Reporte generado correctamente', 'success');
                }, 1000);
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'No se pudo generar el reporte', 'error');
            }
        }

        function generarReporteFichaSeleccionada(idInstructor) {
            const selectFicha = document.getElementById('selectFichaIndividual');
            const idFicha = selectFicha.value;
            
            if (!idFicha) {
                Swal.fire('Atención', 'Por favor selecciona una ficha', 'warning');
                return;
            }
            
            generarReporte('ficha_individual', idInstructor, idFicha);
        }
    </script>
    <?php
    $html = ob_get_clean();
    
    echo json_encode([
        'success' => true,
        'html' => $html
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar opciones de reporte: ' . $e->getMessage()
    ]);
}
?>

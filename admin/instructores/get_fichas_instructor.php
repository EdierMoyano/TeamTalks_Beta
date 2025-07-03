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

if (empty($id_instructor)) {
    echo json_encode(['success' => false, 'message' => 'ID de instructor requerido']);
    exit;
}

try {
    // Obtener fichas del instructor
    $stmt = $conexion->prepare("
        SELECT DISTINCT
            f.id_ficha,
            fo.nombre as programa,
            tf.tipo_formacion,
            j.jornada,
            COUNT(DISTINCT uf.id_user) as aprendices_asignados
        FROM fichas f
        INNER JOIN materia_ficha mf ON f.id_ficha = mf.id_ficha
        LEFT JOIN formacion fo ON f.id_formacion = fo.id_formacion
        LEFT JOIN tipo_formacion tf ON fo.id_tipo_formacion = tf.id_tipo_formacion
        LEFT JOIN jornada j ON f.id_jornada = j.id_jornada
        LEFT JOIN user_ficha uf ON f.id_ficha = uf.id_ficha AND uf.id_estado = 1
        WHERE mf.id_instructor = ? AND f.id_estado = 1
        GROUP BY f.id_ficha, fo.nombre, tf.tipo_formacion, j.jornada
        ORDER BY f.id_ficha DESC
    ");
    $stmt->execute([$id_instructor]);
    $fichas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener instructores con el mismo rol para transferencia
    $stmt = $conexion->prepare("
        SELECT u.id, u.nombres, u.apellidos
        FROM usuarios u
        WHERE u.id_rol = 3 AND u.id_estado = 1 AND u.id != ?
        ORDER BY u.nombres, u.apellidos
    ");
    $stmt->execute([$id_instructor]);
    $instructores_disponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_start();
    ?>
    <form id="formGestionarFichas">
        <input type="hidden" name="id_instructor" value="<?php echo htmlspecialchars($id_instructor); ?>">
        
        <div class="mb-4">
            <h6 class="text-primary"><i class="bi bi-folder-check"></i> Fichas Asignadas</h6>
            <p class="text-muted">Selecciona las fichas que deseas gestionar:</p>
        </div>

        <?php if (!empty($fichas)): ?>
            <?php foreach ($fichas as $ficha): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h6 class="mb-1">Ficha: <strong><?php echo $ficha['id_ficha']; ?></strong></h6>
                                <p class="mb-1 text-muted"><?php echo htmlspecialchars($ficha['programa']); ?></p>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($ficha['tipo_formacion']); ?> - 
                                    <?php echo htmlspecialchars($ficha['jornada']); ?> - 
                                    <?php echo $ficha['aprendices_asignados']; ?> aprendices
                                </small>
                            </div>
                            <div class="col-md-4">
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-warning btn-sm transferir-ficha" 
                                            data-ficha="<?php echo $ficha['id_ficha']; ?>"
                                            data-programa="<?php echo htmlspecialchars($ficha['programa']); ?>">
                                        <i class="bi bi-arrow-right-circle"></i> Transferir
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm dejar-administrar" 
                                            data-ficha="<?php echo $ficha['id_ficha']; ?>"
                                            data-programa="<?php echo htmlspecialchars($ficha['programa']); ?>">
                                        <i class="bi bi-x-circle"></i> Dejar de Administrar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> Este instructor no tiene fichas asignadas.
            </div>
        <?php endif; ?>
    </form>

    <!-- Modal para transferir ficha -->
    <div class="modal fade" id="transferirFichaModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">Transferir Ficha</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formTransferirFicha">
                    <div class="modal-body">
                        <input type="hidden" id="ficha_transferir" name="id_ficha">
                        <input type="hidden" name="id_instructor_origen" value="<?php echo htmlspecialchars($id_instructor); ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Ficha a transferir:</label>
                            <p class="fw-bold" id="info_ficha_transferir"></p>
                        </div>

                        <div class="mb-3">
                            <label for="instructor_destino" class="form-label">Transferir a:</label>
                            <select class="form-select" id="instructor_destino" name="id_instructor_destino" required>
                                <option value="">Seleccionar instructor...</option>
                                <?php foreach ($instructores_disponibles as $instructor_disp): ?>
                                    <option value="<?php echo $instructor_disp['id']; ?>">
                                        <?php echo htmlspecialchars($instructor_disp['nombres'] . ' ' . $instructor_disp['apellidos']); ?>
                                        (<?php echo $instructor_disp['id']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Transferir Ficha</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para dejar de administrar -->
    <div class="modal fade" id="dejarAdministrarModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Dejar de Administrar Ficha</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="formDejarAdministrar">
                    <div class="modal-body">
                        <input type="hidden" id="ficha_dejar" name="id_ficha">
                        <input type="hidden" name="id_instructor_origen" value="<?php echo htmlspecialchars($id_instructor); ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Ficha:</label>
                            <p class="fw-bold" id="info_ficha_dejar"></p>
                        </div>

                        <div class="mb-3">
                            <label for="nuevo_instructor" class="form-label">Asignar a:</label>
                            <select class="form-select" id="nuevo_instructor" name="id_nuevo_instructor" required>
                                <option value="">Seleccionar instructor...</option>
                                <?php foreach ($instructores_disponibles as $instructor_disp): ?>
                                    <option value="<?php echo $instructor_disp['id']; ?>">
                                        <?php echo htmlspecialchars($instructor_disp['nombres'] . ' ' . $instructor_disp['apellidos']); ?>
                                        (<?php echo $instructor_disp['id']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Atención:</strong> Esta acción transferirá todas las materias de esta ficha al nuevo instructor.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Confirmar Cambio</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Event listeners para los botones
        document.querySelectorAll('.transferir-ficha').forEach(btn => {
            btn.addEventListener('click', function() {
                const ficha = this.dataset.ficha;
                const programa = this.dataset.programa;
                
                document.getElementById('ficha_transferir').value = ficha;
                document.getElementById('info_ficha_transferir').textContent = `${ficha} - ${programa}`;
                
                const modal = new bootstrap.Modal(document.getElementById('transferirFichaModal'));
                modal.show();
            });
        });

        document.querySelectorAll('.dejar-administrar').forEach(btn => {
            btn.addEventListener('click', function() {
                const ficha = this.dataset.ficha;
                const programa = this.dataset.programa;
                
                document.getElementById('ficha_dejar').value = ficha;
                document.getElementById('info_ficha_dejar').textContent = `${ficha} - ${programa}`;
                
                const modal = new bootstrap.Modal(document.getElementById('dejarAdministrarModal'));
                modal.show();
            });
        });

        // Manejar envío de formularios
        document.getElementById('formTransferirFicha').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('accion', 'transferir');
            
            try {
                const response = await fetch('gestionar_fichas_instructor.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire('Éxito', data.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch (error) {
                Swal.fire('Error', 'Error de conexión', 'error');
            }
        });

        document.getElementById('formDejarAdministrar').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('accion', 'dejar_administrar');
            
            try {
                const response = await fetch('gestionar_fichas_instructor.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire('Éxito', data.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch (error) {
                Swal.fire('Error', 'Error de conexión', 'error');
            }
        });
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
        'message' => 'Error al cargar fichas: ' . $e->getMessage()
    ]);
}
?>

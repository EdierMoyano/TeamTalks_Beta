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
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$busqueda = $_GET['busqueda'] ?? '';

if (empty($id_instructor)) {
    echo json_encode(['success' => false, 'message' => 'ID de instructor requerido']);
    exit;
}

$materias_por_pagina = 8;
$offset = ($pagina - 1) * $materias_por_pagina;

try {
    // Construir consulta con filtro de búsqueda
    $where_clause = "1=1";
    $params = [];

    if (!empty($busqueda)) {
        $where_clause .= " AND (materia LIKE ? OR descripcion LIKE ?)";
        $busqueda_param = "%$busqueda%";
        $params = [$busqueda_param, $busqueda_param];
    }

    // Obtener total de materias para paginación
    $count_query = "SELECT COUNT(*) as total FROM materias WHERE $where_clause";
    $stmt = $conexion->prepare($count_query);
    $stmt->execute($params);
    $total_materias = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_paginas = ceil($total_materias / $materias_por_pagina);

    // Obtener materias con paginación
    $query = "SELECT id_materia, materia, descripcion FROM materias WHERE $where_clause ORDER BY materia LIMIT $materias_por_pagina OFFSET $offset";
    $stmt = $conexion->prepare($query);
    $stmt->execute($params);
    $materias_disponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener materia ya asignada al instructor (solo una)
    $stmt = $conexion->prepare("SELECT id_materia FROM materia_instructor WHERE id_instructor = ? LIMIT 1");
    $stmt->execute([$id_instructor]);
    $materia_asignada = $stmt->fetchColumn();

    // Generar HTML
    ob_start();
?>
    <form id="formAsignarMaterias" data-instructor-id="<?php echo htmlspecialchars($id_instructor); ?>">
        <input type="hidden" name="id_instructor" value="<?php echo htmlspecialchars($id_instructor); ?>">

        <!-- Buscador -->
        <div class="mb-4">
            <div class="input-group">
                <span class="input-group-text bg-primary text-white">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text"
                    class="form-control"
                    id="buscarMaterias"
                    placeholder="Buscar materias por nombre o descripción..."
                    value="<?php echo htmlspecialchars($busqueda); ?>">
                <button type="button" class="btn btn-outline-primary" id="limpiarBusqueda">
                    <i class="bi bi-x-circle"></i>
                </button>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold text-primary">
                <i class="bi bi-book"></i> Seleccionar Materia Especializada:
            </label>
            <div class="form-text mb-3">
                <i class="bi bi-info-circle"></i>
                Selecciona UNA materia en la que este instructor se especializa.
                <?php if ($materia_asignada): ?>
                    <br><strong class="text-warning">Nota:</strong> Este instructor ya tiene una materia asignada. Seleccionar una nueva reemplazará la anterior.
                <?php endif; ?>
            </div>

            <?php if (!empty($materias_disponibles)): ?>
                <div class="materias-container border rounded p-3" style="max-height: 400px; overflow-y: auto;">
                    <div class="row">
                        <?php foreach ($materias_disponibles as $materia): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card h-100 materia-card <?php echo ($materia['id_materia'] == $materia_asignada) ? 'border-primary bg-light' : 'border-secondary'; ?>">
                                    <div class="card-body p-3">
                                        <div class="form-check">
                                            <input class="form-check-input materia-radio"
                                                type="radio"
                                                name="materia_seleccionada"
                                                value="<?php echo $materia['id_materia']; ?>"
                                                id="materia_<?php echo $materia['id_materia']; ?>"
                                                <?php echo ($materia['id_materia'] == $materia_asignada) ? 'checked' : ''; ?>>
                                            <label class="form-check-label w-100" for="materia_<?php echo $materia['id_materia']; ?>">
                                                <div class="d-flex flex-column">
                                                    <strong class="text-primary"><?php echo htmlspecialchars($materia['materia']); ?></strong>
                                                    <?php if (!empty($materia['descripcion'])): ?>
                                                        <small class="text-muted mt-1">
                                                            <?php echo htmlspecialchars($materia['descripcion']); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                    <?php if ($materia['id_materia'] == $materia_asignada): ?>
                                                        <small class="text-success mt-1">
                                                            <i class="bi bi-check-circle"></i> Actualmente asignada
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Paginación -->
                <?php if ($total_paginas > 1): ?>
                    <nav aria-label="Paginación de materias" class="mt-3">
                        <ul class="pagination pagination-sm justify-content-center" id="paginacionMaterias">
                            <?php if ($pagina > 1): ?>
                                <li class="page-item">
                                    <button type="button" class="page-link" data-pagina="<?php echo ($pagina - 1); ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </button>
                                </li>
                            <?php endif; ?>

                            <?php
                            $inicio_pag = max(1, $pagina - 2);
                            $fin_pag = min($total_paginas, $inicio_pag + 4);

                            if ($fin_pag - $inicio_pag < 4) {
                                $inicio_pag = max(1, $fin_pag - 4);
                            }

                            for ($i = $inicio_pag; $i <= $fin_pag; $i++): ?>
                                <li class="page-item <?php echo ($i == $pagina) ? 'active' : ''; ?>">
                                    <button type="button" class="page-link" data-pagina="<?php echo $i; ?>">
                                        <?php echo $i; ?>
                                    </button>
                                </li>
                            <?php endfor; ?>

                            <?php if ($pagina < $total_paginas): ?>
                                <li class="page-item">
                                    <button type="button" class="page-link" data-pagina="<?php echo ($pagina + 1); ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </button>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

                <!-- Información de paginación -->
                <div class="text-center mt-2">
                    <small class="text-muted">
                        <i class="bi bi-info-circle"></i>
                        Mostrando <?php echo count($materias_disponibles); ?> de <?php echo $total_materias; ?> materias
                        <?php if (!empty($busqueda)): ?>
                            | Filtrado por: "<?php echo htmlspecialchars($busqueda); ?>"
                        <?php endif; ?>
                    </small>
                </div>

            <?php else: ?>
                <div class="text-center py-4">
                    <i class="bi bi-search display-4 text-muted"></i>
                    <h6 class="text-muted mt-2">No se encontraron materias</h6>
                    <?php if (!empty($busqueda)): ?>
                        <p class="text-muted">Intenta con otros términos de búsqueda</p>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="limpiarBusquedaVacio">
                            <i class="bi bi-arrow-clockwise"></i> Limpiar búsqueda
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-lg" id="btnGuardarMaterias">
                <i class="bi bi-check-circle"></i> Guardar Asignación de Materia
            </button>
        </div>
    </form>

    <!-- Datos para JavaScript -->
    <script type="application/json" id="materiasData">
        {
            "instructorId": "<?php echo htmlspecialchars($id_instructor); ?>",
            "pagina": <?php echo $pagina; ?>,
            "busqueda": "<?php echo htmlspecialchars($busqueda); ?>",
            "totalPaginas": <?php echo $total_paginas; ?>
        }
    </script>

    <style>
        .materia-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .materia-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .materia-card .form-check-label {
            cursor: pointer;
        }

        .materias-container {
            background-color: #f8f9fa;
        }

        .page-link {
            color: #0e4a86;
        }

        .page-item.active .page-link {
            background-color: #0e4a86;
            border-color: #0e4a86;
        }

        .btn-primary {
            background-color: #0e4a86;
            border-color: #0e4a86;
        }

        .btn-primary:hover {
            background-color: #1765b4;
            border-color: #1765b4;
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

        /* Estilo especial para radio buttons */
        .materia-radio:checked + .form-check-label {
            font-weight: bold;
        }

        .materia-card.border-primary {
            box-shadow: 0 0 0 0.2rem rgba(14, 74, 134, 0.25);
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
        'message' => 'Error al cargar materias: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error inesperado: ' . $e->getMessage()
    ]);
}
?>

<?php
if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['DOCUMENT_ROOT'], 'htdocs') !== false) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php';
} else {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';
}
include 'session.php';

if ($_SESSION['rol'] !== 3 && $_SESSION['rol'] !== 5) {
    header('Location:' . BASE_URL . '/includes/exit.php?motivo=acceso-denegado');
    exit;
}

$id_instructor = $_SESSION['documento'];
$id_aprendiz = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$sql_aprendiz = "
  SELECT 
    u.nombres, 
    u.apellidos, 
    u.id,
    u.avatar, 
    f.id_ficha, 
    fo.nombre AS nombre_formacion
  FROM usuarios u
  JOIN user_ficha uf ON u.id = uf.id_user
  JOIN fichas f ON uf.id_ficha = f.id_ficha
  JOIN formacion fo ON f.id_formacion = fo.id_formacion
  WHERE u.id = :id_aprendiz
";

$stmt_aprendiz = $conex->prepare($sql_aprendiz);
$stmt_aprendiz->execute(['id_aprendiz' => $id_aprendiz]);
$aprendiz = $stmt_aprendiz->fetch(PDO::FETCH_ASSOC);

if (!$aprendiz) {
    echo "<div class='alert alert-danger text-center mt-4'>Aprendiz no encontrado.</div>";
    exit;
}

// Obtener actividades del aprendiz
$sql = "
  SELECT
    a.id_actividad,
    a.titulo,
    a.descripcion,
    a.fecha_entrega,
    m.materia,
    e.estado AS estado_actividad,
    au.nota,
    au.comentario_inst,
    au.fecha_entrega AS fecha_entregada_estudiante,
    au.archivo1,
    au.archivo2,
    au.archivo3,
    au.contenido
  FROM actividades_user au
  JOIN actividades a ON au.id_actividad = a.id_actividad
  JOIN estado e ON au.id_estado_actividad = e.id_estado
  JOIN materia_ficha mf ON a.id_materia_ficha = mf.id_materia_ficha
  JOIN materias m ON mf.id_materia = m.id_materia
  WHERE au.id_user = :id_aprendiz 
    AND mf.id_instructor = :id_instructor
  ORDER BY a.fecha_entrega DESC
";

$stmt = $conex->prepare($sql);
$stmt->execute([
    'id_aprendiz' => $id_aprendiz,
    'id_instructor' => $id_instructor
]);
$actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);

function mapEstadoToClass($estado)
{
    $estadoMap = [
        'Aprobado' => 'aprobado',
        'Desaprobado' => 'desaprobado',
        'Entregado' => 'entregado',
        'Pendiente' => 'pendiente',
        'No entregado' => 'noentregado'
    ];
    return $estadoMap[$estado] ?? strtolower(str_replace(' ', '', $estado));
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Actividades de <?= htmlspecialchars($aprendiz['nombres'] . ' ' . $aprendiz['apellidos']) ?> - Teamtalks</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/styles/style_side.css" />
    <link rel="stylesheet" href="<?= BASE_URL ?>/styles/header.css">
    <link rel="icon" href="<?= BASE_URL ?>/assets/img/icon2.png" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
        crossorigin="anonymous"
        referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/styles/act_aprendiz.css">
</head>

<body class="sidebar-collapsed">
    <?php include 'design/header.php'; ?>
    <?php include 'design/sidebar.php'; ?>

    <div class="main-content">
        <!-- Navigation Bar -->
        <div class="navigation-bar">
            <div>
                <a href="ver_aprendices.php?id_ficha=<?= $aprendiz['id_ficha'] ?>" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                    Volver a Aprendices
                </a>
            </div>
            <div class="breadcrumb-text">
                <i class="fas fa-home"></i>
                Aprendices > <?= htmlspecialchars($aprendiz['nombre_formacion']) ?> > Actividades
            </div>
        </div>

        <!-- Student Header -->
        <div class="student-header">
            <div class="student-info">
                <div class="student-avatar">
                    <img class="user-default" src="<?= BASE_URL ?>/<?= empty($aprendiz['avatar']) ? 'uploads/avatar/user.webp' : htmlspecialchars($aprendiz['avatar']) ?>" alt="Avatar" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #ffffff; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);">
                </div>
                <div class="student-details">
                    <h1><?= htmlspecialchars($aprendiz['nombres'] . ' ' . $aprendiz['apellidos']) ?></h1>
                    <div class="student-meta">
                        <div class="meta-item">
                            <i class="fas fa-id-card"></i>
                            <span>ID: <?= htmlspecialchars($aprendiz['id']) ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-users"></i>
                            <span><?= htmlspecialchars($aprendiz['nombre_formacion']) ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-tasks"></i>
                            <span><?= count($actividades) ?> Actividades</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botón PDF en el header -->
            <div class="header-actions">
                <a href="generar_pdf_aprendiz.php?id=<?= $id_aprendiz ?>" target="_blank" class="btn-export-pdf">
                    <i class="fas fa-file-pdf"></i>
                    Exportar PDF
                </a>
                <a href="generar_excel_aprendiz.php?id=<?= $id_aprendiz ?>" target="_blank" class="btn-export-pdf">
                    <i class="fas fa-file-pdf"></i>
                    Exportar EXCEL
                </a>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="filters-section">
            <div class="filters-header">
                <h3 class="filters-title">
                    <i class="fas fa-filter"></i>
                    Filtros y Búsqueda
                </h3>
                <div class="activities-count" id="activitiesCount">
                    <?= count($actividades) ?> actividades encontradas
                </div>
            </div>
            <div class="filters-controls">
                <div class="filter-group">
                    <label class="filter-label">Buscar por título</label>
                    <input type="text" class="filter-input" id="searchTitle" placeholder="Nombre de la actividad...">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Filtrar por estado</label>
                    <select class="filter-input" id="filterStatus">
                        <option value="">Todos los estados</option>
                        <option value="aprobado">Aprobado</option>
                        <option value="desaprobado">Desaprobado</option>
                        <option value="entregado">Entregado</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="noentregado">No entregado</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Desde fecha</label>
                    <input type="date" class="filter-input" id="filterDateFrom">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Hasta fecha</label>
                    <input type="date" class="filter-input" id="filterDateTo">
                </div>
                <div class="filter-group">
                    <label class="filter-label">&nbsp;</label>
                    <button class="clear-filters" id="clearFilters">
                        <i class="fas fa-times"></i>
                        Limpiar Filtros
                    </button>
                </div>
            </div>
        </div>

        <!-- Activities Container -->
        <div class="activities-container" id="activitiesContainer">
            <?php if (count($actividades) > 0): ?>
                <?php foreach ($actividades as $act): ?>
                    <article class="activity-card"
                        data-title="<?= strtolower(htmlspecialchars($act['titulo'])) ?>"
                        data-status="<?= mapEstadoToClass($act['estado_actividad']) ?>"
                        data-date="<?= $act['fecha_entrega'] ?>"
                        data-subject="<?= strtolower(htmlspecialchars($act['materia'])) ?>">

                        <!-- Header Compacto -->
                        <div class="activity-header-compact" onclick="toggleActivity(this)">
                            <div class="activity-header-info">
                                <h2 class="activity-title-compact"><?= htmlspecialchars($act['titulo']) ?></h2>
                                <div class="activity-subject-compact">
                                    <i class="fas fa-book"></i>
                                    <?= htmlspecialchars($act['materia']) ?>
                                </div>
                                <div class="activity-quick-info">
                                    <div class="quick-info-item">
                                        <i class="fas fa-calendar-alt"></i>
                                        <span><?= date('d/m/Y', strtotime($act['fecha_entrega'])) ?></span>
                                    </div>
                                    <div class="quick-info-item">
                                        <i class="fas fa-circle"></i>
                                        <span><?= htmlspecialchars($act['estado_actividad']) ?></span>
                                    </div>
                                    <?php if ($act['nota'] !== null): ?>
                                        <div class="quick-info-item">
                                            <i class="fas fa-star"></i>
                                            <span><?= number_format($act['nota'], 1) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <button class="activity-expand-btn" type="button">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </div>

                        <!-- Contenido Expandible -->
                        <div class="activity-expandable-content">
                            <div class="activity-content-inner">
                                <!-- Descripción Detallada -->
                                <div class="activity-description-detailed">
                                    <h3 class="description-title">
                                        <i class="fas fa-align-left"></i>
                                        Descripción de la Actividad
                                    </h3>
                                    <p class="description-text"><?= nl2br(htmlspecialchars($act['descripcion'])) ?></p>
                                </div>

                                <!-- Grid de Información Detallada -->
                                <div class="activity-details-grid">
                                    <!-- Fecha de Entrega -->
                                    <div class="detail-card due-date">
                                        <div class="detail-header">
                                            <div class="detail-icon due-date">
                                                <i class="fas fa-calendar-alt"></i>
                                            </div>
                                            <h4 class="detail-title">Fecha Límite</h4>
                                        </div>
                                        <p class="detail-value large"><?= date('d/m/Y', strtotime($act['fecha_entrega'])) ?></p>
                                        <p class="detail-value" style="font-size: 0.75rem; color: var(--text-muted);">
                                            <?= date('H:i', strtotime($act['fecha_entrega'])) ?> hrs
                                        </p>
                                    </div>

                                    <!-- Estado -->
                                    <div class="detail-card status">
                                        <div class="detail-header">
                                            <div class="detail-icon status">
                                                <i class="fas fa-info-circle"></i>
                                            </div>
                                            <h4 class="detail-title">Estado Actual</h4>
                                        </div>
                                        <div class="status-badge-detailed <?= mapEstadoToClass($act['estado_actividad']) ?>">
                                            <i class="fas fa-circle"></i>
                                            <?= htmlspecialchars($act['estado_actividad']) ?>
                                        </div>
                                    </div>

                                    <!-- Fecha de Entrega del Estudiante -->
                                    <div class="detail-card submitted">
                                        <div class="detail-header">
                                            <div class="detail-icon submitted">
                                                <i class="fas fa-check-circle"></i>
                                            </div>
                                            <h4 class="detail-title">Fecha de Entrega</h4>
                                        </div>
                                        <?php if (!empty($act['fecha_entregada_estudiante'])): ?>
                                            <p class="detail-value large"><?= date('d/m/Y', strtotime($act['fecha_entregada_estudiante'])) ?></p>
                                            <p class="detail-value" style="font-size: 0.75rem; color: var(--text-muted);">
                                                <?= date('H:i', strtotime($act['fecha_entregada_estudiante'])) ?> hrs
                                            </p>
                                        <?php else: ?>
                                            <div class="status-badge-detailed sin-entregar">
                                                <i class="fas fa-times-circle"></i>
                                                Sin Entregar
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Calificación -->
                                    <div class="detail-card grade">
                                        <div class="detail-header">
                                            <div class="detail-icon grade">
                                                <i class="fas fa-star"></i>
                                            </div>
                                            <h4 class="detail-title">Calificación</h4>
                                        </div>
                                        <?php if ($act['nota'] !== null): ?>
                                            <div class="grade-display-detailed">
                                                <?= number_format($act['nota'], 1) ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="grade-display-detailed no-grade">
                                                S/N
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Comentario del Instructor -->
                                <?php if (!empty($act['comentario_inst'])): ?>
                                    <div class="instructor-comment-detailed">
                                        <div class="comment-header-detailed">
                                            <div class="comment-icon-detailed">
                                                <i class="fas fa-comment-dots"></i>
                                            </div>
                                            <h4 class="comment-title-detailed">Comentario del Instructor</h4>
                                        </div>
                                        <p class="comment-text-detailed"><?= htmlspecialchars($act['comentario_inst']) ?></p>
                                    </div>
                                <?php endif; ?>

                                <!-- Archivos Entregados -->
                                <div class="files-section-detailed">
                                    <div class="files-header-detailed">
                                        <div class="files-icon-detailed">
                                            <i class="fas fa-paperclip"></i>
                                        </div>
                                        <h4 class="files-title-detailed">Archivos Entregados</h4>
                                    </div>
                                    <div class="files-grid-detailed">
                                        <?php
                                        $hasFiles = false;
                                        for ($i = 1; $i <= 3; $i++) {
                                            if (!empty($act["archivo$i"])) {
                                                $hasFiles = true;
                                                $archivo = htmlspecialchars($act["archivo$i"]);
                                                $nombreVisible = explode('_', $archivo, 2)[1] ?? $archivo;
                                                echo "<a href='../uploads/$archivo' class='file-link-detailed' target='_blank'>
                                            <i class='fas fa-file-alt file-icon-detailed'></i>
                                            <span>$nombreVisible</span>
                                          </a>";
                                            }
                                        }
                                        if (!$hasFiles) {
                                            echo '<div class="no-files-message">
                                        <i class="fas fa-folder-open" style="margin-right: 0.5rem;"></i>
                                        No se entregaron archivos para esta actividad
                                      </div>';
                                        }
                                        ?>
                                    </div>
                                </div>

                                <!-- Acciones -->
                                <div class="activity-actions">
                                    <a href="../mod/ver_entregas.php?id_actividad=<?= $act['id_actividad'] ?>&id_aprendiz=<?= $id_aprendiz ?>"
                                        class="btn-view-details-improved">
                                        <i class="fas fa-eye"></i>
                                        Ver Entrega Completa
                                    </a>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <h3>No hay actividades asignadas</h3>
                    <p>Este aprendiz no tiene actividades asignadas en este momento.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- No Results State -->
        <div class="no-results" id="noResults" style="display: none;">
            <div class="no-results-icon">
                <i class="fas fa-search"></i>
            </div>
            <h3>No se encontraron actividades</h3>
            <p>Intenta ajustar los filtros de búsqueda para encontrar lo que buscas.</p>
        </div>
    </div>

    <script>
        // Función para expandir/colapsar actividades
        function toggleActivity(headerElement) {
            const card = headerElement.closest('.activity-card');
            const isExpanded = card.classList.contains('expanded');

            if (isExpanded) {
                card.classList.remove('expanded');
            } else {
                card.classList.add('expanded');
            }
        }

        // Clase ActivityFilter mejorada
        class ActivityFilter {
            constructor() {
                this.activities = document.querySelectorAll('.activity-card');
                this.searchTitle = document.getElementById('searchTitle');
                this.filterStatus = document.getElementById('filterStatus');
                this.filterDateFrom = document.getElementById('filterDateFrom');
                this.filterDateTo = document.getElementById('filterDateTo');
                this.clearFilters = document.getElementById('clearFilters');
                this.activitiesCount = document.getElementById('activitiesCount');
                this.noResults = document.getElementById('noResults');

                this.init();
            }

            init() {
                this.bindEvents();
                this.addKeyboardSupport();
            }

            bindEvents() {
                // Search and filter events
                this.searchTitle.addEventListener('input', () => this.applyFilters());
                this.filterStatus.addEventListener('change', () => this.applyFilters());
                this.filterDateFrom.addEventListener('change', () => this.applyFilters());
                this.filterDateTo.addEventListener('change', () => this.applyFilters());

                // Clear filters
                this.clearFilters.addEventListener('click', () => this.clearAllFilters());
            }

            addKeyboardSupport() {
                // Soporte para teclado en las tarjetas
                this.activities.forEach(activity => {
                    const header = activity.querySelector('.activity-header-compact');
                    if (header) {
                        header.setAttribute('tabindex', '0');
                        header.setAttribute('role', 'button');
                        header.setAttribute('aria-expanded', 'false');

                        header.addEventListener('keydown', (e) => {
                            if (e.key === 'Enter' || e.key === ' ') {
                                e.preventDefault();
                                toggleActivity(header);
                                const isExpanded = activity.classList.contains('expanded');
                                header.setAttribute('aria-expanded', isExpanded);
                            }
                        });
                    }
                });
            }

            applyFilters() {
                const searchTerm = this.searchTitle.value.toLowerCase().trim();
                const statusFilter = this.filterStatus.value.toLowerCase();
                const dateFrom = this.filterDateFrom.value;
                const dateTo = this.filterDateTo.value;

                let visibleCount = 0;

                this.activities.forEach(activity => {
                    const title = activity.dataset.title;
                    const status = activity.dataset.status;
                    const date = activity.dataset.date;

                    let showActivity = true;

                    // Title filter
                    if (searchTerm && !title.includes(searchTerm)) {
                        showActivity = false;
                    }

                    // Status filter
                    if (statusFilter && status !== statusFilter) {
                        showActivity = false;
                    }

                    // Date range filter
                    if (dateFrom && date < dateFrom) {
                        showActivity = false;
                    }

                    if (dateTo && date > dateTo) {
                        showActivity = false;
                    }

                    // Show/hide activity
                    if (showActivity) {
                        activity.classList.remove('hidden');
                        visibleCount++;
                    } else {
                        activity.classList.add('hidden');
                        // Colapsar si está oculta
                        activity.classList.remove('expanded');
                    }
                });

                // Update count and show/hide no results
                this.updateCount(visibleCount);
                this.toggleNoResults(visibleCount === 0);
            }

            clearAllFilters() {
                this.searchTitle.value = '';
                this.filterStatus.value = '';
                this.filterDateFrom.value = '';
                this.filterDateTo.value = '';

                this.activities.forEach(activity => {
                    activity.classList.remove('hidden');
                });

                this.updateCount(this.activities.length);
                this.toggleNoResults(false);
            }

            updateCount(count) {
                this.activitiesCount.textContent = `${count} actividades encontradas`;
            }

            toggleNoResults(show) {
                this.noResults.style.display = show ? 'block' : 'none';
            }
        }

        // Initialize filter system when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            new ActivityFilter();

            // Debug: Verificar que las actividades se están cargando
            console.log('Actividades encontradas:', document.querySelectorAll('.activity-card').length);
        });
    </script>
</body>

</html>
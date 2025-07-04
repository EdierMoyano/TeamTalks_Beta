<?php
if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['DOCUMENT_ROOT'], 'htdocs') !== false) {
  require_once $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php';
} else {
  require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';
}include 'session.php';

$id_instructor = $_SESSION['documento'];

if ($_SESSION['rol'] !== 3) {
    header('Location:' . BASE_URL . '/includes/exit.php?motivo=acceso-denegado');
    exit;
}

// Paginación
$foros_por_pagina = 9;
$pagina_actual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset = ($pagina_actual - 1) * $foros_por_pagina;

// Filtros
$filtro_materia = isset($_GET['materia']) ? trim($_GET['materia']) : '';
$filtro_ficha = isset($_GET['ficha']) ? trim($_GET['ficha']) : '';
$orden = isset($_GET['orden']) ? $_GET['orden'] : 'fecha_desc';

// Construir consulta con filtros
$where_conditions = ["mf.id_instructor = ?"];
$params = [$id_instructor];

if (!empty($filtro_materia)) {
    $where_conditions[] = "m.materia LIKE ?";
    $params[] = "%$filtro_materia%";
}

if (!empty($filtro_ficha)) {
    $where_conditions[] = "fi.id_ficha LIKE ?";
    $params[] = "%$filtro_ficha%";
}

$where_clause = implode(' AND ', $where_conditions);

// Determinar orden
$order_clause = match ($orden) {
    'fecha_asc' => 'f.fecha_foro ASC',
    'materia_asc' => 'm.materia ASC',
    'materia_desc' => 'm.materia DESC',
    'temas_desc' => 'cantidad_temas DESC',
    'temas_asc' => 'cantidad_temas ASC',
    default => 'f.fecha_foro DESC'
};

// Consulta principal con paginación
$sql = "
    SELECT 
        f.id_foro,
        f.fecha_foro,
        mf.id_ficha,
        fi.id_ficha,
        fo.nombre AS nombre_formacion,
        m.materia,
        COUNT(tf.id_tema_foro) AS cantidad_temas
    FROM foros f
    INNER JOIN materia_ficha mf ON f.id_materia_ficha = mf.id_materia_ficha
    INNER JOIN fichas fi ON mf.id_ficha = fi.id_ficha
    INNER JOIN formacion fo ON fi.id_formacion = fo.id_formacion
    INNER JOIN materias m ON mf.id_materia = m.id_materia
    LEFT JOIN temas_foro tf ON tf.id_foro = f.id_foro
    WHERE $where_clause
    GROUP BY f.id_foro
    ORDER BY $order_clause
    LIMIT $foros_por_pagina OFFSET $offset
";

$stmt = $conex->prepare($sql);
$stmt->execute($params);
$foros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contar total de foros para paginación
$sql_count = "
    SELECT COUNT(DISTINCT f.id_foro) as total
    FROM foros f
    INNER JOIN materia_ficha mf ON f.id_materia_ficha = mf.id_materia_ficha
    INNER JOIN fichas fi ON mf.id_ficha = fi.id_ficha
    INNER JOIN formacion fo ON fi.id_formacion = fo.id_formacion
    INNER JOIN materias m ON mf.id_materia = m.id_materia
    WHERE $where_clause
";

$stmt_count = $conex->prepare($sql_count);
$stmt_count->execute($params);
$total_foros = $stmt_count->fetchColumn();
$total_paginas = ceil($total_foros / $foros_por_pagina);

// Obtener materias únicas para el filtro
$sql_materias = "
    SELECT DISTINCT m.materia
    FROM foros f
    INNER JOIN materia_ficha mf ON f.id_materia_ficha = mf.id_materia_ficha
    INNER JOIN materias m ON mf.id_materia = m.id_materia
    WHERE mf.id_instructor = ?
    ORDER BY m.materia ASC
";
$stmt_materias = $conex->prepare($sql_materias);
$stmt_materias->execute([$id_instructor]);
$materias_disponibles = $stmt_materias->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foros - TeamTalks</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/styles/style_side.css" />
    <link rel="stylesheet" href="<?= BASE_URL ?>/styles/header.css">
    <link rel="icon" href="<?= BASE_URL ?>/assets/img/icon2.png" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="<?= BASE_URL ?>/styles/foro.css"  />
</head>

<body class="sidebar-collapsed foros-page-body">
    <?php include 'design/header.php'; ?>
    <?php include 'design/sidebar.php'; ?>

    <main class="foros-page-main-content">
        <div class="foros-page-container">
            <!-- Header Section -->
            <header class="foros-page-header foros-page-animate-slide">
                <h1 class="foros-page-title">
                    <div class="foros-page-title-icon">
                        <i class="bi bi-chat-dots"></i>
                    </div>
                    <span>Mis Foros Académicos</span>
                </h1>
                <p class="foros-page-subtitle">
                    Gestiona y supervisa todos los foros asignados a tus fichas de formación
                </p>
            </header>

            <!-- Filters Section -->
            <section class="foros-page-filters foros-page-animate-in">
                <div class="foros-page-filters-header">
                    <h2 class="foros-page-filters-title">
                        <i class="bi bi-funnel"></i>
                        Filtros y Búsqueda
                    </h2>
                    <div class="foros-page-results-info">
                        <?= $total_foros ?> foro<?= $total_foros != 1 ? 's' : '' ?> encontrado<?= $total_foros != 1 ? 's' : '' ?>
                    </div>
                </div>

                <form method="GET" class="foros-page-filters-form">
                    <div class="foros-page-filter-group">
                        <label class="foros-page-filter-label">Buscar por materia</label>
                        <input type="text"
                            name="materia"
                            value="<?= htmlspecialchars($filtro_materia) ?>"
                            placeholder="Nombre de la materia..."
                            class="foros-page-filter-input">
                    </div>

                    <div class="foros-page-filter-group">
                        <label class="foros-page-filter-label">Filtrar por ficha</label>
                        <input type="text"
                            name="ficha"
                            value="<?= htmlspecialchars($filtro_ficha) ?>"
                            placeholder="Número de ficha..."
                            class="foros-page-filter-input">
                    </div>

                    <div class="foros-page-filter-group">
                        <label class="foros-page-filter-label">Ordenar por</label>
                        <select name="orden" class="foros-page-filter-input foros-page-filter-select">
                            <option value="fecha_desc" <?= $orden == 'fecha_desc' ? 'selected' : '' ?>>Fecha (más reciente)</option>
                            <option value="fecha_asc" <?= $orden == 'fecha_asc' ? 'selected' : '' ?>>Fecha (más antiguo)</option>
                            <option value="materia_asc" <?= $orden == 'materia_asc' ? 'selected' : '' ?>>Materia (A-Z)</option>
                            <option value="materia_desc" <?= $orden == 'materia_desc' ? 'selected' : '' ?>>Materia (Z-A)</option>
                            <option value="temas_desc" <?= $orden == 'temas_desc' ? 'selected' : '' ?>>Más temas</option>
                            <option value="temas_asc" <?= $orden == 'temas_asc' ? 'selected' : '' ?>>Menos temas</option>
                        </select>
                    </div>

                    <div class="foros-page-filter-buttons">
                        <button type="submit" class="foros-page-btn-filter">
                            <i class="bi bi-search"></i>
                            Filtrar
                        </button>
                        <a href="?" class="foros-page-btn-clear">
                            <i class="bi bi-x-circle"></i>
                            Limpiar
                        </a>
                    </div>
                </form>
            </section>

            <?php if ($total_foros > 0): ?>
                <!-- Statistics Section -->
                <section class="foros-page-stats foros-page-animate-in">
                    <div class="foros-page-stat-card">
                        <div class="foros-page-stat-header">
                            <div class="foros-page-stat-icon">
                                <i class="bi bi-collection"></i>
                            </div>
                        </div>
                        <h3 class="foros-page-stat-value"><?= $total_foros ?></h3>
                        <p class="foros-page-stat-label">Total de Foros</p>
                    </div>

                    <div class="foros-page-stat-card">
                        <div class="foros-page-stat-header">
                            <div class="foros-page-stat-icon">
                                <i class="bi bi-file-earmark-text"></i>
                            </div>
                        </div>
                        <h3 class="foros-page-stat-value"><?= $pagina_actual ?>/<?= $total_paginas ?></h3>
                        <p class="foros-page-stat-label">Página Actual</p>
                    </div>
                </section>

                <!-- Forums Grid -->
                <section class="foros-page-grid">
                    <?php foreach ($foros as $index => $foro): ?>
                        <article class="foros-page-card foros-page-animate-in">
                            <div class="foros-page-card-header">
                                <h2 class="foros-page-card-title">
                                    <div class="foros-page-card-title-icon">
                                        <i class="bi bi-bookmark-star-fill"></i>
                                    </div>
                                    <span><?= htmlspecialchars($foro['materia']) ?></span>
                                </h2>
                            </div>

                            <div class="foros-page-card-body">
                                <div class="foros-page-card-info">
                                    <div class="foros-page-info-item">
                                        <div class="foros-page-info-icon">
                                            <i class="bi bi-journal-code"></i>
                                        </div>
                                        <div class="foros-page-info-content">
                                            <p class="foros-page-info-label">Ficha</p>
                                            <p class="foros-page-info-value"><?= htmlspecialchars($foro['id_ficha']) ?></p>
                                        </div>
                                    </div>

                                    <div class="foros-page-info-item">
                                        <div class="foros-page-info-icon">
                                            <i class="bi bi-mortarboard-fill"></i>
                                        </div>
                                        <div class="foros-page-info-content">
                                            <p class="foros-page-info-label">Programa de Formación</p>
                                            <p class="foros-page-info-value"><?= htmlspecialchars($foro['nombre_formacion']) ?></p>
                                        </div>
                                    </div>

                                    <div class="foros-page-info-item">
                                        <div class="foros-page-info-icon">
                                            <i class="bi bi-calendar-event"></i>
                                        </div>
                                        <div class="foros-page-info-content">
                                            <p class="foros-page-info-label">Fecha de Creación</p>
                                            <p class="foros-page-info-value"><?= date("d/m/Y", strtotime($foro['fecha_foro'])) ?></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="foros-page-topics-badge">
                                    <i class="bi bi-chat-left-text foros-page-topics-icon"></i>
                                    <span>
                                        <?= $foro['cantidad_temas'] ?>
                                        <?= $foro['cantidad_temas'] == 1 ? 'tema' : 'temas' ?>
                                    </span>
                                </div>

                                <a href="../mod/temas_foro.php?id_foro=<?= $foro['id_foro'] ?>"
                                    class="foros-page-btn"
                                    aria-label="Acceder al foro de <?= htmlspecialchars($foro['materia']) ?>">
                                    <i class="bi bi-arrow-right-circle foros-page-btn-icon"></i>
                                    <span>Acceder al Foro</span>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>

                <!-- Pagination -->
                <?php if ($total_paginas > 1): ?>
                    <nav class="foros-page-pagination" aria-label="Navegación de páginas">
                        <div class="foros-page-pagination-info">
                            Mostrando <?= (($pagina_actual - 1) * $foros_por_pagina) + 1 ?> - <?= min($pagina_actual * $foros_por_pagina, $total_foros) ?> de <?= $total_foros ?> foros
                        </div>

                        <?php
                        // Construir URL base con filtros
                        $url_params = [];
                        if (!empty($filtro_materia)) $url_params['materia'] = $filtro_materia;
                        if (!empty($filtro_ficha)) $url_params['ficha'] = $filtro_ficha;
                        if ($orden != 'fecha_desc') $url_params['orden'] = $orden;
                        $url_base = '?' . http_build_query($url_params);
                        $url_base .= empty($url_params) ? '?pagina=' : '&pagina=';
                        ?>

                        <!-- Botón Anterior -->
                        <?php if ($pagina_actual > 1): ?>
                            <a href="<?= $url_base . ($pagina_actual - 1) ?>" class="foros-page-pagination-btn">
                                <i class="bi bi-chevron-left"></i>
                                <span class="d-none d-sm-inline">Anterior</span>
                            </a>
                        <?php else: ?>
                            <span class="foros-page-pagination-btn" style="opacity: 0.5; cursor: not-allowed;">
                                <i class="bi bi-chevron-left"></i>
                                <span class="d-none d-sm-inline">Anterior</span>
                            </span>
                        <?php endif; ?>

                        <!-- Números de página -->
                        <?php
                        $inicio = max(1, $pagina_actual - 2);
                        $fin = min($total_paginas, $pagina_actual + 2);

                        if ($inicio > 1): ?>
                            <a href="<?= $url_base ?>1" class="foros-page-pagination-btn">1</a>
                            <?php if ($inicio > 2): ?>
                                <span class="foros-page-pagination-btn" style="cursor: default;">...</span>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $inicio; $i <= $fin; $i++): ?>
                            <a href="<?= $url_base . $i ?>"
                                class="foros-page-pagination-btn <?= $i == $pagina_actual ? 'foros-page-active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($fin < $total_paginas): ?>
                            <?php if ($fin < $total_paginas - 1): ?>
                                <span class="foros-page-pagination-btn" style="cursor: default;">...</span>
                            <?php endif; ?>
                            <a href="<?= $url_base . $total_paginas ?>" class="foros-page-pagination-btn"><?= $total_paginas ?></a>
                        <?php endif; ?>

                        <!-- Botón Siguiente -->
                        <?php if ($pagina_actual < $total_paginas): ?>
                            <a href="<?= $url_base . ($pagina_actual + 1) ?>" class="foros-page-pagination-btn">
                                <span class="d-none d-sm-inline">Siguiente</span>
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="foros-page-pagination-btn" style="opacity: 0.5; cursor: not-allowed;">
                                <span class="d-none d-sm-inline">Siguiente</span>
                                <i class="bi bi-chevron-right"></i>
                            </span>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>

            <?php else: ?>
                <!-- Empty State -->
                <section class="foros-page-empty-state foros-page-animate-in">
                    <div class="foros-page-empty-icon">
                        <i class="bi bi-search"></i>
                    </div>
                    <h2 class="foros-page-empty-title">
                        <?= !empty($filtro_materia) || !empty($filtro_ficha) ? 'No se encontraron foros' : 'No hay foros disponibles' ?>
                    </h2>
                    <p class="foros-page-empty-text">
                        <?php if (!empty($filtro_materia) || !empty($filtro_ficha)): ?>
                            No se encontraron foros que coincidan con los filtros aplicados. Intenta ajustar los criterios de búsqueda.
                        <?php else: ?>
                            Actualmente no tienes foros asignados. Los foros aparecerán aquí cuando sean creados para tus fichas de formación.
                        <?php endif; ?>
                    </p>
                </section>
            <?php endif; ?>
        </div>
    </main>
</body>

</html>
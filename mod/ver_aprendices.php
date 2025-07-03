<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';
include 'session.php';

if ($_SESSION['rol'] !== 3 && $_SESSION['rol'] !== 5) {
  header('Location:' . BASE_URL . '/includes/exit.php?motivo=acceso-denegado');
  exit;
}

$id_instructor = $_SESSION['documento'];
$rol = $_SESSION['rol'] ?? '';
$redirecciones = [
  3 => '/instructor/index.php',
  5 => '/transversal/index.php'
];
$destino = BASE_URL . ($redirecciones[$rol] ?? '/index.php');

$id_ficha = isset($_GET['id_ficha']) ? (int)$_GET['id_ficha'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 6;
$offset = ($page - 1) * $limit;

// Obtener total de aprendices
$total_stmt = $conex->prepare("SELECT COUNT(*) FROM user_ficha WHERE id_ficha = :id_ficha");
$total_stmt->execute(['id_ficha' => $id_ficha]);
$total_aprendices = $total_stmt->fetchColumn();
$total_pages = ceil($total_aprendices / $limit);

// Obtener aprendices de la página actual
$sql = "
    SELECT 
        u.id,
        u.nombres,
        u.apellidos,
        u.correo,
        u.telefono
    FROM user_ficha uf
    JOIN usuarios u ON uf.id_user = u.id
    WHERE uf.id_ficha = :id_ficha
    LIMIT $limit OFFSET $offset
";
$stmt = $conex->prepare($sql);
$stmt->execute(['id_ficha' => $id_ficha]);
$aprendices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener materias de acuerdo al rol
if ($rol == 3) {
  // Instructor común: puede ver todas las materias de la ficha
  $sql_materias = "
        SELECT DISTINCT m.id_materia, m.materia 
        FROM materia_ficha mf 
        JOIN materias m ON mf.id_materia = m.id_materia 
        WHERE mf.id_ficha = :id_ficha
    ";
  $stmt_materias = $conex->prepare($sql_materias);
  $stmt_materias->execute(['id_ficha' => $id_ficha]);
} elseif ($rol == 5) {
  // Instructor transversal: solo puede ver su materia en esta ficha
  $sql_materias = "
        SELECT DISTINCT m.id_materia, m.materia 
        FROM materia_ficha mf 
        JOIN materias m ON mf.id_materia = m.id_materia 
        WHERE mf.id_ficha = :id_ficha AND mf.id_instructor = :id_user
    ";
  $stmt_materias = $conex->prepare($sql_materias);
  $stmt_materias->execute([
    'id_ficha' => $id_ficha,
    'id_user' => $id_instructor
  ]);
}
$materias = $stmt_materias->fetchAll(PDO::FETCH_ASSOC);


// Estados de actividades
$estados_actividades = [
  3 => 'En Proceso',
  4 => 'Completada',
  8 => 'Retrasada',
  9 => 'Pendiente',
  10 => 'Cancelada'
];
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Ficha <?= htmlspecialchars($id_ficha) ?> - TeamTalks</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/styles/style_side.css" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/styles/header.css">
  <link rel="icon" href="<?= BASE_URL ?>/assets/img/icon2.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300..700&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/styles/aprendices.css" />

  <style>
    /* Botón para mostrar/ocultar exportar reportes */
    .export-toggle-btn {
      background: linear-gradient(135deg, #0E4A86 0%, #1a5490 100%);
      border: none;
      color: white;
      padding: 12px 20px;
      border-radius: 25px;
      font-size: 0.9rem;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s ease;
      cursor: pointer;
      box-shadow: 0 3px 10px rgba(14, 74, 134, 0.3);
      margin-left: auto;
    }

    .export-toggle-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(14, 74, 134, 0.4);
      background: linear-gradient(135deg, #1a5490 0%, #2563a8 100%);
    }

    .export-toggle-btn i {
      transition: transform 0.3s ease;
    }

    .export-toggle-btn.active i {
      transform: rotate(180deg);
    }

    /* Modificar el header para incluir el botón */
    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 20px;
    }

    .header-actions {
      display: flex;
      flex-direction: column;
      gap: 10px;
      align-items: flex-end;
      margin-top: 10px;
    }

    /* Sección de exportar reportes - inicialmente oculta */
    .export-section {
      background: linear-gradient(135deg, #0E4A86 0%, #1a5490 50%, #2563a8 100%);
      border-radius: 16px;
      padding: 20px;
      margin: 20px 0;
      box-shadow: 0 4px 20px rgba(14, 74, 134, 0.15);
      border: 1px solid rgba(255, 255, 255, 0.1);
      max-height: 0;
      overflow: hidden;
      opacity: 0;
      transition: all 0.5s ease;
      transform: translateY(-20px);
    }

    .export-section.show {
      max-height: 2000px;
      opacity: 1;
      transform: translateY(0);
      margin: 20px 0;
    }

    .export-header {
      text-align: center;
      margin-bottom: 20px;
    }

    .export-title {
      color: white;
      font-size: 1.4rem;
      font-weight: 600;
      margin-bottom: 5px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .export-subtitle {
      color: rgba(255, 255, 255, 0.85);
      font-size: 0.9rem;
      margin: 0;
    }

    .filters-container {
      background: rgba(255, 255, 255, 0.08);
      backdrop-filter: blur(10px);
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 20px;
      border: 1px solid rgba(255, 255, 255, 0.15);
    }

    .filters-title {
      color: white;
      font-size: 1.1rem;
      font-weight: 500;
      margin-bottom: 15px;
      display: flex;
      align-items: center;
      gap: 6px;
      justify-content: space-between;
    }

    .filter-group {
      margin-bottom: 15px;
    }

    .filter-label {
      color: white;
      font-weight: 500;
      margin-bottom: 6px;
      display: block;
      font-size: 0.85rem;
    }

    .form-control,
    .form-select {
      background: rgba(255, 255, 255, 0.95);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 8px;
      padding: 10px 12px;
      font-size: 0.85rem;
      transition: all 0.3s ease;
      height: auto;
    }

    .form-control:focus,
    .form-select:focus {
      background: white;
      border-color: #4CAF50;
      box-shadow: 0 0 0 0.15rem rgba(76, 175, 80, 0.2);
    }

    .checkbox-group {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 8px;
      margin-top: 8px;
    }

    .checkbox-item {
      background: rgba(255, 255, 255, 0.08);
      border-radius: 6px;
      padding: 8px 12px;
      display: flex;
      align-items: center;
      gap: 6px;
      transition: all 0.2s ease;
      cursor: pointer;
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .checkbox-item:hover {
      background: rgba(255, 255, 255, 0.15);
      transform: translateY(-1px);
    }

    .checkbox-item input[type="checkbox"] {
      margin: 0;
      transform: scale(1.1);
    }

    .checkbox-item label {
      color: white;
      margin: 0;
      cursor: pointer;
      font-size: 0.8rem;
    }

    .export-buttons {
      display: flex;
      gap: 12px;
      justify-content: center;
      flex-wrap: wrap;
    }

    .export-btn {
      background: linear-gradient(45deg, #4CAF50, #45a049);
      border: none;
      color: white;
      padding: 12px 24px;
      border-radius: 8px;
      font-size: 0.9rem;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s ease;
      text-decoration: none;
      min-width: 150px;
      justify-content: center;
      box-shadow: 0 2px 8px rgba(76, 175, 80, 0.3);
    }

    .export-btn.excel {
      background: linear-gradient(45deg, #217346, #1e6b42);
      box-shadow: 0 2px 8px rgba(33, 115, 70, 0.3);
    }

    .export-btn.pdf {
      background: linear-gradient(45deg, #dc3545, #c82333);
      box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
    }

    .export-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
      color: white;
    }

    .export-btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      transform: none;
    }

    .filter-actions {
      display: flex;
      gap: 8px;
      justify-content: center;
      margin-top: 15px;
      flex-wrap: wrap;
    }

    .filter-btn {
      background: rgba(255, 255, 255, 0.15);
      border: 1px solid rgba(255, 255, 255, 0.2);
      color: white;
      padding: 8px 16px;
      border-radius: 20px;
      font-size: 0.8rem;
      transition: all 0.3s ease;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .filter-btn:hover {
      background: rgba(255, 255, 255, 0.25);
      transform: translateY(-1px);
    }

    .filter-btn.clear {
      background: rgba(255, 107, 107, 0.2);
      border-color: rgba(255, 107, 107, 0.4);
    }

    .loading-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.7);
      display: none;
      justify-content: center;
      align-items: center;
      z-index: 9999;
    }

    .loading-content {
      background: white;
      padding: 25px;
      border-radius: 12px;
      text-align: center;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
    }

    .loading-spinner {
      width: 40px;
      height: 40px;
      border: 4px solid #f3f3f3;
      border-top: 4px solid #0E4A86;
      border-radius: 50%;
      animation: spin 1s linear infinite;
      margin: 0 auto 15px;
    }

    @keyframes spin {
      0% {
        transform: rotate(0deg);
      }

      100% {
        transform: rotate(360deg);
      }
    }

    /* Collapse/Expand functionality para filtros internos */
    .filters-toggle {
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      color: white;
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 0.8rem;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 4px;
      transition: all 0.3s ease;
    }

    .filters-toggle:hover {
      background: rgba(255, 255, 255, 0.2);
    }

    .filters-content {
      transition: all 0.3s ease;
      overflow: hidden;
    }

    .filters-content.collapsed {
      max-height: 0;
      opacity: 0;
      margin-bottom: 0;
    }

    /* Indicador de estado */
    .export-status {
      background: rgba(255, 255, 255, 0.1);
      border-radius: 20px;
      padding: 4px 12px;
      font-size: 0.75rem;
      color: rgba(255, 255, 255, 0.8);
      margin-left: 10px;
    }

    @media (max-width: 768px) {
      .page-header {
        flex-direction: column;
        align-items: stretch;
      }

      .header-actions {
        align-items: stretch;
        margin-top: 15px;
      }

      .export-toggle-btn {
        justify-content: center;
        margin-left: 0;
      }

      .export-section {
        padding: 15px;
        margin: 15px 0;
      }

      .filters-container {
        padding: 15px;
      }

      .checkbox-group {
        grid-template-columns: 1fr;
      }

      .export-buttons {
        flex-direction: column;
        align-items: center;
      }

      .export-btn {
        width: 100%;
        max-width: 280px;
      }

      .filter-actions {
        justify-content: center;
      }

      .filter-btn {
        font-size: 0.75rem;
        padding: 6px 12px;
      }

      .filters-title {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
      }
    }
  </style>
</head>

<body style="padding-top:180px;" class="sidebar-collapsed">
  <?php include 'design/header.php'; ?>
  <?php include 'design/sidebar.php'; ?>

  <div class="main-content">
    <!-- Header Section -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-info">
          <a href="<?= $destino ?>" class="back-btn">
            <i class="bi bi-arrow-left"></i>
            Volver
          </a>
          <h1 class="page-title">
            <div class="title-icon">
              <i class="bi bi-people-fill"></i>
            </div>
            Ficha <?= htmlspecialchars($id_ficha) ?>
          </h1>
          <p class="page-subtitle">Gestiona y visualiza los aprendices de esta ficha</p>
          <div class="stats-container">
            <div class="stat-item">
              <i class="bi bi-person-check"></i>
              <span><?= $total_aprendices ?> Aprendices</span>
            </div>
            <div class="stat-item">
              <i class="bi bi-collection"></i>
              <span>Página <?= $page ?> de <?= $total_pages ?></span>
            </div>
          </div>
        </div>
      </div>

      <!-- Botón para mostrar/ocultar exportar reportes -->
      <div class="header-actions">
        <button type="button" class="export-toggle-btn" onclick="toggleExportSection()" id="exportToggleBtn">
          <i class="bi bi-download" id="exportToggleIcon"></i>
          <span id="exportToggleText">Mostrar Exportar Reportes</span>
        </button>
      </div>
    </div>

    <!-- Export Section - Inicialmente oculta -->
    <div class="export-section" id="exportSection">
      <div class="export-header">
        <h2 class="export-title">
          <i class="bi bi-download"></i>
          Exportar Reportes
        </h2>
        <p class="export-subtitle">Genera reportes personalizados con filtros avanzados</p>
      </div>

      <form id="exportForm">
        <input type="hidden" name="id_ficha" value="<?= $id_ficha ?>">

        <div class="filters-container">
          <h3 class="filters-title">
            <div style="display: flex; align-items: center; gap: 6px;">
              <i class="bi bi-funnel"></i>
              Filtros de Exportación
            </div>
            <button type="button" class="filters-toggle" onclick="toggleFilters()">
              <i class="bi bi-chevron-down" id="toggleIcon"></i>
              <span id="toggleText">Ocultar</span>
            </button>
          </h3>

          <div class="filters-content" id="filtersContent">
            <div class="row">
              <!-- Rango de Fechas -->
              <div class="col-md-6">
                <div class="filter-group">
                  <label class="filter-label">
                    <i class="bi bi-calendar-range"></i>
                    Rango de Fechas de Actividades
                  </label>
                  <div class="row">
                    <div class="col-6">
                      <input type="date" class="form-control" name="fecha_desde" id="fecha_desde">
                      <small class="text-white-50">Desde</small>
                    </div>
                    <div class="col-6">
                      <input type="date" class="form-control" name="fecha_hasta" id="fecha_hasta">
                      <small class="text-white-50">Hasta</small>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Tipo de Reporte -->
              <div class="col-md-6">
                <div class="filter-group">
                  <label class="filter-label">
                    <i class="bi bi-file-text"></i>
                    Tipo de Reporte
                  </label>
                  <select class="form-select" name="tipo_reporte" id="tipo_reporte">
                    <option value="completo">Reporte Completo</option>
                    <option value="solo_pendientes">Solo Actividades Pendientes</option>
                    <option value="por_estado">Por Estado de Actividades</option>
                    <option value="resumen">Resumen Ejecutivo</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="row">
              <!-- Materias -->
              <div class="col-md-6">
                <div class="filter-group">
                  <label class="filter-label">
                    <i class="bi bi-book"></i>
                    Materias (Opcional)
                  </label>
                  <select class="form-select" name="materia_filtro" id="materia_filtro">
                    <option value="">Todas las materias</option>
                    <?php foreach ($materias as $materia): ?>
                      <option value="<?= $materia['id_materia'] ?>">
                        <?= htmlspecialchars($materia['materia']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <!-- Ordenar por -->
              <div class="col-md-6">
                <div class="filter-group">
                  <label class="filter-label">
                    <i class="bi bi-sort-down"></i>
                    Ordenar por
                  </label>
                  <select class="form-select" name="orden" id="orden">
                    <option value="apellidos">Apellidos</option>
                    <option value="nombres">Nombres</option>
                    <option value="documento">Documento</option>
                    <option value="actividades_pendientes">Actividades Pendientes</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Estados de Actividades -->
            <div class="filter-group">
              <label class="filter-label">
                <i class="bi bi-check-circle"></i>
                Estados de Actividades a Incluir
              </label>
              <div class="checkbox-group">
                <?php foreach ($estados_actividades as $id_estado => $nombre_estado): ?>
                  <div class="checkbox-item">
                    <input type="checkbox" name="estados[]" value="<?= $id_estado ?>"
                      id="estado_<?= $id_estado ?>" checked>
                    <label for="estado_<?= $id_estado ?>"><?= $nombre_estado ?></label>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="filter-actions">
              <button type="button" class="filter-btn" onclick="aplicarFiltrosRapidos('pendientes')">
                <i class="bi bi-clock"></i> Solo Pendientes
              </button>
              <button type="button" class="filter-btn" onclick="aplicarFiltrosRapidos('completadas')">
                <i class="bi bi-check-all"></i> Solo Completadas
              </button>
              <button type="button" class="filter-btn" onclick="aplicarFiltrosRapidos('mes_actual')">
                <i class="bi bi-calendar-month"></i> Mes Actual
              </button>
              <button type="button" class="filter-btn clear" onclick="limpiarFiltros()">
                <i class="bi bi-x-circle"></i> Limpiar Filtros
              </button>
            </div>
          </div>
        </div>

        <div class="export-buttons">
          <button type="button" class="export-btn excel" onclick="exportar('excel')">
            <i class="bi bi-file-earmark-excel"></i>
            Exportar a Excel
          </button>
          <button type="button" class="export-btn pdf" onclick="exportar('pdf')">
            <i class="bi bi-file-earmark-pdf"></i>
            Exportar a PDF
          </button>
        </div>
      </form>
    </div>

    <!-- Search Section -->
    <div class="search-section">
      <div class="search-header">
        <h2 class="search-title">Buscar Aprendices</h2>
      </div>
      <div class="search-container">
        <i class="bi bi-search search-icon"></i>
        <input
          id="buscarficha"
          class="search-input"
          type="search"
          placeholder="Buscar por número de documento..."
          aria-label="Buscar aprendiz" />
      </div>
    </div>

    <!-- Students Container -->
    <div class="students-container">
      <div class="students-header">
        <h2 class="students-title">Lista de Aprendices</h2>
        <div class="students-count" id="students-count">
          Cargando...
        </div>
      </div>

      <div class="students-grid" id="contenedor-aprendices">
        <!-- Loading state -->
        <div class="loading-container">
          <div class="spinner"></div>
          <p class="loading-text">Cargando aprendices...</p>
        </div>
      </div>

      <!-- Pagination -->
      <div class="pagination-container">
        <div class="pagination" id="paginacion-aprendices">
          <!-- Pagination will be loaded here -->
        </div>
      </div>
    </div>
  </div>

  <!-- Loading Overlay -->
  <div class="loading-overlay" id="loadingOverlay">
    <div class="loading-content">
      <div class="loading-spinner"></div>
      <h4>Generando Reporte</h4>
      <p>Por favor espera mientras procesamos tu solicitud...</p>
    </div>
  </div>

  <!-- Modal for student details -->
  <div class="modal fade" id="modalAprendiz" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="bi bi-person-circle me-2"></i>
            Detalles del Aprendiz
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body" id="modalContenido">
          <div class="loading-container">
            <div class="spinner"></div>
            <p class="loading-text">Cargando detalles...</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const input = document.getElementById('buscarficha');
      const container = document.getElementById('contenedor-aprendices');
      const paginacion = document.getElementById('paginacion-aprendices');
      const studentsCount = document.getElementById('students-count');
      const idFicha = <?= (int)$id_ficha ?>;

      // Establecer fecha actual como máximo
      const hoy = new Date().toISOString().split('T')[0];
      document.getElementById('fecha_hasta').value = hoy;
      document.getElementById('fecha_hasta').max = hoy;
      document.getElementById('fecha_desde').max = hoy;

      function cargarAprendices(query = '', page = 1) {
        // Loading state
        container.innerHTML = `
          <div class="loading-container">
            <div class="spinner"></div>
            <p class="loading-text">Buscando aprendices...</p>
          </div>
        `;

        const formData = new FormData();
        formData.append('id_ficha', idFicha);
        formData.append('query', query);
        formData.append('page', page);

        fetch('buscar_aprendices.php', {
            method: 'POST',
            body: formData
          })
          .then(res => {
            if (!res.ok) {
              throw new Error('Error en la respuesta del servidor');
            }
            return res.json();
          })
          .then(data => {
            container.innerHTML = data.tarjetas;
            paginacion.innerHTML = data.paginacion;
            studentsCount.textContent = data.total_text;

            // Reassign pagination events
            document.querySelectorAll('.page-btn').forEach(link => {
              link.addEventListener('click', e => {
                e.preventDefault();
                const nuevaPagina = e.target.dataset.page;
                if (nuevaPagina) {
                  cargarAprendices(input.value.trim(), nuevaPagina);
                }
              });
            });

            // Animate cards
            setTimeout(() => {
              document.querySelectorAll('.student-card').forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                setTimeout(() => {
                  card.style.opacity = '1';
                  card.style.transform = 'translateY(0)';
                }, index * 100);
              });
            }, 50);
          })
          .catch(error => {
            console.error('Error:', error);
            container.innerHTML = `
              <div class="empty-state">
                <i class="bi bi-exclamation-triangle empty-icon"></i>
                <h3 class="empty-title">Error al cargar</h3>
                <p class="empty-description">No se pudieron cargar los aprendices. Intenta nuevamente.</p>
              </div>
            `;
            studentsCount.textContent = 'Error al cargar';
          });
      }

      // Debounced search
      let searchTimeout;
      input.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
          cargarAprendices(input.value.trim(), 1);
        }, 300);
      });

      // Initial load
      cargarAprendices();
    });

    // Toggle Export Section
    function toggleExportSection() {
      const section = document.getElementById('exportSection');
      const btn = document.getElementById('exportToggleBtn');
      const icon = document.getElementById('exportToggleIcon');
      const text = document.getElementById('exportToggleText');
      const status = document.getElementById('exportStatus');

      if (section.classList.contains('show')) {
        section.classList.remove('show');
        btn.classList.remove('active');
        icon.className = 'bi bi-download';
        text.textContent = 'Mostrar Exportar Reportes';
      } else {
        section.classList.add('show');
        btn.classList.add('active');
        icon.className = 'bi bi-x-circle';
        text.textContent = 'Ocultar Exportar Reportes';
      }
    }

    // Export functions - Mantiene las rutas originales
    function exportar(tipo) {
      const form = document.getElementById('exportForm');
      const formData = new FormData(form);

      // Validar que al menos un estado esté seleccionado
      const estadosSeleccionados = formData.getAll('estados[]');
      if (estadosSeleccionados.length === 0) {
        alert('Debes seleccionar al menos un estado de actividad.');
        return;
      }

      // Mostrar loading
      document.getElementById('loadingOverlay').style.display = 'flex';

      // Crear formulario temporal para envío
      const tempForm = document.createElement('form');
      tempForm.method = 'POST';
      tempForm.action = tipo === 'excel' ? 'excel_aprendices.php' : 'pdf_aprendices.php';
      tempForm.style.display = 'none';

      // Copiar todos los datos del formulario
      for (let [key, value] of formData.entries()) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        tempForm.appendChild(input);
      }

      document.body.appendChild(tempForm);
      tempForm.submit();
      document.body.removeChild(tempForm);

      // Ocultar loading después de un tiempo
      setTimeout(() => {
        document.getElementById('loadingOverlay').style.display = 'none';
      }, 3000);
    }

    // Filter functions
    function aplicarFiltrosRapidos(tipo) {
      const hoy = new Date();
      const primerDiaMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);

      switch (tipo) {
        case 'pendientes':
          // Desmarcar todos los estados
          document.querySelectorAll('input[name="estados[]"]').forEach(cb => cb.checked = false);
          // Marcar solo pendientes
          document.getElementById('estado_9').checked = true;
          document.getElementById('tipo_reporte').value = 'solo_pendientes';
          break;

        case 'completadas':
          // Desmarcar todos los estados
          document.querySelectorAll('input[name="estados[]"]').forEach(cb => cb.checked = false);
          // Marcar solo completadas
          document.getElementById('estado_4').checked = true;
          document.getElementById('tipo_reporte').value = 'por_estado';
          break;

        case 'mes_actual':
          document.getElementById('fecha_desde').value = primerDiaMes.toISOString().split('T')[0];
          document.getElementById('fecha_hasta').value = hoy.toISOString().split('T')[0];
          break;
      }
    }

    function limpiarFiltros() {
      document.getElementById('exportForm').reset();
      // Marcar todos los estados por defecto
      document.querySelectorAll('input[name="estados[]"]').forEach(cb => cb.checked = true);
      // Establecer fecha actual como máximo
      const hoy = new Date().toISOString().split('T')[0];
      document.getElementById('fecha_hasta').value = hoy;
    }

    // Toggle Filters (interno)
    function toggleFilters() {
      const content = document.getElementById('filtersContent');
      const icon = document.getElementById('toggleIcon');
      const text = document.getElementById('toggleText');

      if (content.classList.contains('collapsed')) {
        content.classList.remove('collapsed');
        icon.className = 'bi bi-chevron-down';
        text.textContent = 'Ocultar';
      } else {
        content.classList.add('collapsed');
        icon.className = 'bi bi-chevron-up';
        text.textContent = 'Mostrar';
      }
    }

    // Handle student details modal
    document.addEventListener('click', function(e) {
      if (e.target.classList.contains('view-details-btn') || e.target.closest('.view-details-btn')) {
        const btn = e.target.classList.contains('view-details-btn') ? e.target : e.target.closest('.view-details-btn');
        const id = btn.getAttribute('data-id');

        document.getElementById('modalContenido').innerHTML = `
          <div class="loading-container">
            <div class="spinner"></div>
            <p class="loading-text">Cargando detalles...</p>
          </div>
        `;

        const modal = new bootstrap.Modal(document.getElementById('modalAprendiz'));
        modal.show();

        const formData = new FormData();
        formData.append('id', id);

        fetch('detalles_aprendiz.php', {
            method: 'POST',
            body: formData
          })
          .then(response => {
            if (!response.ok) {
              throw new Error('Error en la respuesta del servidor');
            }
            return response.text();
          })
          .then(html => {
            document.getElementById('modalContenido').innerHTML = html;
          })
          .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalContenido').innerHTML = `
              <div class="empty-state">
                <i class="bi bi-exclamation-triangle empty-icon"></i>
                <h3 class="empty-title">Error al cargar</h3>
                <p class="empty-description">No se pudieron cargar los detalles del aprendiz.</p>
              </div>
            `;
          });
      }
    });

    // Handle orientation changes
    window.addEventListener('orientationchange', () => {
      setTimeout(() => {
        window.scrollTo(0, 0);
      }, 100);
    });

    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalAprendiz'));
        if (modal) {
          modal.hide();
        }
      }
    });

    // Validación de fechas
    document.getElementById('fecha_desde').addEventListener('change', function() {
      const fechaDesde = this.value;
      const fechaHasta = document.getElementById('fecha_hasta');

      if (fechaDesde) {
        fechaHasta.min = fechaDesde;
        if (fechaHasta.value && fechaHasta.value < fechaDesde) {
          fechaHasta.value = fechaDesde;
        }
      }
    });
  </script>
</body>

</html>
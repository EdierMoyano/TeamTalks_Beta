<?php
// ... (mantener todo el código PHP anterior hasta la línea del <style>)

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
  $sql_materias = "
        SELECT DISTINCT m.id_materia, m.materia 
        FROM materia_ficha mf 
        JOIN materias m ON mf.id_materia = m.id_materia 
        WHERE mf.id_ficha = :id_ficha
    ";
  $stmt_materias = $conex->prepare($sql_materias);
  $stmt_materias->execute(['id_ficha' => $id_ficha]);
} elseif ($rol == 5) {
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
  3 => 'Aprobado',
  4 => 'Desaprobado',
  8 => 'Entregado',
  9 => 'Pendiente',
  10 => 'No entregado'
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
    /* Variables CSS para consistencia */
    :root {
      --primary-color: #0E4A86;
      --primary-light: #1a5a9e;
      --primary-dark: #0a3a6b;
      --success-color: #10b981;
      --warning-color: #f59e0b;
      --danger-color: #ef4444;
      --info-color: #3b82f6;
      --gray-50: #f9fafb;
      --gray-100: #f3f4f6;
      --gray-200: #e5e7eb;
      --gray-300: #d1d5db;
      --gray-400: #9ca3af;
      --gray-500: #6b7280;
      --gray-600: #4b5563;
      --gray-700: #374151;
      --gray-800: #1f2937;
      --gray-900: #111827;
      --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
      --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
      --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
      --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
      --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
      --border-radius: 12px;
      --border-radius-lg: 16px;
      --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Sección de Reportes Moderna */
    
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
          </div>
        </div>
      </div>

      <!-- Botón para mostrar/ocultar reportes -->
      <div class="header-actions">
        <button type="button" class="reports-toggle-btn" onclick="toggleReportsSection()" id="reportsToggleBtn">
          <i class="bi bi-download" id="reportsToggleIcon"></i>
          <span id="reportsToggleText">Exportar Reportes</span>
        </button>
      </div>
    </div>

    <!-- Sección de Reportes Moderna -->
    <div class="reports-section" id="reportsSection">
      <div class="reports-header">
        <div class="reports-header-content">
          <h2 class="reports-title">
            <i class="bi bi-file-earmark-arrow-down"></i>
            Centro de Reportes
          </h2>
          <p class="reports-subtitle">Configura y genera reportes personalizados con filtros avanzados</p>
        </div>
      </div>

      <div class="reports-content">
        <form id="exportForm">
          <input type="hidden" name="id_ficha" value="<?= $id_ficha ?>">

          <div class="config-grid">
            <!-- Configuración Principal -->
            <div class="config-card">
              <h4 class="config-card-title">
                <i class="bi bi-gear-fill"></i>
                Configuración General
              </h4>

              <div class="form-group">
                <label class="form-label">
                  Tipo de Reporte
                  <div class="sync-indicator" id="syncIndicator"></div>
                </label>
                <select class="form-select" name="tipo_reporte" id="tipo_reporte">
                  <option value="resumen">📊 Resumen Ejecutivo</option>
                  <option value="solo_pendientes">⏳ Solo Pendientes</option>
                  <option value="por_estado">📈 Por Estado</option>
                  <option value="completo">📋 Reporte Completo</option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label">Competencias</label>
                <select class="form-select" name="materia_filtro" id="materia_filtro">
                  <option value="">🎯 Todas las competencias</option>
                  <?php foreach ($materias as $materia): ?>
                    <option value="<?= $materia['id_materia'] ?>">
                      <?= htmlspecialchars($materia['materia']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label">Ordenar por</label>
                <select class="form-select" name="orden" id="orden">
                  <option value="apellidos">👤 Apellidos</option>
                  <option value="nombres">📝 Nombres</option>
                  <option value="documento">🆔 Documento</option>
                  <option value="actividades_pendientes">⏰ Act. Pendientes</option>
                </select>
              </div>
            </div>

            <!-- Filtros de Fecha -->
            <div class="config-card">
              <h4 class="config-card-title">
                <i class="bi bi-calendar-range-fill"></i>
                Rango de Fechas
              </h4>

              <div class="date-grid">
                <div class="form-group">
                  <label class="form-label">Fecha Inicio</label>
                  <input type="date" class="form-control" name="fecha_desde" id="fecha_desde">
                </div>
                <div class="form-group">
                  <label class="form-label">Fecha Final</label>
                  <input type="date" class="form-control" name="fecha_hasta" id="fecha_hasta">
                </div>
              </div>
            </div>

            <!-- Estados de Actividades -->
            <div class="config-card states-section">
              <h4 class="config-card-title">
                <i class="bi bi-check-circle-fill"></i>
                Estados de Actividades
              </h4>

              <div class="states-grid">
                <?php
                $estado_classes = [
                  3 => 'state-aprobado',
                  4 => 'state-desaprobado',
                  8 => 'state-entregado',
                  9 => 'state-pendiente',
                  10 => 'state-no-entregado'
                ];

                $estado_icons = [
                  3 => '✅',
                  4 => '❌',
                  8 => '📤',
                  9 => '⏳',
                  10 => '📭'
                ];

                foreach ($estados_actividades as $id_estado => $nombre_estado):
                ?>
                  <label class="state-option <?= $estado_classes[$id_estado] ?>" for="estado_<?= $id_estado ?>">
                    <input type="checkbox" name="estados[]" value="<?= $id_estado ?>" id="estado_<?= $id_estado ?>">
                    <span><?= $estado_icons[$id_estado] ?> <?= $nombre_estado ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <!-- Filtros Rápidos -->
          <div class="quick-filters">
            <h4 class="quick-filters-title">
              <i class="bi bi-lightning-fill"></i>
              Filtros Rápidos
            </h4>
            <div class="quick-filters-grid">
              <button type="button" class="quick-filter-btn" onclick="aplicarFiltrosRapidos('pendientes')">
                <i class="bi bi-clock-fill"></i>
                Solo Pendientes
              </button>
              <button type="button" class="quick-filter-btn" onclick="aplicarFiltrosRapidos('completadas')">
                <i class="bi bi-check-all"></i>
                Completadas
              </button>
              <button type="button" class="quick-filter-btn" onclick="aplicarFiltrosRapidos('mes_actual')">
                <i class="bi bi-calendar-month-fill"></i>
                Mes Actual
              </button>
              <button type="button" class="quick-filter-btn" onclick="limpiarFiltros()">
                <i class="bi bi-arrow-clockwise"></i>
                Limpiar Todo
              </button>
            </div>
          </div>

          <!-- Botones de Exportación -->
          <div class="export-actions">
            <button type="button" class="export-btn excel" onclick="exportar('excel')">
              <i class="bi bi-file-earmark-excel-fill"></i>
              Exportar Excel
            </button>
            <button type="button" class="export-btn pdf" onclick="exportar('pdf')">
              <i class="bi bi-file-earmark-pdf-fill"></i>
              Exportar PDF
            </button>
          </div>
        </form>
      </div>
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

  <!-- Modal de Carga Moderno -->
  <div class="loading-modal" id="loadingModal">
    <div class="loading-content">
      <div class="loading-spinner"></div>
      <h4 class="loading-title">Generando Reporte</h4>
      <p class="loading-description">Estamos procesando tu solicitud, esto puede tomar unos momentos...</p>

      <div class="loading-progress">
        <div class="loading-progress-bar"></div>
      </div>

      <div class="loading-steps">
        <div class="loading-step active" id="step1">
          <div class="loading-step-icon">1</div>
          <span>Validando filtros</span>
        </div>
        <div class="loading-step" id="step2">
          <div class="loading-step-icon">2</div>
          <span>Consultando base de datos</span>
        </div>
        <div class="loading-step" id="step3">
          <div class="loading-step-icon">3</div>
          <span>Generando documento</span>
        </div>
        <div class="loading-step" id="step4">
          <div class="loading-step-icon">4</div>
          <span>Preparando descarga</span>
        </div>
      </div>
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
      document.getElementById('fecha_desde').max = hoy;

      // Inicializar sincronización bidireccional
      initializeBidirectionalSync();

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

    // Función para inicializar la sincronización bidireccional
    function initializeBidirectionalSync() {
      const tipoReporte = document.getElementById('tipo_reporte');
      const checkboxes = document.querySelectorAll('input[name="estados[]"]');
      const syncIndicator = document.getElementById('syncIndicator');

      // Estados disponibles
      const todosLosEstados = [3, 4, 8, 9, 10];
      const estadoPendiente = [9];
      const estadosCompletados = [3, 8];

      // Función para mostrar indicador de sincronización
      function showSyncIndicator() {
        syncIndicator.classList.add('active');
        setTimeout(() => {
          syncIndicator.classList.remove('active');
        }, 1500);
      }

      // Función para actualizar estilos de checkboxes
      function updateCheckboxStyles() {
        checkboxes.forEach(checkbox => {
          const label = checkbox.closest('.state-option');
          if (checkbox.checked) {
            label.classList.add('checked');
          } else {
            label.classList.remove('checked');
          }
        });
      }

      // Función para actualizar el select basado en checkboxes
      function updateSelectFromCheckboxes() {
        const estadosSeleccionados = Array.from(checkboxes)
          .filter(cb => cb.checked)
          .map(cb => parseInt(cb.value));

        showSyncIndicator();
        updateCheckboxStyles();

        if (estadosSeleccionados.length === 0) {
          tipoReporte.value = 'resumen';
        } else if (estadosSeleccionados.length === todosLosEstados.length &&
          todosLosEstados.every(estado => estadosSeleccionados.includes(estado))) {
          tipoReporte.value = 'completo';
        } else if (estadosSeleccionados.length === 1 && estadosSeleccionados[0] === 9) {
          tipoReporte.value = 'solo_pendientes';
        } else {
          tipoReporte.value = 'por_estado';
        }
      }

      // Función para actualizar checkboxes basado en el select
      function updateCheckboxesFromSelect() {
        const tipoSeleccionado = tipoReporte.value;
        showSyncIndicator();

        // Desmarcar todos primero
        checkboxes.forEach(cb => cb.checked = false);

        switch (tipoSeleccionado) {
          case 'completo':
            todosLosEstados.forEach(estado => {
              const checkbox = document.getElementById(`estado_${estado}`);
              if (checkbox) checkbox.checked = true;
            });
            break;
          case 'solo_pendientes':
            const checkboxPendiente = document.getElementById('estado_9');
            if (checkboxPendiente) checkboxPendiente.checked = true;
            break;
          case 'por_estado':
            estadosCompletados.forEach(estado => {
              const checkbox = document.getElementById(`estado_${estado}`);
              if (checkbox) checkbox.checked = true;
            });
            break;
          case 'resumen':
            break;
        }

        updateCheckboxStyles();
      }

      // Event listeners para sincronización bidireccional
      checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectFromCheckboxes);
      });

      tipoReporte.addEventListener('change', updateCheckboxesFromSelect);

      // Inicializar con estado por defecto
      tipoReporte.value = 'resumen';
      updateCheckboxStyles();
    }

    // Toggle Reports Section
    function toggleReportsSection() {
      const section = document.getElementById('reportsSection');
      const btn = document.getElementById('reportsToggleBtn');
      const icon = document.getElementById('reportsToggleIcon');
      const text = document.getElementById('reportsToggleText');

      if (section.classList.contains('show')) {
        section.classList.remove('show');
        btn.classList.remove('active');
        icon.className = 'bi bi-download';
        text.textContent = 'Exportar Reportes';
      } else {
        section.classList.add('show');
        btn.classList.add('active');
        icon.className = 'bi bi-x-circle-fill';
        text.textContent = 'Ocultar Reportes';
      }
    }

    // Función de exportación mejorada con modal de carga
    function exportar(tipo) {
      const form = document.getElementById('exportForm');
      const formData = new FormData(form);
      const tipoReporte = document.getElementById('tipo_reporte').value;

      // Validar estados solo si el tipo de reporte NO es "resumen"
      if (tipoReporte !== 'resumen') {
        const estadosSeleccionados = form.querySelectorAll('input[name="estados[]"]:checked');
        if (estadosSeleccionados.length === 0) {
          alert('⚠️ Debes seleccionar al menos un estado de actividad.');
          return;
        }
      }

      // Mostrar modal de carga
      showLoadingModal();

      // Simular pasos de carga
      simulateLoadingSteps();

      // Crear formulario temporal
      const tempForm = document.createElement('form');
      tempForm.method = 'POST';
      tempForm.action = tipo === 'excel' ? 'excel_aprendices.php' : 'pdf_aprendices.php';
      tempForm.style.display = 'none';

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

      // Ocultar modal después de un tiempo
      setTimeout(() => {
        hideLoadingModal();
      }, 1800);
    }

    // Funciones del modal de carga
    function showLoadingModal() {
      const modal = document.getElementById('loadingModal');
      modal.classList.add('show');
      document.body.style.overflow = 'hidden';
    }

    function hideLoadingModal() {
      const modal = document.getElementById('loadingModal');
      modal.classList.remove('show');
      document.body.style.overflow = '';

      // Reset steps
      document.querySelectorAll('.loading-step').forEach((step, index) => {
        step.classList.remove('active', 'completed');
        if (index === 0) step.classList.add('active');
      });
    }

    function simulateLoadingSteps() {
      const steps = ['step1', 'step2', 'step3', 'step4'];
      let currentStep = 0;

      const interval = setInterval(() => {
        if (currentStep > 0) {
          const prevStep = document.getElementById(steps[currentStep - 1]);
          prevStep.classList.remove('active');
          prevStep.classList.add('completed');
          prevStep.querySelector('.loading-step-icon').innerHTML = '✓';
        }

        if (currentStep < steps.length) {
          const currentStepEl = document.getElementById(steps[currentStep]);
          currentStepEl.classList.add('active');
          currentStep++;
        } else {
          clearInterval(interval);
        }
      }, 200);
    }

    // Filter functions
    function aplicarFiltrosRapidos(tipo) {
      const hoy = new Date();
      const primerDiaMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);

      switch (tipo) {
        case 'pendientes':
          document.getElementById('tipo_reporte').value = 'solo_pendientes';
          document.getElementById('tipo_reporte').dispatchEvent(new Event('change'));
          break;
        case 'completadas':
          document.getElementById('tipo_reporte').value = 'por_estado';
          document.getElementById('tipo_reporte').dispatchEvent(new Event('change'));
          break;
        case 'mes_actual':
          document.getElementById('fecha_desde').value = primerDiaMes.toISOString().split('T')[0];
          document.getElementById('fecha_hasta').value = hoy.toISOString().split('T')[0];
          break;
      }
    }

    function limpiarFiltros() {
      document.getElementById('exportForm').reset();
      document.getElementById('tipo_reporte').value = 'resumen';
      document.getElementById('tipo_reporte').dispatchEvent(new Event('change'));
      const hoy = new Date().toISOString().split('T')[0];
      document.getElementById('fecha_hasta').value = hoy;
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

    // Cerrar modal con Escape
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        const loadingModal = document.getElementById('loadingModal');
        if (loadingModal.classList.contains('show')) {
          hideLoadingModal();
        }

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

    // Cerrar modal al hacer clic fuera
    document.getElementById('loadingModal').addEventListener('click', function(e) {
      if (e.target === this) {
        hideLoadingModal();
      }
    });
  </script>
</body>

</html>
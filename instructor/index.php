<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';
include 'session.php';

if ($_SESSION['rol'] !== 3) {
  header('Location:' . BASE_URL . '/includes/exit.php?motivo=acceso-denegado');
  exit;
}

$id_instructor = $_SESSION['documento'];
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Gestión de Fichas - TeamTalks</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/styles/header.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/styles/instru.css">
  <link rel="icon" href="<?= BASE_URL ?>/assets/img/icon2.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300..700&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" />
  
</head>

<body>
  <?php include 'design/header.php'; ?>
  <?php include 'design/sidebar.php'; ?>

  <div class="main-content" id="mainContent">
    <!-- Hero Section -->
    <div class="hero-section">
      <div class="hero-content">
        <h1 class="hero-title">Gestión de Fichas</h1>
        <p class="hero-subtitle">Administra y supervisa todas las fichas de formación bajo tu responsabilidad</p>
        <div class="hero-stats">
          <div class="stat-item">
            <span class="stat-number" id="total-fichas">-</span>
            <span class="stat-label">Fichas Activas</span>
          </div>
          <div class="stat-item">
            <span class="stat-number" id="total-aprendices">-</span>
            <span class="stat-label">Aprendices</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Search Section -->
    <div class="search-section">
      <div class="search-header">
        <h2 class="search-title">
          <div class="search-icon-header">
            <i class="bi bi-search"></i>
          </div>
          Buscar Fichas
        </h2>
      </div>
      <div class="search-container">
        <div class="search-input-group">
          <button class="search-btn" type="button" aria-label="Buscar fichas">
            <i class="bi bi-search"></i>
          </button>
          <input
            id="buscarficha"
            class="search-input"
            type="search"
            placeholder="Buscar por número de ficha o nombre de formación..."
            aria-label="Buscar fichas" />
        </div>
      </div>
    </div>

    <!-- Results Section -->
    <div class="results-section">
      <div class="results-header">
        <h2 class="results-title">Mis Fichas</h2>
        <div class="results-count" id="results-count">
          Cargando...
        </div>
      </div>

      <div id="resultadoFichas" class="fichas-grid">
        <!-- Loading state -->
        <div class="loading-container">
          <div class="spinner"></div>
          <p class="loading-text">Cargando fichas...</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal para Detalles de Ficha -->
  <div class="modal fade" id="modalDetallesFicha" tabindex="-1" aria-labelledby="modalDetallesFichaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalDetallesFichaLabel">
            <i class="bi bi-info-circle me-2"></i>Detalles de la Ficha
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body" id="contenido-modal-detalles">
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
      const searchInput = document.getElementById('buscarficha');
      const resultsContainer = document.getElementById('resultadoFichas');
      const resultsCount = document.getElementById('results-count');
      const totalFichas = document.getElementById('total-fichas');
      const totalAprendices = document.getElementById('total-aprendices');

      function buscarFicha(page = 1) {
        const query = searchInput.value.trim();

        // Loading state
        resultsContainer.innerHTML = `
                    <div class="loading-container">
                        <div class="spinner"></div>
                        <p class="loading-text">Buscando fichas...</p>
                    </div>
                `;

        const xhr = new XMLHttpRequest();
        xhr.open('GET', '../ajax/buscar_fichas.php?q=' + encodeURIComponent(query) + '&page=' + page, true);

        xhr.onload = function() {
          if (xhr.status === 200) {
            try {
              const response = JSON.parse(xhr.responseText);
              resultsContainer.innerHTML = response.html;
              resultsCount.textContent = response.count_text;
              totalFichas.textContent = response.total_fichas;
              totalAprendices.textContent = response.total_aprendices;

              configurarEventosTarjetas();
              animateCards();
            } catch (e) {
              console.error('Error parsing JSON:', e);
              showError('Error al procesar la respuesta del servidor');
            }
          } else {
            showError('Error al cargar las fichas. Intenta nuevamente.');
          }
        };

        xhr.onerror = function() {
          showError('Sin conexión. Verifica tu conexión a internet.');
        };

        xhr.send();
      }

      function showError(message) {
        resultsContainer.innerHTML = `
                    <div class="empty-state">
                        <i class="bi bi-exclamation-triangle empty-icon"></i>
                        <h3 class="empty-title">Error</h3>
                        <p class="empty-description">${message}</p>
                    </div>
                `;
      }

      function animateCards() {
        setTimeout(() => {
          document.querySelectorAll('.ficha-card').forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';

            setTimeout(() => {
              card.style.opacity = '1';
              card.style.transform = 'translateY(0)';
            }, index * 100);
          });
        }, 50);
      }

      // Debounced search
      let searchTimeout;
      searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
          buscarFicha(1);
        }, 300);
      });

      function configurarEventosTarjetas() {
        // Details buttons
        document.querySelectorAll('.btn-detalles').forEach(btn => {
          btn.addEventListener('click', (e) => {
            e.preventDefault();
            const idFicha = btn.getAttribute('data-id');

            document.getElementById('contenido-modal-detalles').innerHTML = `
                            <div class="loading-container">
                                <div class="spinner"></div>
                                <p class="loading-text">Cargando detalles...</p>
                            </div>
                        `;

            const modal = new bootstrap.Modal(document.getElementById('modalDetallesFicha'));
            modal.show();

            fetch('../ajax/detalles_fichas.php?id=' + idFicha)
              .then(response => {
                if (!response.ok) {
                  throw new Error('Error en la respuesta del servidor');
                }
                return response.text();
              })
              .then(html => {
                document.getElementById('contenido-modal-detalles').innerHTML = html;
              })
              .catch(error => {
                console.error('Error:', error);
                document.getElementById('contenido-modal-detalles').innerHTML = `
                                    <div class="empty-state">
                                        <i class="bi bi-exclamation-triangle empty-icon"></i>
                                        <h3 class="empty-title">Error al cargar</h3>
                                        <p class="empty-description">No se pudieron cargar los detalles.</p>
                                    </div>
                                `;
              });
          });
        });

        // Pagination
        document.querySelectorAll('.page-btn').forEach(btn => {
          btn.addEventListener('click', (e) => {
            e.preventDefault();
            const page = btn.getAttribute('data-page');
            if (page) {
              buscarFicha(page);
            }
          });
        });
      }

      // Initial load
      buscarFicha();

      // Handle orientation changes
      window.addEventListener('orientationchange', () => {
        setTimeout(() => {
          window.scrollTo(0, 0);
        }, 100);
      });

      // Keyboard navigation
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
          const modal = bootstrap.Modal.getInstance(document.getElementById('modalDetallesFicha'));
          if (modal) {
            modal.hide();
          }
        }
      });
    });
  </script>
</body>

</html>
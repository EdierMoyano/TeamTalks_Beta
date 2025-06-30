<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php';
include 'session.php';
$id_instructor = $_SESSION['documento'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Teamtalks</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/styles/header.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/styles/instru.css">
  <link rel="icon" href="<?= BASE_URL ?>/assets/img/icon2.png" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" />
  <style>
    .but {
      background-color: #0E4A86;
      border-color: rgb(14, 74, 134);
      color: rgb(255, 255, 255);
      transition: all 0.3s ease;
      min-width: 50px;
      min-height: 50px;
    }

    .but:hover {
      background-color: rgb(9, 50, 91);
      border-color: rgb(23, 101, 180);
      color: white;
      transform: translateY(-1px);
    }
  </style>

</head>

<body style="padding-top: var(--header-height);">
  <?php include 'design/header.php'; ?>
  <?php include 'design/sidebar.php'; ?>

  <div class="main-content" id="mainContent">
    <nav class="search-navbar">
      <div class="">
        <form class="d-flex buscar" role="search">
          <button class="btn but" type="button" aria-label="Buscar fichas">
            <i class="bi bi-search"></i>
          </button>
          <input id="buscarficha" class="form-control me-2" type="search" placeholder="Buscar por ficha o nombre de formación" aria-label="Search" />
        </form>
      </div>
    </nav>

    <h2 class="text-center mb-4 page-title">Ficha(s) que Gestionas</h2>

    <!-- Contenedor de resultados -->
    <div id="resultadoFichas" class="fichas row g-3">
    </div><br>
  </div>

  <!-- Modal para Detalles de Ficha -->
  <div class="modal fade" id="modalDetallesFicha" tabindex="-1" aria-labelledby="modalDetallesFichaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header text-white" style="background-color: #0E4A86;">
          <h5 class="modal-title" id="modalDetallesFichaLabel">
            <i class="bi bi-info-circle me-2"></i>Detalles de la Ficha
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body" id="contenido-modal-detalles">
          <div class="loading-container">
            <div class="spinner-border" role="status">
              <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2">Cargando detalles...</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts mejorados -->
  <script>
    function buscarFicha(page = 1) {
      const query = document.getElementById('buscarficha').value.trim();
      const resultadoContainer = document.getElementById('resultadoFichas');

      // Estado de carga mejorado
      resultadoContainer.innerHTML = `
        <div class="col-12">
          <div class="loading-container">
            <div class="spinner-border" role="status">
              <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2 mb-0">Buscando fichas...</p>
          </div>
        </div>
      `;

      const xhr = new XMLHttpRequest();
      xhr.open('GET', '../ajax/buscar_fichas.php?q=' + encodeURIComponent(query) + '&page=' + page, true);

      xhr.onload = function() {
        if (xhr.status === 200) {
          resultadoContainer.innerHTML = xhr.responseText;
          configurarEventosTarjetas();
        } else {
          resultadoContainer.innerHTML = `
            <div class="col-12">
              <div class="loading-container">
                <i class="bi bi-exclamation-triangle" style="font-size: 3rem; color: var(--text-muted);"></i>
                <h3 style="color: var(--text-muted);">Error al cargar</h3>
                <p style="color: var(--text-muted);">No se pudieron cargar las fichas. Intenta nuevamente.</p>
              </div>
            </div>
          `;
        }
      };

      xhr.onerror = function() {
        resultadoContainer.innerHTML = `
          <div class="col-12">
            <div class="loading-container">
              <i class="bi bi-wifi-off" style="font-size: 3rem; color: var(--text-muted);"></i>
              <h3 style="color: var(--text-muted);">Sin conexión</h3>
              <p style="color: var(--text-muted);">Verifica tu conexión a internet.</p>
            </div>
          </div>
        `;
      };

      xhr.send();
    }

    // Debounce para optimizar búsquedas
    let searchTimeout;
    document.getElementById('buscarficha').addEventListener('input', function() {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        buscarFicha(1);
      }, 300);
    });

    function configurarEventosTarjetas() {
      document.querySelectorAll('.btn-detalles').forEach(btn => {
        btn.addEventListener('click', (e) => {
          e.preventDefault();
          const idFicha = btn.getAttribute('data-id');

          document.getElementById('contenido-modal-detalles').innerHTML = `
            <div class="loading-container">
              <div class="spinner-border" role="status">
                <span class="visually-hidden">Cargando...</span>
              </div>
              <p class="mt-2 mb-0">Cargando detalles...</p>
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
                <div class="loading-container">
                  <i class="bi bi-exclamation-triangle" style="font-size: 3rem; color: var(--text-muted);"></i>
                  <h3 style="color: var(--text-muted);">Error al cargar</h3>
                  <p style="color: var(--text-muted);">No se pudieron cargar los detalles.</p>
                </div>
              `;
            });
        });
      });
    }

    // Cargar fichas al iniciar
    document.addEventListener('DOMContentLoaded', () => {
      buscarFicha();
    });

    // Manejar cambios de orientación
    window.addEventListener('orientationchange', () => {
      setTimeout(() => {
        window.scrollTo(0, 0);
      }, 100);
    });

    // Cargar al inicio (fallback)
    window.onload = function() {
      if (document.getElementById('resultadoFichas').innerHTML.trim() === '') {
        buscarFicha();
      }
    };
  </script>
</body>

</html>
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php';
include 'session.php';

$id_instructor = $_SESSION['documento'];

$actividad_actualizada = '';
if (isset($_SESSION['actividad_actualizada'])) {
  $actividad_actualizada = $_SESSION['actividad_actualizada'];
  unset($_SESSION['actividad_actualizada']);
}

$actividad_creada = '';
if (isset($_SESSION['actividad_creada'])) {
  $actividad_creada = $_SESSION['actividad_creada'];
  unset($_SESSION['actividad_creada']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Teamtalks</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/styles/style_side.css" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/styles/header.css">
  <link rel="icon" href="<?= BASE_URL ?>/assets/img/icon2.png" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" />

  <style>
    :root {
      --sidebar-width: 280px;
      --sidebar-collapsed-width: 70px;
      --header-height: 80px;
      --primary-color: #0E4A86;
      --primary-hover: #0a3d6b;
      --background-color: #f8f9fa;
      --border-color: #dee2e6;
      --text-muted: #6c757d;
    }

    /* SCROLLBAR */
    ::-webkit-scrollbar {
      width: 8px;
    }
    ::-webkit-scrollbar-track {
      background: var(--background-color);
    }
    ::-webkit-scrollbar-thumb {
      background: var(--border-color);
      border-radius: 4px;
    }
    ::-webkit-scrollbar-thumb:hover {
      background: var(--text-muted);
    }

    /* MAIN CONTENT */
    .main-content {
      margin-left: var(--sidebar-width);
      margin-top: -50px;
      transition: margin-left 0.5s cubic-bezier(0.4, 0, 0.2, 1);
      min-height: calc(100vh - var(--header-height));
      padding: 2rem;
    }

    /* Cuando el sidebar está colapsado */
    .sidebar.collapsed~.main-content,
    body.sidebar-collapsed .main-content {
      margin-left: var(--sidebar-collapsed-width);
    }

    /* SCROLL PERSONALIZADO PARA COLUMNAS */
    .fichas-scroll {
      max-height: calc(100vh - 200px);
      overflow-y: auto;
      scrollbar-width: thin;
      scrollbar-color: #888 transparent;
    }

    .fichas-scroll::-webkit-scrollbar {
      width: 6px;
    }
    .fichas-scroll::-webkit-scrollbar-track {
      background: transparent;
    }
    .fichas-scroll::-webkit-scrollbar-thumb {
      background-color: #888;
      border-radius: 10px;
      border: 2px solid transparent;
      background-clip: content-box;
    }
    .fichas-scroll::-webkit-scrollbar-thumb:hover {
      background-color: #555;
    }

    /* TOAST RESPONSIVE */
    #toast-alert {
      position: fixed;
      top: 150px;
      right: 20px;
      background-color: white;
      border: 1px solid #cfe2ff;
      color: #0E4A86;
      font-size: 0.9rem;
      padding: 0.75rem 1rem 1rem;
      width: 280px;
      z-index: 9999;
      border-radius: 0.5rem;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
      overflow: hidden;
    }

    #toast-alert .progress-bar {
      height: 4px;
      width: 0%;
      background-color: #0E4A86;
      position: absolute;
      bottom: 0;
      left: 0;
      animation: progressAnim 3s linear forwards;
    }

    @keyframes progressAnim {
      from { width: 0%; }
      to { width: 100%; }
    }

    /* ALERT RESPONSIVE */
    .size {
      max-width: 800px;
      position: relative;
      left: 650px;
    }

    .img {
      max-width: 200px;
      top: 20px;
    }

    /* COLUMNAS RESPONSIVE */
    .content-columns {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
      margin-right: 0;
    }

    .column-fichas {
      border-right: 1px solid var(--border-color);
      padding: 1rem;
      max-height: 50vh;
      overflow-y: auto;
    }

    .column-actividades {
      padding: 1rem;
      max-height: 65vh;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
    }

    /* ESTADO INICIAL ACTIVIDADES */
    .actividades-placeholder {
      text-align: center;
      color: var(--text-muted);
      font-size: 1.1rem;
      margin-top: 3rem;
    }

    .actividades-placeholder img {
      max-width: 200px;
      margin-top: 1rem;
    }

    /* RESPONSIVE BREAKPOINTS */

    /* Tablets (768px - 991px) */
    @media (max-width: 991px) {
      .main-content {
        margin-left: var(--sidebar-collapsed-width);
        padding: 1.5rem;
      }

      .content-columns {
        gap: 0.75rem;
      }

      .column-fichas,
      .column-actividades {
        padding: 0.75rem;
      }

      .size {
        left: 400px;
        max-width: 600px;
      }
    }

    /* Mobile Large (576px - 767px) */
    @media (max-width: 767px) {
      .main-content {
        margin-left: 0 !important;
        padding: 1rem;
      }

      /* Layout de una columna en móviles */
      .content-columns {
        grid-template-columns: 1fr;
        gap: 1rem;
      }

      .column-fichas {
        border-right: none;
        border-bottom: 1px solid var(--border-color);
        max-height: 40vh;
        padding: 1rem 0.5rem;
      }

      .column-actividades {
        max-height: 50vh;
        padding: 1rem 0.5rem;
      }

      /* Toast responsive */
      #toast-alert {
        top: 100px;
        right: 10px;
        left: 10px;
        width: auto;
        font-size: 0.85rem;
        padding: 0.6rem 0.8rem 0.8rem;
      }

      /* Alert responsive */
      .size {
        position: static;
        left: auto;
        max-width: 100%;
        margin: 0 1rem;
      }

      /* Imagen placeholder más pequeña */
      .actividades-placeholder {
        font-size: 1rem;
        margin-top: 2rem;
      }

      .actividades-placeholder img {
        max-width: 150px;
      }

      .img {
        max-width: 150px;
      }
    }

    /* Mobile Medium (480px - 575px) */
    @media (max-width: 575px) {
      .main-content {
        padding: 0.75rem;
      }

      .content-columns {
        gap: 0.75rem;
      }

      .column-fichas {
        max-height: 35vh;
        padding: 0.75rem 0.25rem;
      }

      .column-actividades {
        max-height: 45vh;
        padding: 0.75rem 0.25rem;
      }

      #toast-alert {
        top: 90px;
        right: 5px;
        left: 5px;
        font-size: 0.8rem;
        padding: 0.5rem 0.6rem 0.6rem;
      }

      .actividades-placeholder {
        font-size: 0.9rem;
        margin-top: 1.5rem;
      }

      .actividades-placeholder img {
        max-width: 120px;
      }

      .img {
        max-width: 120px;
      }
    }

    /* Mobile Small (320px - 479px) */
    @media (max-width: 479px) {
      .main-content {
        padding: 0.5rem;
      }

      .content-columns {
        gap: 0.5rem;
      }

      .column-fichas {
        max-height: 30vh;
        padding: 0.5rem 0.25rem;
      }

      .column-actividades {
        max-height: 40vh;
        padding: 0.5rem 0.25rem;
      }

      #toast-alert {
        top: 80px;
        right: 5px;
        left: 5px;
        font-size: 0.75rem;
        padding: 0.4rem 0.5rem 0.5rem;
      }

      .actividades-placeholder {
        font-size: 0.85rem;
        margin-top: 1rem;
      }

      .actividades-placeholder img {
        max-width: 100px;
      }

      .img {
        max-width: 100px;
      }
    }

    /* Landscape orientation en móviles */
    @media (max-height: 500px) and (orientation: landscape) {
      .main-content {
        margin-top: 60px;
        padding: 0.5rem;
      }

      .column-fichas {
        max-height: 35vh;
      }

      .column-actividades {
        max-height: 40vh;
      }

      #toast-alert {
        top: 70px;
      }
    }

    /* Extra responsive para pantallas muy pequeñas */
    @media (max-width: 320px) {
      .main-content {
        padding: 0.25rem;
      }

      .content-columns {
        gap: 0.25rem;
      }

      .column-fichas,
      .column-actividades {
        padding: 0.25rem;
      }

      #toast-alert {
        font-size: 0.7rem;
        padding: 0.3rem 0.4rem 0.4rem;
      }
    }

    /* Mejoras de accesibilidad */
    @media (prefers-reduced-motion: reduce) {
      * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
      }
    }

    /* Soporte para modo oscuro */
    
  </style>
</head>

<body style="padding-top:180px;" class="sidebar-collapsed">
  <?php include 'design/header.php'; ?>
  <?php include 'design/sidebar.php'; ?>

  <?php if (isset($_SESSION['error_actividad'])): ?>
    <div class="size alert alert-danger alert-dismissible fade show rounded-3 mt-3 d-flex justify-content-end" role="alert">
      <?= htmlspecialchars($_SESSION['error_actividad']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
    <?php unset($_SESSION['error_actividad']); ?>
  <?php endif; ?>

  <div class="main-content">
    <?php if ($actividad_actualizada): ?>
      <div id="toast-alert">
        <div class="d-flex align-items-center gap-2 mb-1">
          <i class="bi bi-bell-fill me-1" style="color: #0E4A86;"></i>
          <strong>Actividad actualizada:</strong>
        </div>
        <div><strong><?= htmlspecialchars($actividad_actualizada) ?></strong></div>
        <div class="progress-bar"></div>
      </div>
    <?php endif; ?>

    <?php if ($actividad_creada): ?>
      <div id="toast-alert">
        <div class="d-flex align-items-center gap-2 mb-1">
          <i class="bi bi-bell-fill me-1" style="color: #0E4A86;"></i>
          <strong>Actividad creada:</strong>
        </div>
        <div><strong><?= htmlspecialchars($actividad_creada) ?></strong></div>
        <div class="progress-bar"></div>
      </div>
    <?php endif; ?>

    <div class="content-columns">
      <div class="column-fichas fichas-scroll">
        <h5 class="mb-3">Mis Fichas</h5>
        <div id="contenedor-fichas">
          <!-- Aquí se cargan las fichas vía AJAX -->
          <div class="text-center text-muted">Cargando fichas...</div>
        </div>
      </div>

      <div class="column-actividades fichas-scroll" id="contenedor-actividades">
        <div class="actividades-placeholder">
          Aquí se mostrarán las actividades de la ficha. <br>
          <img src="../assets/img/monstruo.webp" alt="" class="img-fluid">
        </div>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", function() {
      fetchFichas();
    });

    function fetchFichas() {
      fetch("../ajax/actividad_gerente.php")
        .then(response => response.text())
        .then(html => {
          document.getElementById("contenedor-fichas").innerHTML = html;
          // Activar los clics en las fichas
          document.querySelectorAll(".ficha-item").forEach(item => {
            item.addEventListener("click", function() {
              const idFicha = this.dataset.id;
              cargarActividades(idFicha);
            });
          });
        })
        .catch(error => {
          console.error("Error al cargar fichas:", error);
          document.getElementById("contenedor-fichas").innerHTML = '<div class="text-danger">Error al cargar las fichas.</div>';
        });
    }

    function cargarActividades(idFicha) {
      const contenedor = document.getElementById("contenedor-actividades");
      contenedor.innerHTML = "<div class='text-center text-muted py-4'>Cargando actividades...</div>";

      fetch("../ajax/actividades_de_gerente.php?id=" + idFicha)
        .then(response => response.text())
        .then(html => {
          contenedor.innerHTML = html;
        })
        .catch(error => {
          console.error("Error al cargar actividades:", error);
          contenedor.innerHTML = '<div class="text-danger">Error al cargar actividades.</div>';
        });
    }

    // Toast auto-hide
    document.addEventListener("DOMContentLoaded", function() {
      const toast = document.getElementById('toast-alert');
      if (toast) {
        setTimeout(() => {
          toast.style.transition = 'opacity 0.5s ease';
          toast.style.opacity = 0;
          setTimeout(() => toast.remove(), 500);
        }, 3000);
      }
    });

    // Manejar cambios de orientación
    window.addEventListener('orientationchange', () => {
      setTimeout(() => {
        window.scrollTo(0, 0);
      }, 100);
    });
  </script>
</body>
</html>

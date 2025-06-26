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
  <link rel="icon" href="<?= BASE_URL ?>/assets/img/icon2.png" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
    crossorigin="anonymous"
    referrerpolicy="no-referrer" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" />
  <style>
    .main-content {
      margin-left: 280px;
      transition: margin-left 0.4s ease;
    }

    body.sidebar-collapsed .main-content {
      margin-left: 180px;
    }

    /* Scroll estilizado solo para la columna de fichas */
    .fichas-scroll {
      max-height: calc(100vh - 200px);
      overflow-y: auto;
      scrollbar-width: thin;
      scrollbar-color: #888 transparent;
    }

    /* Estilo para navegadores WebKit (Chrome, Edge, Safari) */
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

    @keyframes progressAnim {
      from {
        width: 0%;
      }

      to {
        width: 100%;
      }
    }

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

    .size {
      max-width: 800px;
      position: relative;
      left: 650px;
    }

    .img {
      max-width: 200px;
      top: 20px;
    }
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



    <div class="row gx-3" style="margin-right: 0px;">
      <div class="col-12 col-md-6 border-md-end p-3 fichas-scroll" style="max-height: 50vh; overflow-y: auto;">
        <h5 class="mb-3">Mis Fichas</h5>
        <div id="contenedor-fichas">
          <!-- Aquí se cargan las fichas vía AJAX -->
          <div class="text-center text-muted">Cargando fichas...</div>
        </div>
      </div>

      <div class="col-12 col-md-6 d-flex flex-column p-3 fichas-scroll" style="max-height: 65vh; overflow-y: auto; " id="contenedor-actividades">
        <div class="text-center text-muted fs-5 mt-5">Aquí se mostrarán las actividades de la ficha. <br>
          <img src="../assets/img/monstruo.webp" alt="" class="img-fluid img">
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
  </script>

  <script>
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
  </script>





</body>

</html>
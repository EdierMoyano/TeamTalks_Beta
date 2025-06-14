<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';
include 'session.php';

$id_instructor = $_SESSION['documento'];

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
      margin-left: 260px;
      transition: margin-left 0.4s ease;
    }

    body.sidebar-collapsed .main-content {
      margin-left: 160px;
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
  </style>
</head>

<body style="padding-top:180px;" class="sidebar-collapsed">
  <?php include 'design/header.php'; ?>
  <?php include 'design/sidebar.php'; ?>

  <div class="main-content">
    <div class="row" style="margin-right: 0px">

      <div class="col-md-6 border-end p-3 fichas-scroll" style="max-height: 500px; overflow-y: auto;">
        <h5 class="mb-3">Mis Fichas</h5>
        <div id="contenedor-fichas">
          <!-- Aquí se cargan las fichas vía AJAX -->
          <div class="text-center text-muted">Cargando fichas...</div>
        </div>
      </div>

      <!-- Columna derecha: Texto centrado -->
      <div class="col-md-6 d-flex align-items-center justify-content-center text-muted">
        <p class="text-center fs-5">Aquí se mostrarán las actividades de la ficha.</p>
      </div>

    </div>
  </div>


  <script>
    document.addEventListener("DOMContentLoaded", function() {
      fetchFichas();
    });

    function fetchFichas() {
      fetch("../ajax/actividad_transversal.php")
        .then(response => response.text())
        .then(html => {
          document.getElementById("contenedor-fichas").innerHTML = html;
        })
        .catch(error => {
          console.error("Error al cargar fichas:", error);
          document.getElementById("contenedor-fichas").innerHTML = '<div class="text-danger">Error al cargar las fichas.</div>';
        });
    }
  </script>

</body>

</html>
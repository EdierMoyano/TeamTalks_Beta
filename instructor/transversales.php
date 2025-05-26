<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php';
include 'session.php';

$id_instructor = $_SESSION['documento'];


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teamtalks</title>
    <link rel="stylesheet" href="../styles/style_side.css">
    <link rel="icon" href="../assets/img/icon2.png">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <style>

        .main-content {
            margin-left: 260px;
            transition: margin-left 0.4s ease;
        }

        body.sidebar-collapsed .main-content {
            margin-left: 160px; /* ajusta según el ancho del sidebar colapsado */
        }

        .btn-blue-dark {
        background-color: #0E4A86;
        border-color:rgb(14, 74, 134);
        color: white;
        cursor: default;
      }

      .btn-blue-dark:hover {
        background-color:rgb(9, 50, 91);
        border-color:rgb(23, 101, 180);
        color: white;
        cursor: default;
      }

      .pagination .page-link {
      background-color: white;
      color: #0E4A86;
      border-color: #0E4A86;
      margin: 0 2px;
    }

    .pagination .page-link:hover {
      background-color: #0E4A86;
      color: white;
    }

    .pagination .active .page-link {
      background-color: #0E4A86;
      color: white;
      border-color: #0E4A86;
    }

    </style>
</head>
<body style="padding-top:180px;" class="sidebar-collapsed">
  <?php include '../includes/design/header.php'; ?>
  <?php include '../includes/design/sidebar.php'; ?>

  <div class="main-content container">
    <nav class="d-flex justify-content-center navbar" style="position: relative; right: 50px;">
      <div class="">
        <form class="d-flex" role="search">
          <button class="btn btn-blue-dark" type="button" style="margin-right: 10px;">
            <i class="bi bi-search"></i>
          </button>

          <!-- Campo de búsqueda de fichas -->
          <input id="buscarficha" class="form-control me-2" type="search" placeholder="Buscar ficha" aria-label="Search" style="width: 800px; "/>
        </form>
      </div>
    </nav><br>

    <h2 class="text-center mb-4">Tus transversales</h2>


    <!-- Aquí se mostrarán los resultados de fichas -->
    <div id="resultadoFichas" class="row g-3">
      
    </div><br>


    
  </div>

<script>
  // Función para buscar fichas por AJAX
  function buscarFicha(page = 1) {
    const query = document.getElementById('buscarficha').value.trim(); // Captura lo que el usuario escribe
    const xhr = new XMLHttpRequest(); // Crea el objeto para la solicitud AJAX

    xhr.open('GET', 'ajax/buscar_transversal.php?q=' + encodeURIComponent(query) + '&page=' + page, true);
    
    xhr.onload = function () {
      if (xhr.status === 200) {
        document.getElementById('resultadoFichas').innerHTML = xhr.responseText; // Inserta el resultado en el contenedor
      }
    };

    xhr.send(); // Envía la solicitud
  }

  // Ejecutar búsqueda cada vez que se escribe en el campo
  document.getElementById('buscarficha').addEventListener('input', function () {
    buscarFicha(1); // Siempre inicia desde la página 1
  });

  // Ejecutar búsqueda automáticamente al cargar la página
  window.onload = function () {
    buscarFicha();
  };
</script>


</body>
</html>

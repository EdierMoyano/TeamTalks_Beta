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
      margin-left: 280px;
      transition: margin-left 0.4s ease;
    }

    body.sidebar-collapsed .main-content {
      margin-left: 200px;
    }

    .ficha-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0, 123, 255, 0.15);
    }

    .btn.btn-detalles {
      background-color: #f5f7fa;
      border: 1px solid #0E4A86;
      color: #0E4A86;
    }

    .btn.btn-detalles:hover {
      background-color: #e9f1ff;
      border-color: #0E4A86;
      color: #0E4A86;
    }

    .but {
      background-color: #0E4A86;
      border-color: rgb(14, 74, 134);
      color: white;
      cursor: default;
    }

    .but:hover {
      background-color: rgb(9, 50, 91);
      border-color: rgb(23, 101, 180);
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

    .buscar input {
      width: 800px;
    }

    @media (max-width: 524px) {
      .buscar input{
        width: 200px;
      }

      .buscar {
        position: relative;
        right: 30px;
      }

      .text-center {
        position: relative;
        right: 30px;
      }

      .fichas {
        position: relative;
        width: 300px;
        right: 50px;
      }

      .btn.btn-detalles {
        right: 0px;
      }
      
    }
  </style>
</head>

<body style="padding-top:180px;" class="sidebar-collapsed">
  <?php include 'design/header.php'; ?>
  <?php include 'design/sidebar.php'; ?>


  <div class="main-content">
    <nav class="d-flex justify-content-center navbar">
      <div class="">
        <form class="d-flex buscar" role="search">
          <button class="but btn" type="button" style="margin-right: 10px;">
            <i class="bi bi-search"></i>
          </button>

          <input id="buscarficha" class="form-control me-2" type="search" placeholder="Buscar por ficha o nombre de formación" aria-label="Search" style="max-width: 800px;" />
        </form>
      </div>
    </nav><br>

    <h2 class="text-center mb-4">Ficha(s) que Gestionas</h2>

    <!-- Contenedor de resultados -->
    <div id="resultadoFichas" class="fichas row g-3" style="margin-right: 0px">

    </div><br>
  </div>

  <!-- Modal para Detalles de Ficha -->
  <div class="modal fade" id="modalDetallesFicha" tabindex="-1" aria-labelledby="modalDetallesFichaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header text-white" style="background-color: #0E4A86;">
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body" id="contenido-modal-detalles">
          <p>Cargando...</p>
        </div>
      </div>
    </div>
  </div>




  <!-- Script para buscar fichas dinámicamente -->
  <script>
    function buscarFicha(page = 1) {
      const query = document.getElementById('buscarficha').value.trim();
      const xhr = new XMLHttpRequest();
      xhr.open('GET', '../ajax/buscar_fichas.php?q=' + encodeURIComponent(query) + '&page=' + page, true);
      xhr.onloadstart = function() {
        document.getElementById('resultadoFichas').innerHTML = '<div class="text-center"><span class="spinner-border"></span> Cargando...</div>';
      };
      xhr.onload = function() {
        if (xhr.status === 200) {
          document.getElementById('resultadoFichas').innerHTML = xhr.responseText;
          configurarEventosTarjetas(); // Reasignar eventos a nuevas tarjetas
        }
      };
      xhr.send();
    }

    // Ejecutar búsqueda cuando el usuario escribe en el input
    document.getElementById('buscarficha').addEventListener('input', function() {
      buscarFicha(1);
    });

    // Cargar fichas automáticamente al cargar la página
    window.onload = function() {
      buscarFicha();
    };
  </script>

  <!-- Script para manejar clic en botón "Detalles" y mostrar modal -->
  <script>
    function configurarEventosTarjetas() {
      document.querySelectorAll('.btn-detalles').forEach(btn => {
        btn.addEventListener('click', () => {
          const idFicha = btn.getAttribute('data-id');
          document.getElementById('contenido-modal-detalles').innerHTML = 'Cargando...';

          // Mostrar modal
          const modal = new bootstrap.Modal(document.getElementById('modalDetallesFicha'));
          modal.show();

          // Obtener contenido del modal vía AJAX
          fetch('../ajax/detalles_fichas.php?id=' + idFicha)
            .then(response => response.text())
            .then(html => {
              document.getElementById('contenido-modal-detalles').innerHTML = html;
            })
            .catch(() => {
              document.getElementById('contenido-modal-detalles').innerHTML = 'Error al cargar los detalles.';
            });
        });
      });

      // Aquí se podrían agregar otros eventos (por ejemplo, para ver aprendices)
    }

    // Ejecutar al finalizar carga del DOM
    document.addEventListener('DOMContentLoaded', () => {
      buscarFicha();
    });
  </script>



</body>

</html>
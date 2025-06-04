<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php';
include 'session.php';

$id_instructor = $_SESSION['documento'];
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

    .fichas {
      background-color: #0E4A86;
      color: white;

    }

    .fichas:hover {
      color: white;
      background-color: rgb(9, 50, 91);
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
  <?php include 'design/header.php'; ?>
  <?php include 'design/sidebar.php'; ?>



  <div class="main-content">
    <nav class="d-flex justify-content-center navbar" style="position: relative; right: 50px;">
      <div class="">
        <form class="d-flex" role="search">
          <a href="<?= BASE_URL ?>/instructor/index.php"><button class="but btn" type="button" style="margin-right: 10px; cursor: pointer;">
              <i class="bi bi-arrow-90deg-left"></i>
            </button></a>
          <button class="but btn" type="button" style="margin-right: 10px;">
            <i class="bi bi-search"></i>
          </button>

          <input id="buscarficha" class="form-control me-2" type="search" placeholder="Buscar ficha" aria-label="Search" style="width: 800px;" />
        </form>
      </div>
    </nav><br>
    <h2 class="text-center mb-4">Ficha <?= htmlspecialchars($id_ficha) ?></h2>

    <div class="container mt-4">

      <div class="row g-4 justify-content-center" id="contenedor-aprendices"></div>

      <div class="d-flex justify-content-center mt-4">
        <nav>
          <ul class="pagination" id="paginacion-aprendices"></ul>
        </nav>
      </div>
    </div>

  </div>

  <div class="modal fade" id="modalAprendiz" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="modalContenido">
          <!-- Aquí se cargará la tabla desde detalles_aprendiz.php -->
        </div>
      </div>
    </div>
  </div>


  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const input = document.getElementById('buscarficha');
      const container = document.getElementById('contenedor-aprendices');
      const paginacion = document.getElementById('paginacion-aprendices');
      const idFicha = <?= (int)$id_ficha ?>;

      function cargarAprendices(query = '', page = 1) {
        const formData = new FormData();
        formData.append('id_ficha', idFicha);
        formData.append('query', query);
        formData.append('page', page);

        fetch('buscar_aprendices.php', {
            method: 'POST',
            body: formData
          })
          .then(res => res.json())
          .then(data => {
            container.innerHTML = data.tarjetas;
            paginacion.innerHTML = data.paginacion;

            // Reasignar eventos de los botones
            document.querySelectorAll('.page-link').forEach(link => {
              link.addEventListener('click', e => {
                e.preventDefault();
                const nuevaPagina = e.target.dataset.page;
                cargarAprendices(input.value.trim(), nuevaPagina);
              });
            });
          });
      }

      input.addEventListener('input', () => {
        cargarAprendices(input.value.trim(), 1);
      });

      // Carga inicial
      cargarAprendices();
    });
  </script>

  <script>
    document.addEventListener('click', function(e) {
      if (e.target.classList.contains('btn-detalles')) {
        const id = e.target.getAttribute('data-id');

        const formData = new FormData();
        formData.append('id', id);

        fetch('detalles_aprendiz.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.text())
          .then(html => {
            document.getElementById('modalContenido').innerHTML = html;

            const modal = new bootstrap.Modal(document.getElementById('modalAprendiz'));
            modal.show();
          })
          .catch(error => {
            document.getElementById('modalContenido').innerHTML = '<div class="alert alert-danger">Error al cargar los detalles del aprendiz.</div>';
            const modal = new bootstrap.Modal(document.getElementById('modalAprendiz'));
            modal.show();
          });
      }
    });
  </script>





</body>

</html>
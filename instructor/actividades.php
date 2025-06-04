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
  </style>
</head>

<body style="padding-top:180px;" class="sidebar-collapsed">
  <?php include 'design/header.php'; ?>
  <?php include 'design/sidebar.php'; ?>

  <div class="main-content">
    <div class="row" style="height: 85vh;">
      <!-- Columna izquierda: Fichas -->
      <div class="col-md-4 scroll-area divider">
        <h5 class="mt-3">Mis Fichas</h5>
        <ul class="list-group" id="lista-fichas">
          <li class="list-group-item list-group-item-action">2593846 - Transversal</li>
          <li class="list-group-item list-group-item-action">1382937 - Gerente</li>
          <li class="list-group-item list-group-item-action">7639382 - Transversal</li>
          <li class="list-group-item list-group-item-action">1882721 - Gerente</li>
          <!-- Más fichas simuladas -->
        </ul>
      </div>

      <!-- Columna derecha: Actividades -->
      <div class="col-md-8 position-relative scroll-area">
        <div class="p-3" id="contenido-actividades">
          <p class="text-muted">Aquí se encontrarán las actividades de las fichas.</p>
        </div>
        <button id="crearActividadBtn" class="btn btn-primary position-absolute bottom-0 end-0 m-3" data-bs-toggle="modal" data-bs-target="#modalActividad">
          Crear Actividad
        </button>
      </div>
    </div>
  </div>

  <!-- Modal para crear actividad -->
  <div class="modal fade" id="modalActividad" tabindex="-1" aria-labelledby="modalActividadLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="formActividad">
          <div class="modal-header">
            <h5 class="modal-title" id="modalActividadLabel">Crear Actividad</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label for="titulo" class="form-label">Título</label>
              <input type="text" class="form-control" id="titulo" required />
            </div>
            <div class="mb-3">
              <label for="descripcion" class="form-label">Descripción</label>
              <textarea class="form-control" id="descripcion" rows="3" required></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary">Guardar</button>
          </div>
        </form>
      </div>
    </div>
  </div>


</body>

</html>
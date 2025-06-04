<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Foros</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0">


  <style>
    body {
      background-color: #f4f4f9;
      font-family: Arial, sans-serif;
    }
    
    .main-content {
      margin-left: 250px;
      transition: margin-left 0.4s ease;
    }

    
    body.sidebar-collapsed .main-content {
    margin-left: 100px; /* ajusta según el ancho del sidebar colapsado */
}


    .card-clase {
      width: 90%;
      margin: 0 auto;
      transition: transform 0.2s, box-shadow 0.2s;
    }

    .card-clase img {
      height: 150px;
      object-fit: cover;
    }

    .card-clase .card-body {
      font-size: 0.9rem;
    }

    .card-clase .card-title {
      font-size: 1rem;
    }

    .card-clase:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }

    .card-title {
      color: #0E4A86;
    }

    .btn-blue-dark {
      background-color: rgb(14, 74, 134);
      border-color: rgb(14, 74, 134);
      color: white;
    }

    .btn-blue-dark:hover {
      background-color: rgb(23, 101, 180);
      border-color: rgb(23, 101, 180);
      color: white;
    }

    @media (max-width: 768px) {
      .sidebar {
        float: none;
        width: 100%;
        margin-left: 0;
      }

      .main-content {
        margin-left: 0;
      }
    }
  </style>
</head>
<body class="sidebar-collapsed">

<?php include '../includes/design/header.php'; ?><br><br>

<?php include '../includes/design/sidebar.php'; ?>


<main class="main-content">
  <div class="container-fluid">
    <form class="d-flex mb-4" role="search" style="max-width: 1000px; margin: 0 auto;">
      <input class="form-control me-2" type="search" placeholder="Buscar" aria-label="Search" style="font-size: 0.9rem; height: 40px;"/>
      <button class="btn btn-blue-dark" type="submit" style="height: 40px; padding: 0 12px;">
        <i class="bi bi-search"></i>
      </button>
    </form>
    <br>

    <div class="row" id="contenedor-foros">
      <!-- Aquí se insertan los foros dinámicamente -->
    </div>
  </div>
</main>

<script>
  window.ID_USER = <?php echo json_encode($_SESSION['documento']); ?>;
</script>
<script src="../js/foros.js"></script>

</body>
</html>
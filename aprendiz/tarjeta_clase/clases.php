<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Mis Clases</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Bootstrap y fuentes -->
  <link rel="stylesheet" href="../../styles/header.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0">
  <link rel="icon" href="../../assets/img/icon2.png">




  <!-- Estilos personalizados -->
  <style>
    body {
      background-color: #f4f4f9;
      font-family: Arial, sans-serif;
    }

    .main-content {
      margin-left: 250px;
      margin-top: 20px;
      transition: margin-left 0.4s ease;
    }

    body.sidebar-collapsed .main-content {
      margin-left: 100px;
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
      color: #0E4A86;
    }

    .card-clase:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
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

    .boton-volver {
      background-color: #0E4A86;
      color: white;
      border: none;
    }

    .boton-volver:hover {
      background-color: #145baf;
      color: white;
    }

    .top-controls {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1rem;
      max-width: 1000px;
      margin: 0 auto 1rem auto;
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

  <!-- Header -->
  <?php include '../../includes/design/header.php'; ?><br><br>

  <!-- Sidebar -->
  <?php include '../../includes/design/sidebar.php'; ?>

  <script>
    // Función para volver a la clase anterior
    function volverAClase() {
      const idClase = document.querySelector("[data-id-clase]")?.getAttribute("data-id-clase");
      if (idClase) {
        window.location.href = `../index.php?id_clase=${idClase}`;
      } else {
        window.location.href = "../tarjeta_formacion/index.php";
      }
    }
  </script>

  <!-- Contenido principal -->
  <main class="main-content">
    <div class="container-fluid">

      <div class="d-flex justify-content-between align-items-center gap-3" style="max-width: 1000px; margin: -25px auto 50px auto;">
        <button type="button" class="btn boton-volver" onclick="volverAClase()" style="height: 40px; padding: 0 16px;">
          <i class="fas fa-arrow-left"></i> Volver
        </button>

        <form class="d-flex flex-grow-1" role="search">
          <input id="input-busqueda" class="form-control me-2" type="search" placeholder="Buscar" aria-label="Buscar"
            style="font-size: 0.9rem; height: 40px; flex: 1;" />
          <button class="btn btn-blue-dark" type="submit" style="height: 40px; padding: 0 12px;">
            <i class="bi bi-search"></i>
          </button>
        </form>
      </div>
      <div class="row" id="contenedor-clases">
        <!-- Aquí irán las tarjetas -->
      </div>
    </div>
  </main>

  <script src="../js/clases.js"></script>

</body>

</html>
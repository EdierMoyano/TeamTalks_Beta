<?php
session_start();
include '../../conexion/conexion.php';

$id_user = $_SESSION['documento'] ?? null;

if (!$id_user) {
  die("Debe iniciar sesión.");
}

$db = new Database();
$conexion = $db->connect();

$sql = "SELECT f.nombre, f.descripcion
    FROM formacion f
    INNER JOIN fichas fi ON fi.id_formacion = f.id_formacion
    INNER JOIN user_ficha uf ON uf.id_ficha = fi.id_ficha
    WHERE uf.id_user = :id_user
    AND f.id_estado = 1
    AND uf.id_estado = 1
";

$stmt = $conexion->prepare($sql);
$stmt->bindParam(':id_user', $id_user, PDO::PARAM_INT);
$stmt->execute();

$formaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<head>
  <meta charset="UTF-8">
  <title>Formaciones</title>
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
  <link rel="stylesheet" href="../../styles/header.css">


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
      margin-left: 100px;
      /* ajusta según el ancho del sidebar colapsado */
    }

    .card-clase {
      width: 90%;
      /* Reduce el ancho de las tarjetas */
      margin: 0 auto;
      /* Centra las tarjetas */
      transition: transform 0.2s, box-shadow 0.2s;
    }

    .card-clase img {
      height: 150px;
      /* Ajusta la altura de las imágenes */
      object-fit: cover;
      /* Asegura que las imágenes se ajusten al espacio */
    }

    .card-clase .card-body {
      font-size: 0.9rem;
      /* Reduce el tamaño del texto */
    }

    .card-clase .card-title {
      font-size: 1rem;
      /* Reduce el tamaño del título */
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
      /* Azul oscuro */
      border-color: rgb(14, 74, 134);
      color: white;
    }

    .btn-blue-dark:hover {
      background-color: rgb(23, 101, 180);
      /* Azul aún más oscuro al pasar el mouse */
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

  <!-- Header -->

  <?php include '../../includes/design/header.php'; ?><br><br>


  <!-- Sidebar -->
  <?php include '../../includes/design/sidebar.php'; ?>

  <!-- Contenido principal -->
  <main class="main-content">
    <div class="container-fluid">
      <form class="d-flex mb-4" role="search" style="max-width: 1000px; margin: 0 auto;">
        <input class="form-control me-2" type="search" placeholder="Buscar" aria-label="Search" style="font-size: 0.9rem; height: 40px;" />
        <button class="btn btn-blue-dark" type="submit" style="height: 40px; padding: 0 12px;">
          <i class="bi bi-search"></i>
        </button>
      </form>
      <br>

      <div class="row" id="contenedor-formaciones">
        <?php if (empty($formaciones)): ?>
          <p class="text-center">No hay formaciones disponibles.</p>
        <?php else: ?>
          <?php foreach ($formaciones as $formacion): ?>
            <div class="col-md-4 mb-4">
              <div class="card card-clase shadow-sm">
                <img src="https://starkcloud.com/wp-content/uploads/2024/12/La-tecnologia-del-futuro-5-avances-que-cambiaran-el-mundo-2000x1200-1.jpg" class="card-img-top" alt="Desarrollo de software">
                <div class="card-body">
                  <h5 class="card-title"><?= $formacion['nombre'] ?></h5>
                  <p class="card-text"><?= $formacion['descripcion'] ?? 'Sin descripción.' ?></p>
                  <a href="../../aprendiz/tarjeta_clase/clases.php" class="btn btn-blue-dark mt-2">Ingresar a Formacion</a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

    </div>
  </main>



</body>

</html>
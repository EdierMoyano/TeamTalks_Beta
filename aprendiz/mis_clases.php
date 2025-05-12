<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Mis Clases</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Bootstrap y fuentes -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">


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
      width: 90%; /* Reduce el ancho de las tarjetas */
      margin: 0 auto; /* Centra las tarjetas */
      transition: transform 0.2s, box-shadow 0.2s;
    }

    .card-clase img {
      height: 150px; /* Ajusta la altura de las imágenes */
      object-fit: cover; /* Asegura que las imágenes se ajusten al espacio */
    }

    .card-clase .card-body {
      font-size: 0.9rem; /* Reduce el tamaño del texto */
    }

    .card-clase .card-title {
      font-size: 1rem; /* Reduce el tamaño del título */
    }

    .card-clase:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }

    .card-title {
      color: #0E4A86;
    }

    .btn-blue-dark {
  background-color:rgb(14, 74, 134); /* Azul oscuro */
  border-color:rgb(14, 74, 134);
  color: white;
}

.btn-blue-dark:hover {
  background-color:rgb(23, 101, 180); /* Azul aún más oscuro al pasar el mouse */
  border-color:rgb(23, 101, 180);
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
<body>

  <!-- Header -->
  <?php include '../includes/desing/header.php'; ?><br><br>

  <!-- Sidebar -->
  <?php include '../includes/desing/sidebar.php'; ?>

  <!-- Contenido principal -->
  <main class="main-content">
  <div class="container-fluid">
    <form class="d-flex mb-4" role="search">
      <input class="form-control me-2" type="search" placeholder="Buscar" aria-label="Search"/> 
      <button class="btn btn-blue-dark" type="submit">
        <i class="bi bi-search"></i>
      </button> 
    </form> 
    <br>

    <div class="row" id="contenedor-clases">
      <!-- Aquí irán las tarjetas -->
    </div>
  </div>
</main>



  <!-- Script para cargar las clases -->
  <script>
  document.addEventListener("DOMContentLoaded", function () {
    const ITEMS_POR_PAGINA = 6;
    let clasesTotales = [];
    let paginaActual = 1;

    const contenedor = document.getElementById("contenedor-clases");
    const paginacion = document.createElement("div");
    paginacion.className = "d-flex justify-content-center mt-4";
    contenedor.parentNode.appendChild(paginacion); // se pone después de las tarjetas

    function renderizarPagina(pagina) {
      contenedor.innerHTML = "";

      const inicio = (pagina - 1) * ITEMS_POR_PAGINA;
      const fin = inicio + ITEMS_POR_PAGINA;
      const clasesPagina = clasesTotales.slice(inicio, fin);

      clasesPagina.forEach(clase => {
        const col = document.createElement("div");
        col.className = "col-md-4 mb-4";

        const card = document.createElement("div");
        card.className = "card card-clase h-100";

        card.innerHTML = `
  <img src="${clase.imagen}" class="card-img-top" alt="Imagen de ${clase.nombre_clase}">
  <div class="card-body">
    <h5 class="card-title">${clase.nombre_clase}</h5>
    <p class="card-text"><strong>Profesor:</strong> ${clase.nombre_profesor}</p>
    <p class="card-text"><strong>Número de Ficha:</strong> ${clase.numero_fichas}</p>
  </div>
  <div class="card-footer bg-transparent border-top-0">
    <a href="#" class="btn btn-blue-dark w-100">Ingresar a Clase</a>
  </div>
`;


        col.appendChild(card);
        contenedor.appendChild(col);
      });

      renderizarControles(clasesTotales.length);
    }

    function renderizarControles(totalItems) {
      const totalPaginas = Math.ceil(totalItems / ITEMS_POR_PAGINA);
      paginacion.innerHTML = "";

      for (let i = 1; i <= totalPaginas; i++) {
        const btn = document.createElement("button");
        btn.textContent = i;
        btn.className = `btn mx-1 ${i === paginaActual ? 'btn-blue-dark' : 'btn-outline-secondary'}`;
        btn.addEventListener("click", () => {
          paginaActual = i;
          renderizarPagina(paginaActual);
        });
        paginacion.appendChild(btn);
      }
    }

    fetch("api/clases.php")
      .then(res => res.json())
      .then(clases => {
        clasesTotales = clases;
        if (clasesTotales.length === 0) {
          contenedor.innerHTML = "<p>No hay clases disponibles.</p>";
          return;
        }
        renderizarPagina(paginaActual);
      })
      .catch(err => {
        console.error("Error al cargar clases:", err);
        contenedor.innerHTML = "<p>Error al cargar las clases.</p>";
      });
  });
</script>
</body>
</html>

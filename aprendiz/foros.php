<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Foros</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <style>
    body {
      background-color: #f4f4f9;
      font-family: Arial, sans-serif;
    }
    
    .main-content {
      margin-left: 5px;
      transition: margin-left 0.4s ease;
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

<?php include '../includes/design/header.php'; ?><br><br>

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
      <!-- Tarjetas de foros se insertarán aquí -->
    </div>
  </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const contenedor = document.getElementById('contenedor-foros');

  fetch('get_foros.php')
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        contenedor.innerHTML = `<p class="text-danger text-center">${data.error}</p>`;
        return;
      }

      data.forEach((foro, iF) => {
        const foroDiv = document.createElement('div');
        foroDiv.className = 'card card-clase mb-4 shadow';

        foroDiv.innerHTML = `
          <img src="${foro.imagen}" class="card-img-top" alt="Imagen clase">
          <div class="card-body">
            <h5 class="card-title">Clase: ${foro.nombre_clase}</h5>
            <p><strong>Profesor:</strong> ${foro.nombre_profesor}</p>
            <p><strong>Ficha:</strong> ${foro.numero_fichas}</p>
            <p><strong>Fecha del foro:</strong> ${foro.fecha_foro}</p>
            <hr>
        `;

        foro.temas.forEach((tema, iT) => {
          const collapseId = `respuestas_${iF}_${iT}`;
          let temaHTML = `
            <div class="bg-light p-3 rounded mb-3">
              <h6 class="text-primary mb-1"><i class="bi bi-chat-left-text"></i> ${tema.titulo}</h6>
              <p>${tema.descripcion}</p>
              <p class="text-muted"><small>Creado por <strong>${tema.creador}</strong> el ${tema.fecha_creacion}</small></p>
          `;

          if (tema.respuestas.length > 0) {
            temaHTML += `
              <button class="btn btn-sm btn-outline-primary mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#${collapseId}">
                Ver respuestas (${tema.respuestas.length})
              </button>
              <div class="collapse" id="${collapseId}">
                <div class="ps-3 border-start border-2 border-primary mt-2">
            `;

            tema.respuestas.forEach(resp => {
              temaHTML += `
                <div class="mb-2">
                  <p class="mb-0">${resp.descripcion}</p>
                  <p class="text-muted mb-0"><small>Respondido por <strong>${resp.respondido_por}</strong> el ${resp.fecha_respuesta}</small></p>
                </div>
              `;
            });

            temaHTML += `
                </div>
              </div>
            `;
          } else {
            temaHTML += `<p class="text-secondary"><em>No hay respuestas aún.</em></p>`;
          }

          temaHTML += `</div>`; // Cierre del tema
          foroDiv.innerHTML += temaHTML;
        });

        foroDiv.innerHTML += `</div>`; // Cierre del card-body
        contenedor.appendChild(foroDiv);
      });
    })
    .catch(error => {
      console.error('Error al obtener los foros:', error);
      contenedor.innerHTML = `<p class="text-danger text-center">Ocurrió un error al cargar los foros.</p>`;
    });
});
</script>

</body>
</html> 

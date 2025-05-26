<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Soporte Técnico</title>
  <link rel="icon" href="../styles/icon2.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <style>
    :root {
      --azul-oscuro: #061D35;
      --azul-intermedio: #0E4A86;
      --azul-claro: #348FEA;
      --blanco: #FFFFFF;
    }

    body {
      background-color: var(--blanco);
      font-family: 'Segoe UI', sans-serif;
    }

    .card-custom {
      background-color: var(--blanco);
      border-radius: 15px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .btn-azul {
      background-color: var(--azul-claro);
      color: var(--blanco);
      border-radius: 10px;
    }

    .btn-azul:hover {
      background-color: var(--azul-intermedio);
    }
  </style>
</head>
<body>
<header>
  <?php include 'includes/design/header.php'; ?>
</header>

<section class="py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8 text-center"> <!-- Mueve el h1 dentro de la columna centrada y agrega text-center -->
        <h1><i class="bi bi-tools me-2"></i>Soporte Técnico</h1>
        <p class="lead">Estamos aquí para ayudarte<br>Completa el formulario y nuestro equipo de soporte tecnico se pondrá en contacto contigo</p>
      </div>
      <div class="col-lg-8">
        <div class="card card-custom p-4">
          <form>
            <div class="mb-3">
              <label for="nombreSoporte" class="form-label">Nombre completo</label>
              <input type="text" class="form-control" id="nombreSoporte" placeholder="Tu nombre">
            </div>
            <div class="mb-3">
              <label for="correoSoporte" class="form-label">Correo electrónico</label>
              <input type="email" class="form-control" id="correoSoporte" placeholder="ejemplo@email.com">
            </div>
            <div class="mb-3">
              <label for="problema" class="form-label">Describe tu problema</label>
              <textarea class="form-control" id="problema" rows="4" placeholder="¿Qué está ocurriendo?"></textarea>
            </div>
            <button type="submit" class="btn btn-azul w-100">Enviar solicitud</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include 'includes/design/footer.php'; ?>

</body>
</html>
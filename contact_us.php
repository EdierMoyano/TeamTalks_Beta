<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contactanos</title>
    <link rel="icon" href="../styles/icon2.png">
    <!-- CSS de Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .clickable-card {
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
            border: none;
            background: transparent;
        }
        .clickable-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
        }
        .help-section {
            text-align: center;
        }
        .help-option {
            display: inline-block;
            margin: 15px;
            width: 200px;
        }
        .help-option .card {
            height: 100%;
        }
        .help-title {
            font-size: 2rem;
            margin-bottom: 30px;
            font-weight: bold;
        }
        .card-title {
            font-weight: bold;
            margin-top: 15px;
        }
    </style>
</head>
<body>

    <?php
    include 'includes/header.php';
    ?>

<div class="container-fluid py-4 py-lg-5">
        <!-- Sección de texto contacto -->
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h2 class="display-5">Contacto</h2>
                <p class="lead">Siempre nos alegra escuchar y hablar sobre nuestros productos, servicios.<br>¡Comunícanos qué piensas!</p>
            </div>
        </div>

        <!-- Formulario e imagen -->
        <div class="row mb-5 align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <form class="p-4 shadow-sm rounded">
                    <h2 class="mb-4">Contacto</h2>

                    <div class="mb-3">
                        <label for="correo" class="form-label">Correo Electrónico:</label>
                        <input type="email" class="form-control" id="correo" placeholder="Ingresa tu Correo Electrónico">
                    </div>

                    <div class="mb-3">
                        <label for="mensaje" class="form-label">Mensaje:</label>
                        <textarea class="form-control" id="mensaje" rows="5" placeholder="Escribe tu mensaje aquí"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Enviar</button>
                </form>
            </div>

            <div class="col-lg-6">
                <img src="assets/img/img3.jpg" alt="Grupo de personas" class="img-fluid rounded shadow">
            </div>
        </div>

        <!-- Sección de ayuda -->
        <div class="row mb-5 justify-content-center">
            <div class="col-12 text-center mb-4">
                <h2 class="help-title">¿Cómo podemos ayudarte?</h2>
            </div>

            <div class="col-12 col-md-5 col-lg-5 mb-4">
            <a href="soporte.php" class="text-decoration-none">
            <div class="card text-center p-4 shadow-sm h-100">
                <img src="assets/img/soporte.webp" class="card-img-top mx-auto" style="max-width: 100px;" alt="Soporte Técnico">
                <div class="card-body">
                <h3 class="card-title">Soporte Técnico</h3>
                </div>
            </div>
            </a>

            <a href="reportes.php" class="text-decoration-none">
            <div class="card text-center p-4 shadow-sm h-100 style="max-width: 100px;> 
                <img src="assets/img/seg.webp" class="card-img-top mx-auto" style="max-width: 100px;" alt="Realiza Reportes">
                <div class="card-body">
                <h3 class="card-title">Realiza Reportes</h3>
                </div>
            </div>
            </a>
        </div>
        </div>

        </div>
    </div>

    <!-- Sección de ubicación -->
    <section class="ubicacion py-5 bg-light">
        <div class="container">
            <div class="row d-flex justify-content-center ">
                <div class="col-12 text-center mb-4">
                    <h2 class="display-5">Nuestra ubicación</h2>
                </div>

                <div class="col-12" style="width: 800px;" >
                    <div class="ratio ratio-16x9">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3978.048260414805!2d-75.15232398960484!3d4.402072895553596!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8e38daac36ef33ef%3A0xc4167c4b60b14a15!2sSENA%20Centro%20de%20Industria%20y%20de%20la%20Construcci%C3%B3n!5e0!3m2!1ses-419!2sco!4v1733783533842!5m2!1ses-419!2sco"
                            allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php
    include 'includes/footer.php';
    ?>

</body>
</html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sobre nosotros</title>
    <link rel="icon" href="../styles/icon2.png">
    <!-- CSS de Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


</head>
<body>

    <?php
    include 'includes/design/header.php';
    ?>


    <main class="container-fluid py-4 py-lg-5">
        <!-- Primera sección (sin cambios) -->
        <section class="row align-items-center py-5 g-0">
            <div class="col-lg-6 order-lg-1 order-2 py-5 px-4 px-md-5">
                <div class="text-center text-lg-start">
                    <h1 class="display-5 fw-bold mb-4">Unidos en un mismo lugar</h1>
                    <h2 class="fs-3 mb-4">En <strong class="text-primary">TeamTalks</strong>, buscamos unir a la comunidad de ADSO junto a sus corazones.</h2>
                </div>
            </div>
            <div class="col-lg-6 order-lg-2 order-1" >
                <img src="assets/img/img3.jpg" alt="Imagen Grande" class="img-fluid w-100 h-auto" style="max-height: 600px; object-fit: cover; border-radius: 50px; box-shadow: #0E4A86;">
            </div>
        </section>

        <!-- Franja de misión y visión (modificado color) -->
        <section class="py-5" style="background-color: #f8f9fa;">
            <!-- Mantengo bg-light pero con código exacto -->
            <div class="container">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card h-100 border-0 p-4 text-white" style="background-color: #0E4A86;">
                            <h2 class="card-title fs-3 mb-3">Nuestra Misión y Visión</h2>
                            <p class="card-text">Familiar, Eficaz, Intuitivo eso es TeamTalks siempre lo mejor para nuestro objetivo, así logrando ser una comunidad.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100 border-0 p-4 text-white" style="background-color: #0E4A86;">
                            <h2 class="card-title fs-3 mb-3">Nuestra prioridad es el cuidado</h2>
                            <p class="card-text">Nos importan nuestros usuarios, nuestra empresa, nuestras comunidades, nuestros compañeros y nosotros mismos.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Sección de compromiso (modificado color) -->
        <section class="py-5">
            <div class="container">
                <h2 class="text-center mb-5"><strong class="text-primary">TeamTalks</strong> y su compromiso de hacer feliz a</h2>

                <div class="row g-4">
                    <!-- Item 1 -->
                    <div class="row g-4 justify-content-center">
                        <!-- Item 1 -->
                        <div class="col-md-4">
                            <div class="card h-100 border-0 text-center p-4">
                                <img src="assets/img/img5.png" alt="Usuarios Icono" class="img-fluid mx-auto mb-3" style="max-width: 80px;">
                                <h3 class="fs-3 fw-bold mb-3" style="color: #0E4A86;">Nuestros Usuarios</h3>
                                <p class="fs-5">Escuchamos a nuestros Usuarios y nos esforzamos por hacerlos felices con nuestras innovaciones.</p>
                            </div>
                        </div>

                        <!-- Item 2 -->
                        <div class="col-md-4">
                            <div class="card h-100 border-0 text-center p-4">
                                <img src="assets/img/img6.png" alt="Comunidad Icono" class="img-fluid mx-auto mb-3" style="max-width: 80px;">
                                <h3 class="fs-3 fw-bold mb-3" style="color: #0E4A86;">Nuestra Comunidad</h3>
                                <p class="fs-5">Apoyamos y conectamos a nuestras comunidades en todo el mundo.</p>
                            </div>
                        </div>

                        <!-- Item 3 -->
                        <div class="col-md-4">
                            <div class="card h-100 border-0 text-center p-4">
                                <img src="assets/img/img7.png" alt="Compañeros Icono" class="img-fluid mx-auto mb-3" style="max-width: 80px;">
                                <h3 class="fs-3 fw-bold mb-3" style="color: #0E4A86;">Nuestros compañeros de trabajo</h3>
                                <p class="fs-5">Colaboramos y generamos confianza entre nosotros.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php
    include 'includes/design/footer.php';
    ?>


    
</body>
</html>
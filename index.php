<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TeamTalks - Plataforma Educativa Personal</title>
    <link rel="icon" href="assets/img/icon2.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/index.css">
    <link rel="stylesheet" href="styles/header.css">
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>
    <?php include 'includes/design/header.php'; ?>

    <!-- Hero Section -->
    <main class="hero-section">
        <div class="container">
            <div class="row align-items-center min-vh-100 py-5">

                <!-- Content -->
                <div class="col-lg-6 order-lg-1 order-2">
                    <div class="hero-content">
                        <!-- Badge -->
                        <div class="hero-badge mb-4">
                            <i class='bx bxs-user me-2'></i>
                            <span>Tu espacio personal de aprendizaje</span>
                        </div>

                        <!-- Main Heading -->
                        <h1 class="hero-title mb-4">
                            Mayor <span class="text-gradient">organización</span><br>
                            en tu proceso de formación
                        </h1>

                        <!-- Description -->
                        <div class="hero-description mb-5">
                            <p class="lead mb-3">
                                <strong>TeamTalks</strong>, tu plataforma educativa personal.
                            </p>
                            <p class="mb-3">
                                Accede fácilmente a tus actividades, consulta fechas importantes y mantén el control de tu avance de forma intuitiva.
                            </p>
                            <p class="mb-4">
                                Diseñada para aprendices que valoran la autonomía y el aprendizaje organizado.
                            </p>
                        </div>

                        <!-- CTA -->
                        <div class="hero-cta">
                            <p class="cta-text mb-3">¿Listo para comenzar?</p>
                            <div class="d-flex flex-column flex-sm-row gap-3">
                                <a href="login/login.php">
                                    <button class="btn btn-primary btn-lg px-4">
                                        Inicia Sesión
                                        <i class='bx bx-right-arrow-alt ms-2'></i>
                                    </button>
                                </a>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- Images -->
                <div class="col-lg-6 order-lg-2 order-1 mb-5 mb-lg-0">
                    <div class="hero-images">
                        <div class="image-container">
                            <div class="image-card image-card-1">
                                <img src="assets/img/img2.jpg" alt="Organización del estudio" class="img-fluid">
                                <div class="image-overlay">
                                    <div class="overlay-content">
                                        <i class='bx bx-calendar'></i>
                                        <span>Gestión personal</span>
                                    </div>
                                </div>
                            </div>
                            <div class="image-card image-card-2">
                                <img src="assets/img/img1.jpg" alt="Avance individual" class="img-fluid">
                                <div class="image-overlay">
                                    <div class="overlay-content">
                                        <i class='bx bx-user-check'></i>
                                        <span>Tu progreso</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Floating Elements -->
                        <div class="floating-element floating-1">
                            <i class='bx bx-task'></i>
                        </div>
                        <div class="floating-element floating-2">
                            <i class='bx bx-time'></i>
                        </div>
                        <div class="floating-element floating-3">
                            <i class='bx bx-bar-chart'></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Background Elements -->
        <div class="bg-elements">
            <div class="bg-circle bg-circle-1"></div>
            <div class="bg-circle bg-circle-2"></div>
            <div class="bg-gradient"></div>
        </div>
    </main>

    <!-- Features Preview Section -->
    <section class="features-preview py-5">
        <div class="container">
            <div class="row justify-content-center text-center mb-5">
                <div class="col-lg-8">
                    <h2 class="section-title mb-3">¿Por qué elegir TeamTalks?</h2>
                    <p class="section-subtitle">
                        Descubre cómo TeamTalks mejora tu experiencia de formación personal
                    </p>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class='bx bx-task'></i>
                        </div>
                        <h4>Gestión clara de actividades</h4>
                        <p>Consulta y organiza tus tareas de manera sencilla y rápida</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class='bx bx-user'></i>
                        </div>
                        <h4>Control de tu progreso</h4>
                        <p>Revisa tu historial de entregas y seguimiento de desempeño</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class='bx bx-layout'></i>
                        </div>
                        <h4>Diseño intuitivo</h4>
                        <p>Una plataforma fácil de usar, pensada para aprendices</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/design/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/animations.js"></script>
</body>

</html>
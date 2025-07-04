<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sobre Nosotros - TeamTalks</title>
    <link rel="icon" href="assets/img/icon2.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/index.css">
    <link rel="stylesheet" href="styles/header.css">

</head>

<body>
    <?php include 'includes/design/header.php'; ?>

    <!-- Hero Section -->
    <section class="about-hero-section">
        <div class="container">
            <div class="row align-items-center min-vh-100 py-5">
                <!-- Content -->
                <div class="col-lg-6 order-lg-1 order-2">
                    <div class="about-hero-content">
                        <!-- Badge -->
                        <div class="about-hero-badge mb-4">
                            <i class='bx bx-heart me-2'></i>
                            <span>Conoce nuestra historia</span>
                        </div>

                        <!-- Main Heading -->
                        <h1 class="about-hero-title mb-4">
                            Unidos en un <span class="text-gradient">mismo lugar</span>
                        </h1>

                        <!-- Description -->
                        <div class="about-hero-description mb-5">
                            <h2 class="about-hero-subtitle mb-4">
                                En <strong class="text-primary">TeamTalks</strong>, buscamos unir a la
                                comunidad de ADSO junto a sus corazones.
                            </h2>
                            <p class="about-hero-text">
                                Creamos conexiones auténticas que trascienden las barreras digitales,
                                fomentando un ambiente de aprendizaje colaborativo y crecimiento mutuo.
                            </p>
                        </div>

                        <!-- Stats -->
                        <div class="about-stats-container">
                            <div class="about-stat-item">
                                <div class="about-stat-number">30.000</div>
                                <div class="about-stat-label">Aprendices</div>
                            </div>
                            <div class="about-stat-item">
                                <div class="about-stat-number">200</div>
                                <div class="about-stat-label">Instructores</div>
                            </div>
                            <div class="about-stat-item">
                                <div class="about-stat-number">100%</div>
                                <div class="about-stat-label">Compromiso</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Image -->
                <div class="col-lg-6 order-lg-2 order-1 mb-5 mb-lg-0">
                    <div class="about-hero-image-container">
                        <div class="about-hero-image-wrapper">
                            <img src="assets/img/img3.jpg" alt="Comunidad TeamTalks" class="img-fluid about-hero-image">
                            <div class="about-image-overlay">
                                <div class="about-overlay-content">
                                    <i class='bx bx-group'></i>
                                    <span>Comunidad Unida</span>
                                </div>
                            </div>
                        </div>

                        <!-- Floating Elements -->
                        <div class="about-floating-element about-floating-1">
                            <i class='bx bx-heart'></i>
                        </div>
                        <div class="about-floating-element about-floating-2">
                            <i class='fas fa-users'></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Background Elements -->
        <div class="about-bg-elements">
            <div class="about-bg-circle about-bg-circle-1"></div>
            <div class="about-bg-circle about-bg-circle-2"></div>
        </div>
    </section>

    <!-- Mission & Vision Section -->
    <section class="about-mission-section">
        <div class="container">
            <div class="row justify-content-center text-center mb-5">
                <div class="col-lg-8">
                    <h2 class="section-title mb-3">Nuestra Esencia</h2>
                    <p class="section-subtitle">Los valores y principios que nos guían hacia la excelencia educativa</p>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="about-mission-card">
                        <div class="about-mission-icon">
                            <i class='bx bx-target-lock'></i>
                        </div>
                        <div class="about-mission-content">
                            <h3 class="about-mission-title">Nuestra Misión y Visión</h3>
                            <p class="about-mission-text">
                                Familiar, eficaz e intuitivo: eso es TeamTalks. Siempre buscamos lo mejor
                                para nuestro objetivo, logrando así ser una verdadera comunidad educativa.
                            </p>
                            <div class="about-mission-features">
                                <div class="about-feature-item">
                                    <i class='fas fa-check'></i>
                                    <span>Familiar y cercano</span>
                                </div>
                                <div class="about-feature-item">
                                    <i class='fas fa-check'></i>
                                    <span>Eficaz y funcional</span>
                                </div>
                                <div class="about-feature-item">
                                    <i class='fas fa-check'></i>
                                    <span>Intuitivo y accesible</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="about-mission-card">
                        <div class="about-mission-icon">
                            <i class='bx  bx-shield'></i>
                        </div>
                        <div class="about-mission-content">
                            <h3 class="about-mission-title">Nuestra prioridad es el cuidado</h3>
                            <p class="about-mission-text">
                                Nos importan nuestros usuarios, nuestra empresa, nuestras comunidades,
                                nuestros compañeros y nosotros mismos. El bienestar integral es fundamental.
                            </p>
                            <div class="about-mission-features">
                                <div class="about-feature-item">
                                    <i class='fas fa-check'></i>
                                    <span>Bienestar de usuarios</span>
                                </div>
                                <div class="about-feature-item">
                                    <i class='fas fa-check'></i>
                                    <span>Cuidado del equipo</span>
                                </div>
                                <div class="about-feature-item">
                                    <i class='fas fa-check'></i>
                                    <span>Responsabilidad social</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Commitment Section -->
    <section class="about-commitment-section">
        <div class="container">
            <div class="row justify-content-center text-center mb-5">
                <div class="col-lg-10">
                    <h2 class="section-title mb-3">
                        <strong class="text-primary">TeamTalks</strong> y su compromiso de hacer feliz a
                    </h2>
                    <p class="section-subtitle">
                        Nuestro compromiso va más allá de la tecnología; se trata de crear experiencias
                        significativas para cada miembro de nuestra comunidad
                    </p>
                </div>
            </div>

            <div class="row g-4 justify-content-center">
                <!-- Usuarios -->
                <div class="col-lg-4 col-md-6">
                    <div class="about-commitment-card">
                        <div class="about-commitment-icon">
                            <img src="assets/img/img5.png" alt="Usuarios" class="about-commitment-image">
                            <div class="about-icon-badge">
                                <i class='fas fa-users'></i>
                            </div>
                        </div>
                        <div class="about-commitment-content">
                            <h3 class="about-commitment-title">Nuestros Usuarios</h3>
                            <p class="about-commitment-text">
                                Escuchamos a nuestros usuarios y nos esforzamos por hacerlos
                                felices con nuestras innovaciones constantes.
                            </p>
                            <div class="about-commitment-tags">
                                <span class="about-tag">Feedback continuo</span>
                                <span class="about-tag">Innovación</span>
                                <span class="about-tag">Satisfacción</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Comunidad -->
                <div class="col-lg-4 col-md-6">
                    <div class="about-commitment-card">
                        <div class="about-commitment-icon">
                            <img src="assets/img/img6.png" alt="Comunidad" class="about-commitment-image">
                            <div class="about-icon-badge">
                                <i class='bx bx-group'></i>
                            </div>
                        </div>
                        <div class="about-commitment-content">
                            <h3 class="about-commitment-title">Nuestra Comunidad</h3>
                            <p class="about-commitment-text">
                                Apoyamos y conectamos a nuestras comunidades en todo el mundo,
                                creando redes de aprendizaje globales.
                            </p>
                            <div class="about-commitment-tags">
                                <span class="about-tag">Conexión global</span>
                                <span class="about-tag">Apoyo mutuo</span>
                                <span class="about-tag">Crecimiento</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Compañeros -->
                <div class="col-lg-4 col-md-6">
                    <div class="about-commitment-card">
                        <div class="about-commitment-icon">
                            <img src="assets/img/img7.png" alt="Compañeros" class="about-commitment-image">
                            <div class="about-icon-badge">
                                <i class='fas fa-handshake'></i>
                            </div>
                        </div>
                        <div class="about-commitment-content">
                            <h3 class="about-commitment-title">Nuestros Compañeros</h3>
                            <p class="about-commitment-text">
                                Colaboramos y generamos confianza entre nosotros, construyendo
                                un ambiente de trabajo positivo y productivo.
                            </p>
                            <div class="about-commitment-tags">
                                <span class="about-tag">Colaboración</span>
                                <span class="about-tag">Confianza</span>
                                <span class="about-tag">Respeto</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/design/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/about-animations.js"></script>
</body>

</html>
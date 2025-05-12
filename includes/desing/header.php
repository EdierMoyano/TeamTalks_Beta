<header>
    <nav class="navbar navbar-expand-lg fixed-top" style="background-color: #0E4A86; z-index: 1000;">
        <div class="container">
            <!-- Logo -->
            <a class="navbar-brand" href="index.php">
                <img src="../assets/img/logo.png" alt="Logo de la Empresa" style="height: 120px;">
            </a>
            <!-- Botón para colapsar en dispositivos pequeños -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <!-- Enlaces de navegación -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link text-white" href="index.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="about_we.php">Sobre nosotros</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="contact_us.php">Contáctanos</a>
                    </li>
                    <li class="nav-item">
                        <a class="boton btn" href="login/login.php">Iniciar Sesión</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>
<style>
    body {
    padding-top: 125px;
}
    .boton {
        background-color: white;
        color: #0E4A86;
        transition: box-shadow 0.5s ease;
        border-radius: 5px;
    }
    .boton:hover {
        background-color: white;
        color: #0E4A86;
        box-shadow: 0 4px 12px rgb(170, 168, 168);
    }
    a {
        color: white;
        text-decoration: none;
        font-weight: normal;
        transition: transform 0.3s ease, color 0.3s ease;
        display: inline-block;
    }
    a:hover {
        color: white;
        transform: scale(1.05);
        font-weight: bold;
    }
</style>
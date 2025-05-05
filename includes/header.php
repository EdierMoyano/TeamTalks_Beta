<header>
    <nav class="navbar navbar-expand-lg" style="background-color: #0E4A86 ;">
        <div class="container">
            <!-- Logo -->
            <a class="navbar-brand" href="index.php">
                <img src="./assets/img/logo.png" alt="Logo de la Empresa" style="height: 100px;">
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
    .boton {
        background-color: white; 
        color: #0E4A86;
        transition: box-shadow 0.5s ease; /* Añade una transición suave */
        border-radius: 5px; /* Opcional: bordes redondeados */
    }

    .boton:hover {
        background-color: white;
        color: #0E4A86;
        box-shadow: 0 4px 12px rgb(170, 168, 168); /* Sombra suave alrededor */
    }
</style>
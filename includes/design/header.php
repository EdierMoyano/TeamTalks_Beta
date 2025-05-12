<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$usuario_logueado = isset($_SESSION['documento']);
$rol = $_SESSION['rol'] ?? null; // Por si aún no está definido

?>


<style>
    body {
        padding-top: 120px;
    }

    .boton {
        color: #0E4A86;
        background-color: white;
        border: none;
        box-shadow: 0 0 0 0;
        transition: box-shadow 0.3s ease;
    }

    .boton:hover, .boton:active {
        color: #0E4A86;
        background-color: white;
        box-shadow: 0 2px 8px white;
    }
    

    .l {
      text-decoration: none;
      color: white;
      font-weight: normal;
      transition: font-weight 0.2s ease;
    }

    .l:hover {
      font-weight: bold;
    }


</style>

<header style="position: fixed; top: 0; z-index:99; width:100%;">
    <nav class="navbar navbar-expand-lg" style="background-color: #0E4A86;">
        <div class="container">
            <!-- Logo -->
            <a class="navbar-brand" href="<?php echo $usuario_logueado ? 'inicio_usuario.php' : 'index.php'; ?>">
                <img src="http://localhost/teamtalks/assets/img/logo.png" alt="Logo de la Empresa" style="height: 100px;">
            </a>

            <!-- Botón responsive -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navegación -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">

                <?php if (!$usuario_logueado): ?>
                    <!-- Menú para visitantes -->
                    <li class="nav-item"><a class="l nav-link text-white" href="index.php">Inicio</a></li>
                    <li class="nav-item"><a class="l nav-link text-white" href="about_we.php">Sobre nosotros</a></li>
                    <li class="nav-item"><a class="l nav-link text-white" href="contact_us.php">Contáctanos</a></li>
                    <li class="nav-item"><a class="boton btn btn" href="login/login.php">Iniciar Sesión</a></li>
                <?php else: ?>
                    <!-- Menú para usuarios autenticados -->
                    <li class="nav-item"><a class="l nav-link text-white" href="../perfil.php">Mi Perfil</a></li>

                    <?php if ($rol == 1): ?>
                        <li class="nav-item"><a class="l nav-link text-white" href="../s_admin/dashboard.php">Panel Super Admin</a></li>
                    <?php elseif ($rol == 2): ?>
                        <li class="nav-item"><a class="l nav-link text-white" href="../admin/dashboard.php">Panel Admin</a></li>
                    <?php elseif ($rol == 3): ?>
                        <li class="nav-item"><a class="l nav-link text-white" href="../instructor/dashboard.php">Panel Instructor</a></li>
                    <?php elseif ($rol == 4): ?>
                        <li class="nav-item"><a class="l nav-link text-white" href="../aprendiz/dashboard.php">Panel Aprendiz</a></li>
                    <?php endif; ?>

                    <li class="nav-item"><a class="boton btn btn" href="http://localhost/teamtalks/includes/exit.php">Cerrar Sesión</a></li>
                <?php endif; ?>

                </ul>
            </div>
        </div>
    </nav>
</header>
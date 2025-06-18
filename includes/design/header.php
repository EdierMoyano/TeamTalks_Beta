<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php';


$usuario_logueado = isset($_SESSION['documento']);
$username = $_SESSION['nombres'] ?? 'Nombres';
$lastname = $_SESSION['apellidos'] ?? 'Apellidos';

// Detectar tipo de usuario por la URL actual
$uri = $_SERVER['REQUEST_URI'];
if (strpos($uri, '/instructor/') !== false) {
    $carpeta_inicio = BASE_URL . '/instructor/index.php';
} elseif (strpos($uri, '/aprendiz/') !== false) {
    $carpeta_inicio = BASE_URL . '/aprendiz/index.php';
} else {
    $carpeta_inicio = BASE_URL . '/index.php'; // fallback por si acaso
}

$logo_href = !$usuario_logueado ? BASE_URL . '/index.php' : $carpeta_inicio;




?>



<style>
    body {
        padding-top: 120px;
    }

    .boton {
        position: relative;
        left: 20px;
        color: #0E4A86;
        background-color: white;
        border: none;
        box-shadow: 0 0 0 0;
        transition: box-shadow 0.3s ease;
    }

    .boton:hover {
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

    a {
        text-decoration: none;
    }



    li i {
        position: relative;
        cursor: pointer;
        color: white;
        top: 25px;
        right: 30px;
        font-size: 25px;
        text-shadow: 0 0 0 0;
        transition: text-shadow 0.2s ease;
    }

    li i:hover {
        text-shadow: 0 2px 8px white;
    }

    ul {
        list-style: none;

    }


    .profile {
        top: 8px;
        background-color: none;
        border-radius: 12px;
        cursor: pointer;
        color: white;
        transition: background-color 0.2s ease;
        width: auto;
        height: 70px;
    }

    .options {
        background-color: white;
    }

    .select-options {
        color: #0E4A86;
    }

    .profile:hover {
        background-color: rgba(255, 255, 255, 0.48);
    }

    .select-options:hover {
        background-color:rgb(15, 85, 155);
        color: white;
    }
</style>

<header style="position: fixed; top: 0; z-index:99; width:100%;">
    <div class="offcanvas offcanvas-start" data-bs-scroll="true" tabindex="-1" id="offcanvasWithBothOptions" aria-labelledby="offcanvasWithBothOptionsLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="offcanvasWithBothOptionsLabel">Notificaciones</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <p>Aqui se mostraran todas las notificaciones.</p>
        </div>
    </div>
    <nav class="navbar navbar-expand-lg" style="background-color: #0E4A86;">
        <div class="container">
            <!-- Logo -->
            <a class="navbar-brand" href="<?= $logo_href ?>">
                <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Logo de la Empresa" style="height: 100px;">
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
                        <li>
                            <i class="not bi bi-bell-fill" data-bs-toggle="offcanvas" data-bs-target="#offcanvasWithBothOptions" aria-controls="offcanvasWithBothOptions"></i>

                        </li>
                        <!-- Menú para usuarios autenticados -->
                        <li class="nav-item">
                            <a class="l nav-link text-white" href="<?= $carpeta_inicio ?>" style="position:relative; right: 15px; top:25px">Inicio</a>
                        </li>


                        <li class="nav-item dropdown profile">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="color: white;">
                                <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="" style="max-width: 70px;">
                                <p style="position: relative; top: 8px;">
                                    <?php echo htmlspecialchars($username); ?>
                                </p>
                            </a>
                            <ul class="dropdown-menu options" style="left: 80px;">
                                <li><a class="dropdown-item select-options" href="<?= BASE_URL ?>/actions/edit.php">Editar perfil</a></li>
                                <li><a class="dropdown-item select-options" href="<?= BASE_URL ?>/actions/config.php">Configuración</a></li>
                                <li><a class="dropdown-item select-options" href="<?= BASE_URL ?>/includes/exit.php">Cerrar sesión</a></li>
                            </ul>
                        </li>

                    <?php endif; ?>

                </ul>
            </div>
        </div>
    </nav>
</header>
<?php if ($usuario_logueado): ?>
<script>
(function(){
    const timeoutInSeconds = <?= $timeout ?? 500000000000 ?>; // Tiempo de inactividad en segundos
    const timeoutMillis = timeoutInSeconds * 1000; // Tiempo en milisegundos
    let timeoutId;

    function cerrarSesion() {
    efetch('<?= BASE_URL ?>/includes/exit.php')
        .then(() => {
        alert('Tu sesión ha expirado por inactividad.');
        window.location.href = '<?= BASE_URL ?>/login/login.php';
        })
        .catch(() => {
          // Si no logra salir, al menos redirige
        window.location.href = '<?= BASE_URL ?>/login/login.php';
        });
    }

            function resetTimer() {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(cerrarSesion, timeoutMillis);
            }

            // Escuchar cualquier actividad del usuario
            ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'].forEach(evt => {
                window.addEventListener(evt, resetTimer, true);
            });

            // Iniciar el temporizador
            resetTimer();
        })();
    </script>
<?php endif; ?>
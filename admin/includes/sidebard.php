<?php
// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$nombreUsuario = "Usuario"; // Valor por defecto
if (isset($_SESSION['documento'])) {
    // Conexión a la base de datos
    // Asumiendo que conexion.php está en la raíz del proyecto
    $conexion = new mysqli("localhost", "root", "", "u148394603_teamtalks");
    
    // Verificar conexión
    if ($conexion->connect_error) {
        die("Error de conexión: " . $conexion->connect_error);
    }
    
    // Consultar SOLO el nombre del usuario (sin apellido)
    $id_user = $_SESSION['documento'];
    $sql = "SELECT nombres FROM usuarios WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_user);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($fila = $resultado->fetch_assoc()) {
        $nombreUsuario = $fila['nombres']; // Solo el nombre, sin apellido
    }
    
    $stmt->close();
    $conexion->close();
}

// Obtener la URL actual para marcar el elemento activo
$currentPage = basename($_SERVER['PHP_SELF']);

// Definir la ruta base para todas las URLs (ajustar según tu estructura)
$baseUrl = "/admin/"; // Asegúrate de que esta ruta sea correcta
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    

    <aside class="sidebar">
    <!-- Sidebar header -->
    <header class="sidebar-header">
        <div class="logo-container">
            <img src="<?php echo $baseUrl; ?>../assets/img/logo.png" alt="Logo" class="logo" width="50px" height="50px">
            <h3><?php echo htmlspecialchars($nombreUsuario); ?></h3>
        </div>
        <button class="toggler sidebar-toggler">
            <i class="bi bi-chevron-left"></i>
        </button>
        <button class="toggler menu-toggler">
            <i class="bi bi-list"></i>
        </button>
    </header>
    
    <nav class="sidebar-nav">
        <ul class="nav-list">
            <li class="nav-item <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">
                <?php if ($currentPage == 'index.php'): ?>
                    <a href="#" class="nav-link" style="pointer-events: none; cursor: default; opacity: 0.7;">
                        <span class="nav-icon"><i class="bi bi-speedometer2"></i></span>
                        <span class="nav-label">Dashboard</span>
                    </a>
                <?php else: ?>
                    <a href="<?php echo $baseUrl; ?>index.php" class="nav-link">
                        <span class="nav-icon"><i class="bi bi-speedometer2"></i></span>
                        <span class="nav-label">Dashboard</span>
                    </a>
                <?php endif; ?>
                <span class="nav-tooltip">Dashboard</span>
            </li>
            <li class="nav-item <?php echo ($currentPage == 'usuarios.php') ? 'active' : ''; ?>">
                <a href="<?php echo $baseUrl; ?>registro_usuarios/usuarios.php" class="nav-link">
                    <span class="nav-icon"><i class="bi bi-people"></i></span>
                    <span class="nav-label">Registro Usuarios</span>
                </a>
                <span class="nav-tooltip">Registro Usuarios</span>
            </li>
            <li class="nav-item <?php echo ($currentPage == 'fichas.php') ? 'active' : ''; ?>">
                <a href="<?php echo $baseUrl; ?>fichas/fichas.php" class="nav-link">
                    <span class="nav-icon"><i class="bi bi-card-list"></i></span>
                    <span class="nav-label">Registro Fichas</span>
                </a>
                <span class="nav-tooltip">Registro Fichas</span>
            </li>
            <li class="nav-item <?php echo ($currentPage == 'formaciones.php') ? 'active' : ''; ?>">
                <a href="<?php echo $baseUrl; ?>formacion/formaciones.php" class="nav-link">
                    <span class="nav-icon"><i class="bi bi-card-list"></i></span>
                    <span class="nav-label">Formaciones</span>
                </a>
                <span class="nav-tooltip">Formaciones</span>
            </li>
            <li class="nav-item <?php echo ($currentPage == 'materias.php') ? 'active' : ''; ?>">
                <a href="<?php echo $baseUrl; ?>materias/materias.php" class="nav-link">
                    <span class="nav-icon"><i class="bi bi-book"></i></span>
                    <span class="nav-label">Materias</span>
                </a>
                <span class="nav-tooltip">Materias</span>
            </li>
            <li class="nav-item <?php echo ($currentPage == 'horarios.php') ? 'active' : ''; ?>">
                <a href="<?php echo $baseUrl; ?>horarios/horarios.php" class="nav-link">
                    <span class="nav-icon"><i class="bi bi-calendar3"></i></span>
                    <span class="nav-label">Horarios</span>
                </a>
                <span class="nav-tooltip">Horarios</span>
            </li>
            <li class="nav-item <?php echo ($currentPage == 'gestion_fichas.php') ? 'active' : ''; ?>">
                <a href="<?php echo $baseUrl; ?>gestion_fichas/gestion_fichas.php" class="nav-link">
                    <span class="nav-icon"><i class="bi bi-journal-text"></i></span>
                    <span class="nav-label">Gestion Fichas</span>
                </a>
                <span class="nav-tooltip">Gestion Fichas</span>
            </li>
            <li class="nav-item <?php echo ($currentPage == 'instructores.php') ? 'active' : ''; ?>">
                <a href="<?php echo $baseUrl; ?>instructores.php" class="nav-link">
                    <span class="nav-icon"><i class="bi bi-person-workspace"></i></span>
                    <span class="nav-label">Instructores</span>
                </a>
                <span class="nav-tooltip">Instructores</span>
            </li>
            <li class="nav-item dropdown">
                <a href="#" onclick="toggleDropdown(event)" class="<?php echo (strpos($currentPage, 'aprendices') !== false) ? 'active' : ''; ?>">
                    <i class="bi bi-mortarboard"></i>
                    <span class="nav-label">Aprendices</span>
                    <i class="bi bi-chevron-down" style="margin-left: auto;"></i>
                </a>
                <div class="dropdown-container">
                    <a href="<?php echo $baseUrl; ?>aprendiz/aprendices-activos.php" class="<?php echo ($currentPage == 'aprendices-activos.php') ? 'active' : ''; ?>">
                        <i class="bi bi-check-circle"></i>
                        <span class="nav-label">Aprendices Activos</span>
                    </a>
                    <a href="<?php echo $baseUrl; ?>aprendiz/aprendices-inactivos.php" class="<?php echo ($currentPage == 'aprendices-inactivos.php') ? 'active' : ''; ?>">
                        <i class="bi bi-x-circle"></i>
                        <span class="nav-label">Aprendices Inactivos</span>
                    </a>
                </div>
            </li>
            <li class="nav-item">
                <a href="<?php echo $baseUrl; ?>../includes/exit.php" class="nav-link">
                    <span class="nav-icon"><i class="bi bi-box-arrow-right"></i></span>
                    <span class="nav-label">Cerrar Sesión</span>
                </a>
                <span class="nav-tooltip">Cerrar Sesión</span>
            </li>
        </ul>
    </nav>
</aside>

<!-- Botón de menú para móviles -->
<button class="mobile-toggle">
    <i class="bi bi-list"></i>
</button>
</body>

</html>
<!-- Sidebar -->

<!-- JavaScript para el sidebar -->
<script>
    // Toggle sidebar
    document.getElementById('toggle-sidebar').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('collapsed');
        
        // Ajustar el margen del contenido principal
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            if (document.getElementById('sidebar').classList.contains('collapsed')) {
                mainContent.style.marginLeft = '70px';
            } else {
                mainContent.style.marginLeft = '250px';
            }
        }
    });
    
    // Toggle dropdown
    function toggleDropdown(event) {
        event.preventDefault();
        const dropdownContainer = event.currentTarget.nextElementSibling;
        if (dropdownContainer.style.display === "block") {
            dropdownContainer.style.display = "none";
            event.currentTarget.querySelector('.bi-chevron-down').classList.remove('bi-chevron-up');
            event.currentTarget.querySelector('.bi-chevron-down').classList.add('bi-chevron-down');
        } else {
            dropdownContainer.style.display = "block";
            event.currentTarget.querySelector('.bi-chevron-down').classList.remove('bi-chevron-down');
            event.currentTarget.querySelector('.bi-chevron-down').classList.add('bi-chevron-up');
        }
    }
    
    // Abrir el dropdown si hay un elemento activo dentro
    document.addEventListener('DOMContentLoaded', function() {
        const activeDropdownItems = document.querySelectorAll('.dropdown-container a.active');
        activeDropdownItems.forEach(function(item) {
            const container = item.closest('.dropdown-container');
            if (container) {
                container.style.display = 'block';
                const dropdownToggle = container.previousElementSibling;
                if (dropdownToggle) {
                    dropdownToggle.querySelector('.bi-chevron-down').classList.remove('bi-chevron-down');
                    dropdownToggle.querySelector('.bi').classList.add('bi-chevron-up');
                }
            }
        });
    });
</script>
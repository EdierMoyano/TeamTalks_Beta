<?php
session_start();
require_once '../../aprendiz/clase/functions.php';

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['documento'])) {
    header('Location: ../login.php');
    exit;
}

// Obtener datos de sesión del usuario
$datosSesion = obtenerDatosSesion();
if (!$datosSesion) {
    die("Error: No se pudieron obtener los datos del usuario.");
}

$id_usuario_actual = $datosSesion['id'];
$id_ficha_actual = $datosSesion['id_ficha'];

// Obtener datos de la ficha actual
$ficha = obtenerFicha($id_ficha_actual);
$foros = obtenerForosSesion(); // Usar función que obtiene datos de sesión

// Obtener información de la materia principal para el breadcrumb
$materiaPrincipalData = obtenerMateriaPrincipal($id_ficha_actual);
$materiaPrincipal = $materiaPrincipalData ? $materiaPrincipalData['materia'] : 'Sin materia asignada';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Foros - TeamTalks</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap y fuentes -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0">
    <link rel="icon" href="../../assets/img/icon2.png">


    <link rel="stylesheet" href="../css/styles.css">
</head>

<body class="sidebar-collapsed">
    <!-- Header y Sidebar iguales -->

    <?php include '../../includes/design/header.php'; ?>

    <!-- Sidebar -->
    <?php include '../../includes/design/sidebar.php'; ?>

    <main class="main-content">
        <div class="container-fluid">
            <!-- Breadcrumb corregido -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-custom">
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)" onclick="volverAClase()">
                            <i class="fas fa-home"></i> <?php echo htmlspecialchars($materiaPrincipal); ?>
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        Foros de discusión
                    </li>
                </ol>
            </nav>

            <!-- Encabezado de página -->
            <div class="page-header">
                <h1 class="h2">Foros de discusión</h1>
            </div>

            <!-- Lista de foros -->
            <div class="row">
                <?php if (count($foros) > 0): ?>
                    <?php foreach ($foros as $foro): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card foro-card shadow-sm">
                                <div class="foro-header">
                                    <h3 class="card-title mb-2"><?php echo htmlspecialchars($foro['materia']); ?></h3>
                                    <div class="foro-stats">
                                        <div class="stat-item">
                                            <i class="bi bi-chat-dots"></i>
                                            <span><?php echo $foro['cantidad_temas']; ?> temas</span>
                                        </div>
                                        <div class="stat-item">
                                            <i class="bi bi-calendar"></i>
                                            <span><?php echo formatearFecha($foro['fecha_foro']); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="foro-content">
                                    <p class="foro-description">
                                        Espacio de discusión para la materia <?php echo htmlspecialchars($foro['materia']); ?>.
                                        Participa activamente en los debates y resuelve tus dudas.
                                    </p>
                                    <a href="temas_foro.php?id=<?php echo $foro['id_foro']; ?>" class="btn btn-primary btn-azul-custom">
                                        <i class="bi bi-chat-text"></i> Ver temas
                                    </a>
                                </div>
                                <div class="foro-footer">
                                    <div class="foro-instructor">
                                        <div class="instructor-avatar">
                                            <?php echo obtenerIniciales($foro['nombres'] . ' ' . $foro['apellidos']); ?>
                                        </div>
                                        <div>
                                            <p class="mb-0 fw-medium"><?php echo htmlspecialchars($foro['nombres'] . ' ' . $foro['apellidos']); ?></p>
                                            <small class="text-muted">Instructor</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            No hay foros disponibles para esta ficha.
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <script>
        // Función corregida para volver a la clase
        function volverAClase() {
            const idMateriaFicha = <?php echo json_encode($datosSesion['id_materia_ficha']); ?>;
            if (idMateriaFicha) {
                window.location.href = `index.php?id_clase=${idMateriaFicha}`;
            } else {
                window.location.href = '../index.php';
            }
        }
    </script>

    <script src="../js/script.js"></script>
</body>

</html>

<style>
    .foro-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border-radius: 12px;
        overflow: hidden;
    }

    .foro-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
    }

    .foro-header {
        background-color: #0E4A86;
        color: white;
        padding: 20px;
    }

    .foro-stats {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-top: 10px;
    }

    .stat-item {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .foro-content {
        padding: 20px;
    }

    .foro-description {
        color: #555;
        margin-bottom: 15px;
    }

    .foro-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 20px;
        background-color: #f8f9fa;
        border-top: 1px solid #eee;
    }

    .foro-instructor {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .instructor-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #0E4A86;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
    }

    .breadcrumb-custom {
        background: none;
        padding: 0;
        margin-bottom: 20px;
    }

    .breadcrumb-custom .breadcrumb-item a {
        color: #0E4A86;
        text-decoration: none;
    }

    .breadcrumb-custom .breadcrumb-item a:hover {
        text-decoration: underline;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }

    .btn-azul-custom {
        background-color: #0E4A86 !important;
        border-color: #0E4A86 !important;
        color: #fff !important;
        transition: background 0.2s, color 0.2s;
    }

    .btn-azul-custom:hover,
    .btn-azul-custom:focus {
        background-color: #08325a !important;
        border-color: #08325a !important;
        color: #fff !important;
    }

    /* Ajusta el margen izquierdo del contenido principal según el estado del sidebar */
    body:not(.sidebar-collapsed) .main-content {
        margin-left: 250px;
        /* Ancho del sidebar abierto */
        transition: margin-left 0.4s;
    }

    body.sidebar-collapsed .main-content {
        margin-left: 100px;
        /* Ancho del sidebar colapsado */
        transition: margin-left 0.4s;
    }
</style>
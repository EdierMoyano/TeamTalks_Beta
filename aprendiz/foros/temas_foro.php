<?php
session_start();
require_once '../clase/functions.php';

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

// Obtener el ID del foro desde la URL
$id_foro = $_GET['id_foro'] ?? null;
if (!$id_foro) {
    header('Location: foros.php');
    exit;
}

// Obtener información del foro
$foro = obtenerForoDetalle($id_foro);
if (!$foro) {
    header('Location: foros.php');
    exit;
}

// Verificar si el usuario puede participar en este foro
$puedeParticipar = puedeParticiparForo($id_usuario_actual, $foro['id_materia_ficha']);

// Obtener temas del foro
$temas = obtenerTemasForo($id_foro);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($foro['titulo'] ?? 'Sin título'); ?> - Temas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap y fuentes -->
    <link rel="stylesheet" href="../../styles/header.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="icon" href="../../assets/img/icon2.png">
    <link rel="stylesheet" href="../css/styles.css">

    <style>
        :root {
            --primary-color: #0E4A86;
            --primary-hover: #0d4077;
            --surface-color: #ffffff;
            --border-color: #e2e8f0;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --background-color: #f8fafc;
        }

        body.sidebar-collapsed .main-content {
            margin-left: 100px;
        }

        .main-content .container-fluid {
            max-width: 1200px;
            margin: 0 auto;
            padding-left: 12px;
            padding-right: 12px;
        }

        .breadcrumb-custom {
            background: none;
            padding: 0;
            margin-bottom: 20px;
        }

        .breadcrumb-custom .breadcrumb-item a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .breadcrumb-custom .breadcrumb-item a:hover {
            text-decoration: underline;
        }

        .foro-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }

        .foro-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .foro-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .instructor-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .instructor-avatar {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .temas-section {
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
        }

        .temas-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            background: linear-gradient(135deg, var(--background-color), #f1f5f9);
        }

        .temas-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }

        .tema-item {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .tema-item:last-child {
            border-bottom: none;
        }

        .tema-item:hover {
            background: linear-gradient(135deg, rgba(14, 74, 134, 0.02), rgba(14, 74, 134, 0.01));
        }

        .tema-header-info {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
        }

        .tema-titulo {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
            text-decoration: none;
        }

        .tema-titulo:hover {
            color: var(--primary-color);
        }

        .tema-stats {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .stat-badge {
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .tema-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.75rem;
        }

        .autor-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .autor-avatar {
            width: 32px;
            height: 32px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.75rem;
        }

        .tema-descripcion {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 0.75rem;
        }

        .tema-fecha {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .empty-state {
            padding: 4rem 2rem;
            text-align: center;
            color: var(--text-secondary);
        }

        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state h4 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .empty-state p {
            font-size: 0.875rem;
            margin: 0;
        }

        @media (max-width: 768px) {
            body {
                padding-top: 120px;
            }

            .main-content {
                margin-left: 0;
                padding: 0.75rem;
            }

            .foro-title {
                font-size: 1.5rem;
            }

            .tema-header-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .tema-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }
    </style>
</head>

<body class="sidebar-collapsed">
    <!-- Header -->
    <?php include '../../includes/design/header.php'; ?>
    <!-- Sidebar -->
    <?php include '../../includes/design/sidebar.php'; ?>

    <!-- Contenido principal -->
    <main class="main-content">
        <div class="container-fluid">
            <!-- Breadcrumb -->
            <nav class="breadcrumb-custom">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="foros.php">
                            <i class="bi bi-arrow-left me-1"></i>Volver a Foros
                        </a>
                    </li>
                </ol>
            </nav>

            <!-- Encabezado del foro -->
            <div class="foro-header">
                <?php if (!empty($foro['descripcion'])): ?>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($foro['descripcion'])); ?></p>
                <?php endif; ?>
                <div class="foro-meta">
                    <div class="instructor-info">
                        <div class="instructor-avatar">
                            <?php echo obtenerIniciales($foro['nombres'] . ' ' . $foro['apellidos']); ?>
                        </div>
                        <div>
                            <strong><?php echo htmlspecialchars($foro['nombres'] . ' ' . $foro['apellidos']); ?></strong>
                            <br>
                            <small><?php echo htmlspecialchars($foro['materia']); ?></small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de temas -->
            <div class="temas-section">
                <div class="temas-header">
                    <h3 class="temas-title">
                        <i class="bi bi-chat-dots me-2"></i>
                        Temas de Discusión (<?php echo count($temas); ?>)
                    </h3>
                </div>

                <?php if (count($temas) > 0): ?>
                    <?php foreach ($temas as $tema): ?>
                        <div class="tema-item" onclick="window.location.href='detalle_tema.php?id=<?php echo $tema['id_tema_foro']; ?>'">
                            <div class="tema-header-info">
                                <h4 class="tema-titulo">
                                    <a href="detalle_tema.php?id=<?php echo $tema['id_tema_foro']; ?>" class="tema-titulo">
                                        <?php echo htmlspecialchars($tema['titulo'] ?? 'Sin título'); ?>
                                    </a>
                                </h4>
                                <div class="tema-stats">
                                    <span class="stat-badge"><?php echo $tema['cantidad_respuestas']; ?> respuestas</span>
                                </div>
                            </div>

                            <div class="tema-meta">
                                <div class="autor-info">
                                    <div class="autor-avatar">
                                        <?php echo obtenerIniciales($tema['nombres'] . ' ' . $tema['apellidos']); ?>
                                    </div>
                                    <span>
                                        <strong><?php echo htmlspecialchars($tema['nombres'] . ' ' . $tema['apellidos']); ?></strong>
                                    </span>
                                </div>
                                <div class="tema-fecha">
                                    <i class="bi bi-clock me-1"></i>
                                    <?php echo formatearFecha($tema['fecha_creacion']); ?>
                                </div>
                            </div>

                            <?php if (!empty($tema['descripcion'])): ?>
                                <div class="tema-descripcion">
                                    <?php echo nl2br(htmlspecialchars(substr($tema['descripcion'], 0, 200))); ?>
                                    <?php if (strlen($tema['descripcion']) > 200): ?>...<?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="bi bi-chat-slash"></i>
                        </div>
                        <h4>No hay temas de discusión</h4>
                        <p>Aún no se han creado temas en este foro</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="../js/script.js"></script>
</body>

</html>
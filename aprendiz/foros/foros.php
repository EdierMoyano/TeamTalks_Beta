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

// Obtener foros disponibles para el usuario
$foros = obtenerForosSesion();

// Obtener información de la materia para mostrar contexto
$materiaPrincipalData = obtenerMateriaPrincipal($datosSesion['id_ficha']);
$materiaPrincipal = $materiaPrincipalData ? $materiaPrincipalData['materia'] : 'Sin materia asignada';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Foros - TeamTalks</title>
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

        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            text-align: center;
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin: 0;
        }

        .foro-card {
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .foro-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-hover));
        }

        .foro-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(14, 74, 134, 0.15);
            border-color: var(--primary-color);
        }

        .foro-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .foro-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }

        .foro-badge {
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .foro-meta {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .foro-instructor {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .instructor-avatar {
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

        .foro-stats {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .foro-description {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .foro-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }

        .foro-date {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .btn-acceder {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-acceder:hover {
            background: var(--primary-hover);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(14, 74, 134, 0.3);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .empty-state p {
            font-size: 1rem;
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
            
            .page-header h1 {
                font-size: 2rem;
            }
            
            .foro-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .foro-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }
            
            .foro-actions {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
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
            <!-- Encabezado de la página -->
            <div class="page-header">
                <h1><i class="bi bi-chat-square-text me-3"></i>Foros de Discusión</h1>
                <p>Participa en las discusiones académicas y comparte conocimientos con tus compañeros</p>
            </div>

            <!-- Lista de foros -->
            <?php if (count($foros) > 0): ?>
                <div class="foros-grid">
                    <?php foreach ($foros as $foro): ?>
                        <div class="foro-card" onclick="window.location.href='temas_foro.php?id_foro=<?php echo $foro['id_foro']; ?>'">
                            <div class="foro-header">
                                <span class="foro-badge"><?php echo $foro['cantidad_temas']; ?> temas</span>
                            </div>
                            
                            <div class="foro-meta">
                                <div class="foro-instructor">
                                    <div class="instructor-avatar">
                                        <?php echo obtenerIniciales($foro['nombres'] . ' ' . $foro['apellidos']); ?>
                                    </div>
                                    <span>
                                        <strong><?php echo htmlspecialchars($foro['nombres'] . ' ' . $foro['apellidos']); ?></strong>
                                        <br>
                                        <small><?php echo htmlspecialchars($foro['materia']); ?></small>
                                    </span>
                                </div>
                                
                                <div class="foro-stats">
                                    <div class="stat-item">
                                        <i class="bi bi-chat-dots"></i>
                                        <span><?php echo $foro['cantidad_temas']; ?> temas</span>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (!empty($foro['descripcion'])): ?>
                                <div class="foro-description">
                                    <?php echo nl2br(htmlspecialchars($foro['descripcion'])); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="foro-actions">
                                <div class="foro-date">
                                    <i class="bi bi-calendar3"></i>
                                    Creado el <?php echo date('d/m/Y', strtotime($foro['fecha_foro'])); ?>
                                </div>
                                <a href="temas_foro.php?id_foro=<?php echo $foro['id_foro']; ?>" class="btn-acceder">
                                    <i class="bi bi-arrow-right-circle"></i>
                                    Acceder al Foro
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="bi bi-chat-square-text"></i>
                    </div>
                    <h3>No hay foros disponibles</h3>
                    <p>Aún no se han creado foros para tu ficha. Los instructores podrán crear foros próximamente.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="../js/script.js"></script>
</body>
</html>

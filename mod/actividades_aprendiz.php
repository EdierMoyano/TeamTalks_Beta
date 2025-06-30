<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php';
include 'session.php';

$id_instructor = $_SESSION['documento'];
$id_aprendiz = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$sql_aprendiz = "
  SELECT 
    u.nombres, 
    u.apellidos, 
    u.id, 
    f.id_ficha, 
    fo.nombre AS nombre_formacion
  FROM usuarios u
  JOIN user_ficha uf ON u.id = uf.id_user
  JOIN fichas f ON uf.id_ficha = f.id_ficha
  JOIN formacion fo ON f.id_formacion = fo.id_formacion
  WHERE u.id = :id_aprendiz
";

$stmt_aprendiz = $conex->prepare($sql_aprendiz);
$stmt_aprendiz->execute(['id_aprendiz' => $id_aprendiz]);
$aprendiz = $stmt_aprendiz->fetch(PDO::FETCH_ASSOC);

if (!$aprendiz) {
    echo "<div class='alert alert-danger text-center mt-4'>Aprendiz no encontrado.</div>";
    exit;
}

// Obtener actividades del aprendiz
$sql = "
  SELECT
    a.id_actividad,
    a.titulo,
    a.descripcion,
    a.fecha_entrega,
    m.materia,
    e.estado AS estado_actividad,
    au.nota,
    au.comentario_inst,
    au.fecha_entrega AS fecha_entregada,
    au.archivo1,
    au.archivo2,
    au.archivo3
  FROM actividades_user au
  JOIN actividades a ON au.id_actividad = a.id_actividad
  JOIN estado e ON au.id_estado_actividad = e.id_estado
  JOIN materia_ficha mf ON a.id_materia_ficha = mf.id_materia_ficha
  JOIN materias m ON mf.id_materia = m.id_materia
  WHERE au.id_user = :id_aprendiz
  ORDER BY a.fecha_entrega DESC
";

$stmt = $conex->prepare($sql);
$stmt->execute(['id_aprendiz' => $id_aprendiz]);
$actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);

function mapEstadoToClass($estado)
{
    $estadoMap = [
        'Aprobado' => 'aprobado',
        'Desaprobado' => 'desaprobado',
        'Entregado' => 'entregado',
        'Pendiente' => 'pendiente',
        'No entregado' => 'noentregado'
    ];
    return $estadoMap[$estado] ?? strtolower(str_replace(' ', '', $estado));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Actividades de <?= htmlspecialchars($aprendiz['nombres'] . ' ' . $aprendiz['apellidos']) ?> - Teamtalks</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/styles/style_side.css" />
    <link rel="stylesheet" href="<?= BASE_URL ?>/styles/header.css">
    <link rel="icon" href="<?= BASE_URL ?>/assets/img/icon2.png" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
        crossorigin="anonymous"
        referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* ===== CSS VARIABLES ===== */
        :root {
            --primary-color: #0E4A86;
            --primary-hover: #0d4077;
            --primary-light: #e8f1ff;
            --secondary-color: #6c757d;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            --background-color: #f8fafc;
            --surface-color: #ffffff;
            --border-color: #e2e8f0;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
        }

        /* ===== RESET & BASE STYLES ===== */
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: var(--background-color);
            color: var(--text-primary);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            padding-top: 180px;
        }

        /* ===== LAYOUT ===== */
        .main-content {
            margin-left: 280px;
            transition: margin-left 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 2rem;
            min-height: 100vh;
            margin-top: -50px;
        }

        body.sidebar-collapsed .main-content {
            margin-left: 200px;
        }

        /* ===== NAVIGATION BAR ===== */
        .navigation-bar {
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .back-button {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
        }

        .back-button:hover {
            background: var(--primary-hover);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .breadcrumb-text {
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        /* ===== STUDENT HEADER ===== */
        .student-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            color: white;
            padding: 2rem;
            border-radius: var(--radius-xl);
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }

        .student-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(50%, -50%);
        }

        .student-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            position: relative;
            z-index: 1;
        }

        .student-avatar {
            width: 4rem;
            height: 4rem;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            border: 3px solid rgba(255, 255, 255, 0.3);
        }

        .student-details h1 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0 0 0.5rem 0;
        }

        .student-meta {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            font-size: 0.95rem;
            opacity: 0.9;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* ===== FILTERS SECTION ===== */
        .filters-section {
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
        }

        .filters-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .filters-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .activities-count {
            background: var(--primary-light);
            color: var(--primary-color);
            padding: 0.375rem 0.75rem;
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-weight: 600;
        }

        .filters-controls {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-secondary);
        }

        /* ===== FORM INPUTS ===== */
        .filter-input {
            padding: 0.75rem;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            transition: all 0.2s ease;
            background: var(--surface-color);
            font-family: inherit;
        }

        .filter-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(14, 74, 134, 0.1);
        }

        /* SELECT PERSONALIZADO */
        .filter-input[type="text"],
        .filter-input[type="date"] {
            /* Estilos ya definidos arriba */
        }

        .filter-input select,
        select.filter-input {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23374151' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 1rem;
            padding-right: 2.5rem;
            cursor: pointer;
        }

        .filter-input select:focus,
        select.filter-input:focus {
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%230E4A86' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
        }

        .clear-filters {
            background: var(--danger-color);
            color: white;
            border: none;
            padding: 0.75rem 1rem;
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            align-self: end;
        }

        .clear-filters:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        /* ===== ACTIVITY CARDS ===== */
        .activities-container {
            display: grid;
            gap: 1.5rem;
        }

        .activity-card {
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-xl);
            padding: 0;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            position: relative;
        }

        .activity-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }

        .activity-card.hidden {
            display: none;
        }

        .activity-header {
            padding: 1.5rem 2rem 1rem;
            border-bottom: 1px solid var(--border-color);
            background: linear-gradient(135deg, #fafbfc, #f1f5f9);
        }

        .activity-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0 0 0.5rem 0;
            line-height: 1.3;
        }

        .activity-subject {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--primary-light);
            color: var(--primary-color);
            padding: 0.375rem 0.75rem;
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .activity-body {
            padding: 1.5rem 2rem;
        }

        .activity-description {
            font-size: 1rem;
            line-height: 1.6;
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
        }

        /* ===== ACTIVITY META ===== */
        .activity-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .meta-item-card {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: var(--background-color);
            border-radius: var(--radius-md);
            border-left: 3px solid var(--border-color);
        }

        .meta-item-card.due-date {
            border-left-color: var(--warning-color);
        }

        .meta-item-card.status {
            border-left-color: var(--info-color);
        }

        .meta-item-card.submitted {
            border-left-color: var(--success-color);
        }

        .meta-item-card.grade {
            border-left-color: var(--primary-color);
        }

        .meta-icon {
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            color: white;
            flex-shrink: 0;
        }

        .meta-icon.due-date {
            background: var(--warning-color);
        }

        .meta-icon.status {
            background: var(--info-color);
        }

        .meta-icon.submitted {
            background: var(--success-color);
        }

        .meta-icon.grade {
            background: var(--primary-color);
        }

        .meta-content {
            flex: 1;
        }

        .meta-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 0;
        }

        .meta-value {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-primary);
            margin: 0;
        }

        /* ===== STATUS BADGES ===== */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.75rem;
            border-radius: var(--radius-md);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-badge.entregado {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .status-badge.pendiente {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }

        .status-badge.aprobado {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .status-badge.desaprobado {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .status-badge.noentregado {
            background: rgba(107, 114, 128, 0.1);
            color: var(--secondary-color);
        }

        .status-badge.revision {
            background: rgba(59, 130, 246, 0.1);
            color: var(--info-color);
        }

        /* ===== GRADE DISPLAY ===== */
        .grade-display {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 3rem;
            height: 3rem;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            color: white;
            border-radius: 50%;
            font-size: 1.125rem;
            font-weight: 700;
        }

        /* ===== COMMENTS SECTION ===== */
        .instructor-comment {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border: 1px solid #f59e0b;
            border-radius: var(--radius-md);
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .comment-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .comment-icon {
            color: var(--warning-color);
            font-size: 1.125rem;
        }

        .comment-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: #92400e;
            margin: 0;
        }

        .comment-text {
            color: #92400e;
            font-size: 0.875rem;
            line-height: 1.5;
            margin: 0;
        }

        /* ===== FILES SECTION ===== */
        .files-section {
            background: var(--background-color);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .files-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .files-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }

        .files-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .file-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            color: var(--text-primary);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .file-link:hover {
            background: var(--primary-light);
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-1px);
        }

        .file-icon {
            color: var(--primary-color);
        }

        /* ===== ACTION BUTTONS ===== */
        .activity-footer {
            padding: 1.5rem 2rem;
            border-top: 1px solid var(--border-color);
            background: var(--background-color);
        }

        .btn-view-details {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
        }

        .btn-view-details:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: white;
        }

        /* ===== EMPTY STATES ===== */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--surface-color);
            border-radius: var(--radius-xl);
            border: 1px solid var(--border-color);
        }

        .empty-state-icon {
            font-size: 4rem;
            color: var(--text-muted);
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: var(--text-muted);
            font-size: 1rem;
            margin: 0;
        }

        .no-results {
            text-align: center;
            padding: 3rem 2rem;
            background: var(--surface-color);
            border-radius: var(--radius-xl);
            border: 1px solid var(--border-color);
            margin-top: 2rem;
        }

        .no-results-icon {
            font-size: 3rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* ===== ANIMATIONS ===== */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .activity-card {
            animation: fadeInUp 0.6s ease-out;
        }

        .activity-card:nth-child(even) {
            animation-delay: 0.1s;
        }

        .activity-card:nth-child(odd) {
            animation-delay: 0.2s;
        }

        /* ===== SCROLLBAR ===== */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--background-color);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--text-muted);
        }

        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 768px) {

            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .navigation-bar {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }

            .student-info {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }

            .student-details h1 {
                font-size: 1.5rem;
            }

            .student-meta {
                justify-content: center;
            }

            .filters-controls {
                grid-template-columns: 1fr;
            }

            .activity-header,
            .activity-body,
            .activity-footer {
                padding: 1rem 1.5rem;
            }

            .activity-meta {
                grid-template-columns: 1fr;
            }

            .files-grid {
                flex-direction: column;
            }

            .file-link {
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .filters-section {
                padding: 1rem;
            }

            .activity-header,
            .activity-body,
            .activity-footer {
                padding: 1rem;
            }

            .activity-title {
                font-size: 1.25rem;
            }

            .student-details h1 {
                font-size: 1.25rem;
            }
        }
    </style>
</head>

<body class="sidebar-collapsed">
    <?php include 'design/header.php'; ?>
    <?php include 'design/sidebar.php'; ?>

    <div class="main-content">
        <!-- Navigation Bar -->
        <div class="navigation-bar">
            <div>
                <a href="ver_aprendices.php?id_ficha=<?= $aprendiz['id_ficha'] ?>" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                    Volver a Aprendices
                </a>
            </div>
            <div class="breadcrumb-text">
                <i class="fas fa-home"></i>
                Aprendices > <?= htmlspecialchars($aprendiz['nombre_formacion']) ?> > Actividades
            </div>
        </div>

        <!-- Student Header -->
        <div class="student-header">
            <div class="student-info">
                <div class="student-avatar">
                    <?= strtoupper(substr($aprendiz['nombres'], 0, 1) . substr($aprendiz['apellidos'], 0, 1)) ?>
                </div>
                <div class="student-details">
                    <h1><?= htmlspecialchars($aprendiz['nombres'] . ' ' . $aprendiz['apellidos']) ?></h1>
                    <div class="student-meta">
                        <div class="meta-item">
                            <i class="fas fa-id-card"></i>
                            <span>CC: <?= htmlspecialchars($aprendiz['id']) ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-users"></i>
                            <span><?= htmlspecialchars($aprendiz['nombre_formacion']) ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-tasks"></i>
                            <span><?= count($actividades) ?> Actividades</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="filters-section">
            <div class="filters-header">
                <h3 class="filters-title">
                    <i class="fas fa-filter"></i>
                    Filtros y Búsqueda
                </h3>
                <div class="activities-count" id="activitiesCount">
                    <?= count($actividades) ?> actividades encontradas
                </div>
            </div>
            <div class="filters-controls">
                <div class="filter-group">
                    <label class="filter-label">Buscar por título</label>
                    <input type="text" class="filter-input" id="searchTitle" placeholder="Nombre de la actividad...">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Filtrar por estado</label>
                    <select class="filter-input" id="filterStatus">
                        <option value="">Todos los estados</option>
                        <option value="aprobado">Aprobado</option>
                        <option value="desaprobado">Desaprobado</option>
                        <option value="entregado">Entregado</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="noentregado">No entregado</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Desde fecha</label>
                    <input type="date" class="filter-input" id="filterDateFrom">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Hasta fecha</label>
                    <input type="date" class="filter-input" id="filterDateTo">
                </div>
                <div class="filter-group">
                    <label class="filter-label">&nbsp;</label>
                    <button class="clear-filters" id="clearFilters">
                        <i class="fas fa-times"></i>
                        Limpiar Filtros
                    </button>
                </div>
            </div>
        </div>

        <!-- Activities Container -->
        <div class="activities-container" id="activitiesContainer">
            <?php if (count($actividades) > 0): ?>
                <?php foreach ($actividades as $act): ?>
                    <article class="activity-card"
                        data-title="<?= strtolower(htmlspecialchars($act['titulo'])) ?>"
                        data-status="<?= mapEstadoToClass($act['estado_actividad']) ?>"
                        data-date="<?= $act['fecha_entrega'] ?>"
                        data-subject="<?= strtolower(htmlspecialchars($act['materia'])) ?>">
                        <!-- Activity Header -->
                        <div class="activity-header">
                            <h2 class="activity-title"><?= htmlspecialchars($act['titulo']) ?></h2>
                            <div class="activity-subject">
                                <i class="fas fa-book"></i>
                                <?= htmlspecialchars($act['materia']) ?>
                            </div>
                        </div>

                        <!-- Activity Body -->
                        <div class="activity-body">
                            <!-- Description -->
                            <div class="activity-description">
                                <?= nl2br(htmlspecialchars($act['descripcion'])) ?>
                            </div>

                            <!-- Meta Information -->
                            <div class="activity-meta">
                                <!-- Due Date -->
                                <div class="meta-item-card due-date">
                                    <div class="meta-icon due-date">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <div class="meta-content">
                                        <p class="meta-label">Fecha de Entrega</p>
                                        <p class="meta-value"><?= date('d/m/Y', strtotime($act['fecha_entrega'])) ?></p>
                                    </div>
                                </div>

                                <!-- Status -->
                                <div class="meta-item-card status">
                                    <div class="meta-icon status">
                                        <i class="fas fa-info-circle"></i>
                                    </div>
                                    <div class="meta-content">
                                        <p class="meta-label">Estado</p>
                                        <div class="status-badge <?= mapEstadoToClass($act['estado_actividad']) ?>">
                                            <i class="fas fa-circle"></i>
                                            <?= htmlspecialchars($act['estado_actividad']) ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Submitted Date -->
                                <?php if (!empty($act['fecha_entregada'])): ?>
                                    <div class="meta-item-card submitted">
                                        <div class="meta-icon submitted">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                        <div class="meta-content">
                                            <p class="meta-label">Entregado</p>
                                            <p class="meta-value"><?= date('d/m/Y H:i', strtotime($act['fecha_entregada'])) ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Grade -->
                                <?php if ($act['nota'] !== null): ?>
                                    <div class="meta-item-card grade">
                                        <div class="meta-icon grade">
                                            <i class="fas fa-star"></i>
                                        </div>
                                        <div class="meta-content">
                                            <p class="meta-label">Calificación</p>
                                            <div class="grade-display">
                                                <?= number_format($act['nota'], 1) ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Instructor Comment -->
                            <?php if (!empty($act['comentario_inst'])): ?>
                                <div class="instructor-comment">
                                    <div class="comment-header">
                                        <i class="fas fa-comment-dots comment-icon"></i>
                                        <h4 class="comment-title">Comentario del Instructor</h4>
                                    </div>
                                    <p class="comment-text"><?= htmlspecialchars($act['comentario_inst']) ?></p>
                                </div>
                            <?php endif; ?>

                            <!-- Files Section -->
                            <div class="files-section">
                                <div class="files-header">
                                    <i class="fas fa-paperclip" style="color: var(--primary-color);"></i>
                                    <h4 class="files-title">Archivos Entregados</h4>
                                </div>
                                <div class="files-grid">
                                    <?php
                                    $archivos = [];
                                    for ($i = 1; $i <= 3; $i++) {
                                        if (!empty($act["archivo$i"])) {
                                            $archivo = htmlspecialchars($act["archivo$i"]);
                                            $nombreVisible = explode('_', $archivo, 2)[1] ?? $archivo;
                                            echo "<a href='../uploads/$archivo' class='file-link' target='_blank'>
                                                    <i class='fas fa-file-alt file-icon'></i>
                                                    $nombreVisible
                                                  </a>";
                                        }
                                    }
                                    if (empty($archivos) && empty($act["archivo1"]) && empty($act["archivo2"]) && empty($act["archivo3"])) {
                                        echo '<span style="color: var(--text-muted); font-style: italic;">No se entregaron archivos</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>

                        <!-- Activity Footer -->
                        <div class="activity-footer">
                            <a href="../mod/ver_entregas.php?id_actividad=<?= $act['id_actividad'] ?>&id_aprendiz=<?= $id_aprendiz ?>"
                                class="btn-view-details">
                                <i class="fas fa-eye"></i>
                                Ver Entrega Detallada
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <h3>No hay actividades asignadas</h3>
                    <p>Este aprendiz no tiene actividades asignadas en este momento.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- No Results State -->
        <div class="no-results" id="noResults" style="display: none;">
            <div class="no-results-icon">
                <i class="fas fa-search"></i>
            </div>
            <h3>No se encontraron actividades</h3>
            <p>Intenta ajustar los filtros de búsqueda para encontrar lo que buscas.</p>
        </div>
    </div>

    <script>
        class ActivityFilter {
            constructor() {
                this.activities = document.querySelectorAll('.activity-card');
                this.searchTitle = document.getElementById('searchTitle');
                this.filterStatus = document.getElementById('filterStatus');
                this.filterDateFrom = document.getElementById('filterDateFrom');
                this.filterDateTo = document.getElementById('filterDateTo');
                this.clearFilters = document.getElementById('clearFilters');
                this.activitiesCount = document.getElementById('activitiesCount');
                this.noResults = document.getElementById('noResults');
                this.init();
            }

            init() {
                this.bindEvents();
            }

            bindEvents() {
                // Search and filter events
                this.searchTitle.addEventListener('input', () => this.applyFilters());
                this.filterStatus.addEventListener('change', () => this.applyFilters());
                this.filterDateFrom.addEventListener('change', () => this.applyFilters());
                this.filterDateTo.addEventListener('change', () => this.applyFilters());
                // Clear filters
                this.clearFilters.addEventListener('click', () => this.clearAllFilters());
            }

            applyFilters() {
                const searchTerm = this.searchTitle.value.toLowerCase().trim();
                const statusFilter = this.filterStatus.value.toLowerCase();
                const dateFrom = this.filterDateFrom.value;
                const dateTo = this.filterDateTo.value;
                let visibleCount = 0;

                this.activities.forEach(activity => {
                    const title = activity.dataset.title;
                    const status = activity.dataset.status;
                    const date = activity.dataset.date;
                    let showActivity = true;

                    // Title filter
                    if (searchTerm && !title.includes(searchTerm)) {
                        showActivity = false;
                    }
                    // Status filter
                    if (statusFilter && status !== statusFilter) {
                        showActivity = false;
                    }
                    // Date range filter
                    if (dateFrom && date < dateFrom) {
                        showActivity = false;
                    }
                    if (dateTo && date > dateTo) {
                        showActivity = false;
                    }

                    // Show/hide activity
                    if (showActivity) {
                        activity.classList.remove('hidden');
                        visibleCount++;
                    } else {
                        activity.classList.add('hidden');
                    }
                });

                // Update count and show/hide no results
                this.updateCount(visibleCount);
                this.toggleNoResults(visibleCount === 0);
            }

            clearAllFilters() {
                this.searchTitle.value = '';
                this.filterStatus.value = '';
                this.filterDateFrom.value = '';
                this.filterDateTo.value = '';
                this.activities.forEach(activity => {
                    activity.classList.remove('hidden');
                });
                this.updateCount(this.activities.length);
                this.toggleNoResults(false);
            }

            updateCount(count) {
                this.activitiesCount.textContent = `${count} actividades encontradas`;
            }

            toggleNoResults(show) {
                this.noResults.style.display = show ? 'block' : 'none';
            }
        }

        // Initialize filter system when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            new ActivityFilter();
        });
    </script>
</body>
</html>

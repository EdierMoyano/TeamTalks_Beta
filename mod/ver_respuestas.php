<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php';
include 'session.php';

if (isset($_GET['id_notificacion'])) {
    $id_notificacion = (int) $_GET['id_notificacion'];
    $id_usuario = $_SESSION['documento'] ?? null;

    if ($id_usuario && $id_notificacion) {
        $stmt = $conex->prepare("UPDATE notificaciones SET leido = 1 WHERE id_notificacion = ? AND id_usuario = ?");
        $stmt->execute([$id_notificacion, $id_usuario]);
    }
}

$id_tema = isset($_GET['id_tema']) ? (int) $_GET['id_tema'] : 0;
$id_user = $_SESSION['documento'];

// Obtener datos del tema (sin cambios)
$stmt = $conex->prepare("
    SELECT tf.*, u.nombres, u.apellidos, f.id_foro, u.avatar
    FROM temas_foro tf
    JOIN usuarios u ON tf.id_user = u.id
    JOIN foros f ON tf.id_foro = f.id_foro
    WHERE tf.id_tema_foro = ?
");
$stmt->execute([$id_tema]);
$tema = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tema) {
    echo "<div class='alert alert-danger text-center mt-4'>Tema no encontrado.</div>";
    exit;
}

// Obtener respuestas con estructura jerárquica - CORREGIDO
$stmt = $conex->prepare("
    SELECT r.*, u.nombres, u.apellidos, roles.rol, u.avatar,
           rp.id_respuesta_foro as respuesta_padre_id,
           up.nombres as padre_nombres, up.apellidos as padre_apellidos
    FROM respuesta_foro r
    JOIN usuarios u ON r.id_user = u.id
    JOIN roles ON u.id_rol = roles.id_rol
    LEFT JOIN respuesta_foro rp ON r.id_respuesta_padre = rp.id_respuesta_foro
    LEFT JOIN usuarios up ON rp.id_user = up.id
    WHERE r.id_tema_foro = ?
    ORDER BY 
        CASE WHEN r.id_respuesta_padre IS NULL THEN r.id_respuesta_foro ELSE r.id_respuesta_padre END DESC,
        r.id_respuesta_padre IS NULL DESC,
        r.fecha_respuesta DESC
");
$stmt->execute([$id_tema]);
$todas_respuestas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organizar respuestas en estructura jerárquica
$respuestas_principales = [];
$respuestas_hijas = [];

foreach ($todas_respuestas as $respuesta) {
    if ($respuesta['id_respuesta_padre'] === null) {
        $respuestas_principales[] = $respuesta;
    } else {
        $respuestas_hijas[$respuesta['id_respuesta_padre']][] = $respuesta;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tema['titulo']) ?> - Foro</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/styles/style_side.css" />
    <link rel="stylesheet" href="<?= BASE_URL ?>/styles/header.css">
    <link rel="icon" href="<?= BASE_URL ?>/assets/img/icon2.png" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
        crossorigin="anonymous"
        referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" />
    
    <style>
        /* ===== CSS VARIABLES ===== */
        :root {
            --primary-color: #0E4A86;
            --primary-hover: #0d4077;
            --primary-light: #e8f1ff;
            --primary-dark: #0a3660;
            --secondary-color: #6c757d;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
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
            margin-top: -40px;
        }

        body.sidebar-collapsed .main-content {
            margin-left: 200px;
        }

        .forum-container {
            max-width: 900px;
            margin: 0 auto;
        }

        /* ===== NAVIGATION ===== */
        .breadcrumb-nav {
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
        }

        .back-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }

        .back-link:hover {
            color: var(--primary-hover);
            transform: translateX(-2px);
        }

        /* ===== TOPIC HEADER ===== */
        .topic-header {
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
        }

        .topic-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-hover));
        }

        .topic-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 1rem 0;
            line-height: 1.2;
        }

        .topic-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .author-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--primary-light);
            color: var(--primary-color);
            padding: 0.5rem 1rem;
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .author-avatar {
            width: 2rem;
            height: 2rem;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.75rem;
        }

        .topic-date {
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .topic-content {
            font-size: 1.125rem;
            line-height: 1.7;
            color: var(--text-primary);
        }

        /* ===== COMMENT FORM ===== */
        .comment-form {
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
        }

        .comment-form::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--success-color), var(--primary-color));
        }

        .form-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .form-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .form-control {
            width: 100%;
            padding: 1rem;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 1rem;
            line-height: 1.5;
            transition: all 0.2s ease;
            resize: vertical;
            min-height: 120px;
            font-family: inherit;
            background: var(--surface-color);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgb(14 74 134 / 0.1);
            background: #fafbfc;
        }

        .form-control::placeholder {
            color: var(--text-muted);
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        /* ===== BUTTONS ===== */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            line-height: 1;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
            box-shadow: var(--shadow-sm);
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-secondary {
            background: var(--surface-color);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: var(--background-color);
            color: var(--text-primary);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }

        /* ===== COMMENTS SECTION ===== */
        .comments-section {
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .comments-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border-color);
            background: linear-gradient(135deg, var(--background-color), #f1f5f9);
        }

        .comments-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .comments-count {
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* ===== COMMENT ITEMS ===== */
        .comment-item {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s ease;
            opacity: 0;
            transform: translateY(20px);
        }

        .comment-item.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .comment-item:last-child {
            border-bottom: none;
        }

        .comment-item:hover {
            background: linear-gradient(135deg, rgba(14, 74, 134, 0.02), rgba(14, 74, 134, 0.01));
        }

        .comment-header {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .comment-author {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            flex: 1;
        }

        .comment-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }

        .comment-author-info {
            flex: 1;
            min-width: 0;
        }

        .comment-author-info h6 {
            margin: 0 0 0.25rem 0;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.875rem;
        }

        .comment-role {
            color: var(--text-muted);
            font-size: 0.75rem;
            margin: 0 0 0.5rem 0;
        }

        .comment-date {
            color: var(--text-muted);
            font-size: 0.75rem;
            white-space: nowrap;
        }

        .comment-content {
            font-size: 1rem;
            line-height: 1.6;
            color: var(--text-primary);
            margin-left: 3.5rem;
        }

        /* ===== COMMENT ACTIONS ===== */
        .comment-actions {
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid var(--border-color);
            margin-left: 3.5rem;
        }

        .btn-reply {
            background: none !important;
            border: none !important;
            color: var(--primary-color) !important;
            font-size: 0.875rem !important;
            padding: 0.25rem 0.5rem !important;
            border-radius: 4px !important;
            transition: all 0.2s ease !important;
            cursor: pointer !important;
        }

        .btn-reply:hover {
            background: rgba(14, 74, 134, 0.1) !important;
            color: var(--primary-hover) !important;
        }

        /* ===== REPLY FORMS ===== */
        .reply-form {
            display: none !important;
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            margin-left: 3.5rem;
        }

        .reply-form.active {
            display: block !important;
            animation: slideDown 0.3s ease;
        }

        .reply-to-info {
            background: #e3f2fd;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            color: #1976d2;
            border-left: 3px solid #2196f3;
        }

        /* ===== NESTED REPLIES ===== */
        .replies-toggle-container {
            margin-top: 1rem;
            margin-left: 3.5rem;
        }

        .btn-toggle-replies {
            background: var(--surface-color) !important;
            border: 1px solid var(--border-color) !important;
            color: var(--primary-color) !important;
            font-size: 0.875rem !important;
            padding: 0.5rem 1rem !important;
            border-radius: 20px !important;
            transition: all 0.3s ease !important;
            cursor: pointer !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
        }

        .btn-toggle-replies:hover {
            background: var(--primary-light) !important;
            border-color: var(--primary-color) !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 2px 8px rgba(14, 74, 134, 0.15) !important;
        }

        .btn-toggle-replies.expanded {
            background: var(--primary-color) !important;
            color: white !important;
            border-color: var(--primary-color) !important;
        }

        .btn-toggle-replies.expanded:hover {
            background: var(--primary-hover) !important;
        }

        .btn-toggle-replies.expanded .toggle-icon {
            transform: rotate(180deg) !important;
        }

        .comment-replies {
            margin-left: 3rem;
            margin-top: 1rem;
            border-left: 3px solid var(--primary-color);
            padding-left: 1rem;
            background: rgba(14, 74, 134, 0.02);
            border-radius: 0 8px 8px 0;
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.4s ease;
        }

        .comment-replies.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .nested-comment {
            background: rgba(255, 255, 255, 0.8);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(14, 74, 134, 0.1);
            transition: all 0.2s ease;
        }

        .nested-comment:hover {
            background: rgba(255, 255, 255, 0.95);
            border-color: rgba(14, 74, 134, 0.2);
            transform: translateX(5px);
        }

        .nested-comment .comment-avatar {
            width: 36px;
            height: 36px;
        }

        .nested-comment .comment-content {
            margin-left: 0;
            margin-top: 0.5rem;
        }

        /* ===== EMPTY STATES ===== */
        .empty-state {
            padding: 4rem 2rem;
            text-align: center;
            color: var(--text-muted);
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
            color: var(--text-secondary);
        }

        .empty-state p {
            font-size: 0.875rem;
            margin: 0;
        }

        /* ===== LOAD MORE ===== */
        .load-more-container {
            padding: 2rem;
            text-align: center;
            border-top: 1px solid var(--border-color);
            background: linear-gradient(135deg, var(--background-color), #f1f5f9);
        }

        .btn-load-more {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: var(--radius-lg);
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }

        .btn-load-more:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-load-more:active {
            transform: translateY(0);
        }

        .btn-load-more.loading {
            pointer-events: none;
            opacity: 0.7;
        }

        .load-more-info {
            margin-top: 1rem;
            font-size: 0.875rem;
            color: var(--text-muted);
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

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }

        .animate-in {
            animation: fadeInUp 0.6s ease-out;
        }

        .pulse {
            animation: pulse 1.5s ease-in-out infinite;
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

        /* Large tablets and small desktops (1024px - 1200px) */
        @media (max-width: 1200px) {
            .main-content {
                margin-left: 200px;
                padding: 1.5rem;
            }

            .forum-container {
                max-width: 100%;
            }
        }

        /* Tablets (768px - 1024px) */
        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .topic-header,
            .comment-form,
            .comments-section {
                border-radius: var(--radius-md);
            }

            .topic-title {
                font-size: 1.75rem;
            }

            .comment-item {
                padding: 1.25rem 1.5rem;
            }

            .comments-header {
                padding: 1.25rem 1.5rem;
            }
        }

        /* Mobile landscape and small tablets (481px - 768px) */
        @media (max-width: 768px) {
            body {
                padding-top: 120px;
            }

            .main-content {
                padding: 0.75rem;
                margin-top: -20px;
            }

            .breadcrumb-nav {
                padding: 0.75rem 1rem;
                margin-bottom: 1rem;
            }

            .topic-header {
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }

            .topic-title {
                font-size: 1.5rem;
                line-height: 1.3;
            }

            .topic-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }

            .topic-content {
                font-size: 1rem;
            }

            .comment-form {
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }

            .form-title {
                font-size: 1.125rem;
            }

            .form-control {
                padding: 0.875rem;
                min-height: 100px;
            }

            .form-actions {
                flex-direction: column;
                gap: 0.75rem;
            }

            .btn {
                justify-content: center;
                width: 100%;
            }

            .comment-item {
                padding: 1rem 1.25rem;
            }

            .comments-header {
                padding: 1rem 1.25rem;
            }

            .comment-header {
                flex-direction: column;
                gap: 0.75rem;
                align-items: flex-start;
            }

            .comment-author {
                width: 100%;
            }

            .comment-author-info {
                display: flex;
                flex-direction: column;
                gap: 0.25rem;
            }

            .comment-date {
                align-self: flex-start;
                margin-top: 0.25rem;
            }

            .comment-content {
                margin-left: 0;
                margin-top: 1rem;
            }

            .comment-actions {
                margin-left: 0;
            }

            .reply-form {
                margin-left: 0;
            }

            .replies-toggle-container {
                margin-left: 0;
            }

            .comment-replies {
                margin-left: 1rem;
                padding-left: 0.75rem;
            }

            .load-more-container {
                padding: 1.5rem 1rem;
            }

            .btn-load-more {
                padding: 0.875rem 1.5rem;
                width: 100%;
            }
        }

        /* Mobile portrait (320px - 480px) */
        @media (max-width: 480px) {
            .main-content {
                padding: 0.5rem;
            }

            .breadcrumb-nav {
                padding: 0.75rem;
                margin-bottom: 0.75rem;
            }

            .back-link {
                font-size: 0.875rem;
            }

            .topic-header {
                padding: 1rem;
                margin-bottom: 1rem;
            }

            .topic-title {
                font-size: 1.25rem;
            }

            .author-badge {
                padding: 0.375rem 0.75rem;
                font-size: 0.8rem;
            }

            .author-avatar,
            .comment-avatar {
                width: 40px;
                height: 40px;
            }

            .nested-comment .comment-avatar {
                width: 32px;
                height: 32px;
            }

            .topic-date {
                font-size: 0.8rem;
            }

            .comment-form {
                padding: 1rem;
                margin-bottom: 1rem;
            }

            .form-header {
                gap: 0.5rem;
                margin-bottom: 1rem;
            }

            .form-title {
                font-size: 1rem;
            }

            .form-control {
                padding: 0.75rem;
                font-size: 0.9rem;
                min-height: 80px;
            }

            .comment-item {
                padding: 0.875rem 1rem;
            }

            .comments-header {
                padding: 0.875rem 1rem;
            }

            .comments-title {
                font-size: 1rem;
            }

            .comment-author-info h6 {
                font-size: 0.8rem;
            }

            .comment-role {
                font-size: 0.7rem;
            }

            .comment-date {
                font-size: 0.7rem;
            }

            .comment-content {
                font-size: 0.9rem;
                line-height: 1.5;
            }

            .btn-reply {
                font-size: 0.8rem !important;
                padding: 0.25rem 0.375rem !important;
            }

            .btn-toggle-replies {
                font-size: 0.8rem !important;
                padding: 0.375rem 0.75rem !important;
            }

            .reply-form {
                padding: 0.75rem;
            }

            .reply-to-info {
                padding: 0.375rem 0.75rem;
                font-size: 0.8rem;
            }

            .nested-comment {
                padding: 0.75rem;
            }

            .comment-replies {
                margin-left: 0.5rem;
                padding-left: 0.5rem;
            }

            .empty-state {
                padding: 2rem 1rem;
            }

            .empty-state-icon {
                font-size: 2rem;
            }

            .empty-state h4 {
                font-size: 1rem;
            }

            .empty-state p {
                font-size: 0.8rem;
            }

            .load-more-container {
                padding: 1rem 0.75rem;
            }

            .btn-load-more {
                padding: 0.75rem 1rem;
                font-size: 0.8rem;
            }

            .load-more-info {
                font-size: 0.8rem;
            }
        }

        /* Very small screens (max 360px) */
        @media (max-width: 360px) {
            .topic-title {
                font-size: 1.125rem;
            }

            .author-avatar,
            .comment-avatar {
                width: 36px;
                height: 36px;
            }

            .nested-comment .comment-avatar {
                width: 28px;
                height: 28px;
            }

            .comment-content {
                font-size: 0.85rem;
            }

            .form-control {
                font-size: 0.85rem;
            }
        }

        /* Landscape orientation adjustments */
        @media (max-height: 600px) and (orientation: landscape) {
            body {
                padding-top: 80px;
            }

            .main-content {
                margin-top: -10px;
            }

            .topic-header {
                padding: 1rem;
            }

            .comment-form {
                padding: 1rem;
            }

            .form-control {
                min-height: 60px;
            }
        }

        /* Focus and accessibility improvements */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* High contrast mode support */
        @media (prefers-contrast: high) {
            .comment-item {
                border: 2px solid var(--border-color);
            }

            .btn {
                border: 2px solid currentColor;
            }
        }

        /* Touch device optimizations */
        @media (pointer: coarse) {
            .btn-reply,
            .btn-toggle-replies {
                min-height: 44px !important;
                min-width: 44px !important;
            }

            .form-control {
                min-height: 44px;
            }
        }
    </style>
</head>

<body class="sidebar-collapsed">
    <?php include 'design/header.php'; ?>
    <?php include 'design/sidebar.php'; ?>

    <div class="main-content">
        <div class="forum-container">
            <!-- Navigation -->
            <nav class="breadcrumb-nav">
                <a href="temas_foro.php?id_foro=<?= $tema['id_foro'] ?>" class="back-link">
                    <i class="fas fa-arrow-left"></i>
                    Volver a Temas del Foro
                </a>
            </nav>

            <!-- Topic Header -->
            <article class="topic-header animate-in">
                <h1 class="topic-title"><?= htmlspecialchars($tema['titulo']) ?></h1>
                <div class="topic-meta">
                    <div class="author-badge">
                        <div class="author-avatar">
                            <img src="<?= BASE_URL ?>/<?= empty($tema['avatar']) ? 'uploads/avatar/user.webp' : htmlspecialchars($tema['avatar']) ?>" alt="avatar"
                                class="rounded-circle me-3" style="width: 48px; height: 48px; object-fit: cover;">
                        </div>
                        <span><?= htmlspecialchars($tema['nombres'] . ' ' . $tema['apellidos']) ?></span>
                    </div>
                    <time class="topic-date" datetime="<?= $tema['fecha_creacion'] ?>">
                        <i class="fas fa-clock"></i>
                        <?= date('d/m/Y \a \l\a\s H:i', strtotime($tema['fecha_creacion'])) ?>
                    </time>
                </div>
                <div class="topic-content">
                    <?= nl2br(htmlspecialchars($tema['descripcion'])) ?>
                </div>
            </article>

            <!-- Comment Form Principal -->
            <section class="comment-form animate-in">
                <div class="form-header">
                    <i class="fas fa-comment-dots" style="color: var(--primary-color);"></i>
                    <h2 class="form-title">Agregar Comentario</h2>
                </div>
                <form action="procesar_respuesta.php" method="POST" id="commentForm">
                    <input type="hidden" name="id_tema_foro" value="<?= $id_tema ?>">
                    <input type="hidden" name="id_respuesta_padre" value="">
                    <div class="form-group">
                        <label for="descripcion" class="form-label">
                            Tu respuesta <span style="color: var(--danger-color);">*</span>
                        </label>
                        <textarea
                            class="form-control"
                            id="descripcion"
                            name="descripcion"
                            placeholder="Comparte tu opinión, experiencia o pregunta sobre este tema..."
                            required
                            aria-describedby="descripcionHelp"></textarea>
                        <small id="descripcionHelp" class="form-text" style="color: var(--text-muted); font-size: 0.75rem; margin-top: 0.5rem; display: block;">
                            Sé respetuoso y constructivo en tu comentario.
                        </small>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="clearForm()">
                            <i class="fas fa-times"></i>
                            Limpiar texto
                        </button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-paper-plane"></i>
                            Publicar Comentario
                        </button>
                    </div>
                </form>
            </section>

            <!-- Comments Section con respuestas anidadas -->
            <section class="comments-section animate-in">
                <div class="comments-header">
                    <h3 class="comments-title">
                        <i class="fas fa-comments"></i>
                        Comentarios
                        <span class="comments-count"><?= count($respuestas_principales) ?></span>
                    </h3>
                </div>

                <?php if (empty($respuestas_principales)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-comment-slash"></i>
                        </div>
                        <h4>No hay comentarios aún</h4>
                        <p>Sé el primero en participar en esta discusión</p>
                    </div>
                <?php else: ?>
                    <div id="commentsContainer">
                        <?php foreach ($respuestas_principales as $index => $respuesta): ?>
                            <article class="comment-item" data-index="<?= $index ?>" data-comment-id="<?= $respuesta['id_respuesta_foro'] ?>" style="display: none;">
                                <div class="comment-header">
                                    <div class="comment-author">
                                        <img src="<?= BASE_URL ?>/<?= empty($respuesta['avatar']) ? 'uploads/avatar/user.webp' : htmlspecialchars($respuesta['avatar']) ?>" 
                                             alt="avatar" class="comment-avatar">
                                        <div class="comment-author-info">
                                            <h6><?= htmlspecialchars($respuesta['nombres'] . ' ' . $respuesta['apellidos']) ?></h6>
                                            <p class="comment-role"><?= htmlspecialchars($respuesta['rol']) ?></p>
                                        </div>
                                    </div>
                                    <time class="comment-date" datetime="<?= $respuesta['fecha_respuesta'] ?>">
                                        <?= date('d/m/Y \a \l\a\s H:i', strtotime($respuesta['fecha_respuesta'])) ?>
                                    </time>
                                </div>

                                <div class="comment-content">
                                    <?= nl2br(htmlspecialchars($respuesta['descripcion'])) ?>
                                </div>

                                <!-- Acciones del comentario -->
                                <div class="comment-actions">
                                    <button class="btn-reply" data-comment-id="<?= $respuesta['id_respuesta_foro'] ?>" data-author-name="<?= htmlspecialchars($respuesta['nombres'] . ' ' . $respuesta['apellidos']) ?>">
                                        <i class="fas fa-reply"></i>
                                        Responder
                                    </button>
                                </div>

                                <!-- Formulario de respuesta -->
                                <div class="reply-form" id="replyForm-<?= $respuesta['id_respuesta_foro'] ?>">
                                    <div class="reply-to-info">
                                        <i class="fas fa-reply"></i>
                                        Respondiendo a <strong><?= htmlspecialchars($respuesta['nombres'] . ' ' . $respuesta['apellidos']) ?></strong>
                                    </div>
                                    <form action="procesar_respuesta.php" method="POST" class="reply-form-inner">
                                        <input type="hidden" name="id_tema_foro" value="<?= $id_tema ?>">
                                        <input type="hidden" name="id_respuesta_padre" value="<?= $respuesta['id_respuesta_foro'] ?>">
                                        <div class="form-group">
                                            <textarea
                                                class="form-control"
                                                name="descripcion"
                                                placeholder="Escribe tu respuesta..."
                                                required
                                                rows="3"></textarea>
                                        </div>
                                        <div class="form-actions">
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="cancelReply(<?= $respuesta['id_respuesta_foro'] ?>)">
                                                <i class="fas fa-times"></i>
                                                Cancelar
                                            </button>
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="fas fa-paper-plane"></i>
                                                Responder
                                            </button>
                                        </div>
                                    </form>
                                </div>

                                <?php if (isset($respuestas_hijas[$respuesta['id_respuesta_foro']])): ?>
                                    <!-- Botón para mostrar/ocultar respuestas -->
                                    <div class="replies-toggle-container">
                                        <button class="btn-toggle-replies" data-comment-id="<?= $respuesta['id_respuesta_foro'] ?>" data-replies-count="<?= count($respuestas_hijas[$respuesta['id_respuesta_foro']]) ?>">
                                            <i class="fas fa-chevron-down toggle-icon"></i>
                                            <span class="toggle-text">
                                                Mostrar <?= count($respuestas_hijas[$respuesta['id_respuesta_foro']]) ?>
                                                <?= count($respuestas_hijas[$respuesta['id_respuesta_foro']]) === 1 ? 'respuesta' : 'respuestas' ?>
                                            </span>
                                        </button>
                                    </div>

                                    <!-- Contenedor de respuestas (inicialmente oculto) -->
                                    <div class="comment-replies" id="replies-<?= $respuesta['id_respuesta_foro'] ?>" style="display: none;">
                                        <?php foreach ($respuestas_hijas[$respuesta['id_respuesta_foro']] as $respuesta_hija): ?>
                                            <div class="nested-comment">
                                                <div class="comment-header">
                                                    <div class="comment-author">
                                                        <img src="<?= BASE_URL ?>/<?= empty($respuesta_hija['avatar']) ? 'uploads/avatar/user.webp' : htmlspecialchars($respuesta_hija['avatar']) ?>" 
                                                             alt="avatar" class="comment-avatar">
                                                        <div class="comment-author-info">
                                                            <h6><?= htmlspecialchars($respuesta_hija['nombres'] . ' ' . $respuesta_hija['apellidos']) ?></h6>
                                                            <small class="comment-role"><?= htmlspecialchars($respuesta_hija['rol']) ?></small>
                                                        </div>
                                                    </div>
                                                    <time class="comment-date" datetime="<?= $respuesta_hija['fecha_respuesta'] ?>">
                                                        <?= date('d/m/Y H:i', strtotime($respuesta_hija['fecha_respuesta'])) ?>
                                                    </time>
                                                </div>
                                                <div class="comment-content">
                                                    <?= nl2br(htmlspecialchars($respuesta_hija['descripcion'])) ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <?php if (count($respuestas_principales) > 5): ?>
                        <div class="load-more-container">
                            <button class="btn-load-more" id="loadMoreBtn">
                                <i class="fas fa-chevron-down"></i>
                                Mostrar más comentarios
                            </button>
                            <div class="load-more-info">
                                <span id="loadMoreInfo">Mostrando <span id="currentCount">0</span> de <?= count($respuestas_principales) ?> comentarios</span>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </div>
    </div>

    <script>
        // ===== SISTEMA DE COMENTARIOS CON TOGGLE DE RESPUESTAS =====
        // 1. FUNCIONES GLOBALES
        window.toggleReplyForm = function(commentId, authorName) {
            console.log('🔄 Función llamada:', commentId, authorName);
            // Cerrar otros formularios
            document.querySelectorAll('.reply-form').forEach(form => {
                if (form.id !== `replyForm-${commentId}`) {
                    form.style.display = 'none';
                    form.classList.remove('active');
                }
            });

            // Mostrar/ocultar formulario actual
            const replyForm = document.getElementById(`replyForm-${commentId}`);
            if (!replyForm) {
                console.error('❌ Formulario no encontrado:', commentId);
                return;
            }

            if (replyForm.style.display === 'none' || !replyForm.style.display) {
                replyForm.style.display = 'block';
                replyForm.classList.add('active');
                // Focus en textarea
                const textarea = replyForm.querySelector('textarea');
                if (textarea) {
                    setTimeout(() => textarea.focus(), 100);
                }
                console.log('✅ Formulario mostrado');
            } else {
                replyForm.style.display = 'none';
                replyForm.classList.remove('active');
                console.log('✅ Formulario ocultado');
            }
        };

        window.cancelReply = function(commentId) {
            const replyForm = document.getElementById(`replyForm-${commentId}`);
            if (replyForm) {
                replyForm.style.display = 'none';
                replyForm.classList.remove('active');
                const textarea = replyForm.querySelector('textarea');
                if (textarea) textarea.value = '';
            }
        };

        // 2. FUNCIÓN PARA TOGGLE DE RESPUESTAS
        window.toggleReplies = function(commentId, repliesCount) {
            console.log('🔄 Toggle respuestas para comentario:', commentId);
            const repliesContainer = document.getElementById(`replies-${commentId}`);
            const toggleBtn = document.querySelector(`[data-comment-id="${commentId}"].btn-toggle-replies`);

            if (!repliesContainer || !toggleBtn) {
                console.error('❌ Elementos no encontrados');
                return;
            }

            const icon = toggleBtn.querySelector('.toggle-icon');
            const text = toggleBtn.querySelector('.toggle-text');
            const isVisible = repliesContainer.style.display !== 'none';

            if (isVisible) {
                // Ocultar respuestas
                repliesContainer.style.display = 'none';
                repliesContainer.classList.remove('visible');
                icon.className = 'fas fa-chevron-down toggle-icon';
                text.textContent = `Mostrar ${repliesCount} ${repliesCount === 1 ? 'respuesta' : 'respuestas'}`;
                toggleBtn.classList.remove('expanded');
                console.log('✅ Respuestas ocultadas');
            } else {
                // Mostrar respuestas
                repliesContainer.style.display = 'block';
                setTimeout(() => {
                    repliesContainer.classList.add('visible');
                }, 10);
                icon.className = 'fas fa-chevron-up toggle-icon';
                text.textContent = `Ocultar ${repliesCount === 1 ? 'respuesta' : 'respuestas'}`;
                toggleBtn.classList.add('expanded');
                console.log('✅ Respuestas mostradas');
            }
        };

        // 3. EVENT LISTENERS PARA BOTONES
        function bindAllButtons() {
            console.log('🔗 Vinculando todos los event listeners...');
            // Botones de responder
            document.querySelectorAll('.btn-reply').forEach(btn => {
                btn.replaceWith(btn.cloneNode(true));
            });

            document.querySelectorAll('.btn-reply').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const commentId = this.getAttribute('data-comment-id');
                    const authorName = this.getAttribute('data-author-name');
                    console.log('🖱️ Click en botón responder:', commentId);
                    toggleReplyForm(commentId, authorName);
                });
            });

            // Botones de cancelar
            document.querySelectorAll('[data-cancel-reply]').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const commentId = this.getAttribute('data-cancel-reply');
                    cancelReply(commentId);
                });
            });

            // Botones de toggle respuestas
            document.querySelectorAll('.btn-toggle-replies').forEach(btn => {
                btn.replaceWith(btn.cloneNode(true));
            });

            document.querySelectorAll('.btn-toggle-replies').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const commentId = this.getAttribute('data-comment-id');
                    const repliesCount = parseInt(this.getAttribute('data-replies-count'));
                    console.log('🖱️ Click en toggle respuestas:', commentId);
                    toggleReplies(commentId, repliesCount);
                });
            });

            console.log('✅ Todos los event listeners vinculados');
        }

        // 4. PAGINACIÓN CON BINDING AUTOMÁTICO
        class CommentPagination {
            constructor() {
                this.commentsPerPage = 5;
                this.currentPage = 0;
                this.totalComments = <?= count($respuestas_principales) ?>;
                this.comments = document.querySelectorAll('.comment-item');
                this.loadMoreBtn = document.getElementById('loadMoreBtn');
                this.currentCountSpan = document.getElementById('currentCount');
                this.init();
            }

            init() {
                this.showInitialComments();
                this.bindEvents();
            }

            showInitialComments() {
                this.showComments(0, this.commentsPerPage);
                this.updateUI();
            }

            showComments(start, end) {
                for (let i = start; i < end && i < this.comments.length; i++) {
                    const comment = this.comments[i];
                    comment.style.display = 'block';
                    setTimeout(() => {
                        comment.classList.add('visible');
                    }, (i - start) * 100);
                }

                // Re-vincular botones después de mostrar comentarios
                setTimeout(() => {
                    bindAllButtons();
                }, 500);
            }

            loadMore() {
                const nextStart = (this.currentPage + 1) * this.commentsPerPage;
                const nextEnd = nextStart + this.commentsPerPage;

                if (this.loadMoreBtn) {
                    this.loadMoreBtn.classList.add('loading');
                    this.loadMoreBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando...';
                }

                setTimeout(() => {
                    this.showComments(nextStart, nextEnd);
                    this.currentPage++;
                    this.updateUI();

                    if (this.loadMoreBtn) {
                        this.loadMoreBtn.classList.remove('loading');
                        this.loadMoreBtn.innerHTML = '<i class="fas fa-chevron-down"></i> Mostrar más comentarios';
                    }
                }, 800);
            }

            updateUI() {
                const visibleComments = (this.currentPage + 1) * this.commentsPerPage;
                const actualVisible = Math.min(visibleComments, this.totalComments);

                if (this.currentCountSpan) {
                    this.currentCountSpan.textContent = actualVisible;
                }

                if (actualVisible >= this.totalComments && this.loadMoreBtn) {
                    this.loadMoreBtn.style.display = 'none';
                    const loadMoreInfo = document.querySelector('.load-more-info');
                    if (loadMoreInfo) {
                        loadMoreInfo.innerHTML = `<span style="color: var(--success-color);"><i class="fas fa-check"></i> Todos los comentarios mostrados</span>`;
                    }
                }
            }

            bindEvents() {
                if (this.loadMoreBtn) {
                    this.loadMoreBtn.addEventListener('click', () => {
                        this.loadMore();
                    });
                }
            }
        }

        // 5. FUNCIONES AUXILIARES
        function autoResize() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        }

        function clearForm() {
            const textarea = document.getElementById('descripcion');
            if (textarea) {
                textarea.value = '';
                textarea.style.height = 'auto';
                textarea.focus();
            }
        }

        // 6. INICIALIZACIÓN
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Inicializando página...');

            // Elementos básicos
            const commentForm = document.getElementById('commentForm');
            const submitBtn = document.getElementById('submitBtn');
            const textarea = document.getElementById('descripcion');

            // Auto-resize textareas
            if (textarea) {
                textarea.addEventListener('input', autoResize);
            }

            document.querySelectorAll('textarea').forEach(ta => {
                ta.addEventListener('input', autoResize);
            });

            // Form submission principal
            if (commentForm) {
                commentForm.addEventListener('submit', function(e) {
                    if (submitBtn) {
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publicando...';
                        submitBtn.disabled = true;
                    }
                });
            }

            // Inicializar paginación
            if (<?= count($respuestas_principales) ?> > 0) {
                new CommentPagination();
            }

            // Vincular botones iniciales
            setTimeout(() => {
                bindAllButtons();
            }, 1000);

            console.log('✅ Inicialización completa');
        });

        // 7. MANEJO DE FORMULARIOS DE RESPUESTA
        document.addEventListener('submit', function(e) {
            if (e.target.classList.contains('reply-form-inner')) {
                const form = e.target;
                const submitBtn = form.querySelector('button[type="submit"]');
                const textarea = form.querySelector('textarea');

                if (!textarea.value.trim()) {
                    e.preventDefault();
                    alert('Por favor escribe una respuesta');
                    textarea.focus();
                    return;
                }

                if (submitBtn) {
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
                    submitBtn.disabled = true;
                }
            }
        });

        console.log('🎉 Sistema de comentarios responsive cargado');
    </script>
</body>
</html>

<?php
if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['DOCUMENT_ROOT'], 'htdocs') !== false) {
  require_once $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php';
} else {
  require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';
}include 'session.php';

if ($_SESSION['rol'] !== 3 && $_SESSION['rol'] !== 5) {
    header('Location:' . BASE_URL . '/includes/exit.php?motivo=acceso-denegado');
    exit;
}

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

// Obtener datos del tema
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

// Obtener respuestas con estructura jerárquica
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" />

    <style>
        /* ===== FORUM RESPONSES CSS VARIABLES ===== */
        :root {
            --forum-responses-primary: #0E4A86;
            --forum-responses-primary-hover: #0d4077;
            --forum-responses-primary-light: #e8f1ff;
            --forum-responses-primary-dark: #0a3660;
            --forum-responses-secondary: #6c757d;
            --forum-responses-success: #10b981;
            --forum-responses-danger: #ef4444;
            --forum-responses-warning: #f59e0b;
            --forum-responses-background: #f8fafc;
            --forum-responses-surface: #ffffff;
            --forum-responses-border: #e2e8f0;
            --forum-responses-text-primary: #1e293b;
            --forum-responses-text-secondary: #64748b;
            --forum-responses-text-muted: #94a3b8;
            --forum-responses-shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --forum-responses-shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --forum-responses-shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --forum-responses-radius-sm: 0.375rem;
            --forum-responses-radius-md: 0.5rem;
            --forum-responses-radius-lg: 0.75rem;
            --forum-responses-radius-xl: 1rem;
        }

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

        /* ===== FORUM RESPONSES RESET & BASE STYLES ===== */
        .forum-responses-body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: var(--forum-responses-background);
            color: var(--forum-responses-text-primary);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            padding-top: 180px;
        }

        /* ===== FORUM RESPONSES LAYOUT ===== */
        .forum-responses-main-content {
            margin-left: 280px;
            transition: margin-left 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 2rem;
            min-height: 100vh;
            margin-top: -40px;
        }

        body.sidebar-collapsed .forum-responses-main-content {
            margin-left: 100px;
        }

        .forum-responses-container {
            max-width: 900px;
            margin: 0 auto;
        }

        /* ===== FORUM RESPONSES NAVIGATION ===== */
        .forum-responses-breadcrumb-nav {
            background: var(--forum-responses-surface);
            border: 1px solid var(--forum-responses-border);
            border-radius: var(--forum-responses-radius-lg);
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--forum-responses-shadow-sm);
        }

        .forum-responses-back-link {
            color: var(--forum-responses-primary);
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }

        .forum-responses-back-link:hover {
            color: var(--forum-responses-primary-hover);
            transform: translateX(-2px);
        }

        /* ===== FORUM RESPONSES TOPIC HEADER ===== */
        .forum-responses-topic-header {
            background: var(--forum-responses-surface);
            border: 1px solid var(--forum-responses-border);
            border-radius: var(--forum-responses-radius-lg);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--forum-responses-shadow-sm);
            position: relative;
            overflow: hidden;
        }

        .forum-responses-topic-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--forum-responses-primary), var(--forum-responses-primary-hover));
        }

        .forum-responses-topic-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--forum-responses-text-primary);
            margin: 0 0 1rem 0;
            line-height: 1.2;
        }

        .forum-responses-topic-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .forum-responses-author-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--forum-responses-primary-light);
            color: var(--forum-responses-primary);
            padding: 0.5rem 1rem;
            border-radius: var(--forum-responses-radius-md);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .forum-responses-author-avatar {
            width: 2rem;
            height: 2rem;
            background: var(--forum-responses-primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.75rem;
        }

        .forum-responses-topic-date {
            color: var(--forum-responses-text-muted);
            font-size: 0.875rem;
        }

        .forum-responses-topic-content {
            font-size: 1.125rem;
            line-height: 1.7;
            color: var(--forum-responses-text-primary);
        }

        /* ===== FORUM RESPONSES COMMENT FORM ===== */
        .forum-responses-comment-form {
            background: var(--forum-responses-surface);
            border: 1px solid var(--forum-responses-border);
            border-radius: var(--forum-responses-radius-lg);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--forum-responses-shadow-sm);
            position: relative;
            overflow: hidden;
        }

        .forum-responses-comment-form::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--forum-responses-success), var(--forum-responses-primary));
        }

        .forum-responses-form-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .forum-responses-form-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--forum-responses-text-primary);
            margin: 0;
        }

        .forum-responses-form-group {
            margin-bottom: 1.5rem;
        }

        .forum-responses-form-label {
            display: block;
            font-weight: 500;
            color: var(--forum-responses-text-primary);
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .forum-responses-form-control {
            width: 100%;
            padding: 1rem;
            border: 2px solid var(--forum-responses-border);
            border-radius: var(--forum-responses-radius-md);
            font-size: 1rem;
            line-height: 1.5;
            transition: all 0.2s ease;
            resize: vertical;
            min-height: 120px;
            font-family: inherit;
            background: var(--forum-responses-surface);
        }

        .forum-responses-form-control:focus {
            outline: none;
            border-color: var(--forum-responses-primary);
            box-shadow: 0 0 0 3px rgb(14 74 134 / 0.1);
            background: #fafbfc;
        }

        .forum-responses-form-control::placeholder {
            color: var(--forum-responses-text-muted);
        }

        .forum-responses-form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        /* ===== FORUM RESPONSES BUTTONS ===== */
        .forum-responses-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--forum-responses-radius-md);
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            line-height: 1;
            position: relative;
            overflow: hidden;
        }

        .forum-responses-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .forum-responses-btn:hover::before {
            left: 100%;
        }

        .forum-responses-btn-primary {
            background: var(--forum-responses-primary);
            color: white;
            box-shadow: var(--forum-responses-shadow-sm);
        }

        .forum-responses-btn-primary:hover {
            background: var(--forum-responses-primary-hover);
            transform: translateY(-1px);
            box-shadow: var(--forum-responses-shadow-md);
        }

        .forum-responses-btn-secondary {
            background: var(--forum-responses-surface);
            color: var(--forum-responses-text-secondary);
            border: 1px solid var(--forum-responses-border);
        }

        .forum-responses-btn-secondary:hover {
            background: var(--forum-responses-background);
            color: var(--forum-responses-text-primary);
        }

        .forum-responses-btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }

        /* ===== FORUM RESPONSES COMMENTS SECTION ===== */
        .forum-responses-comments-section {
            background: var(--forum-responses-surface);
            border: 1px solid var(--forum-responses-border);
            border-radius: var(--forum-responses-radius-lg);
            overflow: hidden;
            box-shadow: var(--forum-responses-shadow-sm);
        }

        .forum-responses-comments-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--forum-responses-border);
            background: linear-gradient(135deg, var(--forum-responses-background), #f1f5f9);
        }

        .forum-responses-comments-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--forum-responses-text-primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .forum-responses-comments-count {
            background: var(--forum-responses-primary);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: var(--forum-responses-radius-sm);
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* ===== FORUM RESPONSES COMMENT ITEMS ===== */
        .forum-responses-comment-item {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--forum-responses-border);
            transition: all 0.3s ease;
            opacity: 0;
            transform: translateY(20px);
        }

        .forum-responses-comment-item.forum-responses-visible {
            opacity: 1;
            transform: translateY(0);
        }

        .forum-responses-comment-item:last-child {
            border-bottom: none;
        }

        .forum-responses-comment-item:hover {
            background: linear-gradient(135deg, rgba(14, 74, 134, 0.02), rgba(14, 74, 134, 0.01));
        }

        .forum-responses-comment-header {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .forum-responses-comment-author {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            flex: 1;
        }

        .forum-responses-comment-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }

        .forum-responses-comment-author-info {
            flex: 1;
            min-width: 0;
        }

        .forum-responses-comment-author-info h6 {
            margin: 0 0 0.25rem 0;
            font-weight: 600;
            color: var(--forum-responses-text-primary);
            font-size: 0.875rem;
        }

        .forum-responses-comment-role {
            color: var(--forum-responses-text-muted);
            font-size: 0.75rem;
            margin: 0 0 0.5rem 0;
        }

        .forum-responses-comment-date {
            color: var(--forum-responses-text-muted);
            font-size: 0.75rem;
            white-space: nowrap;
        }

        .forum-responses-comment-content {
            font-size: 1rem;
            line-height: 1.6;
            color: var(--forum-responses-text-primary);
            margin-left: 3.5rem;
        }

        /* ===== FORUM RESPONSES COMMENT ACTIONS ===== */
        .forum-responses-comment-actions {
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid var(--forum-responses-border);
            margin-left: 3.5rem;
        }

        .forum-responses-btn-reply {
            background: none !important;
            border: none !important;
            color: var(--forum-responses-primary) !important;
            font-size: 0.875rem !important;
            padding: 0.25rem 0.5rem !important;
            border-radius: 4px !important;
            transition: all 0.2s ease !important;
            cursor: pointer !important;
        }

        .forum-responses-btn-reply:hover {
            background: rgba(14, 74, 134, 0.1) !important;
            color: var(--forum-responses-primary-hover) !important;
        }

        /* ===== FORUM RESPONSES REPLY FORMS ===== */
        .forum-responses-reply-form {
            display: none !important;
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            margin-left: 3.5rem;
        }

        .forum-responses-reply-form.forum-responses-active {
            display: block !important;
            animation: forum-responses-slideDown 0.3s ease;
        }

        .forum-responses-reply-to-info {
            background: #e3f2fd;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            color: #1976d2;
            border-left: 3px solid #2196f3;
        }

        /* ===== FORUM RESPONSES NESTED REPLIES ===== */
        .forum-responses-replies-toggle-container {
            margin-top: 1rem;
            margin-left: 3.5rem;
        }

        .forum-responses-btn-toggle-replies {
            background: var(--forum-responses-surface) !important;
            border: 1px solid var(--forum-responses-border) !important;
            color: var(--forum-responses-primary) !important;
            font-size: 0.875rem !important;
            padding: 0.5rem 1rem !important;
            border-radius: 20px !important;
            transition: all 0.3s ease !important;
            cursor: pointer !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
        }

        .forum-responses-btn-toggle-replies:hover {
            background: var(--forum-responses-primary-light) !important;
            border-color: var(--forum-responses-primary) !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 2px 8px rgba(14, 74, 134, 0.15) !important;
        }

        .forum-responses-btn-toggle-replies.forum-responses-expanded {
            background: var(--forum-responses-primary) !important;
            color: white !important;
            border-color: var(--forum-responses-primary) !important;
        }

        .forum-responses-btn-toggle-replies.forum-responses-expanded:hover {
            background: var(--forum-responses-primary-hover) !important;
        }

        .forum-responses-btn-toggle-replies.forum-responses-expanded .forum-responses-toggle-icon {
            transform: rotate(180deg) !important;
        }

        .forum-responses-comment-replies {
            margin-left: 3rem;
            margin-top: 1rem;
            border-left: 3px solid var(--forum-responses-primary);
            padding-left: 1rem;
            background: rgba(14, 74, 134, 0.02);
            border-radius: 0 8px 8px 0;
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.4s ease;
        }

        .forum-responses-comment-replies.forum-responses-visible {
            opacity: 1;
            transform: translateY(0);
        }

        .forum-responses-nested-comment {
            background: rgba(255, 255, 255, 0.8);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(14, 74, 134, 0.1);
            transition: all 0.2s ease;
        }

        .forum-responses-nested-comment:hover {
            background: rgba(255, 255, 255, 0.95);
            border-color: rgba(14, 74, 134, 0.2);
            transform: translateX(5px);
        }

        .forum-responses-nested-comment .forum-responses-comment-avatar {
            width: 36px;
            height: 36px;
        }

        .forum-responses-nested-comment .forum-responses-comment-content {
            margin-left: 0;
            margin-top: 0.5rem;
        }

        /* ===== FORUM RESPONSES EMPTY STATES ===== */
        .forum-responses-empty-state {
            padding: 4rem 2rem;
            text-align: center;
            color: var(--forum-responses-text-muted);
        }

        .forum-responses-empty-state-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .forum-responses-empty-state h4 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--forum-responses-text-secondary);
        }

        .forum-responses-empty-state p {
            font-size: 0.875rem;
            margin: 0;
        }

        /* ===== FORUM RESPONSES LOAD MORE ===== */
        .forum-responses-load-more-container {
            padding: 2rem;
            text-align: center;
            border-top: 1px solid var(--forum-responses-border);
            background: linear-gradient(135deg, var(--forum-responses-background), #f1f5f9);
        }

        .forum-responses-btn-load-more {
            background: linear-gradient(135deg, var(--forum-responses-primary), var(--forum-responses-primary-hover));
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: var(--forum-responses-radius-lg);
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: var(--forum-responses-shadow-md);
            position: relative;
            overflow: hidden;
        }

        .forum-responses-btn-load-more:hover {
            transform: translateY(-2px);
            box-shadow: var(--forum-responses-shadow-lg);
        }

        .forum-responses-btn-load-more:active {
            transform: translateY(0);
        }

        .forum-responses-btn-load-more.forum-responses-loading {
            pointer-events: none;
            opacity: 0.7;
        }

        .forum-responses-load-more-info {
            margin-top: 1rem;
            font-size: 0.875rem;
            color: var(--forum-responses-text-muted);
        }

        /* ===== FORUM RESPONSES ANIMATIONS ===== */
        @keyframes forum-responses-fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes forum-responses-slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes forum-responses-pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }

        .forum-responses-animate-in {
            animation: forum-responses-fadeInUp 0.6s ease-out;
        }

        .forum-responses-pulse {
            animation: forum-responses-pulse 1.5s ease-in-out infinite;
        }

        /* ===== FORUM RESPONSES SCROLLBAR ===== */
        .forum-responses-container::-webkit-scrollbar {
            width: 8px;
        }

        .forum-responses-container::-webkit-scrollbar-track {
            background: var(--forum-responses-background);
        }

        .forum-responses-container::-webkit-scrollbar-thumb {
            background: var(--forum-responses-border);
            border-radius: 4px;
        }

        .forum-responses-container::-webkit-scrollbar-thumb:hover {
            background: var(--forum-responses-text-muted);
        }

        /* ===== FORUM RESPONSES RESPONSIVE DESIGN ===== */
        @media (max-width: 1200px) {
            .forum-responses-main-content {
                margin-left: 200px;
                padding: 1.5rem;
            }

            .forum-responses-container {
                max-width: 100%;
            }
        }

        @media (max-width: 1024px) {
            .forum-responses-main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .forum-responses-topic-header,
            .forum-responses-comment-form,
            .forum-responses-comments-section {
                border-radius: var(--forum-responses-radius-md);
            }

            .forum-responses-topic-title {
                font-size: 1.75rem;
            }

            .forum-responses-comment-item {
                padding: 1.25rem 1.5rem;
            }

            .forum-responses-comments-header {
                padding: 1.25rem 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .forum-responses-body {
                padding-top: 120px;
            }

            .forum-responses-main-content {
                padding: 0.75rem;
                margin-top: -20px;
            }

            .forum-responses-breadcrumb-nav {
                padding: 0.75rem 1rem;
                margin-bottom: 1rem;
            }

            .forum-responses-topic-header {
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }

            .forum-responses-topic-title {
                font-size: 1.5rem;
                line-height: 1.3;
            }

            .forum-responses-topic-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }

            .forum-responses-topic-content {
                font-size: 1rem;
            }

            .forum-responses-comment-form {
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }

            .forum-responses-form-title {
                font-size: 1.125rem;
            }

            .forum-responses-form-control {
                padding: 0.875rem;
                min-height: 100px;
            }

            .forum-responses-form-actions {
                flex-direction: column;
                gap: 0.75rem;
            }

            .forum-responses-btn {
                justify-content: center;
                width: 100%;
            }

            .forum-responses-comment-item {
                padding: 1rem 1.25rem;
            }

            .forum-responses-comments-header {
                padding: 1rem 1.25rem;
            }

            .forum-responses-comment-header {
                flex-direction: column;
                gap: 0.75rem;
                align-items: flex-start;
            }

            .forum-responses-comment-author {
                width: 100%;
            }

            .forum-responses-comment-author-info {
                display: flex;
                flex-direction: column;
                gap: 0.25rem;
            }

            .forum-responses-comment-date {
                align-self: flex-start;
                margin-top: 0.25rem;
            }

            .forum-responses-comment-content {
                margin-left: 0;
                margin-top: 1rem;
            }

            .forum-responses-comment-actions {
                margin-left: 0;
            }

            .forum-responses-reply-form {
                margin-left: 0;
            }

            .forum-responses-replies-toggle-container {
                margin-left: 0;
            }

            .forum-responses-comment-replies {
                margin-left: 1rem;
                padding-left: 0.75rem;
            }

            .forum-responses-load-more-container {
                padding: 1.5rem 1rem;
            }

            .forum-responses-btn-load-more {
                padding: 0.875rem 1.5rem;
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .forum-responses-main-content {
                padding: 0.5rem;
            }

            .forum-responses-breadcrumb-nav {
                padding: 0.75rem;
                margin-bottom: 0.75rem;
            }

            .forum-responses-back-link {
                font-size: 0.875rem;
            }

            .forum-responses-topic-header {
                padding: 1rem;
                margin-bottom: 1rem;
            }

            .forum-responses-topic-title {
                font-size: 1.25rem;
            }

            .forum-responses-author-badge {
                padding: 0.375rem 0.75rem;
                font-size: 0.8rem;
            }

            .forum-responses-author-avatar,
            .forum-responses-comment-avatar {
                width: 40px;
                height: 40px;
            }

            .forum-responses-nested-comment .forum-responses-comment-avatar {
                width: 32px;
                height: 32px;
            }

            .forum-responses-topic-date {
                font-size: 0.8rem;
            }

            .forum-responses-comment-form {
                padding: 1rem;
                margin-bottom: 1rem;
            }

            .forum-responses-form-header {
                gap: 0.5rem;
                margin-bottom: 1rem;
            }

            .forum-responses-form-title {
                font-size: 1rem;
            }

            .forum-responses-form-control {
                padding: 0.75rem;
                font-size: 0.9rem;
                min-height: 80px;
            }

            .forum-responses-comment-item {
                padding: 0.875rem 1rem;
            }

            .forum-responses-comments-header {
                padding: 0.875rem 1rem;
            }

            .forum-responses-comments-title {
                font-size: 1rem;
            }

            .forum-responses-comment-author-info h6 {
                font-size: 0.8rem;
            }

            .forum-responses-comment-role {
                font-size: 0.7rem;
            }

            .forum-responses-comment-date {
                font-size: 0.7rem;
            }

            .forum-responses-comment-content {
                font-size: 0.9rem;
                line-height: 1.5;
            }

            .forum-responses-btn-reply {
                font-size: 0.8rem !important;
                padding: 0.25rem 0.375rem !important;
            }

            .forum-responses-btn-toggle-replies {
                font-size: 0.8rem !important;
                padding: 0.375rem 0.75rem !important;
            }

            .forum-responses-reply-form {
                padding: 0.75rem;
            }

            .forum-responses-reply-to-info {
                padding: 0.375rem 0.75rem;
                font-size: 0.8rem;
            }

            .forum-responses-nested-comment {
                padding: 0.75rem;
            }

            .forum-responses-comment-replies {
                margin-left: 0.5rem;
                padding-left: 0.5rem;
            }

            .forum-responses-empty-state {
                padding: 2rem 1rem;
            }

            .forum-responses-empty-state-icon {
                font-size: 2rem;
            }

            .forum-responses-empty-state h4 {
                font-size: 1rem;
            }

            .forum-responses-empty-state p {
                font-size: 0.8rem;
            }

            .forum-responses-load-more-container {
                padding: 1rem 0.75rem;
            }

            .forum-responses-btn-load-more {
                padding: 0.75rem 1rem;
                font-size: 0.8rem;
            }

            .forum-responses-load-more-info {
                font-size: 0.8rem;
            }
        }

        @media (max-width: 360px) {
            .forum-responses-topic-title {
                font-size: 1.125rem;
            }

            .forum-responses-author-avatar,
            .forum-responses-comment-avatar {
                width: 36px;
                height: 36px;
            }

            .forum-responses-nested-comment .forum-responses-comment-avatar {
                width: 28px;
                height: 28px;
            }

            .forum-responses-comment-content {
                font-size: 0.85rem;
            }

            .forum-responses-form-control {
                font-size: 0.85rem;
            }
        }

        @media (max-height: 600px) and (orientation: landscape) {
            .forum-responses-body {
                padding-top: 80px;
            }

            .forum-responses-main-content {
                margin-top: -10px;
            }

            .forum-responses-topic-header {
                padding: 1rem;
            }

            .forum-responses-comment-form {
                padding: 1rem;
            }

            .forum-responses-form-control {
                min-height: 60px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        @media (prefers-contrast: high) {
            .forum-responses-comment-item {
                border: 2px solid var(--forum-responses-border);
            }

            .forum-responses-btn {
                border: 2px solid currentColor;
            }
        }

        @media (pointer: coarse) {

            .forum-responses-btn-reply,
            .forum-responses-btn-toggle-replies {
                min-height: 44px !important;
                min-width: 44px !important;
            }

            .forum-responses-form-control {
                min-height: 44px;
            }
        }
    </style>
</head>

<body class="sidebar-collapsed forum-responses-body">
    <?php include 'design/header.php'; ?>
    <?php include 'design/sidebar.php'; ?>

    <div class="forum-responses-main-content">
        <div class="forum-responses-container">
            <!-- Navigation -->
            <nav class="forum-responses-breadcrumb-nav">
                <a href="temas_foro.php?id_foro=<?= $tema['id_foro'] ?>" class="forum-responses-back-link">
                    <i class="fas fa-arrow-left"></i>
                    Volver a Temas del Foro
                </a>
            </nav>

            <!-- Topic Header -->
            <article class="forum-responses-topic-header forum-responses-animate-in">
                <h1 class="forum-responses-topic-title"><?= htmlspecialchars($tema['titulo']) ?></h1>
                <div class="forum-responses-topic-meta">
                    <div class="forum-responses-author-badge">
                        <div class="forum-responses-author-avatar">
                            <img src="<?= BASE_URL ?>/<?= empty($tema['avatar']) ? 'uploads/avatar/user.webp' : htmlspecialchars($tema['avatar']) ?>" alt="avatar"
                                class="rounded-circle me-3" style="width: 48px; height: 48px; object-fit: cover;">
                        </div>
                        <span><?= htmlspecialchars($tema['nombres'] . ' ' . $tema['apellidos']) ?></span>
                    </div>
                    <time class="forum-responses-topic-date" datetime="<?= $tema['fecha_creacion'] ?>">
                        <i class="fas fa-clock"></i>
                        <?= date('d/m/Y \a \l\a\s H:i', strtotime($tema['fecha_creacion'])) ?>
                    </time>
                </div>
                <div class="forum-responses-topic-content">
                    <?= nl2br(htmlspecialchars($tema['descripcion'])) ?>
                </div>
            </article>

            <!-- Comment Form Principal -->
            <section class="forum-responses-comment-form forum-responses-animate-in">
                <div class="forum-responses-form-header">
                    <i class="fas fa-comment-dots" style="color: var(--forum-responses-primary);"></i>
                    <h2 class="forum-responses-form-title">Agregar Comentario</h2>
                </div>
                <form action="procesar_respuesta.php" method="POST" id="forum-responses-commentForm">
                    <input type="hidden" name="id_tema_foro" value="<?= $id_tema ?>">
                    <input type="hidden" name="id_respuesta_padre" value="">
                    <div class="forum-responses-form-group">
                        <label for="forum-responses-descripcion" class="forum-responses-form-label">
                            Tu respuesta <span style="color: var(--forum-responses-danger);">*</span>
                        </label>
                        <textarea
                            class="forum-responses-form-control"
                            id="forum-responses-descripcion"
                            name="descripcion"
                            placeholder="Comparte tu opinión, experiencia o pregunta sobre este tema..."
                            required
                            aria-describedby="forum-responses-descripcionHelp"></textarea>
                        <small id="forum-responses-descripcionHelp" class="form-text" style="color: var(--forum-responses-text-muted); font-size: 0.75rem; margin-top: 0.5rem; display: block;">
                            Sé respetuoso y constructivo en tu comentario.
                        </small>
                    </div>
                    <div class="forum-responses-form-actions">
                        <button type="button" class="forum-responses-btn forum-responses-btn-secondary" onclick="forumResponsesClearForm()">
                            <i class="fas fa-times"></i>
                            Limpiar texto
                        </button>
                        <button type="submit" class="forum-responses-btn forum-responses-btn-primary" id="forum-responses-submitBtn">
                            <i class="fas fa-paper-plane"></i>
                            Publicar Comentario
                        </button>
                    </div>
                </form>
            </section>

            <!-- Comments Section con respuestas anidadas -->
            <section class="forum-responses-comments-section forum-responses-animate-in">
                <div class="forum-responses-comments-header">
                    <h3 class="forum-responses-comments-title">
                        <i class="fas fa-comments"></i>
                        Comentarios
                        <span class="forum-responses-comments-count"><?= count($respuestas_principales) ?></span>
                    </h3>
                </div>

                <?php if (empty($respuestas_principales)): ?>
                    <div class="forum-responses-empty-state">
                        <div class="forum-responses-empty-state-icon">
                            <i class="fas fa-comment-slash"></i>
                        </div>
                        <h4>No hay comentarios aún</h4>
                        <p>Sé el primero en participar en esta discusión</p>
                    </div>
                <?php else: ?>
                    <div id="forum-responses-commentsContainer">
                        <?php foreach ($respuestas_principales as $index => $respuesta): ?>
                            <article class="forum-responses-comment-item" data-index="<?= $index ?>" data-comment-id="<?= $respuesta['id_respuesta_foro'] ?>" style="display: none;">
                                <div class="forum-responses-comment-header">
                                    <div class="forum-responses-comment-author">
                                        <img src="<?= BASE_URL ?>/<?= empty($respuesta['avatar']) ? 'uploads/avatar/user.webp' : htmlspecialchars($respuesta['avatar']) ?>"
                                            alt="avatar" class="forum-responses-comment-avatar">
                                        <div class="forum-responses-comment-author-info">
                                            <h6><?= htmlspecialchars($respuesta['nombres'] . ' ' . $respuesta['apellidos']) ?></h6>
                                            <p class="forum-responses-comment-role"><?= htmlspecialchars($respuesta['rol']) ?></p>
                                        </div>
                                    </div>
                                    <time class="forum-responses-comment-date" datetime="<?= $respuesta['fecha_respuesta'] ?>">
                                        <?= date('d/m/Y \a \l\a\s H:i', strtotime($respuesta['fecha_respuesta'])) ?>
                                    </time>
                                </div>
                                <div class="forum-responses-comment-content">
                                    <?= nl2br(htmlspecialchars($respuesta['descripcion'])) ?>
                                </div>

                                <!-- Acciones del comentario -->
                                <div class="forum-responses-comment-actions">
                                    <button class="forum-responses-btn-reply" data-comment-id="<?= $respuesta['id_respuesta_foro'] ?>" data-author-name="<?= htmlspecialchars($respuesta['nombres'] . ' ' . $respuesta['apellidos']) ?>">
                                        <i class="fas fa-reply"></i>
                                        Responder
                                    </button>
                                </div>

                                <!-- Formulario de respuesta -->
                                <div class="forum-responses-reply-form" id="forum-responses-replyForm-<?= $respuesta['id_respuesta_foro'] ?>">
                                    <div class="forum-responses-reply-to-info">
                                        <i class="fas fa-reply"></i>
                                        Respondiendo a <strong><?= htmlspecialchars($respuesta['nombres'] . ' ' . $respuesta['apellidos']) ?></strong>
                                    </div>
                                    <form action="procesar_respuesta.php" method="POST" class="forum-responses-reply-form-inner">
                                        <input type="hidden" name="id_tema_foro" value="<?= $id_tema ?>">
                                        <input type="hidden" name="id_respuesta_padre" value="<?= $respuesta['id_respuesta_foro'] ?>">
                                        <div class="forum-responses-form-group">
                                            <textarea
                                                class="forum-responses-form-control"
                                                name="descripcion"
                                                placeholder="Escribe tu respuesta..."
                                                required
                                                rows="3"></textarea>
                                        </div>
                                        <div class="forum-responses-form-actions">
                                            <button type="button" class="forum-responses-btn forum-responses-btn-secondary forum-responses-btn-sm" onclick="forumResponsesCancelReply(<?= $respuesta['id_respuesta_foro'] ?>)">
                                                <i class="fas fa-times"></i>
                                                Cancelar
                                            </button>
                                            <button type="submit" class="forum-responses-btn forum-responses-btn-primary forum-responses-btn-sm">
                                                <i class="fas fa-paper-plane"></i>
                                                Responder
                                            </button>
                                        </div>
                                    </form>
                                </div>

                                <?php if (isset($respuestas_hijas[$respuesta['id_respuesta_foro']])): ?>
                                    <!-- Botón para mostrar/ocultar respuestas -->
                                    <div class="forum-responses-replies-toggle-container">
                                        <button class="forum-responses-btn-toggle-replies" data-comment-id="<?= $respuesta['id_respuesta_foro'] ?>" data-replies-count="<?= count($respuestas_hijas[$respuesta['id_respuesta_foro']]) ?>">
                                            <i class="fas fa-chevron-down forum-responses-toggle-icon"></i>
                                            <span class="forum-responses-toggle-text">
                                                Mostrar <?= count($respuestas_hijas[$respuesta['id_respuesta_foro']]) ?>
                                                <?= count($respuestas_hijas[$respuesta['id_respuesta_foro']]) === 1 ? 'respuesta' : 'respuestas' ?>
                                            </span>
                                        </button>
                                    </div>

                                    <!-- Contenedor de respuestas (inicialmente oculto) -->
                                    <div class="forum-responses-comment-replies" id="forum-responses-replies-<?= $respuesta['id_respuesta_foro'] ?>" style="display: none;">
                                        <?php foreach ($respuestas_hijas[$respuesta['id_respuesta_foro']] as $respuesta_hija): ?>
                                            <div class="forum-responses-nested-comment">
                                                <div class="forum-responses-comment-header">
                                                    <div class="forum-responses-comment-author">
                                                        <img src="<?= BASE_URL ?>/<?= empty($respuesta_hija['avatar']) ? 'uploads/avatar/user.webp' : htmlspecialchars($respuesta_hija['avatar']) ?>"
                                                            alt="avatar" class="forum-responses-comment-avatar">
                                                        <div class="forum-responses-comment-author-info">
                                                            <h6><?= htmlspecialchars($respuesta_hija['nombres'] . ' ' . $respuesta_hija['apellidos']) ?></h6>
                                                            <small class="forum-responses-comment-role"><?= htmlspecialchars($respuesta_hija['rol']) ?></small>
                                                        </div>
                                                    </div>
                                                    <time class="forum-responses-comment-date" datetime="<?= $respuesta_hija['fecha_respuesta'] ?>">
                                                        <?= date('d/m/Y H:i', strtotime($respuesta_hija['fecha_respuesta'])) ?>
                                                    </time>
                                                </div>
                                                <div class="forum-responses-comment-content">
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
                        <div class="forum-responses-load-more-container">
                            <button class="forum-responses-btn-load-more" id="forum-responses-loadMoreBtn">
                                <i class="fas fa-chevron-down"></i>
                                Mostrar más comentarios
                            </button>
                            <div class="forum-responses-load-more-info">
                                <span id="forum-responses-loadMoreInfo">Mostrando <span id="forum-responses-currentCount">0</span> de <?= count($respuestas_principales) ?> comentarios</span>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </div>
    </div>

    <script>
        // ===== FORUM RESPONSES SISTEMA DE COMENTARIOS CON TOGGLE DE RESPUESTAS =====
        // 1. FUNCIONES GLOBALES
        window.forumResponsesToggleReplyForm = function(commentId, authorName) {
            console.log('🔄 Función llamada:', commentId, authorName);
            // Cerrar otros formularios
            document.querySelectorAll('.forum-responses-reply-form').forEach(form => {
                if (form.id !== `forum-responses-replyForm-${commentId}`) {
                    form.style.display = 'none';
                    form.classList.remove('forum-responses-active');
                }
            });
            // Mostrar/ocultar formulario actual
            const replyForm = document.getElementById(`forum-responses-replyForm-${commentId}`);
            if (!replyForm) {
                console.error('❌ Formulario no encontrado:', commentId);
                return;
            }
            if (replyForm.style.display === 'none' || !replyForm.style.display) {
                replyForm.style.display = 'block';
                replyForm.classList.add('forum-responses-active');
                // Focus en textarea
                const textarea = replyForm.querySelector('textarea');
                if (textarea) {
                    setTimeout(() => textarea.focus(), 100);
                }
                console.log('✅ Formulario mostrado');
            } else {
                replyForm.style.display = 'none';
                replyForm.classList.remove('forum-responses-active');
                console.log('✅ Formulario ocultado');
            }
        };

        window.forumResponsesCancelReply = function(commentId) {
            const replyForm = document.getElementById(`forum-responses-replyForm-${commentId}`);
            if (replyForm) {
                replyForm.style.display = 'none';
                replyForm.classList.remove('forum-responses-active');
                const textarea = replyForm.querySelector('textarea');
                if (textarea) textarea.value = '';
            }
        };

        // 2. FUNCIÓN PARA TOGGLE DE RESPUESTAS
        window.forumResponsesToggleReplies = function(commentId, repliesCount) {
            console.log('🔄 Toggle respuestas para comentario:', commentId);
            const repliesContainer = document.getElementById(`forum-responses-replies-${commentId}`);
            const toggleBtn = document.querySelector(`[data-comment-id="${commentId}"].forum-responses-btn-toggle-replies`);
            if (!repliesContainer || !toggleBtn) {
                console.error('❌ Elementos no encontrados');
                return;
            }
            const icon = toggleBtn.querySelector('.forum-responses-toggle-icon');
            const text = toggleBtn.querySelector('.forum-responses-toggle-text');
            const isVisible = repliesContainer.style.display !== 'none';
            if (isVisible) {
                // Ocultar respuestas
                repliesContainer.style.display = 'none';
                repliesContainer.classList.remove('forum-responses-visible');
                icon.className = 'fas fa-chevron-down forum-responses-toggle-icon';
                text.textContent = `Mostrar ${repliesCount} ${repliesCount === 1 ? 'respuesta' : 'respuestas'}`;
                toggleBtn.classList.remove('forum-responses-expanded');
                console.log('✅ Respuestas ocultadas');
            } else {
                // Mostrar respuestas
                repliesContainer.style.display = 'block';
                setTimeout(() => {
                    repliesContainer.classList.add('forum-responses-visible');
                }, 10);
                icon.className = 'fas fa-chevron-up forum-responses-toggle-icon';
                text.textContent = `Ocultar ${repliesCount === 1 ? 'respuesta' : 'respuestas'}`;
                toggleBtn.classList.add('forum-responses-expanded');
                console.log('✅ Respuestas mostradas');
            }
        };

        // 3. EVENT LISTENERS PARA BOTONES
        function forumResponsesBindAllButtons() {
            console.log('🔗 Vinculando todos los event listeners...');
            // Botones de responder
            document.querySelectorAll('.forum-responses-btn-reply').forEach(btn => {
                btn.replaceWith(btn.cloneNode(true));
            });
            document.querySelectorAll('.forum-responses-btn-reply').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const commentId = this.getAttribute('data-comment-id');
                    const authorName = this.getAttribute('data-author-name');
                    console.log('🖱️ Click en botón responder:', commentId);
                    forumResponsesToggleReplyForm(commentId, authorName);
                });
            });
            // Botones de cancelar
            document.querySelectorAll('[data-cancel-reply]').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const commentId = this.getAttribute('data-cancel-reply');
                    forumResponsesCancelReply(commentId);
                });
            });
            // Botones de toggle respuestas
            document.querySelectorAll('.forum-responses-btn-toggle-replies').forEach(btn => {
                btn.replaceWith(btn.cloneNode(true));
            });
            document.querySelectorAll('.forum-responses-btn-toggle-replies').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const commentId = this.getAttribute('data-comment-id');
                    const repliesCount = parseInt(this.getAttribute('data-replies-count'));
                    console.log('🖱️ Click en toggle respuestas:', commentId);
                    forumResponsesToggleReplies(commentId, repliesCount);
                });
            });
            console.log('✅ Todos los event listeners vinculados');
        }

        // 4. PAGINACIÓN CON BINDING AUTOMÁTICO
        class ForumResponsesCommentPagination {
            constructor() {
                this.commentsPerPage = 5;
                this.currentPage = 0;
                this.totalComments = <?= count($respuestas_principales) ?>;
                this.comments = document.querySelectorAll('.forum-responses-comment-item');
                this.loadMoreBtn = document.getElementById('forum-responses-loadMoreBtn');
                this.currentCountSpan = document.getElementById('forum-responses-currentCount');
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
                        comment.classList.add('forum-responses-visible');
                    }, (i - start) * 100);
                }
                // Re-vincular botones después de mostrar comentarios
                setTimeout(() => {
                    forumResponsesBindAllButtons();
                }, 500);
            }
            loadMore() {
                const nextStart = (this.currentPage + 1) * this.commentsPerPage;
                const nextEnd = nextStart + this.commentsPerPage;
                if (this.loadMoreBtn) {
                    this.loadMoreBtn.classList.add('forum-responses-loading');
                    this.loadMoreBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando...';
                }
                setTimeout(() => {
                    this.showComments(nextStart, nextEnd);
                    this.currentPage++;
                    this.updateUI();
                    if (this.loadMoreBtn) {
                        this.loadMoreBtn.classList.remove('forum-responses-loading');
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
                    const loadMoreInfo = document.querySelector('.forum-responses-load-more-info');
                    if (loadMoreInfo) {
                        loadMoreInfo.innerHTML = `<span style="color: var(--forum-responses-success);"><i class="fas fa-check"></i> Todos los comentarios mostrados</span>`;
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
        function forumResponsesAutoResize() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        }

        function forumResponsesClearForm() {
            const textarea = document.getElementById('forum-responses-descripcion');
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
            const commentForm = document.getElementById('forum-responses-commentForm');
            const submitBtn = document.getElementById('forum-responses-submitBtn');
            const textarea = document.getElementById('forum-responses-descripcion');

            // Auto-resize textareas
            if (textarea) {
                textarea.addEventListener('input', forumResponsesAutoResize);
            }
            document.querySelectorAll('textarea').forEach(ta => {
                ta.addEventListener('input', forumResponsesAutoResize);
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
                new ForumResponsesCommentPagination();
            }

            // Vincular botones iniciales
            setTimeout(() => {
                forumResponsesBindAllButtons();
            }, 1000);

            console.log('✅ Inicialización completa');
        });

        // 7. MANEJO DE FORMULARIOS DE RESPUESTA
        document.addEventListener('submit', function(e) {
            if (e.target.classList.contains('forum-responses-reply-form-inner')) {
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
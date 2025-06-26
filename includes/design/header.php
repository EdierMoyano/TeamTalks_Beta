<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php';

$usuario_logueado = isset($_SESSION['documento']);
$id_usuario = $_SESSION['documento'] ?? null;
$username = $_SESSION['nombres'] ?? 'Nombres';
$lastname = $_SESSION['apellidos'] ?? 'Apellidos';
$rol = $_SESSION['rol'] ?? null;

$uri = $_SERVER['REQUEST_URI'];

if (strpos($uri, '/instructor/') !== false) {
    $carpeta_inicio = BASE_URL . '/instructor/index.php';
} elseif (strpos($uri, '/transversal/') !== false) {
    $carpeta_inicio = BASE_URL . '/transversal/index.php';
} elseif (strpos($uri, '/aprendiz/') !== false) {
    $carpeta_inicio = BASE_URL . '/aprendiz/tarjeta_formacion/index.php';
} elseif (strpos($uri, '/mod/') !== false) {
    // Para rutas compartidas, usamos el rol para definir a qué dashboard enviar
    if ($rol == 3) { // Instructor gerente
        $carpeta_inicio = BASE_URL . '/instructor/index.php';
    } elseif ($rol == 5) { // Instructor transversal
        $carpeta_inicio = BASE_URL . '/transversal/index.php';
    } else {
        $carpeta_inicio = BASE_URL . '/index.php';
    }
} else {
    $carpeta_inicio = BASE_URL . '/index.php';
}

$logo_href = !$usuario_logueado ? BASE_URL . '/index.php' : $carpeta_inicio;

// Cargar datos del usuario si hay sesión
if ($id_usuario) {
    $datos = $conex->prepare("SELECT * FROM usuarios WHERE id = ?");
    $datos->execute([$id_usuario]);
    $user = $datos->fetch(PDO::FETCH_ASSOC);
} else {
    $user = null;
}

$notificaciones = [];

if ($id_usuario) {
    $stmt = $conex->prepare("
        SELECT
        n.id_notificacion,
        n.mensaje,
        n.url_destino,
        n.leido,
        n.fecha,
        u.nombres AS autor_nombres,
        u.apellidos AS autor_apellidos,
        r.descripcion AS contenido
    FROM notificaciones n
    JOIN usuarios u ON n.id_emisor = u.id
    LEFT JOIN respuesta_foro r ON n.id_respuesta_foro = r.id_respuesta_foro
    WHERE n.id_usuario = ?
    ORDER BY n.fecha DESC
    LIMIT 10
    ");

    $stmt->execute([$id_usuario]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $notificaciones[] = [
            'id_notificacion' => $row['id_notificacion'],
            'mensaje' => $row['mensaje'],
            'url_destino' => $row['url_destino'],
            'leido' => $row['leido'],
            'fecha' => $row['fecha'],
            'autor' => trim(($row['autor_nombres'] ?? 'Usuario') . ' ' . ($row['autor_apellidos'] ?? 'Anónimo')),
            'contenido' => $row['contenido'] ? mb_strimwidth($row['contenido'], 0, 100, '...') : 'Sin contenido disponible'
        ];
    }
}

// Contar notificaciones no leídas
$notificaciones_no_leidas = 0;
if ($id_usuario) {
    $stmt_count = $conex->prepare("SELECT COUNT(*) FROM notificaciones WHERE id_usuario = ? AND leido = 0");
    $stmt_count->execute([$id_usuario]);
    $notificaciones_no_leidas = $stmt_count->fetchColumn();
}



?>



<style>
    body {
        padding-top: 120px;
    }

    :root {
        --notification-primary: #0E4A86;
        --notification-primary-light: #e8f1ff;
        --notification-danger: #dc2626;
        --notification-danger-light: rgba(220, 38, 38, 0.1);
        --notification-bg: #ffffff;
        --notification-surface: #f8fafc;
        --notification-border: #e2e8f0;
        --notification-text: #1e293b;
        --notification-text-secondary: #64748b;
        --notification-text-muted: #94a3b8;
        --notification-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        --notification-shadow-lg: 0 20px 25px -5px rgb(0 0 0 / 0.1);
        --notification-radius: 12px;
        --notification-radius-sm: 8px;
    }

    /* Panel principal */
    .notification-panel {
        width: 420px;
        border: none;
        box-shadow: var(--notification-shadow-lg);
        background: var(--notification-bg);
    }

    /* Header del panel */
    .notification-header {
        background: linear-gradient(135deg, var(--notification-primary), #1e40af);
        color: white;
        padding: 0;
        border: none;
        position: relative;
        overflow: hidden;
    }

    .notification-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 200px;
        height: 200px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        transform: rotate(45deg);
    }

    .header-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.5rem 1.5rem 1rem 1.5rem;
        position: relative;
        z-index: 2;
    }

    .header-title {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .notification-icon {
        width: 2.5rem;
        height: 2.5rem;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.125rem;
        backdrop-filter: blur(10px);
    }

    .offcanvas-title {
        font-size: 1.25rem;
        font-weight: 600;
        margin: 0;
        color: white;
    }

    .btn-close-modern {
        background: rgba(255, 255, 255, 0.1);
        border: none;
        width: 2rem;
        height: 2rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        transition: all 0.2s ease;
        backdrop-filter: blur(10px);
    }

    .btn-close-modern:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: scale(1.1);
    }

    .header-actions {
        padding: 0 1.5rem 1.5rem 1.5rem;
        position: relative;
        z-index: 2;
    }

    /* Botones de acción */
    .btn-mark-all,
    .btn-delete-all {
        padding: 0.5rem 1rem;
        border-radius: var(--notification-radius-sm);
        font-size: 0.875rem;
        font-weight: 500;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        backdrop-filter: blur(10px);
        width: 100%;
        justify-content: center;
        border: 1px solid;
    }

    .btn-mark-all {
        background: rgba(255, 255, 255, 0.15);
        border-color: rgba(255, 255, 255, 0.2);
        color: white;
    }

    .btn-mark-all:hover {
        background: rgba(255, 255, 255, 0.25);
        border-color: rgba(255, 255, 255, 0.3);
        transform: translateY(-1px);
    }

    .btn-delete-all {
        background: rgba(220, 38, 38, 0.56);
        border-color: rgba(220, 38, 38, 0.4);
        color:white;
        margin-top: 0.5rem;
    }

    .btn-delete-all:hover {
        background: rgba(220, 38, 38, 0.3);
        border-color: rgba(220, 38, 38, 0.5);
        color:rgb(255, 255, 255);
        transform: translateY(-1px);
    }

    .btn-mark-all:active,
    .btn-delete-all:active {
        transform: translateY(0) scale(0.98);
    }

    .btn-mark-all:disabled,
    .btn-delete-all:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    /* Body del panel */
    .notification-body {
        padding: 0;
        background: var(--notification-surface);
    }

    /* Estado vacío */
    .empty-state {
        text-align: center;
        padding: 3rem 2rem;
        color: var(--notification-text-muted);
    }

    .empty-icon {
        width: 4rem;
        height: 4rem;
        background: var(--notification-border);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        font-size: 1.5rem;
        color: var(--notification-text-muted);
    }

    .empty-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: var(--notification-text-secondary);
        margin-bottom: 0.5rem;
    }

    .empty-text {
        font-size: 0.875rem;
        margin: 0;
        color: var(--notification-text-muted);
    }

    /* Lista de notificaciones */
    .notifications-list {
        padding: 1rem 0;
    }

    .notification-item {
        display: block;
        padding: 1rem 1.5rem;
        text-decoration: none;
        color: inherit;
        border-bottom: 1px solid var(--notification-border);
        transition: all 0.3s ease;
        position: relative;
        background: var(--notification-bg);
        opacity: 0;
        transform: translateX(-20px);
        animation: slideInNotification 0.4s ease forwards;
    }

    .notification-item:hover {
        background: var(--notification-primary-light);
        text-decoration: none;
        color: inherit;
    }

    .notification-item.unread {
        background: linear-gradient(90deg, var(--notification-primary-light), var(--notification-bg));
        border-left: 4px solid var(--notification-primary);
    }

    .notification-item.unread:hover {
        background: linear-gradient(90deg, #dbeafe, var(--notification-primary-light));
    }

    .notification-content {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        position: relative;
    }

    .notification-avatar {
        width: 2.5rem;
        height: 2.5rem;
        background: linear-gradient(135deg, var(--notification-primary), #1e40af);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.875rem;
        flex-shrink: 0;
        box-shadow: var(--notification-shadow);
    }

    .notification-body {
        flex: 1;
        min-width: 0;
    }

    .notification-message {
        font-size: 0.875rem;
        line-height: 1.4;
        color: var(--notification-text);
        margin-bottom: 0.5rem;
        font-weight: 500;
    }

    .notification-message strong {
        color: var(--notification-primary);
        font-weight: 600;
    }

    .notification-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
    }

    .notification-time {
        font-size: 0.75rem;
        color: var(--notification-text-muted);
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .notification-badge {
        background: var(--notification-primary);
        color: white;
        padding: 0.125rem 0.5rem;
        border-radius: 12px;
        font-size: 0.625rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }

    /* Botón eliminar individual */
    .notification-actions {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        align-items: center;
    }

    .btn-delete-individual {
        background: var(--notification-danger-light);
        border: 1px solid rgba(220, 38, 38, 0.2);
        color: var(--notification-danger);
        width: 2rem;
        height: 2rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        transition: all 0.2s ease;
        cursor: pointer;
        opacity: 0;
        transform: scale(0.8);
    }

    .notification-item:hover .btn-delete-individual {
        opacity: 1;
        transform: scale(1);
    }

    .btn-delete-individual:hover {
        background: rgba(220, 38, 38, 0.2);
        border-color: rgba(220, 38, 38, 0.4);
        color: #b91c1c;
        transform: scale(1.1);
    }

    .unread-indicator {
        width: 8px;
        height: 8px;
        background: var(--notification-primary);
        border-radius: 50%;
        animation: pulse 2s infinite;
    }

    .notification-preview {
        background: rgba(14, 74, 134, 0.05);
        border-left: 3px solid var(--notification-primary);
        padding: 0.5rem 0.75rem;
        margin: 0.5rem 0;
        border-radius: 0 6px 6px 0;
        font-size: 0.8rem;
        color: var(--notification-text-secondary);
    }

    .quote-icon {
        font-size: 0.7rem;
        color: var(--notification-primary);
        margin-right: 0.5rem;
        opacity: 0.7;
    }

    .preview-text {
        font-style: italic;
        line-height: 1.3;
    }

    .action-text {
        color: var(--notification-text-secondary);
        font-weight: normal;
    }

    /* Badge de notificaciones no leídas */
    .notification-badge-icon {
        position: relative;
        display: inline-block;
    }

    .notification-badge-icon .badge-count {
        position: absolute;
        top: 12px;
        right: 10px;
        background: #ef4444;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        font-size: 11px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid white;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        animation: pulse-badge 2s infinite;
        z-index: 10;
    }

    .notification-badge-icon .badge-count.hidden {
        display: none;
    }

    /* Estilos generales */
    .boton {
        position: relative;
        left: 20px;
        color: #0E4A86;
        background-color: white;
        border: none;
        transition: box-shadow 0.3s ease;
    }

    .boton:hover {
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

    ul {
        list-style: none;
        padding-left: 0;
        margin-bottom: 0;
    }

    li i {
        position: relative;
        cursor: pointer;
        color: white;
        font-size: 25px;
        transition: text-shadow 0.2s ease;
        margin-right: 20px;
    }

    li i:hover {
        text-shadow: 0 2px 8px white;
    }

    .profile {
        background-color: transparent;
        border-radius: 12px;
        cursor: pointer;
        color: white;
        transition: background-color 0.2s ease;
        height: 70px;
        display: flex;
        align-items: center;
        padding: 0 10px;
    }

    .profile:hover {
        background-color: rgba(255, 255, 255, 0.48);
    }

    .options {
        background-color: white;
    }

    .select-options {
        color: #0E4A86;
    }

    .select-options:hover {
        background-color: rgb(15, 85, 155);
        color: white;
    }

    /* Animaciones */
    @keyframes slideInNotification {
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes slideOutNotification {
        to {
            opacity: 0;
            transform: translateX(-100%);
            height: 0;
            padding: 0;
            margin: 0;
        }
    }

    @keyframes pulse {

        0%,
        100% {
            opacity: 1;
            transform: scale(1);
        }

        50% {
            opacity: 0.7;
            transform: scale(1.2);
        }
    }

    @keyframes pulse-badge {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.1);
        }
    }

    @keyframes fadeOut {
        to {
            opacity: 0;
            transform: scale(0.8);
        }
    }

    /* Responsive */
    @media (max-width: 576px) {
        .notification-panel {
            width: 100vw;
            max-width: 100vw;
        }

        .header-content,
        .header-actions {
            padding: 1rem;
        }

        .notification-item {
            padding: 0.75rem 1rem;
        }

        .notification-avatar {
            width: 2rem;
            height: 2rem;
            font-size: 0.75rem;
        }

        .empty-state {
            padding: 2rem 1rem;
        }

        .notification-badge-icon .badge-count {
            width: 16px;
            height: 16px;
            font-size: 9px;
            top: -6px;
            right: -6px;
        }

        .notification-preview {
            padding: 0.4rem 0.6rem;
            font-size: 0.75rem;
        }

        .quote-icon {
            font-size: 0.6rem;
        }

        .navbar-brand img {
            height: 60px;
        }

        .navbar-collapse {
            flex-direction: row;
            justify-content: space-between !important;
        }

        .navbar-nav {
            display: flex;
            flex-direction: row !important;
            justify-content: flex-end;
            align-items: center;
            gap: 0.5rem;
            width: 100%;
            flex-wrap: nowrap;
        }

        .navbar-nav li {
            margin-left: 0.3rem;
            position: static !important;
        }

        .nav-item .nav-link,
        .dropdown-toggle p {
            font-size: 14px;
            padding: 0 4px;
            margin: 0;
            position: static !important;
        }

        .profile img {
            max-width: 35px;
        }

        .profile p {
            font-size: 12px;
            margin-left: 6px;
            margin-bottom: 0;
            white-space: nowrap;
        }

        li i.bi-bell-fill {
            font-size: 20px;
            margin-bottom: 30px;
        }

        .dropdown-menu.options {
            left: auto !important;
            right: 0;
            position: absolute;
            top: 185px;
            right: 12px;
        }

        .boton {
            font-size: 14px;
            padding: 6px 12px;
            left: 0;
        }

        .navbar-toggler {
            border-color: white;
            background-color: white;
        }

        .navbar-nav .nav-item {
            display: flex;
            align-items: center;
        }
    }

    /* Estilos del modal (mantenidos igual) */
    .modal-editar-perfil .modal-content {
        border: none;
        border-radius: 16px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        background: #ffffff;
    }

    .modal-editar-perfil .modal-header {
        border: none;
        padding: 2rem 2rem 0 2rem;
        background: transparent;
    }

    .modal-editar-perfil .modal-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #1f2937;
        margin: 0;
    }

    .modal-editar-perfil .btn-close {
        background: none;
        border: none;
        font-size: 1.25rem;
        opacity: 0.5;
        transition: opacity 0.2s ease;
        padding: 0;
        width: 24px;
        height: 24px;
    }

    .modal-editar-perfil .btn-close:hover {
        opacity: 0.8;
    }

    .modal-editar-perfil .modal-body {
        padding: 1.5rem 2rem;
    }

    .avatar-section {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-bottom: 2rem;
        padding: 1.5rem;
        background: #f8fafc;
        border-radius: 12px;
        border: 2px dashed #e2e8f0;
        transition: all 0.3s ease;
    }

    .avatar-section:hover {
        border-color: #0E4A86;
        background: #f1f5f9;
    }

    .avatar-preview {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #ffffff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        margin-bottom: 1rem;
    }

    .avatar-label {
        font-size: 0.875rem;
        color: #64748b;
        text-align: center;
        margin: 0;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-label-modern {
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
        margin-bottom: 0.5rem;
        display: block;
    }

    .form-control-modern {
        border: 1.5px solid #e5e7eb;
        border-radius: 8px;
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
        transition: all 0.2s ease;
        background: #ffffff;
    }

    .form-control-modern:focus {
        border-color: #0E4A86;
        box-shadow: 0 0 0 3px rgba(14, 74, 134, 0.1);
        outline: none;
    }

    .form-control-modern.is-invalid {
        border-color: #ef4444;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
    }

    .password-group {
        position: relative;
    }

    .password-toggle {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #6b7280;
        cursor: pointer;
        padding: 0;
        font-size: 1rem;
        transition: color 0.2s ease;
    }

    .password-toggle:hover {
        color: #0E4A86;
    }

    .file-input-wrapper {
        position: relative;
        overflow: hidden;
        display: inline-block;
        width: 100%;
    }

    .file-input-modern {
        position: absolute;
        left: -9999px;
    }

    .file-input-label {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0.75rem 1rem;
        background: #f8fafc;
        border: 1.5px dashed #cbd5e1;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 0.875rem;
        color: #64748b;
    }

    .file-input-label:hover {
        background: #f1f5f9;
        border-color: #0E4A86;
        color: #0E4A86;
    }

    .error-message {
        font-size: 0.75rem;
        color: #ef4444;
        margin-top: 0.25rem;
        display: block;
    }

    .modal-editar-perfil .modal-footer {
        border: none;
        padding: 0 2rem 2rem 2rem;
        background: transparent;
        flex-direction: column;
        gap: 0.75rem;
    }

    .btn-primary-modern {
        background: linear-gradient(135deg, #0E4A86 0%, #1e40af 100%);
        border: none;
        border-radius: 8px;
        padding: 0.875rem 1.5rem;
        font-weight: 500;
        font-size: 0.875rem;
        transition: all 0.2s ease;
        width: 100%;
        color: white;
    }

    .btn-primary-modern:hover {
        transform: translateY(-1px);
        color: white;
        box-shadow: 0 4px 12px rgba(14, 74, 134, 0.3);
        background: linear-gradient(135deg, #1e40af 0%, #0E4A86 100%);
    }

    .btn-secondary-modern {
        background: transparent;
        border: none;
        color: #6b7280;
        font-size: 0.875rem;
        padding: 0.5rem;
        transition: color 0.2s ease;
        width: 100%;
    }

    .btn-secondary-modern:hover {
        color: #374151;
    }

    #vistaPreviaAvatar img {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #e5e7eb;
        margin-top: 0.5rem;
    }

    .modal-editar-perfil .modal-dialog {
        max-width: 400px;
        height: 70vh;
        max-height: 70vh;
    }

    .modal-editar-perfil .modal-content {
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .modal-editar-perfil .modal-body {
        flex: 1;
        overflow-y: auto;
    }

    @media (max-width: 576px) {

        .modal-editar-perfil .modal-header,
        .modal-editar-perfil .modal-body,
        .modal-editar-perfil .modal-footer {
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }

        .avatar-section {
            padding: 1rem;
        }

        .avatar-preview {
            width: 70px;
            height: 70px;
        }
    }
</style>

<header style="position: fixed; top: 0; z-index:99; width:100%;">
    <div class="offcanvas offcanvas-start notification-panel" data-bs-scroll="true" tabindex="-1" id="offcanvasWithBothOptions" aria-labelledby="offcanvasWithBothOptionsLabel">
        <div class="notification-header">
            <div class="header-content">
                <div class="header-title">
                    <div class="notification-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h5 class="offcanvas-title" id="offcanvasWithBothOptionsLabel">Notificaciones</h5>
                </div>
                <button type="button" class="btn-close-modern" data-bs-dismiss="offcanvas" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <?php if (!empty($notificaciones)): ?>
                <div class="header-actions">
                    <button type="button" id="marcar-todas-leidas" class="btn-mark-all">
                        <i class="fas fa-check-double"></i>
                        Marcar todas como leídas
                    </button>

                    <button type="button" id="eliminar-todas" class="btn-delete-all">
                        <i class="fas fa-trash-alt"></i>
                        Eliminar todas
                    </button>
                </div>
            <?php endif; ?>

        </div>

        <div class="offcanvas-body notification-body">
            <?php if (empty($notificaciones)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-bell-slash"></i>
                    </div>
                    <h6 class="empty-title">Sin notificaciones</h6>
                    <p class="empty-text">No tienes notificaciones recientes</p>
                </div>
            <?php else: ?>
                <div class="notifications-list">
                    <?php foreach ($notificaciones as $index => $noti): ?>
                        <div class="notification-item <?= $noti['leido'] ? 'read' : 'unread' ?>"
                            data-notification-id="<?= $noti['id_notificacion'] ?>"
                            style="animation-delay: <?= $index * 0.1 ?>s;">

                            <div class="notification-content">
                                <div class="notification-avatar">
                                    <i class="fas fa-comment-dots"></i>
                                </div>

                                <a href="<?= htmlspecialchars($noti['url_destino']) . '&id_notificacion=' . $noti['id_notificacion'] ?>"
                                    class="notification-body" style="text-decoration: none; color: inherit;">
                                    <div class="notification-message">
                                        <strong><?= htmlspecialchars($noti['autor']) ?></strong>
                                        <span class="action-text">respondió a tu comentario</span>
                                    </div>

                                    <?php if (!empty($noti['contenido']) && $noti['contenido'] !== 'Sin contenido disponible'): ?>
                                        <div class="notification-preview">
                                            <i class="fas fa-quote-left quote-icon"></i>
                                            <span class="preview-text"><?= htmlspecialchars($noti['contenido']) ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <div class="notification-meta">
                                        <span class="notification-time">
                                            <i class="fas fa-clock"></i>
                                            <?= $noti['fecha'] ?>
                                        </span>
                                        <?php if (!$noti['leido']): ?>
                                            <span class="notification-badge">Nuevo</span>
                                        <?php endif; ?>
                                    </div>
                                </a>

                                <div class="notification-actions">
                                    <button type="button"
                                        class="btn-delete-individual"
                                        data-notification-id="<?= $noti['id_notificacion'] ?>"
                                        title="Eliminar notificación">
                                        <i class="fas fa-times"></i>
                                    </button>

                                    <?php if (!$noti['leido']): ?>
                                        <div class="unread-indicator"></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>
            <?php endif; ?>
        </div>
    </div>
    <nav class="navbar navbar-expand-lg" style="background-color: #0E4A86;">
        <div class="container">
            <!-- Logo -->
            <a class="navbar-brand" href="<?= $carpeta_inicio ?>">
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
                        <li class="nav-item">
                            <div class="notification-badge-icon">
                                <i class="not bi bi-bell-fill" data-bs-toggle="offcanvas" data-bs-target="#offcanvasWithBothOptions" aria-controls="offcanvasWithBothOptions" style="position: relative; top: 15px"></i>
                                <?php if ($notificaciones_no_leidas > 0): ?>
                                    <span class="badge-count" id="notification-count"><?= $notificaciones_no_leidas > 99 ? '99+' : $notificaciones_no_leidas ?></span>
                                <?php endif; ?>
                            </div>
                        </li>
                        <!-- Menú para usuarios autenticados -->
                        <li class="nav-item">
                            <a class="l nav-link text-white" style="position: relative; top: 15px; margin-right: 30px;" href="<?= $carpeta_inicio ?>">Inicio</a>
                        </li>


                        <li class="nav-item dropdown profile">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="color: white;">
                                <img src="<?= BASE_URL ?>/<?= empty($user['avatar']) ? 'uploads/avatar/user.webp' : htmlspecialchars($user['avatar']) ?>" alt="Avatar" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 3px solid #fff; position: relative; right: 10px;">
                                <p style="position: relative; top: 8px;">
                                    <?php echo htmlspecialchars($username); ?>
                                </p>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end options">
                                <li><a class="dropdown-item select-options" href="#" data-bs-toggle="modal" data-bs-target="#modalEditarPerfil">Editar perfil</a></li>
                                <li><a class="dropdown-item select-options" href="#" data-bs-toggle="modal" data-bs-target="#modalConfiguracion">Configuración</a></li>
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
        (function() {
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


<!-- Modal Editar Perfil Rediseñado -->
<div class="modal fade modal-editar-perfil" id="modalEditarPerfil" tabindex="-1" aria-labelledby="modalEditarPerfilLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <form action="<?= BASE_URL ?>/actions/editar_perfil.php" method="POST" enctype="multipart/form-data" class="modal-content" onsubmit="return validarFormularioEditarPerfil();">

            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarPerfilLabel">Editar Perfil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body">
                <!-- Avatar Section -->
                <div class="avatar-section">
                    <img id="imgPreview" src="<?= BASE_URL ?>/<?= $user['avatar'] ?? 'assets/img/default_avatar.png' ?>" alt="Avatar actual" class="avatar-preview">
                    <p class="avatar-label">Foto de perfil actual</p>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="email" class="form-label-modern">Correo electrónico</label>
                    <input type="email" class="form-control form-control-modern" name="email" id="email" placeholder="ejemplo@correo.com" value="<?= htmlspecialchars($user['correo'] ?? '') ?>">
                </div>

                <!-- Teléfono -->
                <div class="form-group">
                    <label for="telefono" class="form-label-modern">Número de teléfono</label>
                    <input type="text" class="form-control form-control-modern" name="telefono" id="telefono" placeholder="+57 300 123 4567" value="<?= htmlspecialchars($user['telefono'] ?? '') ?>">
                </div>

                <!-- Contraseña -->
                <div class="form-group">
                    <label for="password" class="form-label-modern">Nueva contraseña</label>
                    <div class="password-group">
                        <input type="password" class="form-control form-control-modern" name="password" id="password" placeholder="Mínimo 8 caracteres">
                        <button class="password-toggle" type="button" onclick="togglePassword('password')">
                        </button>
                    </div>
                </div>

                <!-- Confirmar Contraseña -->
                <div class="form-group">
                    <label for="confirmar_password" class="form-label-modern">Confirmar nueva contraseña</label>
                    <div class="password-group">
                        <input type="password" class="form-control form-control-modern" name="confirmar_password" id="confirmar_password" placeholder="Repite la contraseña">
                        <button class="password-toggle" type="button" onclick="togglePassword('confirmar_password')">
                        </button>
                    </div>
                </div>

                <!-- Avatar Upload -->
                <div class="form-group">
                    <label for="avatar" class="form-label-modern">Cambiar foto de perfil</label>
                    <div class="file-input-wrapper">
                        <input type="file" class="file-input-modern" name="avatar" id="avatar" accept="image/*">
                        <label for="avatar" class="file-input-label">
                            <i class="bi bi-cloud-upload me-2"></i>
                            Seleccionar imagen (máx. 800x800 px)
                        </label>
                    </div>
                    <div id="vistaPreviaAvatar" class="text-center"></div>
                </div>

                <div id="errorMensaje" class="error-message text-center"></div>
            </div>

            <div class="modal-footer">
                <button type="submit" class="btn btn-primary-modern">
                    <i class="bi bi-check-circle me-2"></i>
                    Guardar cambios
                </button>
                <button type="button" class="btn btn-secondary-modern" data-bs-dismiss="modal">
                    Cancelar
                </button>
            </div>

        </form>
    </div>
</div>



<!-- Modal Configuración -->
<div class="modal fade" id="modalConfiguracion" tabindex="-1" aria-labelledby="modalConfiguracionLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= BASE_URL ?>/actions/config_guardar.php" method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalConfiguracionLabel">Configuración</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <!-- Puedes agregar aquí más opciones configurables -->
                <div class="mb-3">
                    <label for="tema" class="form-label">Tema de interfaz</label>
                    <select name="tema" id="tema" class="form-select">
                        <option value="claro">Claro</option>
                        <option value="oscuro">Oscuro</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="notificaciones" class="form-label">Notificaciones</label>
                    <select name="notificaciones" id="notificaciones" class="form-select">
                        <option value="activadas">Activadas</option>
                        <option value="desactivadas">Desactivadas</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Guardar configuración</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
    function mostrarError(elemento, mensaje) {
        eliminarError(elemento);
        const error = document.createElement("div");
        error.className = "text-danger small mt-1";
        error.innerText = mensaje;

        const contenedor = elemento.closest(".mb-3") || elemento.parentElement;
        contenedor.appendChild(error);
        elemento.classList.add("is-invalid");
    }

    function eliminarError(elemento) {
        elemento.classList.remove("is-invalid");
        const contenedor = elemento.closest(".mb-3") || elemento.parentElement;
        const errores = contenedor.querySelectorAll(".text-danger");
        errores.forEach(e => e.remove());
    }



    function validarFormularioEditarPerfil() {
        return validarTodo();
    }

    document.addEventListener('DOMContentLoaded', function() {
        const email = document.getElementById("email");
        const telefono = document.getElementById("telefono");
        const password = document.getElementById("password");
        const confirmar = document.getElementById("confirmar_password");
        const btnGuardar = document.querySelector('#modalEditarPerfil .btn.btn-primary');

        function validarCorreo() {
            const correo = email.value.trim();
            eliminarError(email);
            if (correo !== "") {
                const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!regex.test(correo)) {
                    mostrarError(email, "Correo electrónico inválido.");
                    return false;
                }
            }
            return true;
        }

        function validarPassword() {
            const pass = password.value;
            eliminarError(password);
            if (pass !== "") {
                const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;
                if (!regex.test(pass)) {
                    mostrarError(password, "Mínimo 8 caracteres, una mayúscula, una minúscula y un número.");
                    return false;
                }
            }
            return true;
        }

        function validarConfirmacion() {
            eliminarError(confirmar);
            if (password.value !== "" && password.value !== confirmar.value) {
                mostrarError(confirmar, "Las contraseñas no coinciden.");
                return false;
            }
            return true;
        }

        window.validarTodo = function() {
            const esValido = validarCorreo() && validarPassword() && validarConfirmacion() && validarAvatarActual();
            btnGuardar.disabled = !esValido;
            return esValido;
        };

        email.addEventListener("input", validarTodo);
        password.addEventListener("input", validarTodo);
        confirmar.addEventListener("input", validarTodo);
    });

    document.addEventListener("DOMContentLoaded", function() {
        const avatarInput = document.getElementById("avatar");
        const vistaPrevia = document.getElementById("vistaPreviaAvatar");
        const btnGuardar = document.querySelector('#modalEditarPerfil .btn.btn-primary');

        window.validarAvatarActual = function() {
            const file = avatarInput.files[0];
            if (!file) {
                eliminarError(avatarInput);
                vistaPrevia.innerHTML = "";
                return true;
            }

            if (!file.type.startsWith('image/')) {
                mostrarError(avatarInput, "Solo se permiten archivos de imagen.");
                vistaPrevia.innerHTML = "";
                return false;
            }

            if (file.size > 1024 * 1024) {
                mostrarError(avatarInput, "El archivo no debe superar 1MB.");
                vistaPrevia.innerHTML = "";
                return false;
            }

            return true;
        };

        avatarInput.addEventListener("change", function() {
            const file = avatarInput.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(e) {
                const img = new Image();
                img.onload = function() {
                    if (img.width > 800 || img.height > 800) {
                        mostrarError(avatarInput, "El avatar no debe superar 800x800 píxeles.");
                        vistaPrevia.innerHTML = "";
                        btnGuardar.disabled = true;
                    } else {
                        eliminarError(avatarInput);
                        vistaPrevia.innerHTML = `<img src="${e.target.result}" alt="Vista previa" style="max-width:100px; max-height:100px; border-radius:50%;">`;
                        validarTodo();
                    }
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });
    });
</script>

<script>
    // JavaScript para mejorar la interactividad
    document.addEventListener('DOMContentLoaded', function() {
        // Marcar notificación como leída al hacer click
        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', function(e) {
                const notificationId = this.dataset.notificationId;

                // Marcar visualmente como leída
                this.classList.remove('unread');
                this.classList.add('read');

                // Remover indicador de no leída
                const indicator = this.querySelector('.unread-indicator');
                if (indicator) {
                    indicator.style.animation = 'fadeOut 0.3s ease forwards';
                    setTimeout(() => indicator.remove(), 300);
                }

                // Remover badge "Nuevo"
                const badge = this.querySelector('.notification-badge');
                if (badge) {
                    badge.style.animation = 'fadeOut 0.3s ease forwards';
                    setTimeout(() => badge.remove(), 300);
                }
            });
        });

        // Animación para el botón de marcar todas como leídas
        const markAllBtn = document.querySelector('.btn-mark-all');
        if (markAllBtn) {
            markAllBtn.addEventListener('click', function(e) {
                e.preventDefault();

                // Animación de carga
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Marcando...';
                this.disabled = true;

                // Simular envío del formulario
                setTimeout(() => {
                    this.closest('form').submit();
                }, 500);
            });
        }
    });

    // Animación CSS adicional
    const style = document.createElement('style');
    style.textContent = `
    @keyframes fadeOut {
        to {
            opacity: 0;
            transform: scale(0.8);
        }
    }
`;
    document.head.appendChild(style);
</script>

<script>
    // Reemplaza solo la parte del JavaScript relacionada con las notificaciones
    document.addEventListener('DOMContentLoaded', function() {
        // Marcar notificación individual como leída (solo en el panel de notificaciones)
        document.querySelectorAll('.notification-panel .notification-item').forEach(item => {
            item.addEventListener('click', function(e) {
                const notificationId = this.dataset.notificationId;

                // Marcar visualmente como leída
                this.classList.remove('unread');
                this.classList.add('read');

                // Remover indicador de no leída
                const indicator = this.querySelector('.unread-indicator');
                if (indicator) {
                    indicator.style.animation = 'fadeOut 0.3s ease forwards';
                    setTimeout(() => indicator.remove(), 300);
                }

                // Remover badge "Nuevo"
                const badge = this.querySelector('.notification-badge');
                if (badge) {
                    badge.style.animation = 'fadeOut 0.3s ease forwards';
                    setTimeout(() => badge.remove(), 300);
                }
            });
        });

        // AJAX específico para marcar todas las notificaciones como leídas
        const markAllBtn = document.getElementById('marcar-todas-leidas');
        if (markAllBtn) {
            markAllBtn.addEventListener('click', function(e) {
                e.preventDefault();

                // Guardar contenido original
                const originalContent = this.innerHTML;

                // Mostrar estado de carga
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Marcando...';
                this.disabled = true;

                // Realizar petición AJAX
                fetch('<?= BASE_URL ?>/ajax/marcar_todas_leidas.php', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Content-Type': 'application/x-www-form-urlencoded'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Mostrar estado de éxito
                            this.innerHTML = '<i class="fas fa-check-double"></i> ¡Marcadas!';

                            // Actualizar solo las notificaciones en el panel
                            document.querySelectorAll('.notification-panel .notification-item.unread').forEach(item => {
                                item.classList.remove('unread');
                                item.classList.add('read');

                                const badge = item.querySelector('.notification-badge');
                                const indicator = item.querySelector('.unread-indicator');

                                if (badge) badge.remove();
                                if (indicator) indicator.remove();
                            });

                            // Restaurar botón después de 2 segundos
                            setTimeout(() => {
                                this.innerHTML = originalContent;
                                this.disabled = false;

                                // Ocultar el botón si no hay más notificaciones no leídas
                                const hasUnread = document.querySelector('.notification-panel .notification-item.unread');
                                if (!hasUnread) {
                                    this.parentElement.style.display = 'none';
                                }
                            }, 2000);

                        } else {
                            // Error: restaurar botón
                            this.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
                            setTimeout(() => {
                                this.innerHTML = originalContent;
                                this.disabled = false;
                            }, 2000);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Error: restaurar botón
                        this.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
                        setTimeout(() => {
                            this.innerHTML = originalContent;
                            this.disabled = false;
                        }, 2000);
                    });
            });
        }
    });

    // CSS específico solo para notificaciones (no afecta otros elementos)
    const notificationStyles = document.createElement('style');
    notificationStyles.textContent = `
    @keyframes fadeOut {
        to {
            opacity: 0;
            transform: scale(0.8);
        }
    }
    
    /* Solo aplicar estilos dentro del panel de notificaciones */
    .notification-panel .notification-item {
        transition: all 0.3s ease !important;
    }
    
    .notification-panel .notification-item.read {
        opacity: 0.7;
    }
    
    .notification-panel .btn-mark-all {
        transition: all 0.3s ease !important;
    }
    
    .notification-panel .btn-mark-all:disabled {
        opacity: 0.8 !important;
        cursor: not-allowed !important;
    }
`;
    document.head.appendChild(notificationStyles);
</script>

<script>
    // Función para actualizar el contador del badge
    function actualizarBadgeNotificaciones() {
        const badgeElement = document.getElementById('notification-count');
        const unreadItems = document.querySelectorAll('.notification-panel .notification-item.unread');
        const count = unreadItems.length;

        if (badgeElement) {
            if (count === 0) {
                badgeElement.classList.add('hidden');
            } else {
                badgeElement.textContent = count > 99 ? '99+' : count;
                badgeElement.classList.remove('hidden');
            }
        }
    }

    // Actualizar el JavaScript existente para incluir la actualización del badge
    document.addEventListener('DOMContentLoaded', function() {
        // Marcar notificación individual como leída
        document.querySelectorAll('.notification-panel .notification-item').forEach(item => {
            item.addEventListener('click', function(e) {
                const notificationId = this.dataset.notificationId;

                // Marcar visualmente como leída
                this.classList.remove('unread');
                this.classList.add('read');

                // Remover indicador de no leída
                const indicator = this.querySelector('.unread-indicator');
                if (indicator) {
                    indicator.style.animation = 'fadeOut 0.3s ease forwards';
                    setTimeout(() => indicator.remove(), 300);
                }

                // Remover badge "Nuevo"
                const badge = this.querySelector('.notification-badge');
                if (badge) {
                    badge.style.animation = 'fadeOut 0.3s ease forwards';
                    setTimeout(() => badge.remove(), 300);
                }

                // Actualizar badge del icono
                setTimeout(() => {
                    actualizarBadgeNotificaciones();
                }, 100);
            });
        });

        // Modificar el evento del botón "marcar todas como leídas"
        const markAllBtn = document.getElementById('marcar-todas-leidas');
        if (markAllBtn) {
            markAllBtn.addEventListener('click', function(e) {
                e.preventDefault();

                const originalContent = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Marcando...';
                this.disabled = true;

                fetch('<?= BASE_URL ?>/ajax/marcar_todas_leidas.php', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Content-Type': 'application/x-www-form-urlencoded'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.innerHTML = '<i class="fas fa-check-double"></i> ¡Marcadas!';

                            // Actualizar notificaciones en el panel
                            document.querySelectorAll('.notification-panel .notification-item.unread').forEach(item => {
                                item.classList.remove('unread');
                                item.classList.add('read');

                                const badge = item.querySelector('.notification-badge');
                                const indicator = item.querySelector('.unread-indicator');

                                if (badge) badge.remove();
                                if (indicator) indicator.remove();
                            });

                            // Actualizar badge del icono
                            actualizarBadgeNotificaciones();

                            setTimeout(() => {
                                this.innerHTML = originalContent;
                                this.disabled = false;

                                const hasUnread = document.querySelector('.notification-panel .notification-item.unread');
                                if (!hasUnread) {
                                    this.parentElement.style.display = 'none';
                                }
                            }, 2000);

                        } else {
                            this.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
                            setTimeout(() => {
                                this.innerHTML = originalContent;
                                this.disabled = false;
                            }, 2000);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        this.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
                        setTimeout(() => {
                            this.innerHTML = originalContent;
                            this.disabled = false;
                        }, 2000);
                    });
            });
        }
    });
</script>

<script>
    // AJAX para eliminar todas las notificaciones
    document.addEventListener('DOMContentLoaded', function() {
        const deleteAllBtn = document.getElementById('eliminar-todas');
        if (deleteAllBtn) {
            deleteAllBtn.addEventListener('click', function(e) {
                e.preventDefault();

                // Mostrar confirmación
                if (!confirm('¿Estás seguro de que quieres eliminar TODAS las notificaciones? Esta acción no se puede deshacer.')) {
                    return;
                }

                // Guardar contenido original
                const originalContent = this.innerHTML;

                // Mostrar estado de carga
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Eliminando...';
                this.disabled = true;

                // Realizar petición AJAX
                fetch('<?= BASE_URL ?>/ajax/eliminar_todas_notificaciones.php', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Content-Type': 'application/x-www-form-urlencoded'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Mostrar estado de éxito
                            this.innerHTML = '<i class="fas fa-check"></i> ¡Eliminadas!';

                            // Ocultar todas las notificaciones con animación
                            const notificationItems = document.querySelectorAll('.notification-panel .notification-item');
                            notificationItems.forEach((item, index) => {
                                setTimeout(() => {
                                    item.style.animation = 'slideOutNotification 0.3s ease forwards';
                                    setTimeout(() => item.remove(), 300);
                                }, index * 50);
                            });

                            // Mostrar estado vacío después de que se eliminen todas
                            setTimeout(() => {
                                const notificationsList = document.querySelector('.notifications-list');
                                if (notificationsList) {
                                    notificationsList.innerHTML = `
                                    <div class="empty-state">
                                        <div class="empty-icon">
                                            <i class="fas fa-bell-slash"></i>
                                        </div>
                                        <h6 class="empty-title">Sin notificaciones</h6>
                                        <p class="empty-text">No tienes notificaciones recientes</p>
                                    </div>
                                `;
                                }

                                // Ocultar los botones de acción
                                this.parentElement.style.display = 'none';

                                // Actualizar badge del icono
                                const badgeElement = document.getElementById('notification-count');
                                if (badgeElement) {
                                    badgeElement.classList.add('hidden');
                                }
                            }, notificationItems.length * 50 + 300);

                        } else {
                            // Error: restaurar botón
                            this.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
                            setTimeout(() => {
                                this.innerHTML = originalContent;
                                this.disabled = false;
                            }, 2000);

                            // Mostrar mensaje de error
                            alert(data.message || 'Error al eliminar las notificaciones');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Error: restaurar botón
                        this.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
                        setTimeout(() => {
                            this.innerHTML = originalContent;
                            this.disabled = false;
                        }, 2000);

                        alert('Error de conexión. Inténtalo de nuevo.');
                    });
            });
        }
    });
</script>


<script>
    // JavaScript para eliminar notificaciones individuales
    document.addEventListener('DOMContentLoaded', function() {
        // Eliminar notificación individual
        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-delete-individual')) {
                e.preventDefault();
                e.stopPropagation();

                const btn = e.target.closest('.btn-delete-individual');
                const notificationId = btn.dataset.notificationId;
                const notificationItem = btn.closest('.notification-item');

                if (!confirm('¿Eliminar esta notificación?')) {
                    return;
                }

                const originalContent = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                btn.disabled = true;

                fetch('<?= BASE_URL ?>/ajax/eliminar_notificacion_individual.php', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `id_notificacion=${notificationId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            notificationItem.style.animation = 'slideOutNotification 0.3s ease forwards';
                            setTimeout(() => {
                                notificationItem.remove();
                                actualizarBadgeNotificaciones();

                                // Verificar si quedan notificaciones
                                const remainingNotifications = document.querySelectorAll('.notification-item');
                                if (remainingNotifications.length === 0) {
                                    const notificationsList = document.querySelector('.notifications-list');
                                    if (notificationsList) {
                                        notificationsList.innerHTML = `
                                    <div class="empty-state">
                                        <div class="empty-icon">
                                            <i class="fas fa-bell-slash"></i>
                                        </div>
                                        <h6 class="empty-title">Sin notificaciones</h6>
                                        <p class="empty-text">No tienes notificaciones recientes</p>
                                    </div>
                                `;
                                    }

                                    // Ocultar botones de acción
                                    const headerActions = document.querySelector('.header-actions');
                                    if (headerActions) {
                                        headerActions.style.display = 'none';
                                    }
                                }
                            }, 300);
                        } else {
                            btn.innerHTML = originalContent;
                            btn.disabled = false;
                            alert(data.message || 'Error al eliminar la notificación');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        btn.innerHTML = originalContent;
                        btn.disabled = false;
                        alert('Error de conexión. Inténtalo de nuevo.');
                    });
            }
        });
    });
</script>
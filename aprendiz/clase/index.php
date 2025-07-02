<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['documento'])) {
    header('Location: ../login.php');
    exit;
}

$documento_usuario = $_SESSION['documento'];

// Obtener el ID real del usuario a partir del documento
$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['documento']]);
$usuario = $stmt->fetch();

if (!$usuario) {
    die("Error: No se encontró el usuario en la base de datos.");
}

$id_usuario_actual = $usuario['id'];

// Obtener el ID de la clase desde la URL
$id_clase = isset($_GET['id_clase']) ? intval($_GET['id_clase']) : null;

if (!$id_clase) {
    die("Error: No se ha especificado una clase. Por favor, seleccione una clase desde la página de 'Mis Clases'.");
}

// Verificar que la materia_ficha existe y obtener sus datos
$stmt = $pdo->prepare("
    SELECT mf.id_materia_ficha, mf.id_materia, mf.id_ficha, m.materia, f.id_ficha
    FROM materia_ficha mf
    JOIN materias m ON mf.id_materia = m.id_materia
    JOIN fichas f ON mf.id_ficha = f.id_ficha
    WHERE mf.id_materia_ficha = ?
");
$stmt->execute([$id_clase]);
$materiaFichaData = $stmt->fetch();

if (!$materiaFichaData) {
    die("Error: La clase especificada no existe.");
}

// Verificar que el usuario tiene acceso a esta materia_ficha
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count
    FROM user_ficha uf
    WHERE uf.id_user = ? AND uf.id_ficha = ?
");
$stmt->execute([$id_usuario_actual, $materiaFichaData['id_ficha']]);
$result = $stmt->fetch();

if (!isset($result['count']) || intval($result['count']) === 0) {
    die("Error: No tienes acceso a esta clase.");
}

// Asignar los valores necesarios para el resto del código
$id_materia_seleccionada = $materiaFichaData['id_materia'];
$id_ficha_actual = $materiaFichaData['id_ficha'];
$idMateriaFicha = $id_clase;

// Obtener datos de la ficha
$stmt = $pdo->prepare("
    SELECT f.*, fo.nombre as nombre_formacion, j.jornada, a.ambiente
    FROM fichas f
    LEFT JOIN formacion fo ON f.id_formacion = fo.id_formacion
    LEFT JOIN jornada j ON f.id_jornada = j.id_jornada
    LEFT JOIN ambientes a ON f.id_ambiente = a.id_ambiente
    WHERE f.id_ficha = ? AND f.id_estado = 1
");
$stmt->execute([$id_ficha_actual]);
$ficha = $stmt->fetch();

if (!$ficha) {
    die("Error: No se pudo obtener información de la ficha.");
}

// Obtener información de la materia
$stmt = $pdo->prepare("
    SELECT m.materia, m.id_materia, mf.id_materia_ficha
    FROM materia_ficha mf
    JOIN materias m ON mf.id_materia = m.id_materia
    WHERE mf.id_materia_ficha = ?
");
$stmt->execute([$id_clase]);
$materiaActual = $stmt->fetch();

if (!$materiaActual) {
    die("Error: No se pudo obtener información de la materia.");
}

// Obtener actividades usando la función corregida
$todasActividades = obtenerTodasActividadesConEstado($idMateriaFicha, $id_usuario_actual);

// Obtener temas recientes del foro
$temasRecientes = obtenerTemasForoRecientes($idMateriaFicha);

// Obtener anuncios recientes
$anuncios = obtenerAnunciosRecientes($idMateriaFicha);

// Obtener instructores de la materia
$instructores = obtenerInstructoresFicha($id_ficha_actual, $id_materia_seleccionada);

// Verificar si el usuario actual es instructor de esta materia_ficha
$esInstructor = esInstructorMateriaFicha($id_usuario_actual, $idMateriaFicha);

// Separar actividades por estado - LÓGICA CORREGIDA
$actividadesPendientes = [];
$actividadesVencidas = [];
$actividadesEntregadas = [];

foreach ($todasActividades as $actividad) {
    // Usar el estado ya calculado en la función
    switch ($actividad['estado_entrega']) {
        case 'entregada':
            $actividadesEntregadas[] = $actividad;
            break;
        case 'vencida':
            $actividadesVencidas[] = $actividad;
            break;
        case 'pendiente':
        default:
            $actividadesPendientes[] = $actividad;
            break;
    }
}

// Para el tablón, mostrar próximas entregas (actividades pendientes)
$proximasEntregas = $actividadesPendientes;

// Función para formatear fechas
function formatearFecha($fecha)
{
    $timestamp = strtotime($fecha);
    return date('d/m/Y H:i', $timestamp);
}

// Función para obtener iniciales
function obtenerIniciales($nombre_completo)
{
    $palabras = explode(' ', $nombre_completo);
    $iniciales = '';
    foreach ($palabras as $palabra) {
        if (!empty($palabra)) {
            $iniciales .= strtoupper(substr($palabra, 0, 1));
        }
    }
    return $iniciales;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Clases - <?php echo htmlspecialchars($materiaActual['materia']); ?></title>
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
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0">

    <link rel="stylesheet" href="../css/styles.css">

    <style>
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

        .main-content .container-fluid {
            max-width: 1400px;
            margin: 0 auto;
            padding-left: 24px;
            padding-right: 24px;
        }

        .clase-info-card {
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .card {
            margin-bottom: 20px;
        }

        .tab-button,
        .personas-tab-button {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .task-list,
        .announcement-list,
        .people-list {
            padding-left: 0 !important;
        }

        .avatar {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-width: 50px;
        }

        .task-item {
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .task-item:hover {
            background-color: #f8f9fa;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .task-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .task-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 0;
            cursor: pointer;
            transition: background 0.2s;
        }

        .task-item:hover {
            background-color: #f9f9f9;
            border-radius: 8px;
        }

        .task-icon {
            flex-shrink: 0;
            width: 38px;
            height: 38px;
            background: #f1f1f1;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 6px;
        }

        .task-icon i {
            color: #111;
            font-size: 1.5rem;
        }

        .task-info {
            flex: 1;
        }

        .task-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .task-title {
            margin: 0;
            font-weight: bold;
        }

        .task-meta {
            margin: 4px 0;
            color: #555;
        }

        .task-description {
            background: #f8f9fa;
            padding: 8px 12px;
            border-radius: 6px;
            margin: 0;
            font-size: 0.95rem;
            color: #333;
        }

        .status-badge.pendiente {
            background-color: #ffeb3b;
            color: #333;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .task-item.vencida {
            border-left: 4px solid #dc3545;
            background-color: #fff5f5;
            padding-left: 16px;
        }

        .task-item.vencida:hover {
            background-color: #ffe6e6;
        }

        .task-icon.status-vencida {
            background-color: #dc3545 !important;
        }

        .task-icon.status-vencida i {
            color: white !important;
        }

        .status-badge.vencida {
            background-color: #dc3545;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .alerta-vencidas {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
            position: relative;
        }

        .alerta-vencidas .icono-alerta {
            font-size: 1.5rem;
            animation: pulse 2s infinite;
        }

        .alerta-vencidas .btn-close-alert {
            position: absolute;
            top: 10px;
            right: 15px;
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s;
        }

        .alerta-vencidas .btn-close-alert:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .alerta-vencidas.hidden {
            display: none;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }

            100% {
                transform: scale(1);
            }
        }

        .status-badge.completed {
            background-color: #28a745;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .actividad-entregada {
            border-left: 4px solid #28a745;
            background-color: #f8fff8;
            margin-bottom: 18px;
            /* Separación entre tarjetas */
            border-radius: 10px;
            padding: 18px 18px 12px 18px;
            /* Espaciado interno */
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.04);
            border: 1px solid #e6f4ea;
            /* Borde sutil */
        }

        .task-list .actividad-entregada:last-child {
            margin-bottom: 0;
        }

        .nota-comentario {
            background-color: #e8f5e8;
            padding: 10px;
            border-radius: 8px;
            margin-top: 12px;
            border-left: 3px solid #28a745;
            margin-bottom: 0;
        }

        .nota-valor {
            font-weight: bold;
            color: #155724;
            font-size: 1.1rem;
        }

        .comentario-instructor {
            color: #155724;
            font-style: italic;
            margin-top: 5px;
        }

        .task-list .task-item.vencida {
            margin-bottom: 16px;
            /* Espacio entre tarjetas */
            border-radius: 8px;
            border: 1px solid #ffd6d6;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.04);
        }

        .task-list .task-item.vencida:last-child {
            margin-bottom: 0;
        }

        .btn-volver-xs {
            padding: 9px 5px !important;
            font-size: 0.75rem !important;
            line-height: 1.2 !important;
            border-radius: 6px !important;
            max-width: 120px;
            /* Limita el ancho máximo */
            white-space: nowrap;
            /* Evita que el texto se divida en dos líneas */
            overflow: hidden;
            /* Oculta el texto que sobrepase el ancho */
        }

        .actividad-entregada:hover {
            background-color: #f3fcf3;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.10);
            transition: background 0.2s, box-shadow 0.2s, transform 0.2s;
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
            <div class="row" id="contenedor-clases">
                <div class="col-12">
                    <!-- Información de la clase -->
                    <div class="clase-info-card card mb-4 p-3 shadow-sm bg-white" data-id-clase="<?php echo $idMateriaFicha; ?>">
                        <h2 class="mb-1"><?php echo htmlspecialchars($materiaActual['materia']); ?></h2>
                        <p class="text-muted mb-0">
                            Ficha: <?php echo htmlspecialchars($ficha['id_ficha']); ?> •
                            Instructor: <?php echo !empty($instructores) ? htmlspecialchars($instructores[0]['nombres'] . ' ' . $instructores[0]['apellidos']) : 'No asignado'; ?>
                        </p>
                        <button class="btn btn-outline-black btn-sm btn-volver-xs mt-2" onclick="volverAClase()">
                            <i class="bi bi-arrow-return-left"></i> Volver a clases
                        </button>
                    </div>

                    <!-- Alerta de tareas vencidas -->
                    <?php if (count($actividadesVencidas) > 0): ?>
                        <div class="alerta-vencidas" id="alertaVencidas">
                            <i class="bi bi-exclamation-triangle-fill icono-alerta"></i>
                            <div>
                                <strong>¡Atención!</strong> Tienes <?php echo count($actividadesVencidas); ?>
                                actividad<?php echo count($actividadesVencidas) > 1 ? 'es' : ''; ?> vencida<?php echo count($actividadesVencidas) > 1 ? 's' : ''; ?>
                                sin entregar.
                            </div>
                            <button class="btn-close-alert" onclick="cerrarAlertaVencidas()" title="Cerrar alerta por 2 días">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    <?php endif; ?>

                    <!-- Navegación por pestañas -->
                    <div class="tabs-container">
                        <div class="tabs">
                            <button class="tab-button active" data-tab="tablon">
                                <span class="tab-icon"></span>
                                Tablón
                            </button>
                            <button class="tab-button" data-tab="trabajo">
                                <span class="tab-icon"></span>
                                Trabajo en clase
                                <?php if (count($actividadesVencidas) > 0): ?>
                                    <span class="badge bg-danger ms-2"><?php echo count($actividadesVencidas); ?></span>
                                <?php endif; ?>
                            </button>
                            <button class="tab-button" data-tab="personas">
                                <span class="tab-icon"></span>
                                Foro
                            </button>
                        </div>
                    </div>

                    <!-- Contenido de las pestañas -->
                    <div class="tab-content active" id="tablon">
                        <div class="grid">
                            <!-- Próximas entregas -->
                            <div class="card">
                                <div class="card-header">
                                    <h3><span class="icon"><i class="bi bi-alarm-fill"></i></span> Próximas entregas</h3>
                                </div>
                                <div class="card-content">
                                    <?php if (count($proximasEntregas) > 0): ?>
                                        <ul class="task-list">
                                            <?php foreach ($proximasEntregas as $actividad): ?>
                                                <li class="task-item" onclick="window.location.href='detalle_actividad.php?id=<?php echo $actividad['id_actividad']; ?>'">
                                                    <div class="task-icon status-pendiente">
                                                        <i class="bi bi-clock-history"></i>
                                                    </div>
                                                    <div class="task-info">
                                                        <div class="task-header">
                                                            <p class="task-title"><?php echo htmlspecialchars($actividad['titulo']); ?></p>
                                                            <span class="status-badge pendiente">Pendiente</span>
                                                        </div>
                                                        <p class="task-meta">
                                                            <strong><?php echo htmlspecialchars($actividad['materia']); ?></strong> •
                                                            <?php echo htmlspecialchars($actividad['nombres'] . ' ' . $actividad['apellidos']); ?> •
                                                            Entrega: <?php echo formatearFecha($actividad['fecha_entrega']); ?>
                                                        </p>
                                                        <?php if (!empty($actividad['descripcion'])): ?>
                                                            <p class="task-description"><?php echo htmlspecialchars($actividad['descripcion']); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <div class="empty-state">
                                            <span class="empty-icon"><i class="bi bi-journals"></i></span>
                                            <p>No hay entregas próximas</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Temas de foro recientes -->
                            <div class="card">
                                <div class="card-header">
                                    <h3>
                                        <span class="icon"><i class="bi bi-chat-fill"></i></span>
                                        Temas recientes en el foro
                                        <?php if (count($temasRecientes) > 0): ?>
                                            <span class="badge"><?php echo count($temasRecientes); ?> tema<?php echo count($temasRecientes) > 1 ? 's' : ''; ?></span>
                                        <?php endif; ?>
                                    </h3>
                                </div>
                                <div class="card-content">
                                    <?php if (count($temasRecientes) > 0): ?>
                                        <ul class="task-list">
                                            <?php foreach ($temasRecientes as $tema): ?>
                                                <li class="task-item" onclick="window.location.href='../foros/detalle_tema.php?id=<?php echo $tema['id_tema_foro']; ?>'">
                                                    <div class="task-icon"><i class="bi bi-chat-dots"></i></div>
                                                    <div class="task-info">
                                                        <p class="task-title"><?php echo htmlspecialchars($tema['titulo']); ?></p>
                                                        <p class="task-meta">
                                                            <strong><?php echo htmlspecialchars($tema['nombres'] . ' ' . $tema['apellidos']); ?></strong> •
                                                            <?php echo formatearFecha($tema['fecha_creacion']); ?>
                                                        </p>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <div class="empty-state">
                                            <span class="empty-icon"><i class="bi bi-chat"></i></span>
                                            <p>No hay temas recientes</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Anuncios recientes -->
                        <div class="card full-width">
                            <div class="card-header">
                                <h3><span class="icon"><i class="bi bi-megaphone-fill"></i></span> Anuncios</h3>
                            </div>
                            <div class="card-content">
                                <div id="listaAnuncios">
                                    <?php if (count($anuncios) > 0): ?>
                                        <ul class="announcement-list">
                                            <?php foreach ($anuncios as $anuncio): ?>
                                                <li class="announcement-item">
                                                    <div class="announcement-header">
                                                        <div class="avatar instructor-avatar">
                                                            <?php echo obtenerIniciales($anuncio['nombres'] . ' ' . $anuncio['apellidos']); ?>
                                                        </div>
                                                        <div class="announcement-meta">
                                                            <p class="instructor-name"><?php echo htmlspecialchars($anuncio['nombres'] . ' ' . $anuncio['apellidos']); ?></p>
                                                            <p class="announcement-date"><?php echo formatearFecha($anuncio['fecha_creacion']); ?></p>
                                                        </div>
                                                    </div>
                                                    <div class="announcement-content">
                                                        <p class="announcement-title"><?php echo htmlspecialchars($anuncio['titulo']); ?></p>
                                                        <?php if ($anuncio['descripcion']): ?>
                                                            <p class="announcement-text"><?php echo htmlspecialchars($anuncio['descripcion']); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <div class="empty-state">
                                            <span class="empty-icon"><i class="bi bi-megaphone"></i></span>
                                            <p>No hay anuncios recientes</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-content" id="trabajo">
                        <div class="card full-width">
                            <div class="card-header">
                                <h3><span class="icon"><i class="bi bi-clipboard2-minus-fill"></i></span> Trabajo en clase</h3>
                            </div>
                            <div class="card-content">
                                <!-- Tareas incompletas (vencidas) -->
                                <div class="work-section">
                                    <h4>
                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                        Actividades incompletas
                                    </h4>
                                    <?php if (count($actividadesVencidas) > 0): ?>
                                        <ul class="task-list">
                                            <?php foreach ($actividadesVencidas as $actividad): ?>
                                                <li class="task-item vencida" onclick="window.location.href='detalle_actividad.php?id=<?php echo $actividad['id_actividad']; ?>'">
                                                    <div class="task-icon status-vencida">
                                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                                    </div>
                                                    <div class="task-info">
                                                        <div class="task-header">
                                                            <p class="task-title"><?php echo htmlspecialchars($actividad['titulo']); ?></p>
                                                            <div class="task-actions">
                                                                <span class="status-badge vencida">Vencida</span>
                                                            </div>
                                                        </div>
                                                        <p class="task-meta">
                                                            <strong><?php echo htmlspecialchars($actividad['materia']); ?></strong> •
                                                            <?php echo htmlspecialchars($actividad['nombres'] . ' ' . $actividad['apellidos']); ?> •
                                                            <span style="color: #dc3545; font-weight: bold;">
                                                                Venció: <?php echo formatearFecha($actividad['fecha_entrega']); ?>
                                                            </span>
                                                        </p>
                                                        <?php if (isset($actividad['descripcion']) && $actividad['descripcion']): ?>
                                                            <p class="task-description"><?php echo htmlspecialchars($actividad['descripcion']); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <div class="empty-state">
                                            <span class="empty-icon"><i class="bi bi-exclamation-triangle"></i></span>
                                            <p>No hay actividades incompletas</p>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="work-section">
                                    <h4>
                                        <i class="bi bi-clock-fill"></i>
                                        Actividades pendientes
                                    </h4>
                                    <?php if (count($actividadesPendientes) > 0): ?>
                                        <ul class="task-list">
                                            <?php foreach ($actividadesPendientes as $actividad): ?>
                                                <li class="task-item" onclick="window.location.href='detalle_actividad.php?id=<?php echo $actividad['id_actividad']; ?>'">
                                                    <div class="task-icon status-pendiente">
                                                        <i class="bi bi-clock-history"></i>
                                                    </div>
                                                    <div class="task-info">
                                                        <div class="task-header">
                                                            <p class="task-title"><?php echo htmlspecialchars($actividad['titulo']); ?></p>
                                                            <div class="task-actions">
                                                                <span class="status-badge pendiente">Pendiente</span>
                                                            </div>
                                                        </div>
                                                        <p class="task-meta">
                                                            <strong><?php echo htmlspecialchars($actividad['materia']); ?></strong> •
                                                            <?php echo htmlspecialchars($actividad['nombres'] . ' ' . $actividad['apellidos']); ?> •
                                                            Entrega: <?php echo formatearFecha($actividad['fecha_entrega']); ?>
                                                        </p>
                                                        <?php if (isset($actividad['descripcion']) && $actividad['descripcion']): ?>
                                                            <p class="task-description"><?php echo htmlspecialchars($actividad['descripcion']); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <div class="empty-state">
                                            <span class="empty-icon"><i class="bi bi-clock-history"></i></span>
                                            <p>No hay actividades pendientes</p>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="work-section">
                                    <h4>
                                        <i class="bi bi-book-fill"></i>
                                        Actividades completadas
                                    </h4>
                                    <?php if (count($actividadesEntregadas) > 0): ?>
                                        <ul class="task-list">
                                            <?php foreach ($actividadesEntregadas as $actividad): ?>
                                                <li class="task-item actividad-entregada" onclick="window.location.href='detalle_actividad.php?id=<?php echo $actividad['id_actividad']; ?>'">
                                                    <div class="task-icon status-completed"><i class="bi bi-patch-check"></i></div>
                                                    <div class="task-info">
                                                        <div class="task-header">
                                                            <p class="task-title"><?php echo htmlspecialchars($actividad['titulo']); ?></p>
                                                            <span class="status-badge completed">Completada</span>
                                                        </div>
                                                        <p class="task-meta">
                                                            <strong><?php echo htmlspecialchars($actividad['materia']); ?></strong> •
                                                            <?php echo htmlspecialchars($actividad['nombres'] . ' ' . $actividad['apellidos']); ?> •
                                                            Entregado: <?php echo formatearFecha($actividad['fecha_entregada']); ?>
                                                        </p>

                                                        <!-- Mostrar nota y comentario del instructor -->
                                                        <?php if ($actividad['nota'] || $actividad['comentario_inst']): ?>
                                                            <div class="nota-comentario">
                                                                <?php if ($actividad['nota']): ?>
                                                                    <div class="nota-valor">
                                                                        <i class="bi bi-star-fill"></i> Nota: <?php echo htmlspecialchars($actividad['nota']); ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <?php if ($actividad['comentario_inst']): ?>
                                                                    <div class="comentario-instructor">
                                                                        <i class="bi bi-chat-quote"></i> "<?php echo htmlspecialchars($actividad['comentario_inst']); ?>"
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <div class="empty-state">
                                            <span class="empty-icon"><i class="bi bi-journals"></i></span>
                                            <p>No hay actividades completadas</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-content" id="personas">
                        <div class="card full-width">
                            <div class="card-header">
                                <h3><span class="icon"><i class="bi bi-chat-fill"></i></span> Foros de discusión</h3>
                            </div>
                            <div class="card-content">
                                <div class="mb-4">
                                    <p>Participa en los foros de discusión para resolver dudas y compartir conocimientos con tus compañeros e instructores.</p>
                                </div>

                                <!-- Temas recientes -->
                                <h4 class="mb-3">Temas recientes</h4>
                                <?php if (count($temasRecientes) > 0): ?>
                                    <ul class="task-list">
                                        <?php foreach ($temasRecientes as $tema): ?>
                                            <li class="task-item" onclick="window.location.href='../foros/detalle_tema.php?id=<?php echo $tema['id_tema_foro']; ?>'">
                                                <div class="task-icon"><i class="bi bi-chat-dots"></i></div>
                                                <div class="task-info">
                                                    <p class="task-title"><?php echo htmlspecialchars($tema['titulo']); ?></p>
                                                    <p class="task-meta">
                                                        <strong><?php echo htmlspecialchars($tema['nombres'] . ' ' . $tema['apellidos']); ?></strong> •
                                                        <?php echo formatearFecha($tema['fecha_creacion']); ?>
                                                    </p>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <span class="empty-icon"><i class="bi bi-chat"></i></span>
                                        <p>No hay temas recientes</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../js/script.js"></script>

    <script>
        // Función para manejar la alerta de actividades vencidas
        function cerrarAlertaVencidas() {
            const alerta = document.getElementById('alertaVencidas');
            if (alerta) {
                alerta.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                alerta.style.opacity = '0';
                alerta.style.transform = 'translateY(-10px)';

                setTimeout(() => {
                    alerta.classList.add('hidden');
                }, 300);

                const fechaCierre = new Date().getTime();
                const claveAlerta = 'alertaVencidas_' + <?php echo $id_usuario_actual; ?> + '_' + <?php echo $idMateriaFicha; ?>;
                localStorage.setItem(claveAlerta, fechaCierre.toString());
            }
        }

        // Función corregida para volver a la clase
        function volverAClase() {
            window.location.href = '../index.php';
        }

        // Verificar si la alerta debe mostrarse al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            const alerta = document.getElementById('alertaVencidas');
            if (alerta) {
                const claveAlerta = 'alertaVencidas_' + <?php echo $id_usuario_actual; ?> + '_' + <?php echo $idMateriaFicha; ?>;
                const fechaCierre = localStorage.getItem(claveAlerta);

                if (fechaCierre) {
                    const fechaCierreMs = parseInt(fechaCierre);
                    const fechaActual = new Date().getTime();
                    const dosDiasEnMs = 2 * 24 * 60 * 60 * 1000;

                    if (fechaActual - fechaCierreMs < dosDiasEnMs) {
                        alerta.classList.add('hidden');
                    } else {
                        localStorage.removeItem(claveAlerta);
                    }
                }
            }
        });
    </script>
</body>

</html>
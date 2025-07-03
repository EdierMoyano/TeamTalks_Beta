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

// Obtener el ID del tema desde la URL
$id_tema = $_GET['id'] ?? null;

if (!$id_tema) {
    header('Location: foros.php');
    exit;
}

// Obtener información del tema
$tema = obtenerDetalleTema($id_tema);
if (!$tema) {
    header('Location: foros.php');
    exit;
}

// Obtener respuestas del tema con jerarquía
$respuestasData = obtenerRespuestasConJerarquia($id_tema);
$respuestasPrincipales = $respuestasData['principales'];
$respuestasHijas = $respuestasData['hijas'];

// Verificar si el usuario puede participar en este foro
$puedeParticipar = puedeParticiparForo($id_usuario_actual, $tema['id_materia_ficha']);

// Procesar la creación de una nueva respuesta
$mensaje = '';
$tipoMensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_respuesta']) && $puedeParticipar) {
    $descripcion = $_POST['descripcion'] ?? '';
    $id_respuesta_padre = !empty($_POST['id_respuesta_padre']) ? $_POST['id_respuesta_padre'] : null;

    if (empty($descripcion)) {
        $mensaje = 'El contenido de la respuesta es obligatorio';
        $tipoMensaje = 'danger';
    } else {
        $resultado = crearRespuestaAComentarioSesion($id_tema, $descripcion, $id_respuesta_padre);

        if ($resultado['success']) {
            $mensaje = 'Respuesta publicada exitosamente';
            $tipoMensaje = 'success';
            // Recargar respuestas
            $respuestasData = obtenerRespuestasConJerarquia($id_tema);
            $respuestasPrincipales = $respuestasData['principales'];
            $respuestasHijas = $respuestasData['hijas'];
        } else {
            $mensaje = $resultado['message'];
            $tipoMensaje = 'danger';
        }
    }
}

// Obtener información de la materia para el breadcrumb
$stmt = $pdo->prepare("
    SELECT m.materia, mf.id_materia_ficha
    FROM materia_ficha mf
    JOIN materias m ON mf.id_materia = m.id_materia
    WHERE mf.id_ficha = ?
    ORDER BY mf.id_materia_ficha ASC
    LIMIT 1
");
$stmt->execute([$tema['id_ficha']]);
$materiaPrincipalData = $stmt->fetch();
$materiaPrincipal = $materiaPrincipalData ? $materiaPrincipalData['materia'] : 'Sin materia asignada';
$idMateriaFicha = $materiaPrincipalData ? $materiaPrincipalData['id_materia_ficha'] : null;
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($tema['titulo']); ?> - TeamTalks</title>
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
            max-width: 1200px;
            margin: 0 auto;
            padding-left: 12px;
            padding-right: 12px;
        }

        .tema-header {
            background-color: #0E4A86;
            color: white;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 30px;
            font-size: 1rem;
        }

        .tema-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 15px;
        }

        .tema-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.2rem;
        }

        .tema-autor {
            font-weight: 600;
            margin-bottom: 2px;
        }

        .tema-fecha {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .respuesta-card {
            border-radius: 10px;
            margin-bottom: 12px;
            font-size: 0.97rem;
            overflow: hidden;
            transition: all 0.2s ease;
        }

        .respuesta-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .respuesta-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
        }

        .respuesta-avatar {
            width: 32px;
            height: 32px;
            font-size: 1rem;
            border-radius: 50%;
            background-color: #0E4A86;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .respuesta-meta {
            flex: 1;
        }

        .respuesta-autor {
            font-weight: 600;
            margin-bottom: 2px;
        }

        .respuesta-fecha {
            font-size: 0.85rem;
            color: #666;
        }

        .respuesta-content {
            padding: 12px 14px;
        }

        .respuesta-actions {
            padding: 7px 14px;
            gap: 6px;
            background-color: #f8f9fa;
            border-top: 1px solid #eee;
        }

        .btn-responder {
            background-color: #0E4A86 !important;
            color: #fff !important;
            border: none !important;
            border-radius: 6px !important;
            font-size: 0.97rem;
            padding: 7px 18px;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(14,74,134,0.08);
            transition: background 0.2s, box-shadow 0.2s;
            margin-right: 8px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-responder:hover, .btn-responder:focus {
            background-color: #1565c0 !important;
            color: #fff !important;
            box-shadow: 0 4px 16px rgba(21,101,192,0.12);
        }

        .btn-link, .btn.btn-link {
            color: #0E4A86 !important;
            background: none !important;
            border: none !important;
            text-decoration: underline !important;
            font-weight: 500;
            font-size: 0.97rem;
            border-radius: 6px;
            padding: 7px 12px;
            transition: background 0.2s, color 0.2s;
            margin-left: 0;
            margin-right: 8px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-link:hover, .btn-link:focus, .btn.btn-link:hover, .btn.btn-link:focus {
            background-color: #e3f2fd !important;
            color: #1565c0 !important;
            text-decoration: underline !important;
        }

        .btn.btn-primary, .btn.btn-primary:focus, .btn.btn-primary:active {
            background-color: #0E4A86 !important;
            border-color: #0E4A86 !important;
            color: #fff !important;
        }
        .btn.btn-primary:hover {
            background-color: #1565c0 !important;
            border-color: #1565c0 !important;
            color: #fff !important;
        }

        .btn.btn-secondary, .btn.btn-secondary:focus {
            border-radius: 6px !important;
            font-weight: 500;
            color: #0E4A86 !important;
            background: #e3f2fd !important;
            border: 1px solid #b6d4fe !important;
            transition: background 0.2s, color 0.2s;
        }
        .btn.btn-secondary:hover {
            background: #bbdefb !important;
            color: #1565c0 !important;
            border-color: #90caf9 !important;
        }

        .respuesta-hija {
            margin-left: 24px;
            margin-top: 8px;
            padding-left: 10px;
            border-left: 3px solid #e9ecef;
        }

        .respuesta-hija .respuesta-card {
            margin-bottom: 6px;
            padding: 8px 10px;
            font-size: 0.92rem;
            background: #f8f9fa;
            border-radius: 8px;
            box-shadow: none;
            border: 1px solid #e3e6ea;
        }

        .respuesta-hija .respuesta-header {
            padding: 6px 8px;
        }

        .respuesta-hija .respuesta-content {
            padding: 6px 8px;
        }

        .form-respuesta-inline {
            margin-top: 15px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            display: none;
        }

        .form-respuesta-inline.show {
            display: block;
            animation: slideDown 0.3s ease;
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

        .respuesta-form {
            background-color: white;
            border-radius: 12px;
            padding: 14px;
            margin-top: 18px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .respuesta-contador {
            font-size: 0.85rem;
            color: #666;
            margin-left: 5px;
        }

        textarea.form-control {
            font-size: 0.97rem;
            min-height: 60px;
            max-height: 180px;
        }

        .btn-responder,
        .btn.btn-primary,
        .breadcrumb-custom .breadcrumb-item a,
        .btn-link {
            color: #0E4A86 !important;
            border-color: #0E4A86 !important;
        }

        .btn-responder,
        .btn.btn-primary {
            background-color: #0E4A86 !important;
        }

        .btn-responder:hover,
        .btn.btn-primary:hover,
        .btn-link:hover {
            background-color: #1565c0 !important;
            color: #fff !important;
        }

        .btn-link {
            background: none !important;
            border: none !important;
            text-decoration: underline;
            padding: 0;
        }

        .breadcrumb-custom .breadcrumb-item a:hover {
            color: #1565c0 !important;
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
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-custom">
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)" onclick="volverAClase()">
                            <i class="fas fa-home"></i> <?php echo htmlspecialchars($materiaPrincipal); ?>
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="foros.php">Foros de discusión</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="temas_foro.php?id=<?php echo $tema['id_foro']; ?>">
                            <?php echo htmlspecialchars($tema['materia']); ?>
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?php echo htmlspecialchars($tema['titulo']); ?>
                    </li>
                </ol>
            </nav>

            <!-- Mensaje de resultado -->
            <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipoMensaje; ?> alert-dismissible fade show" role="alert">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Encabezado del tema -->
            <div class="tema-header">
                <h1 class="h2 mb-3"><?php echo htmlspecialchars($tema['titulo']); ?></h1>
                <?php if ($tema['descripcion']): ?>
                    <p class="mb-3"><?php echo nl2br(htmlspecialchars($tema['descripcion'])); ?></p>
                <?php endif; ?>
                <div class="tema-meta">
                    <div class="tema-avatar">
                        <?php echo obtenerIniciales($tema['nombres'] . ' ' . $tema['apellidos']); ?>
                    </div>
                    <div>
                        <p class="tema-autor"><?php echo htmlspecialchars($tema['nombres'] . ' ' . $tema['apellidos']); ?></p>
                        <p class="tema-fecha"><?php echo formatearFecha($tema['fecha_creacion']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Respuestas -->
            <h2 class="h4 mb-4">
                Respuestas (<?php echo count($respuestasPrincipales); ?>)
                <span class="respuesta-contador">
                    <?php
                    $totalRespuestas = count($respuestasPrincipales);
                    foreach ($respuestasHijas as $hijas) {
                        $totalRespuestas += count($hijas);
                    }
                    if ($totalRespuestas > count($respuestasPrincipales)) {
                        echo '• ' . $totalRespuestas . ' total';
                    }
                    ?>
                </span>
            </h2>

            <?php if (count($respuestasPrincipales) > 0): ?>
                <?php foreach ($respuestasPrincipales as $respuesta): ?>
                    <div class="card respuesta-card shadow-sm">
                        <div class="respuesta-header">
                            <div class="respuesta-avatar">
                                <?php echo obtenerIniciales($respuesta['nombres'] . ' ' . $respuesta['apellidos']); ?>
                            </div>
                            <div class="respuesta-meta">
                                <p class="respuesta-autor"><?php echo htmlspecialchars($respuesta['nombres'] . ' ' . $respuesta['apellidos']); ?></p>
                                <p class="respuesta-fecha"><?php echo formatearFecha($respuesta['fecha_respuesta']); ?></p>
                            </div>
                        </div>
                        <div class="respuesta-content">
                            <p><?php echo nl2br(htmlspecialchars($respuesta['descripcion'])); ?></p>
                        </div>

                        <?php if ($puedeParticipar): ?>
                            <div class="respuesta-actions">
                                <button class="btn btn-link btn-sm px-0" type="button"
                                    onclick="mostrarFormularioRespuesta(<?php echo $respuesta['id_respuesta_foro']; ?>)">
                                    <i class="bi bi-reply"></i> Responder
                                </button>
                                <?php if (isset($respuestasHijas[$respuesta['id_respuesta_foro']]) && count($respuestasHijas[$respuesta['id_respuesta_foro']]) > 0): ?>
                                    <button class="btn btn-link btn-sm px-0" type="button"
                                        onclick="toggleRespuestasHijas(<?php echo $respuesta['id_respuesta_foro']; ?>)">
                                        <i class="bi bi-chat-right-text"></i>
                                        Ver respuesta<?php echo count($respuestasHijas[$respuesta['id_respuesta_foro']]) > 1 ? 's' : ''; ?>
                                        (<?php echo count($respuestasHijas[$respuesta['id_respuesta_foro']]); ?>)
                                    </button>
                                    <div class="respuesta-hija" id="respuestas-hija-<?php echo $respuesta['id_respuesta_foro']; ?>" style="display:none;">
                                        <?php foreach ($respuestasHijas[$respuesta['id_respuesta_foro']] as $respuestaHija): ?>
                                            <div class="card respuesta-card respuesta-card-hija">
                                                <div class="respuesta-header">
                                                    <div class="respuesta-avatar">
                                                        <?php echo obtenerIniciales($respuestaHija['nombres'] . ' ' . $respuestaHija['apellidos']); ?>
                                                    </div>
                                                    <div class="respuesta-meta">
                                                        <p class="respuesta-autor"><?php echo htmlspecialchars($respuestaHija['nombres'] . ' ' . $respuestaHija['apellidos']); ?></p>
                                                        <p class="respuesta-fecha"><?php echo formatearFecha($respuestaHija['fecha_respuesta']); ?></p>
                                                    </div>
                                                </div>
                                                <div class="respuesta-content">
                                                    <p><?php echo nl2br(htmlspecialchars($respuestaHija['descripcion'])); ?></p>
                                                </div>
                                                <?php if ($puedeParticipar): ?>
                                                    <div class="respuesta-actions">
                                                        <button class="btn btn-link btn-sm px-0" type="button"
                                                            onclick="mostrarFormularioRespuesta(<?php echo $respuestaHija['id_respuesta_foro']; ?>)">
                                                            <i class="bi bi-reply"></i> Responder
                                                        </button>
                                                    </div>
                                                <?php endif; ?>
                                                <!-- Formulario de respuesta inline para respuesta hija -->
                                                <div class="form-respuesta-inline" id="form-respuesta-<?php echo $respuestaHija['id_respuesta_foro']; ?>">
                                                    <form method="POST" action="">
                                                        <input type="hidden" name="id_respuesta_padre" value="<?php echo $respuestaHija['id_respuesta_foro']; ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label">Responder a <?php echo htmlspecialchars($respuestaHija['nombres']); ?></label>
                                                            <textarea class="form-control" name="descripcion" rows="3" required placeholder="Escribe tu respuesta..."></textarea>
                                                        </div>
                                                        <div class="d-flex gap-2">
                                                            <button type="submit" name="crear_respuesta" class="btn btn-primary btn-sm">
                                                                <i class="bi bi-send"></i> Responder
                                                            </button>
                                                            <button type="button" class="btn btn-secondary btn-sm" onclick="ocultarFormularioRespuesta(<?php echo $respuestaHija['id_respuesta_foro']; ?>)">
                                                                Cancelar
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Formulario de respuesta inline -->
                        <div class="form-respuesta-inline" id="form-respuesta-<?php echo $respuesta['id_respuesta_foro']; ?>">
                            <form method="POST" action="">
                                <input type="hidden" name="id_respuesta_padre" value="<?php echo $respuesta['id_respuesta_foro']; ?>">
                                <div class="mb-3">
                                    <label class="form-label">Responder a <?php echo htmlspecialchars($respuesta['nombres']); ?></label>
                                    <textarea class="form-control" name="descripcion" rows="3" required placeholder="Escribe tu respuesta..."></textarea>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" name="crear_respuesta" class="btn btn-primary btn-sm">
                                        <i class="bi bi-send"></i> Responder
                                    </button>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="ocultarFormularioRespuesta(<?php echo $respuesta['id_respuesta_foro']; ?>)">
                                        Cancelar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    No hay respuestas en este tema. ¡Sé el primero en responder!
                </div>
            <?php endif; ?>

            <!-- Formulario para responder al tema principal -->
            <?php if ($puedeParticipar): ?>
                <div class="respuesta-form">
                    <h3 class="h5 mb-4">Responder al tema</h3>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Tu respuesta *</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="5" required></textarea>
                        </div>
                        <button type="submit" name="crear_respuesta" class="btn btn-primary">
                            Enviar respuesta
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="../js/script.js"></script>

    <script>
        // Función para mostrar formulario de respuesta
        function mostrarFormularioRespuesta(idRespuesta) {
            // Ocultar todos los formularios abiertos
            document.querySelectorAll('.form-respuesta-inline').forEach(form => {
                form.classList.remove('show');
            });

            // Mostrar el formulario específico
            const form = document.getElementById('form-respuesta-' + idRespuesta);
            if (form) {
                form.classList.add('show');
                // Enfocar el textarea
                const textarea = form.querySelector('textarea');
                if (textarea) {
                    setTimeout(() => textarea.focus(), 100);
                }
            }
        }

        // Función para ocultar formulario de respuesta
        function ocultarFormularioRespuesta(idRespuesta) {
            const form = document.getElementById('form-respuesta-' + idRespuesta);
            if (form) {
                form.classList.remove('show');
                // Limpiar el textarea
                const textarea = form.querySelector('textarea');
                if (textarea) {
                    textarea.value = '';
                }
            }
        }

        // Función corregida para volver a la clase
        function volverAClase() {
            <?php if ($idMateriaFicha): ?>
                window.location.href = `index.php?id_clase=<?php echo $idMateriaFicha; ?>`;
            <?php else: ?>
                window.location.href = '../index.php';
            <?php endif; ?>
        }

        // Cerrar formularios al hacer clic fuera
        document.addEventListener('click', function(e) {
            // Si NO se hizo clic dentro del formulario NI en un botón/link de responder
            if (
                !e.target.closest('.form-respuesta-inline') &&
                !e.target.closest('.btn-link')
            ) {
                document.querySelectorAll('.form-respuesta-inline.show').forEach(form => {
                    form.classList.remove('show');
                });
            }
        });

        // Función para mostrar/ocultar respuestas hijas
        function toggleRespuestasHijas(id) {
            const contenedor = document.getElementById('respuestas-hija-' + id);
            if (contenedor) {
                contenedor.style.display = contenedor.style.display === 'none' ? 'block' : 'none';
            }
        }
    </script>
</body>

</html>   
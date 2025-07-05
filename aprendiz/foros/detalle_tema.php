<?php
session_start();
require_once '../clase/functions.php';
if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['DOCUMENT_ROOT'], 'htdocs') !== false) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php';
} else {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';
}
// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['documento'])) {
    header('Location: ../login.php');
    exit;
}

// Marcar notificación como leída si viene por GET
if (isset($_GET['id_notificacion'])) {
    $id_notificacion = (int) $_GET['id_notificacion'];
    $id_usuario = $_SESSION['documento'] ?? null;

    if ($id_usuario && $id_notificacion) {
        $stmt = $conex->prepare("UPDATE notificaciones SET leido = 1 WHERE id_notificacion = ? AND id_usuario = ?");
        $stmt->execute([$id_notificacion, $id_usuario]);
    }
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

// Procesar envío de respuesta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_respuesta']) && $puedeParticipar) {
    $descripcion = $_POST['descripcion'] ?? '';
    $id_respuesta_padre = (!empty($_POST['id_respuesta_padre'])) ? (int) $_POST['id_respuesta_padre'] : null;

    if (empty($descripcion)) {
        $_SESSION['mensaje_foro'] = [
            'tipo' => 'danger',
            'texto' => 'El contenido de la respuesta es obligatorio'
        ];
    } else {
        $resultado = procesarRespuestaForo($id_tema, $descripcion, $id_respuesta_padre);
        if ($resultado['success']) {
            $_SESSION['mensaje_foro'] = [
                'tipo' => 'success',
                'texto' => $id_respuesta_padre
                    ? 'Respuesta a comentario publicada exitosamente'
                    : 'Respuesta al tema publicada exitosamente'
            ];
            header("Location: detalle_tema.php?id=" . $id_tema);
            exit;
        } else {
            $_SESSION['mensaje_foro'] = [
                'tipo' => 'danger',
                'texto' => $resultado['message']
            ];
        }
    }
}
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

        .tema-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }

        .tema-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .tema-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .autor-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .autor-avatar {
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .respuesta-form-principal {
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            border-left: 4px solid var(--primary-color);
        }

        .respuesta-form-principal h3 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .form-control {
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 1rem;
            font-size: 1rem;
            transition: all 0.2s ease;
            min-height: 120px;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(14, 74, 134, 0.1);
        }

        .btn-enviar-principal {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-enviar-principal:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(14, 74, 134, 0.2);
        }

        .respuestas-section {
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
        }

        .respuestas-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            background: linear-gradient(135deg, var(--background-color), #f1f5f9);
        }

        .respuestas-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }

        .respuesta-item {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            transition: all 0.2s ease;
        }

        .respuesta-item:last-child {
            border-bottom: none;
        }

        .respuesta-item:hover {
            background: linear-gradient(135deg, rgba(14, 74, 134, 0.02), rgba(14, 74, 134, 0.01));
        }

        .respuesta-header {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .respuesta-avatar {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.875rem;
            flex-shrink: 0;
        }

        .respuesta-autor-info {
            flex: 1;
        }

        .respuesta-autor {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .respuesta-fecha {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .respuesta-content {
            margin-left: 3rem;
            color: var(--text-primary);
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .respuesta-actions {
            margin-left: 3rem;
            padding-top: 0.75rem;
            border-top: 1px solid var(--border-color);
        }

        .btn-responder {
            background: none;
            border: none;
            color: var(--primary-color);
            font-size: 0.875rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            transition: all 0.2s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .btn-responder:hover {
            background: rgba(14, 74, 134, 0.1);
            color: var(--primary-hover);
        }

        .form-respuesta-inline {
            margin-top: 1rem;
            margin-left: 3rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
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

        .respuesta-hija {
            margin-left: 3rem;
            margin-top: 1rem;
            padding-left: 1rem;
            border-left: 3px solid var(--primary-color);
            background: rgba(14, 74, 134, 0.02);
            border-radius: 0 8px 8px 0;
        }

        .respuesta-hija .respuesta-item {
            background: rgba(255, 255, 255, 0.8);
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid rgba(14, 74, 134, 0.1);
        }

        .respuesta-hija .respuesta-item:hover {
            background: rgba(255, 255, 255, 0.95);
            border-color: rgba(14, 74, 134, 0.2);
        }

        .btn-toggle-respuestas {
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            color: var(--primary-color);
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-left: 3rem;
            margin-top: 1rem;
        }

        .btn-toggle-respuestas:hover {
            background: rgba(14, 74, 134, 0.1);
            border-color: var(--primary-color);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(14, 74, 134, 0.15);
        }

        .btn.btn-primary {
            background: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
            color: white !important;
        }

        .btn.btn-primary:hover {
            background: var(--primary-hover) !important;
            border-color: var(--primary-hover) !important;
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

            .tema-title {
                font-size: 1.5rem;
            }

            .respuesta-content,
            .respuesta-actions,
            .form-respuesta-inline,
            .btn-toggle-respuestas {
                margin-left: 0;
            }

            .respuesta-hija {
                margin-left: 1rem;
                padding-left: 0.75rem;
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
                        <a href="temas_foro.php?id_foro=<?php echo $tema['id_foro']; ?>">
                            <i class="bi bi-arrow-left me-1"></i>Volver al Foro
                        </a>
                    </li>
                </ol>
            </nav>

            <!-- Mostrar mensajes de éxito o error -->
            <?php if (isset($_SESSION['mensaje_foro'])): ?>
                <div class="alert alert-<?php echo $_SESSION['mensaje_foro']['tipo']; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['mensaje_foro']['texto']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
                <?php unset($_SESSION['mensaje_foro']); ?>
            <?php endif; ?>



            <!-- Encabezado del tema -->
            <div class="tema-header">
                <h1 class="tema-title"><?php echo htmlspecialchars($tema['titulo']); ?></h1>
                <?php if (!empty($tema['descripcion'])): ?>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($tema['descripcion'])); ?></p>
                <?php endif; ?>
                <div class="tema-meta">
                    <div class="autor-info">
                        <div class="autor-avatar">
                            <?php echo obtenerIniciales($tema['nombres'] . ' ' . $tema['apellidos']); ?>
                        </div>
                        <div>
                            <div class="respuesta-autor"><?php echo htmlspecialchars($tema['nombres'] . ' ' . $tema['apellidos']); ?></div>
                            <div class="respuesta-fecha"><?php echo formatearFecha($tema['fecha_creacion']); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección de respuestas -->
            <div class="respuestas-section">
                <div class="respuestas-header">
                    <h3 class="respuestas-title">
                        <i class="bi bi-chat-text me-2"></i>
                        Respuestas (<?php echo count($respuestasPrincipales); ?>)
                    </h3>
                </div>

                <?php if (count($respuestasPrincipales) > 0): ?>
                    <?php foreach ($respuestasPrincipales as $respuesta): ?>
                        <div class="respuesta-item">
                            <div class="respuesta-header">
                                <div class="respuesta-avatar">
                                    <?php echo obtenerIniciales($respuesta['nombres'] . ' ' . $respuesta['apellidos']); ?>
                                </div>
                                <div class="respuesta-autor-info">
                                    <div class="respuesta-autor"><?php echo htmlspecialchars($respuesta['nombres'] . ' ' . $respuesta['apellidos']); ?></div>
                                    <div class="respuesta-fecha"><?php echo formatearFecha($respuesta['fecha_respuesta']); ?></div>
                                </div>
                            </div>

                            <div class="respuesta-content">
                                <?php echo nl2br(htmlspecialchars($respuesta['descripcion'])); ?>
                            </div>

                            <?php if ($puedeParticipar): ?>
                                <div class="respuesta-actions">
                                    <button class="btn-responder" onclick="mostrarFormularioRespuesta(<?php echo $respuesta['id_respuesta_foro']; ?>)">
                                        <i class="bi bi-reply"></i> Responder
                                    </button>
                                </div>
                            <?php endif; ?>

                            <!-- Formulario de respuesta inline -->
                            <div class="form-respuesta-inline" id="form-respuesta-<?php echo $respuesta['id_respuesta_foro']; ?>">
                                <form method="POST" action="">
                                    <input type="hidden" name="crear_respuesta" value="1">
                                    <input type="hidden" name="id_respuesta_padre" value="<?php echo $respuesta['id_respuesta_foro']; ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Responder a <?php echo htmlspecialchars($respuesta['nombres']); ?></label>
                                        <textarea class="form-control" name="descripcion" rows="3" required
                                            placeholder="Escribe tu respuesta..."></textarea>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="bi bi-send"></i> Responder
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-sm"
                                            onclick="ocultarFormularioRespuesta(<?php echo $respuesta['id_respuesta_foro']; ?>)">
                                            Cancelar
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Respuestas hijas -->
                            <?php if (isset($respuestasHijas[$respuesta['id_respuesta_foro']])): ?>
                                <button class="btn-toggle-respuestas" onclick="toggleRespuestasHijas(<?php echo $respuesta['id_respuesta_foro']; ?>)">
                                    <i class="bi bi-chevron-down"></i>
                                    Ver <?php echo count($respuestasHijas[$respuesta['id_respuesta_foro']]); ?> respuesta<?php echo count($respuestasHijas[$respuesta['id_respuesta_foro']]) > 1 ? 's' : ''; ?>
                                </button>

                                <div class="respuesta-hija" id="respuestas-hija-<?php echo $respuesta['id_respuesta_foro']; ?>" style="display:none;">
                                    <?php foreach ($respuestasHijas[$respuesta['id_respuesta_foro']] as $respuestaHija): ?>
                                        <div class="respuesta-item">
                                            <div class="respuesta-header">
                                                <div class="respuesta-avatar">
                                                    <?php echo obtenerIniciales($respuestaHija['nombres'] . ' ' . $respuestaHija['apellidos']); ?>
                                                </div>
                                                <div class="respuesta-autor-info">
                                                    <div class="respuesta-autor"><?php echo htmlspecialchars($respuestaHija['nombres'] . ' ' . $respuestaHija['apellidos']); ?></div>
                                                    <div class="respuesta-fecha"><?php echo formatearFecha($respuestaHija['fecha_respuesta']); ?></div>
                                                </div>
                                            </div>
                                            <div class="respuesta-content">
                                                <?php echo nl2br(htmlspecialchars($respuestaHija['descripcion'])); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="bi bi-chat-slash"></i>
                        </div>
                        <h4>No hay respuestas aún</h4>
                        <p>Sé el primero en participar en esta discusión</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- FORMULARIO PRINCIPAL MOVIDO AL FINAL - Respuesta directa al tema -->
            <?php if ($puedeParticipar): ?>
                <div class="respuesta-form-principal">
                    <h3><i class="bi bi-chat-text me-2"></i>Responder al Tema</h3>
                    <form method="POST" action="">
                        <input type="hidden" name="crear_respuesta" value="1">
                        <!-- IMPORTANTE: Campo vacío para respuesta directa al tema -->
                        <input type="hidden" name="id_respuesta_padre" value="">

                        <div class="mb-3">
                            <label for="descripcion_principal" class="form-label">Tu respuesta al tema *</label>
                            <textarea class="form-control" id="descripcion_principal" name="descripcion" rows="4"
                                required placeholder="Comparte tu opinión, experiencia o pregunta sobre este tema..."></textarea>
                        </div>
                        <button type="submit" class="btn-enviar-principal">
                            <i class="bi bi-send"></i> Publicar Respuesta
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

        // Función para mostrar/ocultar respuestas hijas
        function toggleRespuestasHijas(id) {
            const contenedor = document.getElementById('respuestas-hija-' + id);
            const boton = document.querySelector(`[onclick="toggleRespuestasHijas(${id})"]`);

            if (contenedor && boton) {
                const icono = boton.querySelector('i');
                if (contenedor.style.display === 'none' || !contenedor.style.display) {
                    contenedor.style.display = 'block';
                    icono.className = 'bi bi-chevron-up';
                    boton.innerHTML = boton.innerHTML.replace('Ver', 'Ocultar');
                } else {
                    contenedor.style.display = 'none';
                    icono.className = 'bi bi-chevron-down';
                    boton.innerHTML = boton.innerHTML.replace('Ocultar', 'Ver');
                }
            }
        }

        // Cerrar formularios al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.form-respuesta-inline') && !e.target.closest('.btn-responder')) {
                document.querySelectorAll('.form-respuesta-inline.show').forEach(form => {
                    form.classList.remove('show');
                });
            }
        });

        // Auto-resize textareas
        document.querySelectorAll('textarea').forEach(textarea => {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = this.scrollHeight + 'px';
            });
        });
    </script>
</body>

</html>
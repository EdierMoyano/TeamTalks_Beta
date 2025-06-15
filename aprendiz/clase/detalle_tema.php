<?php
session_start();
require_once 'functions.php';


// Simulamos un usuario logueado (en producción esto vendría de la sesión)
$id_usuario_actual = 1107977746;

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

// Obtener respuestas del tema
$respuestas = obtenerRespuestasTema($id_tema);

// Verificar si el usuario puede participar en este foro
$puedeParticipar = puedeParticiparForo($id_usuario_actual, $tema['id_materia_ficha']);

// Procesar la creación de una nueva respuesta
$mensaje = '';
$tipoMensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_respuesta']) && $puedeParticipar) {
    $descripcion = $_POST['descripcion'] ?? '';

    if (empty($descripcion)) {
        $mensaje = 'El contenido de la respuesta es obligatorio';
        $tipoMensaje = 'danger';
    } else {
        $resultado = crearRespuestaForo($id_tema, $descripcion, $id_usuario_actual);

        if ($resultado['success']) {
            $mensaje = 'Respuesta publicada exitosamente';
            $tipoMensaje = 'success';
            // Recargar las respuestas para mostrar la nueva
            $respuestas = obtenerRespuestasTema($id_tema);
        } else {
            $mensaje = $resultado['message'];
            $tipoMensaje = 'danger';
        }
    }
}

// Obtener información de la materia para el breadcrumb
$materiaPrincipalData = obtenerMateriaPrincipal($tema['id_ficha']);
$materiaPrincipal = $materiaPrincipalData ? $materiaPrincipalData['materia'] : 'Sin materia asignada';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($tema['titulo']); ?> - TeamTalks</title>
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


    <link rel="stylesheet" href="../css/styles.css">

    <style>
        body.sidebar-collapsed .main-content {
            margin-left: 100px;
        }

        .main-content {
            padding: 20px;
        }

        .tema-header {
            background-color: #0E4A86;
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
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
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .respuesta-header {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 20px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #eee;
        }

        .respuesta-avatar {
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
            padding: 20px;
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
            padding: 20px;
            margin-top: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
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
                        <a href="index.php">
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
            <h2 class="h4 mb-4">Respuestas (<?php echo count($respuestas); ?>)</h2>

            <?php if (count($respuestas) > 0): ?>
                <?php foreach ($respuestas as $respuesta): ?>
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
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    No hay respuestas en este tema. ¡Sé el primero en responder!
                </div>
            <?php endif; ?>

            <!-- Formulario para responder -->
            <?php if ($puedeParticipar): ?>
                <div class="respuesta-form">
                    <h3 class="h5 mb-4">Responder al tema</h3>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Tu respuesta *</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="5" required></textarea>
                        </div>
                        <button type="submit" name="crear_respuesta" class="btn btn-primary">
                            <i class="bi bi-send"></i> Publicar respuesta
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="../js/script.js"></script>
</body>

</html>
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
$id_foro = $_GET['id'] ?? null;

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

// Obtener temas del foro
$temas = obtenerTemasForo($id_foro);

// Verificar si el usuario puede participar en este foro
$puedeParticipar = puedeParticiparForo($id_usuario_actual, $foro['id_materia_ficha']);

// Verificar si el usuario es instructor
$esInstructor = esInstructorMateriaFicha($id_usuario_actual, $foro['id_materia_ficha']);

// Procesar la creación de un nuevo tema (solo para instructores)
$mensaje = '';
$tipoMensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_tema']) && $esInstructor) {
    $titulo = $_POST['titulo'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';

    if (empty($titulo)) {
        $mensaje = 'El título del tema es obligatorio';
        $tipoMensaje = 'danger';
    } else {
        $resultado = crearTemaForoSesion($id_foro, $titulo, $descripcion);

        if ($resultado['success']) {
            $mensaje = 'Tema creado exitosamente';
            $tipoMensaje = 'success';
            $temas = obtenerTemasForo($id_foro);
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
$stmt->execute([$foro['id_ficha']]);
$materiaPrincipalData = $stmt->fetch();
$materiaPrincipal = $materiaPrincipalData ? $materiaPrincipalData['materia'] : 'Sin materia asignada';
$idMateriaFicha = $materiaPrincipalData ? $materiaPrincipalData['id_materia_ficha'] : null;
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Temas del Foro - TeamTalks</title>
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

        .tema-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .tema-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .tema-header {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
        }

        .tema-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #0E4A86;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.2rem;
        }

        .tema-meta {
            flex: 1;
        }

        .tema-autor {
            font-weight: 600;
            margin-bottom: 2px;
        }

        .tema-fecha {
            font-size: 0.9rem;
            color: #666;
        }

        .tema-content {
            padding: 20px;
        }

        .tema-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .tema-description {
            color: #555;
            margin-bottom: 15px;
        }

        .tema-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background-color: #f8f9fa;
            border-top: 1px solid #eee;
        }

        .tema-stats {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #666;
        }

        .foro-header {
            background-color: #0E4A86;
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
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

        .btn-azul-custom {
            background-color: #0E4A86 !important;
            border-color: #0E4A86 !important;
            color: #fff !important;
            transition: background 0.2s, color 0.2s;
        }

        .btn-azul-custom:hover,
        .btn-azul-custom:focus {
            background-color: #08325a !important;
            border-color: #08325a !important;
            color: #fff !important;
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

            <!-- Encabezado del foro -->
            <div class="foro-header">
                <h1 class="h2 mb-3">Foro: <?php echo htmlspecialchars($foro['materia']); ?></h1>
                <p class="mb-0">
                    <i class="fas fa-user-tie"></i>
                    Instructor: <?php echo htmlspecialchars($foro['nombres'] . ' ' . $foro['apellidos']); ?>
                </p>
            </div>

            <!-- Mensaje de resultado -->
            <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipoMensaje; ?> alert-dismissible fade show" role="alert">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Botón para crear nuevo tema (solo para instructores) -->
            <?php if ($esInstructor): ?>
                <div class="mb-4">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearTema">
                        <i class="bi bi-plus-circle"></i> Crear nuevo tema
                    </button>
                </div>
            <?php endif; ?>

            <!-- Lista de temas -->
            <div class="row">
                <div class="col-12">
                    <?php if (count($temas) > 0): ?>
                        <?php foreach ($temas as $tema): ?>
                            <div class="card tema-card shadow-sm">
                                <div class="tema-header">
                                    <div class="tema-avatar">
                                        <?php echo obtenerIniciales($tema['nombres'] . ' ' . $tema['apellidos']); ?>
                                    </div>
                                    <div class="tema-meta">
                                        <p class="tema-autor"><?php echo htmlspecialchars($tema['nombres'] . ' ' . $tema['apellidos']); ?></p>
                                        <p class="tema-fecha"><?php echo formatearFecha($tema['fecha_creacion']); ?></p>
                                    </div>
                                </div>
                                <div class="tema-content">
                                    <h3 class="tema-title"><?php echo htmlspecialchars($tema['titulo']); ?></h3>
                                    <?php if ($tema['descripcion']): ?>
                                        <p class="tema-description"><?php echo nl2br(htmlspecialchars($tema['descripcion'])); ?></p>
                                    <?php endif; ?>
                                    <a href="detalle_tema.php?id=<?php echo $tema['id_tema_foro']; ?>" class="btn btn-outline-primary btn-azul-custom">
                                        <i class="bi bi-chat-text"></i> Ver discusión
                                    </a>
                                </div>
                                <div class="tema-footer">
                                    <div class="tema-stats">
                                        <div class="stat-item">
                                            <i class="bi bi-chat"></i>
                                            <span><?php echo $tema['cantidad_respuestas']; ?> respuestas</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            No hay temas en este foro.
                            <?php if ($esInstructor): ?>
                                ¡Sé el primero en crear uno!
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal para crear nuevo tema (solo para instructores) -->
    <?php if ($esInstructor): ?>
        <div class="modal fade" id="modalCrearTema" tabindex="-1" aria-labelledby="modalCrearTemaLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalCrearTemaLabel">Crear nuevo tema</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="titulo" class="form-label">Título del tema *</label>
                                <input type="text" class="form-control" id="titulo" name="titulo" required>
                            </div>
                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción (opcional)</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="5"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" name="crear_tema" class="btn btn-primary">Crear tema</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script src="../js/script.js"></script>

    <script>
        // Función corregida para volver a la clase
        function volverAClase() {
            <?php if ($idMateriaFicha): ?>
                window.location.href = `index.php?id_clase=<?php echo $idMateriaFicha; ?>`;
            <?php else: ?>
                window.location.href = '../index.php';
            <?php endif; ?>
        }
    </script>
</body>

</html>
<?php
session_start();
require_once 'functions.php';

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

// Obtener el ID de la actividad desde la URL
$id_actividad = $_GET['id'] ?? null;

if (!$id_actividad) {
    header('Location: index.php');
    exit;
}

// Obtener información de la actividad
$actividad = obtenerDetalleActividad($id_actividad);
if (!$actividad) {
    die("Error: La actividad no existe.");
}

// Verificar si el usuario tiene acceso a esta actividad
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count
    FROM user_ficha uf
    WHERE uf.id_user = ? AND uf.id_ficha = ?
");
$stmt->execute([$id_usuario_actual, $actividad['id_ficha']]);
$result = $stmt->fetch();

if (!isset($result['count']) || intval($result['count']) === 0) {
    die("Error: No tienes acceso a esta actividad.");
}

// Verificar si ya existe una entrega
$entregaExistente = obtenerEntregaUsuario($id_actividad, $id_usuario_actual);

// Verificar si la actividad está vencida
$actividadVencida = actividadEstaVencida($id_actividad);

// Obtener información de la materia para el breadcrumb
$materiaPrincipalData = obtenerMateriaPrincipal($actividad['id_ficha']);
$materiaPrincipal = $materiaPrincipalData ? $materiaPrincipalData['materia'] : 'Sin materia asignada';

// Función auxiliar para formatear tamaño de archivos
function formatFileSize($bytes)
{
    if ($bytes === 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($actividad['titulo']); ?> - TeamTalks</title>
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
    <link rel="icon" href="../../assets/img/icon2.png">

    <link rel="stylesheet" href="../css/styles.css">


</head>

<body class="sidebar-collapsed">

    <!-- Header -->
    <?php include '../../includes/design/header.php'; ?>

    <!-- Sidebar -->
    <?php include '../../includes/design/sidebar.php'; ?>

    <!-- Contenido principal -->
    <main class="main-content">
        <div class="container-fluid"
            data-id-clase="<?php echo $actividad['id_materia_ficha']; ?>"
            data-id-actividad="<?php echo $id_actividad; ?>"
            data-id-usuario="<?php echo $id_usuario_actual; ?>">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-custom">
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)" onclick="volverAClase()">
                            <i class="fas fa-home"></i> <?php echo htmlspecialchars($materiaPrincipal); ?>
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="index.php?id_clase=<?php echo $actividad['id_materia_ficha']; ?>">Trabajo en clase</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?php echo htmlspecialchars($actividad['titulo']); ?>
                    </li>
                </ol>
            </nav>

            <!-- Encabezado de la actividad -->
            <div class="actividad-header">
                <h1 class="h2 mb-3"><?php echo htmlspecialchars($actividad['titulo']); ?></h1>
                <?php if ($actividad['descripcion']): ?>
                    <p class="mb-3"><?php echo nl2br(htmlspecialchars($actividad['descripcion'])); ?></p>
                <?php endif; ?>
                <div class="actividad-meta">
                    <div class="meta-item">
                        <i class="fas fa-book"></i>
                        <span><?php echo htmlspecialchars($actividad['materia']); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-user-tie"></i>
                        <span><?php echo htmlspecialchars($actividad['instructor_nombres'] . ' ' . $actividad['instructor_apellidos']); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Entrega: <?php echo date('d/m/Y H:i', strtotime($actividad['fecha_entrega'])); ?></span>
                    </div>
                    <?php if ($actividadVencida): ?>
                        <div class="meta-item" style="background: rgba(220, 53, 69, 0.2);">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>Vencida</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Archivos de apoyo del instructor -->
            <?php
            $archivosInstructor = [
                $actividad['archivo_instructor_1'],
                $actividad['archivo_instructor_2'],
                $actividad['archivo_instructor_3']
            ];
            $tieneArchivosInstructor = false;
            foreach ($archivosInstructor as $archivo) {
                if (!empty($archivo)) {
                    // Buscar el archivo en la ruta correcta desde aprendiz/clase/
                    if (file_exists('../../uploads/' . $archivo)) {
                        $tieneArchivosInstructor = true;
                        break;
                    }
                }
            }
            ?>

            <?php if ($tieneArchivosInstructor): ?>
                <div class="archivos-instructor-section-compact">
                    <h4> Material de trabajo</h4>
                    <div class="archivos-instructor-list">
                        <?php foreach ($archivosInstructor as $index => $archivo): ?>
                            <?php if (!empty($archivo) && file_exists('../../uploads/' . $archivo)): ?>
                                <?php
                                $rutaCompleta = '../../uploads/' . $archivo;
                                $nombreArchivo = basename($archivo);
                                $extension = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
                                $tamanoArchivo = filesize($rutaCompleta);

                                // Determinar icono según extensión
                                $iconClass = "fas fa-file";
                                if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                                    $iconClass = "fas fa-file-image";
                                } elseif ($extension === 'pdf') {
                                    $iconClass = "fas fa-file-pdf";
                                } elseif (in_array($extension, ['doc', 'docx'])) {
                                    $iconClass = "fas fa-file-word";
                                } elseif (in_array($extension, ['zip', 'rar'])) {
                                    $iconClass = "fas fa-file-archive";
                                }
                                ?>

                                <div class="archivo-instructor-compact">
                                    <div class="archivo-icon-compact">
                                        <i class="<?php echo $iconClass; ?>"></i>
                                    </div>
                                    <div class="archivo-info-compact">
                                        <span class="archivo-nombre-compact"><?php echo htmlspecialchars($nombreArchivo); ?></span>
                                        <small class="archivo-tamano-compact"><?php echo formatFileSize($tamanoArchivo); ?></small>
                                    </div>
                                    <div class="archivo-acciones-compact">
                                        <a href="<?php echo $rutaCompleta; ?>" class="btn-compact btn-ver" target="_blank" title="Ver archivo">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?php echo $rutaCompleta; ?>" class="btn-compact btn-descargar" download title="Descargar archivo">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Contenido de la actividad -->
            <?php if ($entregaExistente): ?>
                <!-- Mostrar entrega existente -->
                <div class="entrega-existente">
                    <h3><i class="fas fa-check-circle"></i> Actividad entregada</h3>
                    <p><strong>Fecha de entrega:</strong> <?php echo date('d/m/Y H:i', strtotime($entregaExistente['fecha_entrega'])); ?></p>

                    <?php if ($entregaExistente['contenido']): ?>
                        <p><strong>Contenido:</strong></p>
                        <div class="bg-white p-3 rounded">
                            <?php echo nl2br(htmlspecialchars($entregaExistente['contenido'])); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($entregaExistente['archivo1'] || $entregaExistente['archivo2'] || $entregaExistente['archivo3']): ?>
                        <p><strong>Archivos entregados:</strong></p>
                        <div>
                            <?php
                            $archivos = [$entregaExistente['archivo1'], $entregaExistente['archivo2'], $entregaExistente['archivo3']];
                            foreach ($archivos as $archivo):
                                if ($archivo && file_exists($archivo)):
                                    $nombreArchivo = basename($archivo);
                            ?>
                                    <a href="<?php echo $archivo; ?>" class="archivo-descarga" target="_blank">
                                        <i class="fas fa-download"></i> <?php echo htmlspecialchars($nombreArchivo); ?>
                                    </a>
                            <?php
                                endif;
                            endforeach;
                            ?>
                        </div>
                    <?php endif; ?>

                    <!-- Mostrar nota y comentario del instructor si existen -->
                    <?php if ($entregaExistente['nota'] || $entregaExistente['comentario_inst']): ?>
                        <div class="mt-3 p-3 bg-light rounded">
                            <?php if ($entregaExistente['nota']): ?>
                                <p><strong><i class="fas fa-star"></i> Nota:</strong> <?php echo htmlspecialchars($entregaExistente['nota']); ?></p>
                            <?php endif; ?>
                            <?php if ($entregaExistente['comentario_inst']): ?>
                                <p><strong><i class="fas fa-comment"></i> Comentario del instructor:</strong></p>
                                <p class="fst-italic">"<?php echo nl2br(htmlspecialchars($entregaExistente['comentario_inst'])); ?>"</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!$actividadVencida): ?>
                        <div class="btn-group-custom">
                            <button type="button" class="btn btn-warning" id="btn-cancelar">
                                <i class="fas fa-times"></i> Cancelar entrega
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php elseif ($actividadVencida): ?>
                <!-- Actividad vencida sin entrega -->
                <div class="actividad-vencida">
                    <h3><i class="fas fa-exclamation-triangle"></i> Actividad vencida</h3>
                    <p>Esta actividad ya ha vencido y no se pueden realizar entregas.</p>
                </div>
            <?php else: ?>
                <!-- Formulario de entrega -->
                <div class="entrega-section">
                    <h3> Entregar actividad</h3>

                    <form id="form-entrega" enctype="multipart/form-data">
                        <input type="hidden" name="id_actividad" value="<?php echo $id_actividad; ?>">
                        <input type="hidden" name="id_usuario" value="<?php echo $id_usuario_actual; ?>">

                        <div class="mb-3">
                            <label for="contenido" class="form-label">Contenido de la entrega (opcional)</label>
                            <textarea class="form-control" id="contenido" name="contenido" rows="4"
                                placeholder="Escribe aquí cualquier comentario o explicación sobre tu entrega..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="archivos" class="form-label">
                                <strong>Seleccionar archivos (máximo 3 archivos, 10MB cada uno)</strong>
                            </label>
                            <div class="input-group">
                                <input type="file"
                                    class="form-control"
                                    id="archivos"
                                    name="archivos[]"
                                    multiple
                                    accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.gif,.zip,.rar">
                                <button type="button" class="btn btn-outline-primary btn-azul-custom" onclick="document.getElementById('archivos').click()">
                                    <i class="fas fa-folder-open"></i> Seleccionar archivos
                                </button>
                            </div>
                            <small class="form-text text-muted">Puedes seleccionar hasta 3 archivos a la vez</small>
                        </div>

                        <div id="preview-archivos"></div>

                        <div class="btn-group-custom">
                            <button type="button" class="btn btn-success" id="btn-entregar" style="display: none;">
                                <i class="fas fa-paper-plane"></i> Entregar Actividad
                            </button>
                            <button type="button" class="btn btn-secondary btn-azul-custom" onclick="volverAClase()">
                                <i class="fas fa-arrow-left"></i> Volver
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="../js/script.js"></script>
    <script src="../js/detalle_actividad.js"></script>
</body>

</html>
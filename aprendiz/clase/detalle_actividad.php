<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['documento'])) {
    header('Location: index.php');
    exit;
}

$id_usuario_actual = $_SESSION['documento'];

// Obtener el ID de la actividad desde la URL
$id_actividad = $_GET['id'] ?? null;

if (!$id_actividad) {
    header('Location: index.php');
    exit;
}

// Obtener información de la actividad
$actividad = obtenerDetalleActividad($id_actividad);
if (!$actividad) {
    header('Location: index.php');
    exit;
}

// Verificar si el usuario ya entregó esta actividad
$entregaExistente = obtenerEntregaUsuario($id_actividad, $id_usuario_actual);
$yaEntregada = $entregaExistente !== false;

// Verificar si la actividad está vencida
$actividadVencida = actividadEstaVencida($id_actividad);

// Verificar si se puede cancelar la entrega (solo si no ha pasado la fecha límite)
$puedeCancel = $yaEntregada && !$actividadVencida;

// Obtener información de la ficha para el breadcrumb
$ficha = obtenerFicha($actividad['id_ficha']);
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

    <link rel="stylesheet" href="../css/styles.css">
    <style>
        body.sidebar-collapsed .main-content {
            margin-left: 100px;
        }

        .main-content {
            padding: 20px;
        }

        .actividad-header {
            background-color: #0E4A86;
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .actividad-info {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .entrega-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .estado-entrega {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .estado-entregada {
            background-color: #d1fae5;
            color: #065f46;
        }

        .estado-pendiente {
            background-color: #fef3c7;
            color: #d97706;
        }

        .estado-vencida {
            background-color: #fee2e2;
            color: #dc2626;
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

        .archivo-preview {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            background-color: #f8f9fa;
            border-radius: 6px;
            margin-bottom: 8px;
        }

        .archivo-icon {
            font-size: 1.2rem;
            color: #6c757d;
        }

        .archivo-info {
            flex: 1;
        }

        .archivo-nombre {
            font-weight: 500;
            font-size: 0.9rem;
        }

        .archivo-tamano {
            font-size: 0.8rem;
            color: #6c757d;
        }

        .btn-eliminar {
            background: none;
            border: none;
            color: #dc3545;
            padding: 4px;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-eliminar:hover {
            background-color: #f8d7da;
        }

        /* Estilos para notificaciones */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 9999;
            min-width: 300px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateX(400px);
            transition: transform 0.3s ease;
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification.success {
            background-color: #28a745;
        }

        .notification.error {
            background-color: #dc3545;
        }

        .notification.info {
            background-color: #17a2b8;
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
                            <i class="fas fa-home"></i> <?php echo htmlspecialchars($actividad['materia']); ?>
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?php echo htmlspecialchars($actividad['titulo']); ?>
                    </li>
                </ol>
            </nav>

            <!-- Header de la actividad -->
            <div class="actividad-header">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h1 class="mb-2"><?php echo htmlspecialchars($actividad['titulo']); ?></h1>
                        <p class="mb-1">
                            <i class="fas fa-user-tie"></i>
                            <?php echo htmlspecialchars($actividad['instructor_nombres'] . ' ' . $actividad['instructor_apellidos']); ?>
                        </p>
                        <p class="mb-0">
                            <i class="fas fa-calendar-alt"></i>
                            Fecha de entrega: <?php echo formatearFecha($actividad['fecha_entrega']); ?>
                        </p>
                    </div>
                    <div>
                        <?php if ($yaEntregada): ?>
                            <span class="estado-entrega estado-entregada">
                                <i class="fas fa-check-circle"></i> Entregada
                            </span>
                        <?php elseif ($actividadVencida): ?>
                            <span class="estado-entrega estado-vencida">
                                <i class="fas fa-exclamation-triangle"></i> Vencida
                            </span>
                        <?php else: ?>
                            <span class="estado-entrega estado-pendiente">
                                <i class="fas fa-clock"></i> Pendiente
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Información de la actividad -->
                <div class="col-lg-8">
                    <div class="actividad-info">
                        <h3 class="mb-3">
                            <i class="fas fa-info-circle text-black"></i>
                            Descripción de la actividad
                        </h3>

                        <?php if ($actividad['descripcion']): ?>
                            <div class="mb-4">
                                <p><?php echo nl2br(htmlspecialchars($actividad['descripcion'])); ?></p>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No hay descripción disponible para esta actividad.</p>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-6">
                                <h5><i class="fas fa-book text-black"></i> Materia</h5>
                                <p><?php echo htmlspecialchars($actividad['materia']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h5><i class="fas fa-calendar text-black"></i> Fecha límite</h5>
                                <p><?php echo date('d/m/Y H:i', strtotime($actividad['fecha_entrega'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sección de entrega -->
                <div class="col-lg-4">
                    <div class="entrega-section">
                        <?php if ($yaEntregada): ?>
                            <!-- Mostrar información de la entrega existente -->
                            <h4 class="mb-3 text-success">
                                <i class="fas fa-check-circle"></i>
                                Actividad Entregada
                            </h4>

                            <div class="alert alert-success">
                                <h6><i class="fas fa-calendar-check"></i> Fecha de entrega:</h6>
                                <p class="mb-2"><?php echo date('d/m/Y H:i', strtotime($entregaExistente['fecha_entrega'])); ?></p>

                                <?php if ($entregaExistente['contenido']): ?>
                                    <h6><i class="fas fa-comment"></i> Comentarios:</h6>
                                    <div class="bg-white p-3 rounded border mb-2">
                                        <?php echo nl2br(htmlspecialchars($entregaExistente['contenido'])); ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (isset($entregaExistente['archivo']) && $entregaExistente['archivo']): ?>
                                    <h6><i class="fas fa-paperclip"></i> Archivos adjuntos:</h6>
                                    <?php
                                    $archivos = json_decode($entregaExistente['archivo'], true);
                                    if ($archivos && is_array($archivos)):
                                    ?>
                                        <div class="mb-2">
                                            <?php foreach ($archivos as $archivo): ?>
                                                <div class="archivo-preview">
                                                    <div class="archivo-icon">
                                                        <i class="fas <?php echo obtenerIconoArchivoPhp($archivo['nombre_original']); ?>"></i>
                                                    </div>
                                                    <div class="archivo-info">
                                                        <div class="archivo-nombre"><?php echo htmlspecialchars($archivo['nombre_original']); ?></div>
                                                        <div class="archivo-tamano"><?php echo formatearTamanoPhp($archivo['tamano']); ?></div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if (isset($entregaExistente['nota']) && $entregaExistente['nota']): ?>
                                    <h6><i class="fas fa-star"></i> Calificación:</h6>
                                    <p class="mb-0 fw-bold"><?php echo htmlspecialchars($entregaExistente['nota']); ?></p>
                                <?php endif; ?>
                            </div>

                            <?php if ($puedeCancel): ?>
                                <div class="d-grid gap-2 mt-3">
                                    <button type="button" class="btn btn-outline-danger" onclick="cancelarEntrega()">
                                        <i class="fas fa-times"></i> Anular entrega
                                    </button>
                                </div>
                            <?php endif; ?>



                        <?php elseif ($actividadVencida): ?>
                            <!-- Mensaje para actividad vencida -->
                            <h4 class="mb-3 text-danger">
                                <i class="fas fa-exclamation-triangle"></i>
                                Actividad Vencida
                            </h4>

                            <div class="alert alert-danger">
                                <h6><i class="fas fa-clock"></i> Tiempo de entrega agotado</h6>
                                <p class="mb-2">Esta actividad venció el <?php echo date('d/m/Y H:i', strtotime($actividad['fecha_entrega'])); ?></p>
                                <p class="mb-0">Ya no es posible enviar entregas para esta actividad.</p>
                            </div>

                            <div class="d-grid gap-2">
                                <a href="index.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left"></i> Volver a la clase
                                </a>
                            </div>

                        <?php else: ?>
                            <!-- Formulario para entregar la actividad -->
                            <h4 class="mb-3 text-black">
                                Entregar Actividad
                            </h4>

                            <form id="formEntregarActividad" enctype="multipart/form-data">
                                <input type="hidden" name="id_actividad" value="<?php echo $id_actividad; ?>">
                                <input type="hidden" name="id_usuario" value="<?php echo $id_usuario_actual; ?>">

                                <div class="mb-3 text-black">
                                    <label for="comentarios" class="form-label">
                                        <i class="fas fa-comment"></i> Comentarios
                                    </label>
                                    <textarea class="form-control" id="comentarios" name="contenido" rows="3"
                                        placeholder="Agrega comentarios sobre tu entrega..."></textarea>
                                </div>

                                <div class="mb-3 text-black">
                                    <label for="archivos" class="form-label">
                                        <i class="fas fa-paperclip"></i> Archivos adjuntos
                                    </label>
                                    <input type="file" class="form-control" id="archivos" name="archivos[]" multiple
                                        accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.gif,.zip,.rar">
                                    <div class="form-text">
                                        <small>
                                            <i class="fas fa-info-circle"></i>
                                            Formatos permitidos: PDF, DOC, DOCX, TXT, JPG, PNG, GIF, ZIP, RAR<br>
                                            Tamaño máximo: 10MB por archivo
                                        </small>
                                    </div>
                                </div>

                                <div id="previsualizacionArchivos" class="mb-3"></div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn " style="background-color: #0E4A86; color: white;">
                                        Entregar Actividad
                                    </button>
                                    <a href="index.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left"></i> Volver a la clase
                                    </a>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="script.js"></script>

    <script>
    // Función para mostrar notificaciones
    function mostrarNotificacion(mensaje, tipo = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification ${tipo}`;
        notification.innerHTML = `
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="fas ${tipo === 'success' ? 'fa-check-circle' : tipo === 'error' ? 'fa-exclamation-triangle' : 'fa-info-circle'}"></i>
                <span>${mensaje}</span>
            </div>
        `;
        document.body.appendChild(notification);
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 4000);
    }

    // Manejar envío del formulario de entrega
    document.getElementById('formEntregarActividad')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Entregando...';
        fetch('entregar_actividad.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    mostrarNotificacion('¡Actividad entregada exitosamente!', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    mostrarNotificacion(data.message || 'Error al entregar la actividad', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarNotificacion('Error de conexión. Por favor, intenta nuevamente.', 'error');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
    });

    function cancelarEntrega() {
        if (confirm('¿Estás seguro de que deseas anular tu entrega? Esta acción no se puede deshacer.')) {
            const formData = new FormData();
            formData.append('id_actividad', '<?php echo $id_actividad; ?>');
            formData.append('id_usuario', '<?php echo $id_usuario_actual; ?>');
            formData.append('cancelar_entrega', '1');

            fetch('cancelar_entrega.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        mostrarNotificacion('Entrega cancelada exitosamente', 'info');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        mostrarNotificacion(data.message || 'Error al cancelar la entrega', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    mostrarNotificacion('Error de conexión', 'error');
                });
        }
    }

    let archivosSeleccionados = [];

    document.getElementById('archivos')?.addEventListener('change', (e) => {
        const nuevosArchivos = Array.from(e.target.files);
        const contenedor = document.getElementById('previsualizacionArchivos');

        archivosSeleccionados = archivosSeleccionados.concat(nuevosArchivos);
        if (archivosSeleccionados.length > 3) {
            mostrarNotificacion('Solo puedes seleccionar hasta 3 archivos', 'error');
            archivosSeleccionados = archivosSeleccionados.slice(0, 3);
        }

        contenedor.innerHTML = '';
        archivosSeleccionados.forEach((archivo, index) => {
            const div = document.createElement('div');
            div.className = 'archivo-preview';
            div.innerHTML = `
                <div class="archivo-icon">
                    <i class="fas ${obtenerIconoArchivo(archivo.name)}"></i>
                </div>
                <div class="archivo-info">
                    <div class="archivo-nombre" style="word-break: break-word; white-space: normal;">${archivo.name}</div>
                    <div class="archivo-tamano">${formatearTamano(archivo.size)}</div>
                </div>
                <button type="button" class="btn btn-sm btn-danger" onclick="eliminarArchivo(${index})">Eliminar</button>
            `;
            contenedor.appendChild(div);
        });

        const dataTransfer = new DataTransfer();
        archivosSeleccionados.forEach(file => dataTransfer.items.add(file));
        document.getElementById('archivos').files = dataTransfer.files;
        e.target.value = '';
    });

    function eliminarArchivo(index) {
        archivosSeleccionados.splice(index, 1);
        const contenedor = document.getElementById('previsualizacionArchivos');
        contenedor.innerHTML = '';
        archivosSeleccionados.forEach((archivo, i) => {
            const div = document.createElement('div');
            div.className = 'archivo-preview';
            div.innerHTML = `
                <div class="archivo-icon">
                    <i class="fas ${obtenerIconoArchivo(archivo.name)}"></i>
                </div>
                <div class="archivo-info">
                    <div class="archivo-nombre" style="word-break: break-word; white-space: normal;">${archivo.name}</div>
                    <div class="archivo-tamano">${formatearTamano(archivo.size)}</div>
                </div>
                <button type="button" class="btn btn-sm btn-danger" onclick="eliminarArchivo(${i})">Eliminar</button>
            `;
            contenedor.appendChild(div);
        });

        const dataTransfer = new DataTransfer();
        archivosSeleccionados.forEach(file => dataTransfer.items.add(file));
        document.getElementById('archivos').files = dataTransfer.files;
    }
</script>

</body>

</html>

<?php
// Función para obtener icono según tipo de archivo (versión PHP)
function obtenerIconoArchivoPhp($nombreArchivo)
{
    $extension = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));

    $iconos = [
        'pdf' => 'fa-file-pdf',
        'doc' => 'fa-file-word',
        'docx' => 'fa-file-word',
        'txt' => 'fa-file-alt',
        'jpg' => 'fa-file-image',
        'jpeg' => 'fa-file-image',
        'png' => 'fa-file-image',
        'gif' => 'fa-file-image',
        'zip' => 'fa-file-archive',
        'rar' => 'fa-file-archive',
    ];

    return $iconos[$extension] ?? 'fa-file';
}

// Función para formatear tamaño de archivo (versión PHP)
function formatearTamanoPhp($bytes)
{
    if ($bytes == 0) return '0 Bytes';

    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));

    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

// Función para formatear fechas
function formatearFecha($fecha)
{
    $timestamp = strtotime($fecha);
    return date('d/m/Y H:i', $timestamp);
}
?>
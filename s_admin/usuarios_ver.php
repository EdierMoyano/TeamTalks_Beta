<?php
session_start();

// Verificar si el usuario está logueado y es super admin
if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1) {
    header('Location: index.php');
    exit;
}

// Incluir archivo de conexión
require_once '../conexion/conexion.php';

// Inicializar variables
$mensaje = '';
$tipo_mensaje = '';
$id = isset($_GET['id']) ? $_GET['id'] : '';

// Verificar si hay mensaje de creación
if (isset($_GET['mensaje']) && $_GET['mensaje'] == 'creado') {
    $mensaje = "Usuario creado correctamente.";
    $tipo_mensaje = "success";
}

// Obtener datos del usuario
try {
    $db = new Database();
    $conn = $db->connect();

    // Obtener información del usuario
    $stmt = $conn->prepare("SELECT u.*, e.empresa AS empresa_nombre, r.rol AS nombre_rol 
                           FROM usuarios u 
                           LEFT JOIN empresa e ON u.nit = e.nit 
                           LEFT JOIN roles r ON u.id_rol = r.id_rol 
                           WHERE u.id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() == 0) {
        header('Location: usuarios.php');
        exit;
    }

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Obtener cursos creados por el usuario
    $stmt = $conn->prepare("SELECT COUNT(*) FROM clases WHERE id_instructor = ?");
    $stmt->execute([$id]);
    $total_cursos = $stmt->fetchColumn();

    // Obtener asistencias del usuario
    $stmt = $conn->prepare("SELECT COUNT(*) FROM asistencia WHERE id_user = ?");
    $stmt->execute([$id]);
    $total_asistencias = $stmt->fetchColumn();

    // Verificar si existe la imagen del código de barras
    $ruta_codigo_barras = '../barcode/' . $id . '.png';
    $codigo_barras_existe = file_exists($ruta_codigo_barras);
} catch (PDOException $e) {
    $mensaje = "Error al obtener datos: " . $e->getMessage();
    $tipo_mensaje = "danger";
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles de Usuario - Super Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .sidebar {
            min-height: calc(100vh - 56px);
        }

        .barcode-container {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            padding: 1rem;
            text-align: center;
            margin-top: 1rem;
        }

        .barcode-image {
            max-width: 100%;
            height: auto;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-danger">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-shield-lock"></i> Panel Super Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="empresas.php">Empresas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="licencias.php">Licencias</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="usuarios.php">Usuarios</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo $_SESSION['nombres']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="../logout.php">Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="empresas.php">
                                <i class="bi bi-building"></i> Empresas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="licencias.php">
                                <i class="bi bi-key"></i> Licencias
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="usuarios.php">
                                <i class="bi bi-people"></i> Usuarios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="tipos_licencia.php">
                                <i class="bi bi-tags"></i> Tipos de Licencia
                            </a>
                        </li>

                    </ul>
                </div>
            </div>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Detalles de Usuario</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="usuarios_editar.php?id=<?php echo $id; ?>" class="btn btn-sm btn-outline-warning">
                                <i class="bi bi-pencil"></i> Editar
                            </a>
                        </div>
                        <a href="usuarios.php" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>

                <?php if (!empty($mensaje)): ?>
                    <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                        <?php echo $mensaje; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-person-circle me-1"></i> Información del Usuario
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <p><strong>Documento de Identidad:</strong> <?php echo $usuario['id']; ?></p>
                                        <p><strong>Nombres:</strong> <?php echo htmlspecialchars($usuario['nombres']); ?></p>
                                        <p><strong>Apellidos:</strong> <?php echo htmlspecialchars($usuario['apellidos']); ?></p>
                                        <p><strong>Correo:</strong> <?php echo htmlspecialchars($usuario['correo']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p>
                                            <strong>Empresa:</strong>
                                            <?php if (!empty($usuario['empresa_nombre'])): ?>
                                                <a href="empresas_ver.php?id=<?php echo $usuario['nit']; ?>">
                                                    <?php echo htmlspecialchars($usuario['empresa_nombre']); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">Sin empresa</span>
                                            <?php endif; ?>
                                        </p>
                                        <p>
                                            <strong>Rol:</strong>
                                            <?php echo htmlspecialchars($usuario['nombre_rol']); ?>
                                        </p>
                                    </div>
                                </div>

                                <?php if ($codigo_barras_existe): ?>
                                    <div class="barcode-container">
                                        <h5 class="mb-3">Código de Barras del Usuario</h5>
                                        <img src="<?php echo '../barcode/' . $id . '.png'; ?>" alt="Código de Barras" class="barcode-image">
                                        <div class="d-flex justify-content-center gap-2">
                                            <a href="<?php echo '../barcode/' . $id . '.png'; ?>" class="btn btn-primary" download="codigo_barras_<?php echo $id; ?>.png">
                                                <i class="bi bi-download"></i> Descargar
                                            </a>
                                            <button class="btn btn-success" onclick="imprimirCodigo()">
                                                <i class="bi bi-printer"></i> Imprimir
                                            </button>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        <i class="bi bi-exclamation-triangle-fill me-2"></i> No se encontró la imagen del código de barras para este usuario.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-info-circle me-1"></i> Resumen
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>Cursos Creados:</div>
                                    <span class="badge bg-primary rounded-pill"><?php echo $total_cursos; ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>Asistencias Registradas:</div>
                                    <span class="badge bg-info rounded-pill"><?php echo $total_asistencias; ?></span>
                                </div>

                                <div class="d-grid gap-2 mt-4">
                                    <?php if (!empty($usuario['nit'])): ?>
                                        <a href="empresas_ver.php?id=<?php echo $usuario['nit']; ?>" class="btn btn-outline-primary">
                                            <i class="bi bi-building"></i> Ver Empresa
                                        </a>
                                    <?php endif; ?>
                                    <a href="usuarios_editar.php?id=<?php echo $id; ?>" class="btn btn-warning">
                                        <i class="bi bi-pencil"></i> Editar Usuario
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-shield-lock me-1"></i> Seguridad
                                </h5>
                            </div>
                            <div class="card-body">
                                <p>Puede restablecer la contraseña del usuario desde la página de edición.</p>
                                <div class="d-grid">
                                    <a href="usuarios_editar.php?id=<?php echo $id; ?>" class="btn btn-outline-info">
                                        <i class="bi bi-key"></i> Cambiar Contraseña
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function imprimirCodigo() {
            const imgSrc = '<?php echo '../barcode/' . $id . '.png'; ?>';
            const codigo = '<?php echo $id; ?>';
            const nombre = '<?php echo htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellidos']); ?>';

            const ventanaImpresion = window.open('', '_blank');
            ventanaImpresion.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Imprimir Código de Barras</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        text-align: center;
                        padding: 20px;
                    }
                    .codigo-container {
                        margin: 0 auto;
                        max-width: 300px;
                    }
                    img {
                        max-width: 100%;
                    }
                    .info {
                        margin-top: 10px;
                        font-size: 14px;
                    }
                </style>
            </head>
            <body>
                <div class="codigo-container">
                    <h3>Código de Barras</h3>
                    <img src="${imgSrc}" alt="Código de Barras">
                    <div class="info">
                        <p><strong>Código:</strong> ${codigo}</p>
                        <p><strong>Usuario:</strong> ${nombre}</p>
                    </div>
                </div>
                <script>
                    window.onload = function() {
                        window.print();
                    }
    </script>
</body>

</html>
`);
ventanaImpresion.document.close();
}
</script>
</body>

</html>
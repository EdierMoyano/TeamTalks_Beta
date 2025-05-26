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
$nit = isset($_GET['nit']) ? (int)$_GET['nit'] : 0;
$nombre_empresa = '';

// Verificar si existe la empresa
try {
    $db = new Database();
    $conn = $db->connect();
    
    $stmt = $conn->prepare("SELECT * FROM empresa WHERE nit = ?");
    $stmt->execute([$nit]);
    
    if ($stmt->rowCount() == 0) {
        header('Location: empresas.php');
        exit;
    }
    
    $empresa = $stmt->fetch(PDO::FETCH_ASSOC);
    $nombre_empresa = $empresa['empresa'];
    
} catch (PDOException $e) {
    $mensaje = "Error al obtener la empresa: " . $e->getMessage();
    $tipo_mensaje = "danger";
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_empresa = trim($_POST['empresa']);
    
    if (empty($nombre_empresa)) {
        $mensaje = "El nombre de la empresa es obligatorio.";
        $tipo_mensaje = "danger";
    } else {
        try {
            // Verificar si ya existe otra empresa con ese nombre
            $stmt = $conn->prepare("SELECT COUNT(*) FROM empresa WHERE empresa = ? AND nit != ?");
            $stmt->execute([$nombre_empresa, $nit]);
            $existe = ($stmt->fetchColumn() > 0);
            
            if ($existe) {
                $mensaje = "Ya existe otra empresa con ese nombre.";
                $tipo_mensaje = "danger";
            } else {
                // Actualizar la empresa
                $stmt = $conn->prepare("UPDATE empresa SET empresa = ? WHERE nit = ?");
                $stmt->execute([$nombre_empresa, $nit]);
                
                $mensaje = "Empresa actualizada correctamente.";
                $tipo_mensaje = "success";
            }
        } catch (PDOException $e) {
            $mensaje = "Error al actualizar la empresa: " . $e->getMessage();
            $tipo_mensaje = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Empresa - Super Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .sidebar {
            min-height: calc(100vh - 56px);
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
                        <a class="nav-link active" href="empresas.php">Empresas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="licencias.php">Licencias</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="usuarios.php">Usuarios</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo $_SESSION['nombres']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><hr class="dropdown-divider"></li>
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
                            <a class="nav-link active" href="empresas.php">
                                <i class="bi bi-building"></i> Empresas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="licencias.php">
                                <i class="bi bi-key"></i> Licencias
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="usuarios.php">
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
            
            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Editar Empresa</h1>
                </div>
                
                <?php if (!empty($mensaje)): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-pencil me-1"></i> Formulario de Edición
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="nit" class="form-label">NIT de la Empresa</label>
                                <input type="text" class="form-control" id="nit" value="<?php echo $nit; ?>" disabled>
                            </div>
                            
                            <div class="mb-3">
                                <label for="empresa" class="form-label">Nombre de la Empresa *</label>
                                <input type="text" class="form-control" id="empresa" name="empresa" value="<?php echo htmlspecialchars($nombre_empresa); ?>" required>
                                <div class="form-text">Ingrese el nombre completo de la empresa.</div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="empresas.php" class="btn btn-secondary me-md-2">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
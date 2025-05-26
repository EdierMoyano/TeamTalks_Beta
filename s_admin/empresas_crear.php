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
$nombre = '';

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    
    if (empty($nombre)) {
        $mensaje = "El nombre de la empresa es obligatorio.";
        $tipo_mensaje = "danger";
    } else {
        try {
            $db = new Database();
            $conn = $db->connect();

            // Verificar si ya existe una empresa con ese nombre
            $stmt = $conn->prepare("SELECT COUNT(*) FROM empresa WHERE empresa = ?");
            $stmt->execute([$nombre]);
            $existe = ($stmt->fetchColumn() > 0);
            
            if ($existe) {
                $mensaje = "Ya existe una empresa con ese nombre.";
                $tipo_mensaje = "danger";
            } else {
                // Generar un NIT aleatorio único de 8 dígitos
                do {
                    $nit = rand(10000000, 99999999);
                    $stmt_nit = $conn->prepare("SELECT COUNT(*) FROM empresa WHERE nit = ?");
                    $stmt_nit->execute([$nit]);
                    $nit_existente = $stmt_nit->fetchColumn() > 0;
                } while ($nit_existente);

                // Insertar la empresa con el NIT generado
                $stmt_insert = $conn->prepare("INSERT INTO empresa (nit, empresa) VALUES (?, ?)");
                $stmt_insert->execute([$nit, $nombre]);

                $mensaje = "Empresa creada correctamente. NIT generado: $nit";
                $tipo_mensaje = "success";

                // Limpiar el formulario
                $nombre = '';
            }
        } catch (PDOException $e) {
            $mensaje = "Error al crear la empresa: " . $e->getMessage();
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
    <title>Crear Empresa - Super Admin</title>
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
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active" href="empresas.php">Empresas</a></li>
                    <li class="nav-item"><a class="nav-link" href="licencias.php">Licencias</a></li>
                    <li class="nav-item"><a class="nav-link" href="usuarios.php">Usuarios</a></li>
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
                        <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link active" href="empresas.php"><i class="bi bi-building"></i> Empresas</a></li>
                        <li class="nav-item"><a class="nav-link" href="licencias.php"><i class="bi bi-key"></i> Licencias</a></li>
                        <li class="nav-item"><a class="nav-link" href="usuarios.php"><i class="bi bi-people"></i> Usuarios</a></li>
                        <li class="nav-item"><a class="nav-link" href="tipos_licencia.php"><i class="bi bi-tags"></i> Tipos de Licencia</a></li>
                        
                    </ul>
                </div>
            </div>
            
            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Crear Nueva Empresa</h1>
                </div>
                
                <?php if (!empty($mensaje)): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-building-add me-1"></i> Formulario de Registro
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre de la Empresa *</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($nombre); ?>" required>
                                <div class="form-text">Ingrese el nombre completo de la empresa.</div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="empresas.php" class="btn btn-secondary me-md-2">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Guardar Empresa</button>
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

<?php
session_start();

// Verificar si el usuario está logueado y es super admin
if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1) {
    header('Location: index.php');
    exit;
}

// Incluir archivo de conexión
require_once '../conexion/conexion.php';


// Obtener estadísticas básicas
try {
    $db = new Database();
    $conn = $db->connect();

    // Total de empresas
    $stmt = $conn->query("SELECT COUNT(*) as total FROM empresa");
    $total_empresas = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Total de licencias activas
    $stmt = $conn->query("SELECT COUNT(*) as total FROM licencias WHERE estado = 'Activa'");
    $total_licencias_activas = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Total de licencias expiradas
    $stmt = $conn->query("SELECT COUNT(*) as total FROM licencias WHERE estado = 'Expirada'");
    $total_licencias_expiradas = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Total de usuarios (rol 2 = cliente)
    $stmt = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE id_rol = 2");
    $total_usuarios = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Licencias próximas a expirar (30 días)
    $stmt = $conn->query("
        SELECT COUNT(*) as total FROM licencias 
        WHERE estado = 'Activa' 
        AND fecha_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ");
    $licencias_por_expirar = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Últimas 5 licencias asignadas
    $stmt = $conn->query("
        SELECT l.id_licencia, l.fecha_ini, l.fecha_fin, l.estado, 
               e.empresa, tl.licencia AS tipo_licencia
        FROM licencias l
        JOIN empresa e ON l.nit = e.nit
        JOIN tipo_licencia tl ON l.id_tipo_licencia = tl.id_tipo_licencia
        ORDER BY l.fecha_ini DESC
        LIMIT 5
    ");
    $ultimas_licencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

     // Últimas 5 empresas registradas
     $stmt = $conn->query("SELECT nit, empresa FROM empresa ORDER BY nit DESC LIMIT 5");
     $ultimas_empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo 'Error: ' . $e->getMessage();
    exit;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Super Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .card-dashboard {
            transition: transform 0.3s;
        }
        .card-dashboard:hover {
            transform: translateY(-5px);
        }
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
                        <a class="nav-link active" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="empresas.php">Empresas</a>
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
                            <li><a class="dropdown-item" href="../includes/exit.php">Cerrar Sesión</a></li>
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
                            <a class="nav-link active" href="dashboard.php">
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
                    
                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                        <span>Acciones Rápidas</span>
                    </h6>
                    <ul class="nav flex-column mb-2">
                        <li class="nav-item">
                            <a class="nav-link" href="empresas_crear.php">
                                <i class="bi bi-building-add"></i> Nueva Empresa
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="licencias_crear.php">
                                <i class="bi bi-key-fill"></i> Nueva Licencia
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="usuarios_crear.php">
                                <i class="bi bi-person-plus"></i> Nuevo Usuario
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        
                    </div>
                </div>
                
                <!-- Tarjetas de estadísticas -->
                <div class="row">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2 card-dashboard bg-primary bg-gradient text-white">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1">Empresas</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $total_empresas; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-building fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a class="small text-white stretched-link" href="empresas.php">Ver Detalles</a>
                                <div class="small text-white"><i class="bi bi-chevron-right"></i></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2 card-dashboard bg-success bg-gradient text-white">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1">Licencias Activas</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $total_licencias_activas; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-key fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a class="small text-white stretched-link" href="licencias.php?estado=Activa">Ver Detalles</a>
                                <div class="small text-white"><i class="bi bi-chevron-right"></i></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2 card-dashboard bg-warning bg-gradient text-white">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1">Por Expirar</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $licencias_por_expirar; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-exclamation-triangle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a class="small text-white stretched-link" href="licencias.php?por_expirar=1">Ver Detalles</a>
                                <div class="small text-white"><i class="bi bi-chevron-right"></i></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-danger shadow h-100 py-2 card-dashboard bg-danger bg-gradient text-white">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1">Licencias Expiradas</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo $total_licencias_expiradas; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-x-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a class="small text-white stretched-link" href="licencias.php?estado=Expirada">Ver Detalles</a>
                                <div class="small text-white"><i class="bi bi-chevron-right"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Gráficos y tablas -->
                <div class="row">
                    <!-- Últimas licencias -->
                    <div class="col-lg-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="bi bi-table me-1"></i>
                                Últimas Licencias Asignadas
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-sm">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Empresa</th>
                                                <th>Tipo</th>
                                                <th>Inicio</th>
                                                <th>Fin</th>
                                                <th>Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($ultimas_licencias as $licencia): ?>
                                            <tr>
                                                <td><?php echo $licencia['id_licencia']; ?></td>
                                                <td><?php echo $licencia['empresa']; ?></td>
                                                <td><?php echo $licencia['tipo_licencia']; ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($licencia['fecha_ini'])); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($licencia['fecha_fin'])); ?></td>
                                                <td>
                                                    <?php if ($licencia['estado'] == 'Activa'): ?>
                                                        <span class="badge bg-success">Activa</span>
                                                    <?php elseif ($licencia['estado'] == 'Expirada'): ?>
                                                        <span class="badge bg-danger">Expirada</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Inactiva</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-end mt-3">
                                    <a href="licencias.php" class="btn btn-sm btn-primary">Ver todas las licencias</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Últimas empresas -->
                    <div class="col-lg-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="bi bi-building me-1"></i>
                                Últimas Empresas Registradas
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-sm">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Nombre</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($ultimas_empresas as $empresa): ?>
                                            <tr>
                                                <td><?php echo $empresa['nit']; ?></td>
                                                <td><?php echo $empresa['empresa']; ?></td>
                                                <td>
                                                    <a href="empresas_ver.php?id=<?php echo $empresa['nit']; ?>" class="btn btn-sm btn-info">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="empresas_editar.php?id=<?php echo $empresa['nit']; ?>" class="btn btn-sm btn-warning">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-end mt-3">
                                    <a href="empresas.php" class="btn btn-sm btn-primary">Ver todas las empresas</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Acciones rápidas -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="bi bi-lightning-charge me-1"></i>
                                Acciones Rápidas
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <a href="empresas_crear.php" class="btn btn-primary w-100 py-3">
                                            <i class="bi bi-building-add me-2"></i> Registrar Nueva Empresa
                                        </a>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <a href="licencias_crear.php" class="btn btn-success w-100 py-3">
                                            <i class="bi bi-key-fill me-2"></i> Asignar Nueva Licencia
                                        </a>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <a href="usuarios_crear.php" class="btn btn-info w-100 py-3">
                                            <i class="bi bi-person-plus-fill me-2"></i> Registrar Nuevo Usuario
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
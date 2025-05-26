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

// Procesar eliminación si se solicita
if (isset($_GET['eliminar']) && !empty($_GET['eliminar'])) {
    $nit_eliminar = $_GET['eliminar'];
    
    try {
        $db = new Database();
        $conn = $db->connect();
        
        // Verificar si la empresa tiene licencias asociadas
        $stmt = $conn->prepare("SELECT COUNT(*) FROM licencias WHERE nit = ?");
        $stmt->execute([$nit_eliminar]);
        $tiene_licencias = ($stmt->fetchColumn() > 0);
        
        // Verificar si la empresa tiene usuarios asociados
        $stmt = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE nit = ?");
        $stmt->execute([$nit_eliminar]);
        $tiene_usuarios = ($stmt->fetchColumn() > 0);
        
        if ($tiene_licencias || $tiene_usuarios) {
            $mensaje = "No se puede eliminar la empresa porque tiene licencias o usuarios asociados.";
            $tipo_mensaje = "danger";
        } else {
            // Eliminar la empresa
            $stmt = $conn->prepare("DELETE FROM empresa WHERE nit = ?");
            $stmt->execute([$nit_eliminar]);
            
            $mensaje = "Empresa eliminada correctamente.";
            $tipo_mensaje = "success";
        }
    } catch (PDOException $e) {
        $mensaje = "Error al eliminar la empresa: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Obtener listado de empresas
try {
    $db = new Database();
    $conn = $db->connect();
    
    // Búsqueda
    $busqueda = isset($_GET['buscar']) ? $_GET['buscar'] : '';
    $where = '';
    $params = [];
    
    if (!empty($busqueda)) {
        $where = "WHERE empresa LIKE ?";
        $params[] = "%$busqueda%";
    }
    
    // Paginación
    $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    $por_pagina = 10;
    $inicio = ($pagina - 1) * $por_pagina;
    
    // Contar total de registros
    $stmt = $conn->prepare("SELECT COUNT(*) FROM empresa $where");
    $stmt->execute($params);
    $total_registros = $stmt->fetchColumn();
    $total_paginas = ceil($total_registros / $por_pagina);
    
    // Obtener empresas para la página actual
    $stmt = $conn->prepare("SELECT e.*, 
                           (SELECT COUNT(*) FROM licencias WHERE nit = e.nit) as total_licencias,
                           (SELECT COUNT(*) FROM usuarios WHERE nit = e.nit) as total_usuarios
                           FROM empresa e
                           $where
                           ORDER BY e.nit DESC
                           LIMIT $inicio, $por_pagina");
    $stmt->execute($params);
    $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $mensaje = "Error al obtener las empresas: " . $e->getMessage();
    $tipo_mensaje = "danger";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Empresas - Super Admin</title>
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
                    <h1 class="h2">Gestión de Empresas</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="empresas_crear.php" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-plus-lg"></i> Nueva Empresa
                        </a>
                    </div>
                </div>
                
                <?php if (!empty($mensaje)): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <!-- Buscador -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form action="" method="GET" class="row g-3">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="buscar" placeholder="Buscar por nombre..." value="<?php echo isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : ''; ?>">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="bi bi-search"></i> Buscar
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6 text-end">
                                <?php if (isset($_GET['buscar']) && !empty($_GET['buscar'])): ?>
                                <a href="empresas.php" class="btn btn-secondary">Limpiar filtros</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Tabla de empresas -->
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-table me-1"></i> Listado de Empresas
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>NIT</th>
                                        <th>Nombre</th>
                                        <th>Licencias</th>
                                        <th>Usuarios</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($empresas) > 0): ?>
                                        <?php foreach ($empresas as $empresa): ?>
                                        <tr>
                                            <td><?php echo $empresa['nit']; ?></td>
                                            <td><?php echo htmlspecialchars($empresa['empresa']); ?></td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo $empresa['total_licencias']; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $empresa['total_usuarios']; ?></span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="empresas_ver.php?nit=<?php echo $empresa['nit']; ?>" class="btn btn-sm btn-info" title="Ver detalles">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="empresas_editar.php?nit=<?php echo $empresa['nit']; ?>" class="btn btn-sm btn-warning" title="Editar">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <?php if ($empresa['total_licencias'] == 0 && $empresa['total_usuarios'] == 0): ?>
                                                    <a href="#" class="btn btn-sm btn-danger" title="Eliminar" 
                                                       onclick="confirmarEliminar(<?php echo $empresa['nit']; ?>, '<?php echo htmlspecialchars($empresa['empresa']); ?>')">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                    <?php else: ?>
                                                    <button class="btn btn-sm btn-secondary" title="No se puede eliminar" disabled>
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No se encontraron empresas.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Paginación -->
                        <?php if ($total_paginas > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo ($pagina <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $pagina - 1; ?><?php echo !empty($busqueda) ? '&buscar=' . urlencode($busqueda) : ''; ?>" aria-label="Anterior">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                
                                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                <li class="page-item <?php echo ($pagina == $i) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $i; ?><?php echo !empty($busqueda) ? '&buscar=' . urlencode($busqueda) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo ($pagina >= $total_paginas) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $pagina + 1; ?><?php echo !empty($busqueda) ? '&buscar=' . urlencode($busqueda) : ''; ?>" aria-label="Siguiente">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Modal de confirmación para eliminar -->
    <div class="modal fade" id="eliminarModal" tabindex="-1" aria-labelledby="eliminarModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eliminarModalLabel">Confirmar eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    ¿Está seguro de que desea eliminar la empresa <span id="nombreEmpresa"></span>?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a href="#" id="btnEliminar" class="btn btn-danger">Eliminar</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmarEliminar(nit, nombre) {
            document.getElementById('nombreEmpresa').textContent = nombre;
            document.getElementById('btnEliminar').href = 'empresas.php?eliminar=' + nit;
            
            var modal = new bootstrap.Modal(document.getElementById('eliminarModal'));
            modal.show();
        }
    </script>
</body>
</html>
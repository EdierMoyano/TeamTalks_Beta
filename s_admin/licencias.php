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

// Procesar cambio de estado si se solicita
if (isset($_GET['activar']) && !empty($_GET['activar'])) {
    $id_activar = $_GET['activar'];
    
    try {
        $db = new Database();
        $conn = $db->connect();
        
        $stmt = $conn->prepare("UPDATE licencias SET estado = 'Activa' WHERE id_licencia = ?");
        $stmt->execute([$id_activar]);
        
        $mensaje = "Licencia activada correctamente.";
        $tipo_mensaje = "success";
    } catch (PDOException $e) {
        $mensaje = "Error al activar la licencia: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
} elseif (isset($_GET['desactivar'])) {
    $id_desactivar = $_GET['desactivar'];
    
    try {
        $db = new Database();
        $conn = $db->connect();
        
        $stmt = $conn->prepare("UPDATE licencias SET estado = 'Inactiva' WHERE id_licencia = ?");
        $stmt->execute([$id_desactivar]);
        
        $mensaje = "Licencia desactivada correctamente.";
        $tipo_mensaje = "success";
    } catch (PDOException $e) {
        $mensaje = "Error al desactivar la licencia: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Obtener listado de licencias
try {
    $db = new Database();
    $conn = $db->connect();
    
    // Filtros
    $where = [];
    $params = [];
    
    // Filtro por tipo de licencia
    if (isset($_GET['tipo']) && !empty($_GET['tipo'])) {
        $where[] = "l.id_tipo_licencia = ?";
        $params[] = $_GET['tipo'];
    }
    
    // Filtro por empresa
    if (isset($_GET['empresa']) && !empty($_GET['empresa'])) {
        $where[] = "l.nit = ?";
        $params[] = $_GET['empresa'];
    }
    
    // Filtro por estado
    if (isset($_GET['estado']) && !empty($_GET['estado'])) {
        $where[] = "l.estado = ?";
        $params[] = $_GET['estado'];
    }
    
    // Búsqueda por código de licencia
    $busqueda = isset($_GET['buscar']) ? $_GET['buscar'] : '';
    if (!empty($busqueda)) {
        $where[] = "(l.codigo_licencia LIKE ? OR e.empresa LIKE ?)";
        $params[] = "%$busqueda%";
        $params[] = "%$busqueda%";
    }
    
    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    
    // Paginación
    $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    $por_pagina = 10;
    $inicio = ($pagina - 1) * $por_pagina;
    
    // Contar total de registros
    $stmt = $conn->prepare("SELECT COUNT(*) FROM licencias l 
                           JOIN tipo_licencia tl ON l.id_tipo_licencia = tl.id_tipo_licencia 
                           JOIN empresa e ON l.nit = e.nit 
                           $whereClause");
    $stmt->execute($params);
    $total_registros = $stmt->fetchColumn();
    $total_paginas = ceil($total_registros / $por_pagina);
    
    // Obtener licencias para la página actual
    $stmt = $conn->prepare("SELECT l.*, tl.licencia as tipo_nombre, e.empresa as empresa_nombre, 
                           DATEDIFF(l.fecha_fin, CURDATE()) as dias_restantes 
                           FROM licencias l 
                           JOIN tipo_licencia tl ON l.id_tipo_licencia = tl.id_tipo_licencia 
                           JOIN empresa e ON l.nit = e.nit 
                           $whereClause
                           ORDER BY l.fecha_ini DESC
                           LIMIT $inicio, $por_pagina");
    $stmt->execute($params);
    $licencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener tipos de licencia para el filtro
    $stmt = $conn->query("SELECT id_tipo_licencia, licencia FROM tipo_licencia ORDER BY licencia");
    $tipos_licencia = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener empresas para el filtro
    $stmt = $conn->query("SELECT nit, empresa FROM empresa ORDER BY empresa");
    $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $mensaje = "Error al obtener las licencias: " . $e->getMessage();
    $tipo_mensaje = "danger";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Licencias - Super Admin</title>
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
                        <a class="nav-link" href="empresas.php">Empresas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="licencias.php">Licencias</a>
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
                            <a class="nav-link" href="empresas.php">
                                <i class="bi bi-building"></i> Empresas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="licencias.php">
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
                    <h1 class="h2">Gestión de Licencias</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="licencias_crear.php" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-plus-lg"></i> Nueva Licencia
                        </a>
                    </div>
                </div>
                
                <?php if (!empty($mensaje)): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="bi bi-funnel me-1"></i> Filtros y Búsqueda
                    </div>
                    <div class="card-body">
                        <form action="" method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="tipo" class="form-label">Tipo de Licencia</label>
                                <select class="form-select" id="tipo" name="tipo">
                                    <option value="">Todos</option>
                                    <?php foreach ($tipos_licencia as $tipo): ?>
                                    <option value="<?php echo $tipo['id_tipo_licencia']; ?>" <?php echo (isset($_GET['tipo']) && $_GET['tipo'] == $tipo['id_tipo_licencia']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tipo['licencia']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="empresa" class="form-label">Empresa</label>
                                <select class="form-select" id="empresa" name="empresa">
                                    <option value="">Todas</option>
                                    <?php foreach ($empresas as $empresa): ?>
                                    <option value="<?php echo $empresa['nit']; ?>" <?php echo (isset($_GET['empresa']) && $_GET['empresa'] == $empresa['nit']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($empresa['empresa']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-select" id="estado" name="estado">
                                    <option value="">Todos</option>
                                    <option value="Activa" <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'Activa') ? 'selected' : ''; ?>>Activa</option>
                                    <option value="Inactiva" <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'Inactiva') ? 'selected' : ''; ?>>Inactiva</option>
                                    <option value="Expirada" <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'Expirada') ? 'selected' : ''; ?>>Expirada</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="buscar" class="form-label">Buscar</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="buscar" name="buscar" placeholder="Buscar por código o empresa..." value="<?php echo isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : ''; ?>">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="bi bi-search"></i> Buscar
                                    </button>
                                </div>
                            </div>
                            <div class="col-12 d-flex justify-content-end">
                                <?php if (isset($_GET['tipo']) || isset($_GET['empresa']) || isset($_GET['estado']) || isset($_GET['buscar'])): ?>
                                <a href="licencias.php" class="btn btn-secondary">Limpiar filtros</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Tabla de licencias -->
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-table me-1"></i> Listado de Licencias
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Código</th>
                                        <th>Tipo</th>
                                        <th>Empresa</th>
                                        <th>Fecha Inicio</th>
                                        <th>Fecha Fin</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($licencias) > 0): ?>
                                        <?php foreach ($licencias as $licencia): ?>
                                        <tr>
                                            <td><?php echo $licencia['id_licencia']; ?></td>
                                            <td>
                                                <span class="badge bg-dark text-monospace"><?php echo $licencia['codigo_licencia']; ?></span>
                                            </td>
                                            <td><?php echo htmlspecialchars($licencia['tipo_nombre']); ?></td>
                                            <td>
                                                <a href="empresas_ver.php?id=<?php echo $licencia['nit']; ?>">
                                                    <?php echo htmlspecialchars($licencia['empresa_nombre']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($licencia['fecha_ini'])); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($licencia['fecha_fin'])); ?></td>
                                            <td>
                                                <?php if ($licencia['estado'] == 'Activa'): ?>
                                                    <span class="badge bg-success">Activa</span>
                                                <?php elseif ($licencia['estado'] == 'Inactiva'): ?>
                                                    <span class="badge bg-secondary">Inactiva</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Expirada</span>
                                                <?php endif; ?>
                                                
                                                <?php if ($licencia['dias_restantes'] > 0 && $licencia['estado'] == 'Activa'): ?>
                                                    <span class="badge bg-info"><?php echo $licencia['dias_restantes']; ?> días</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="licencias_ver.php?id=<?php echo $licencia['id_licencia']; ?>" class="btn btn-sm btn-info" title="Ver detalles">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <?php if ($licencia['estado'] == 'Inactiva' || $licencia['estado'] == 'Expirada'): ?>
                                                    <a href="licencias.php?activar=<?php echo $licencia['id_licencia']; ?>" class="btn btn-sm btn-success" title="Activar licencia">
                                                        <i class="bi bi-check-lg"></i>
                                                    </a>
                                                    <?php elseif ($licencia['estado'] == 'Activa'): ?>
                                                    <a href="licencias.php?desactivar=<?php echo $licencia['id_licencia']; ?>" class="btn btn-sm btn-warning" title="Desactivar licencia">
                                                        <i class="bi bi-x-lg"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No se encontraron licencias.</td>
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
                                    <a class="page-link" href="?pagina=<?php echo $pagina - 1; ?><?php echo isset($_GET['tipo']) ? '&tipo=' . $_GET['tipo'] : ''; ?><?php echo isset($_GET['empresa']) ? '&empresa=' . $_GET['empresa'] : ''; ?><?php echo isset($_GET['estado']) ? '&estado=' . $_GET['estado'] : ''; ?><?php echo isset($_GET['buscar']) ? '&buscar=' . urlencode($_GET['buscar']) : ''; ?>" aria-label="Anterior">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                
                                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                <li class="page-item <?php echo ($pagina == $i) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $i; ?><?php echo isset($_GET['tipo']) ? '&tipo=' . $_GET['tipo'] : ''; ?><?php echo isset($_GET['empresa']) ? '&empresa=' . $_GET['empresa'] : ''; ?><?php echo isset($_GET['estado']) ? '&estado=' . $_GET['estado'] : ''; ?><?php echo isset($_GET['buscar']) ? '&buscar=' . urlencode($_GET['buscar']) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo ($pagina >= $total_paginas) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $pagina + 1; ?><?php echo isset($_GET['tipo']) ? '&tipo=' . $_GET['tipo'] : ''; ?><?php echo isset($_GET['empresa']) ? '&empresa=' . $_GET['empresa'] : ''; ?><?php echo isset($_GET['estado']) ? '&estado=' . $_GET['estado'] : ''; ?><?php echo isset($_GET['buscar']) ? '&buscar=' . urlencode($_GET['buscar']) : ''; ?>" aria-label="Siguiente">
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
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
    $id_eliminar = $_GET['eliminar'];
    
    try {
        $db = new Database();
        $conn = $db->connect();
        
        // Verificar si el usuario tiene registros asociados en otras tablas
        $stmt = $conn->prepare("SELECT COUNT(*) FROM materia_ficha WHERE id_instructor = ?");
        $stmt->execute([$id_eliminar]);
        $tiene_asociaciones = ($stmt->fetchColumn() > 0);
        
        if ($tiene_asociaciones) {
            $mensaje = "No se puede eliminar el usuario porque tiene registros asociados.";
            $tipo_mensaje = "danger";
        } else {
            // Eliminar el usuario
            $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$id_eliminar]);
            
            $mensaje = "Usuario eliminado correctamente.";
            $tipo_mensaje = "success";
        }
    } catch (PDOException $e) {
        $mensaje = "Error al eliminar el usuario: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Obtener listado de usuarios
try {
    $db = new Database();
    $conn = $db->connect();
    
    // Filtros
    $where = [];
    $params = [];
    
    // Filtro por empresa
    if (isset($_GET['empresa']) && !empty($_GET['empresa'])) {
        $where[] = "u.nit = ?";
        $params[] = $_GET['empresa'];
    }
    
    // Filtro por rol
    if (isset($_GET['rol']) && !empty($_GET['rol'])) {
        $where[] = "u.id_rol = ?";
        $params[] = $_GET['rol'];
    }
    
    // Búsqueda
    $busqueda = isset($_GET['buscar']) ? $_GET['buscar'] : '';
    if (!empty($busqueda)) {
        $where[] = "(u.nombres LIKE ? OR u.apellidos LIKE ? OR u.correo LIKE ? OR u.id LIKE ?)";
        $params[] = "%$busqueda%";
        $params[] = "%$busqueda%";
        $params[] = "%$busqueda%";
        $params[] = "%$busqueda%";
    }
    
    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    
    // Paginación
    $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    $por_pagina = 10;
    $inicio = ($pagina - 1) * $por_pagina;
    
    // Contar total de registros
    $stmt = $conn->prepare("SELECT COUNT(*) FROM usuarios u 
                           LEFT JOIN empresa e ON u.nit = e.nit 
                           LEFT JOIN roles r ON u.id_rol = r.id_rol 
                           $whereClause");
    $stmt->execute($params);
    $total_registros = $stmt->fetchColumn();
    $total_paginas = ceil($total_registros / $por_pagina);
    
    // Obtener usuarios para la página actual
    $stmt = $conn->prepare("SELECT u.*, e.empresa as empresa_nombre, r.rol 
                           FROM usuarios u 
                           LEFT JOIN empresa e ON u.nit = e.nit 
                           LEFT JOIN roles r ON u.id_rol = r.id_rol
                           $whereClause
                           ORDER BY u.nombres, u.apellidos
                           LIMIT $inicio, $por_pagina");
    $stmt->execute($params);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    
    // Obtener empresas para el filtro
    $stmt = $conn->query("SELECT nit, empresa FROM empresa ORDER BY empresa");
    $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener roles para el filtro
    $stmt = $conn->query("SELECT id_rol, rol FROM roles ORDER BY id_rol");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $mensaje = "Error al obtener los usuarios: " . $e->getMessage();
    $tipo_mensaje = "danger";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Super Admin</title>
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
            
            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestión de Usuarios</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="usuarios_crear.php" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-person-plus"></i> Nuevo Usuario
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
                                <label for="empresa" class="form-label">Empresa</label>
                                <select class="form-select" id="empresa" name="empresa">
                                    <option value="">Todas</option>
                                    <?php foreach ($empresa as $empresa): ?>
                                    <option value="<?php echo $empresa['nit']; ?>" <?php echo (isset($_GET['empresa']) && $_GET['empresa'] == $empresa['nit']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($empresa['empresa']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="rol" class="form-label">Rol</label>
                                <select class="form-select" id="rol" name="rol">
                                    <option value="">Todos</option>
                                    <?php foreach ($roles as $rol): ?>
                                    <option value="<?php echo $rol['id_rol']; ?>" <?php echo (isset($_GET['rol']) && $_GET['rol'] == $rol['id_rol']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($rol['rol']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="buscar" class="form-label">Búsqueda</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="buscar" name="buscar" placeholder="Buscar por nombre, apellido, email o documento..." value="<?php echo isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : ''; ?>">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="bi bi-search"></i> Buscar
                                    </button>
                                </div>
                            </div>
                            <div class="col-12 d-flex justify-content-end">
                                <?php if (isset($_GET['buscar']) || isset($_GET['empresa']) && !empty($_GET['empresa']) || isset($_GET['rol']) && !empty($_GET['rol'])): ?>
                                <a href="usuarios.php" class="btn btn-secondary">Limpiar filtros</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Tabla de usuarios -->
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-table me-1"></i> Listado de Usuarios
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Empresa</th>
                                        <th>Rol</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($usuarios) > 0): ?>
                                        <?php foreach ($usuarios as $usuario): ?>
                                        <tr>
                                            <td><?php echo $usuario['id']; ?></td>
                                            <td><?php echo htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellidos']); ?></td>
                                            <td><?php echo htmlspecialchars($usuario['correo']); ?></td>
                                            <td>
                                                <?php if (!empty($usuario['empresa_nombre'])): ?>
                                                    <a href="empresas_ver.php?id=<?php echo $usuario['nit']; ?>">
                                                        <?php echo htmlspecialchars($usuario['empresa_nombre']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">Sin empresa</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($usuario['id_rol'] == 1): ?>
                                                    <span class="badge bg-danger">Super Admin</span>
                                                <?php elseif ($usuario['id_rol'] == 2): ?>
                                                    <span class="badge bg-primary">Admin</span>
                                                <?php elseif ($usuario['id_rol'] == 3): ?>
                                                    <span class="badge bg-info">Instructor</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Aprendiz</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="usuarios_ver.php?id=<?php echo $usuario['id']; ?>" class="btn btn-sm btn-info" title="Ver detalles">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="usuarios_editar.php?id=<?php echo $usuario['id']; ?>" class="btn btn-sm btn-warning" title="Editar">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="#" class="btn btn-sm btn-danger" title="Eliminar" 
                                                       onclick="confirmarEliminar('<?php echo $usuario['id']; ?>', '<?php echo htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellidos']); ?>')">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No se encontraron usuarios.</td>
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
                                    <a class="page-link" href="?pagina=<?php echo $pagina - 1; ?><?php echo isset($_GET['empresa']) ? '&empresa=' . $_GET['empresa'] : ''; ?><?php echo isset($_GET['rol']) ? '&rol=' . $_GET['rol'] : ''; ?><?php echo isset($_GET['buscar']) ? '&buscar=' . urlencode($_GET['buscar']) : ''; ?>" aria-label="Anterior">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                
                                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                <li class="page-item <?php echo ($pagina == $i) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $i; ?><?php echo isset($_GET['empresa']) ? '&empresa=' . $_GET['empresa'] : ''; ?><?php echo isset($_GET['rol']) ? '&rol=' . $_GET['rol'] : ''; ?><?php echo isset($_GET['buscar']) ? '&buscar=' . urlencode($_GET['buscar']) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo ($pagina >= $total_paginas) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $pagina + 1; ?><?php echo isset($_GET['empresa']) ? '&empresa=' . $_GET['empresa'] : ''; ?><?php echo isset($_GET['rol']) ? '&rol=' . $_GET['rol'] : ''; ?><?php echo isset($_GET['buscar']) ? '&buscar=' . urlencode($_GET['buscar']) : ''; ?>" aria-label="Siguiente">
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
                    ¿Está seguro de que desea eliminar al usuario <span id="nombreUsuario"></span>?
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
        function confirmarEliminar(id, nombre) {
            document.getElementById('nombreUsuario').textContent = nombre;
            document.getElementById('btnEliminar').href = 'usuarios.php?eliminar=' + id;
            
            var modal = new bootstrap.Modal(document.getElementById('eliminarModal'));
            modal.show();
        }
    </script>
</body>
</html>
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
$id_licencia = isset($_GET['id']) ? $_GET['id'] : '';

// Verificar si hay mensaje de creación
if (isset($_GET['mensaje']) && $_GET['mensaje'] == 'creada') {
    $mensaje = "Licencia creada correctamente.";
    $tipo_mensaje = "success";
}

// Obtener datos de la licencia
try {
    $db = new Database();
    $conn = $db->connect();
    
    $stmt = $conn->prepare("SELECT l.*, 
                           e.empresa as empresa_nombre,
                           tl.licencia as tipo_licencia_nombre,
                           tl.duracion as duracion_dias
                           FROM licencias l 
                           JOIN empresa e ON l.nit = e.nit 
                           JOIN tipo_licencia tl ON l.id_tipo_licencia = tl.id_tipo_licencia 
                           WHERE l.id_licencia = ?");
    $stmt->execute([$id_licencia]);
    
    if ($stmt->rowCount() == 0) {
        header('Location: licencias.php');
        exit;
    }
    
    $licencia = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calcular días restantes
    $fecha_actual = new DateTime();
    $fecha_fin = new DateTime($licencia['fecha_fin']);
    $intervalo = $fecha_actual->diff($fecha_fin);
    $dias_restantes = $intervalo->invert ? -$intervalo->days : $intervalo->days;
    
    // Obtener usuarios de la empresa
    $stmt = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE nit = ?");
    $stmt->execute([$licencia['nit']]);
    $total_usuarios = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    $mensaje = "Error al obtener datos: " . $e->getMessage();
    $tipo_mensaje = "danger";
}

// Procesar cambio de estado si se solicita
if (isset($_GET['cambiar_estado']) && !empty($_GET['cambiar_estado'])) {
    $nuevo_estado = $_GET['cambiar_estado'];
    
    if (in_array($nuevo_estado, ['Activa', 'Inactiva', 'Expirada'])) {
        try {
            $stmt = $conn->prepare("UPDATE licencias SET estado = ? WHERE id_licencia = ?");
            $stmt->execute([$nuevo_estado, $id_licencia]);
            
            $mensaje = "Estado de la licencia actualizado correctamente.";
            $tipo_mensaje = "success";
            
            // Actualizar el estado en el array de licencia
            $licencia['estado'] = $nuevo_estado;
        } catch (PDOException $e) {
            $mensaje = "Error al actualizar el estado de la licencia: " . $e->getMessage();
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
    <title>Detalles de Licencia - Super Admin</title>
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
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Detalles de Licencia</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="licencias_editar.php?id=<?php echo $id_licencia; ?>" class="btn btn-sm btn-outline-warning">
                                <i class="bi bi-pencil"></i> Editar
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-toggle-on"></i> Cambiar Estado
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="?id=<?php echo $id_licencia; ?>&cambiar_estado=Activa">Marcar como Activa</a></li>
                                <li><a class="dropdown-item" href="?id=<?php echo $id_licencia; ?>&cambiar_estado=Inactiva">Marcar como Inactiva</a></li>
                                <li><a class="dropdown-item" href="?id=<?php echo $id_licencia; ?>&cambiar_estado=Expirada">Marcar como Expirada</a></li>
                            </ul>
                        </div>
                        <a href="licencias.php" class="btn btn-sm btn-outline-secondary">
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
                                    <i class="bi bi-key me-1"></i> Información de la Licencia
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <p><strong>ID:</strong> <?php echo $licencia['id_licencia']; ?></p>
                                        <p><strong>Empresa:</strong> <a href="empresas_ver.php?nit=<?php echo $licencia['nit']; ?>"><?php echo htmlspecialchars($licencia['empresa_nombre']); ?></a></p>
                                        <p><strong>Tipo de Licencia:</strong> <?php echo htmlspecialchars($licencia['tipo_licencia_nombre']); ?></p>
                                        <p><strong>Duración:</strong> <?php echo $licencia['duracion_dias']; ?> días</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Fecha de Inicio:</strong> <?php echo date('d/m/Y', strtotime($licencia['fecha_ini'])); ?></p>
                                        <p><strong>Fecha de Fin:</strong> <?php echo date('d/m/Y', strtotime($licencia['fecha_fin'])); ?></p>
                                        <p><strong>Código de Licencia:</strong> <?php echo htmlspecialchars($licencia['codigo_licencia']); ?></p>
                                        <p>
                                            <strong>Estado:</strong> 
                                            <?php if ($licencia['estado'] == 'Activa'): ?>
                                                <span class="badge bg-success">Activa</span>
                                            <?php elseif ($licencia['estado'] == 'Expirada'): ?>
                                                <span class="badge bg-danger">Expirada</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactiva</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
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
                                <?php if ($licencia['estado'] == 'Activa'): ?>
                                    <div class="alert alert-success">
                                        <h6 class="alert-heading">Licencia Activa</h6>
                                        <p>
                                            <?php if ($dias_restantes > 0): ?>
                                                Quedan <strong><?php echo $dias_restantes; ?> días</strong> para que expire esta licencia.
                                            <?php else: ?>
                                                Esta licencia debería haber expirado hace <strong><?php echo abs($dias_restantes); ?> días</strong>.
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                <?php elseif ($licencia['estado'] == 'Expirada'): ?>
                                    <div class="alert alert-danger">
                                        <h6 class="alert-heading">Licencia Expirada</h6>
                                        <p>Esta licencia expiró hace <strong><?php echo abs($dias_restantes); ?> días</strong>.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-secondary">
                                        <h6 class="alert-heading">Licencia Inactiva</h6>
                                        <p>Esta licencia está actualmente inactiva.</p>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="d-grid gap-2">
                                    <a href="empresas_ver.php?nit=<?php echo $licencia['nit']; ?>" class="btn btn-primary">
                                        <i class="bi bi-building"></i> Ver Empresa
                                    </a>
                                    <a href="licencias_crear.php?nit=<?php echo $licencia['nit']; ?>" class="btn btn-outline-success">
                                        <i class="bi bi-plus-circle"></i> Asignar Nueva Licencia
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-people me-1"></i> Usuarios
                                </h5>
                            </div>
                            <div class="card-body">
                                <p>La empresa tiene <strong><?php echo $total_usuarios; ?> usuarios</strong> registrados.</p>
                                <div class="d-grid">
                                    <a href="usuarios.php?nit=<?php echo $licencia['nit']; ?>" class="btn btn-outline-info">
                                        <i class="bi bi-list"></i> Ver Usuarios
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
</body>
</html>
<?php
session_start();

if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1) {
    header('Location: index.php');
    exit;
}

require_once '../conexion/conexion.php';

$mensaje = '';
$tipo_mensaje = '';
$id_tipo_licencia = '';
$fecha_ini = date('Y-m-d');
$nit = isset($_GET['nit']) ? (int)$_GET['nit'] : '';

try {
    $db = new Database();
    $conn = $db->connect();
    
    $stmt = $conn->query("SELECT id_tipo_licencia, licencia, duracion FROM tipo_licencia ORDER BY licencia");
    $tipos_licencia = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $conn->query("SELECT nit, empresa FROM empresa ORDER BY empresa");
    $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $mensaje = "Error al obtener datos: " . $e->getMessage();
    $tipo_mensaje = "danger";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_tipo_licencia = (int)$_POST['id_tipo_licencia'];
    $fecha_ini = $_POST['fecha_ini'];
    $nit = (int)$_POST['nit'];
    
    $errores = [];
    
    if (empty($id_tipo_licencia)) {
        $errores[] = "El tipo de licencia es obligatorio.";
    }
    
    if (empty($fecha_ini)) {
        $errores[] = "La fecha de inicio es obligatoria.";
    }
    
    if (empty($nit)) {
        $errores[] = "La empresa es obligatoria.";
    }
    
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM licencias WHERE nit = ? AND id_tipo_licencia = ? AND estado = 'Activa'");
        $stmt->execute([$nit, $id_tipo_licencia]);
        if ($stmt->fetchColumn() > 0) {
            $errores[] = "Esta empresa ya tiene una licencia activa de este tipo.";
        }
    } catch (PDOException $e) {
        $errores[] = "Error al verificar licencias existentes: " . $e->getMessage();
    }
    
    if (empty($errores)) {
        try {
            // El trigger calculará automáticamente la fecha_fin y generará el código
            $stmt = $conn->prepare("INSERT INTO licencias (id_tipo_licencia, fecha_ini, nit) VALUES (?, ?, ?)");
            $stmt->execute([$id_tipo_licencia, $fecha_ini, $nit]);
            
            $id_licencia = $conn->lastInsertId();
            
            $stmt = $conn->prepare("SELECT codigo_licencia FROM licencias WHERE id_licencia = ?");
            $stmt->execute([$id_licencia]);
            $codigo_licencia = $stmt->fetchColumn();
            
            $mensaje = "Licencia creada correctamente con código: " . $codigo_licencia;
            $tipo_mensaje = "success";
            
            header("Location: licencias_ver.php?id=$id_licencia&mensaje=creada");
            exit;
        } catch (PDOException $e) {
            $mensaje = "Error al crear la licencia: " . $e->getMessage();
            $tipo_mensaje = "danger";
        }
    } else {
        $mensaje = implode("<br>", $errores);
        $tipo_mensaje = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Licencia - Super Admin</title>
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
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Crear Nueva Licencia</h1>
                </div>
                
                <?php if (!empty($mensaje)): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-key-fill me-1"></i> Formulario de Licencia
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="id_tipo_licencia" class="form-label">Tipo de Licencia *</label>
                                    <select class="form-select" id="id_tipo_licencia" name="id_tipo_licencia" required onchange="actualizarDuracion()">
                                        <option value="">Seleccione un tipo</option>
                                        <?php foreach ($tipos_licencia as $tipo): ?>
                                        <option value="<?php echo $tipo['id_tipo_licencia']; ?>" data-duracion="<?php echo $tipo['duracion']; ?>" <?php echo ($id_tipo_licencia == $tipo['id_tipo_licencia']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($tipo['licencia']); ?> (<?php echo $tipo['duracion']; ?> días)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="nit" class="form-label">Empresa *</label>
                                    <select class="form-select" id="nit" name="nit" required>
                                        <option value="">Seleccione una empresa</option>
                                        <?php foreach ($empresas as $empresa): ?>
                                        <option value="<?php echo $empresa['nit']; ?>" <?php echo ($nit == $empresa['nit']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($empresa['empresa']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="fecha_ini" class="form-label">Fecha de Inicio *</label>
                                    <input type="date" class="form-control" id="fecha_ini" name="fecha_ini" value="<?php echo $fecha_ini; ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="fecha_fin_estimada" class="form-label">Fecha de Fin Estimada</label>
                                    <input type="date" class="form-control" id="fecha_fin_estimada" disabled>
                                    <div class="form-text">La fecha de fin se calcula automáticamente según el tipo de licencia.</div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle-fill me-2"></i> Se generará automáticamente un código único de 20 caracteres para esta licencia.
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="licencias.php" class="btn btn-secondary me-md-2">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Crear Licencia</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function actualizarDuracion() {
            const tipoLicencia = document.getElementById('id_tipo_licencia');
            const fechaInicio = document.getElementById('fecha_ini');
            const fechaFinEstimada = document.getElementById('fecha_fin_estimada');
            
            if (tipoLicencia.value && fechaInicio.value) {
                const duracionDias = parseInt(tipoLicencia.options[tipoLicencia.selectedIndex].dataset.duracion);
                const inicio = new Date(fechaInicio.value);
                const fin = new Date(inicio);
                fin.setDate(fin.getDate() + duracionDias);
                
                const year = fin.getFullYear();
                const month = String(fin.getMonth() + 1).padStart(2, '0');
                const day = String(fin.getDate()).padStart(2, '0');
                fechaFinEstimada.value = `${year}-${month}-${day}`;
            } else {
                fechaFinEstimada.value = '';
            }
        }
        
        document.addEventListener('DOMContentLoaded', actualizarDuracion);
        document.getElementById('fecha_ini').addEventListener('change', actualizarDuracion);
    </script>
</body>
</html>
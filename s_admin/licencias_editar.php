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
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$id_empresa = '';
$id_tipo_licencia = '';
$fecha_inicio = '';
$fecha_fin = '';
$estado = '';

// Verificar si existe la licencia
try {
    $db = new Database();
    $conn = $db->connect();
    
    // Obtener datos de la licencia
    $stmt = $conn->prepare("SELECT * FROM licencias WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() == 0) {
        header('Location: licencias.php');
        exit;
    }
    
    $licencia = $stmt->fetch(PDO::FETCH_ASSOC);
    $id_empresa = $licencia['id_empresa'];
    $id_tipo_licencia = $licencia['id_tipo_licencia'];
    $fecha_inicio = $licencia['fecha_inicio'];
    $fecha_fin = $licencia['fecha_fin'];
    $estado = $licencia['estado'];
    
    // Obtener empresas
    $stmt = $conn->query("SELECT id, nombre FROM empresa ORDER BY nombre");
    $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener tipos de licencia
    $stmt = $conn->query("SELECT id, nombre, descripcion, duracion_dias FROM tipo_licencia ORDER BY nombre");
    $tipos_licencia = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $mensaje = "Error al obtener datos: " . $e->getMessage();
    $tipo_mensaje = "danger";
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_empresa = (int)$_POST['id_empresa'];
    $id_tipo_licencia = (int)$_POST['id_tipo_licencia'];
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'];
    $estado = $_POST['estado'];
    
    if (empty($id_empresa) || empty($id_tipo_licencia) || empty($fecha_inicio) || empty($fecha_fin)) {
        $mensaje = "Todos los campos marcados con * son obligatorios.";
        $tipo_mensaje = "danger";
    } else {
        try {
            // Actualizar la licencia
            $stmt = $conn->prepare("UPDATE licencias SET id_empresa = ?, id_tipo_licencia = ?, fecha_inicio = ?, fecha_fin = ?, estado = ? WHERE id = ?");
            $stmt->execute([$id_empresa, $id_tipo_licencia, $fecha_inicio, $fecha_fin, $estado, $id]);
            
            $mensaje = "Licencia actualizada correctamente.";
            $tipo_mensaje = "success";
        } catch (PDOException $e) {
            $mensaje = "Error al actualizar la licencia: " . $e->getMessage();
            $tipo_mensaje = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Licencia - Super Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .sidebar {
            min-height: calc(100vh - 56px);
        }
        .tipo-licencia-info {
            display: none;
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
                    <h1 class="h2">Editar Licencia</h1>
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
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="id" class="form-label">ID de Licencia</label>
                                    <input type="text" class="form-control" id="id" value="<?php echo $id; ?>" disabled>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
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
                                
                                <div class="col-md-6">
                                    <label for="id_tipo_licencia" class="form-label">Tipo de Licencia *</label>
                                    <select class="form-select" id="id_tipo_licencia" name="id_tipo_licencia" required>
                                        <option value="">Seleccione un tipo de licencia</option>
                                        <?php foreach ($tipos_licencia as $tipo): ?>
                                        <option value="<?php echo $tipo['id']; ?>" data-duracion="<?php echo $tipo['duracion_dias']; ?>" data-descripcion="<?php echo htmlspecialchars($tipo['descripcion']); ?>" <?php echo ($id_tipo_licencia == $tipo['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($tipo['licencia']); ?> (<?php echo $tipo['duracion_dias']; ?> días)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="fecha_inicio" class="form-label">Fecha de Inicio *</label>
                                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="fecha_fin" class="form-label">Fecha de Fin *</label>
                                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="<?php echo $fecha_fin; ?>" required>
                                    <div class="form-text">
                                        <button type="button" class="btn btn-sm btn-link p-0" id="recalcular-fecha-fin">
                                            Recalcular según tipo de licencia
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="estado" class="form-label">Estado *</label>
                                    <select class="form-select" id="estado" name="estado" required>
                                        <option value="Activa" <?php echo ($estado == 'Activa') ? 'selected' : ''; ?>>Activa</option>
                                        <option value="Inactiva" <?php echo ($estado == 'Inactiva') ? 'selected' : ''; ?>>Inactiva</option>
                                        <option value="Expirada" <?php echo ($estado == 'Expirada') ? 'selected' : ''; ?>>Expirada</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="alert alert-info tipo-licencia-info" id="tipo-licencia-info">
                                <h5 class="alert-heading">Información del Tipo de Licencia</h5>
                                <p id="tipo-licencia-descripcion"></p>
                                <hr>
                                <p class="mb-0">Duración: <strong><span id="tipo-licencia-duracion"></span> días</strong></p>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="licencias.php" class="btn btn-secondary me-md-2">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tipoLicenciaSelect = document.getElementById('id_tipo_licencia');
            const fechaInicioInput = document.getElementById('fecha_inicio');
            const fechaFinInput = document.getElementById('fecha_fin');
            const recalcularBtn = document.getElementById('recalcular-fecha-fin');
            const tipoLicenciaInfo = document.getElementById('tipo-licencia-info');
            const tipoLicenciaDescripcion = document.getElementById('tipo-licencia-descripcion');
            const tipoLicenciaDuracion = document.getElementById('tipo-licencia-duracion');
            
            function mostrarInfoTipoLicencia() {
                const tipoLicenciaOption = tipoLicenciaSelect.options[tipoLicenciaSelect.selectedIndex];
                
                if (tipoLicenciaOption.value) {
                    const duracionDias = parseInt(tipoLicenciaOption.getAttribute('data-duracion'));
                    const descripcion = tipoLicenciaOption.getAttribute('data-descripcion');
                    
                    // Mostrar información del tipo de licencia
                    tipoLicenciaDescripcion.textContent = descripcion || 'No hay descripción disponible.';
                    tipoLicenciaDuracion.textContent = duracionDias;
                    tipoLicenciaInfo.style.display = 'block';
                } else {
                    tipoLicenciaInfo.style.display = 'none';
                }
            }
            
            function calcularFechaFin() {
                const tipoLicenciaOption = tipoLicenciaSelect.options[tipoLicenciaSelect.selectedIndex];
                const fechaInicio = new Date(fechaInicioInput.value);
                
                if (tipoLicenciaOption.value && fechaInicio) {
                    const duracionDias = parseInt(tipoLicenciaOption.getAttribute('data-duracion'));
                    
                    // Calcular fecha de fin
                    const fechaFin = new Date(fechaInicio);
                    fechaFin.setDate(fechaFin.getDate() + duracionDias);
                    
                    // Formatear fecha para input date (YYYY-MM-DD)
                    const year = fechaFin.getFullYear();
                    const month = String(fechaFin.getMonth() + 1).padStart(2, '0');
                    const day = String(fechaFin.getDate()).padStart(2, '0');
                    
                    fechaFinInput.value = `${year}-${month}-${day}`;
                }
            }
            
            tipoLicenciaSelect.addEventListener('change', mostrarInfoTipoLicencia);
            recalcularBtn.addEventListener('click', calcularFechaFin);
            
            // Mostrar información al cargar la página si hay un tipo seleccionado
            mostrarInfoTipoLicencia();
        });
    </script>
</body>
</html>
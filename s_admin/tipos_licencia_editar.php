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
$licencia = '';
$duracion = '';

// Verificar si existe el tipo de licencia
try {
    $db = new Database();
    $conn = $db->connect();

    $stmt = $conn->prepare("SELECT * FROM tipo_licencia WHERE id_tipo_licencia = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() == 0) {
        header('Location: tipos_licencia.php');
        exit;
    }

    $tipo = $stmt->fetch(PDO::FETCH_ASSOC);
    $licencia = $tipo['licencia'];
    $duracion = $tipo['duracion'];

} catch (PDOException $e) {
    $mensaje = "Error al obtener el tipo de licencia: " . $e->getMessage();
    $tipo_mensaje = "danger";
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $licencia = trim($_POST['licencia']);
    $duracion = (int)$_POST['duracion'];

    if (empty($licencia)) {
        $mensaje = "El nombre del tipo de licencia es obligatorio.";
        $tipo_mensaje = "danger";
    } elseif ($duracion <= 0) {
        $mensaje = "La duración debe ser un número positivo de días.";
        $tipo_mensaje = "danger";
    } else {
        try {
            // Actualizar el tipo de licencia
            $stmt = $conn->prepare("UPDATE tipo_licencia SET licencia = ?, duracion = ? WHERE id_tipo_licencia = ?");
            $stmt->execute([$licencia, $duracion, $id]);

            $mensaje = "Tipo de licencia actualizado correctamente.";
            $tipo_mensaje = "success";
        } catch (PDOException $e) {
            $mensaje = "Error al actualizar el tipo de licencia: " . $e->getMessage();
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
    <title>Editar Tipo de Licencia - Super Admin</title>
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
                    <li class="nav-item"><a class="nav-link" href="empresas.php">Empresas</a></li>
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
            <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="empresas.php"><i class="bi bi-building"></i> Empresas</a></li>
                        <li class="nav-item"><a class="nav-link" href="licencias.php"><i class="bi bi-key"></i> Licencias</a></li>
                        <li class="nav-item"><a class="nav-link" href="usuarios.php"><i class="bi bi-people"></i> Usuarios</a></li>
                        <li class="nav-item"><a class="nav-link active" href="tipos_licencia.php"><i class="bi bi-tags"></i> Tipos de Licencia</a></li>
                        
                    </ul>
                </div>
            </div>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Editar Tipo de Licencia</h1>
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
                                <label for="id" class="form-label">ID del Tipo de Licencia</label>
                                <input type="text" class="form-control" id="id" value="<?php echo $id; ?>" disabled>
                            </div>

                            <div class="mb-3">
                                <label for="licencia" class="form-label">Nombre del Tipo de Licencia *</label>
                                <input type="text" class="form-control" id="licencia" name="licencia" value="<?php echo htmlspecialchars($licencia); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="duracion" class="form-label">Duración en Días *</label>
                                <input type="number" class="form-control" id="duracion" name="duracion" value="<?php echo $duracion; ?>" min="1" required>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="tipos_licencia.php" class="btn btn-secondary me-md-2">Cancelar</a>
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

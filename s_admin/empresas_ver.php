<?php
session_start();

// Verificar si el usuario está logueado y es super admin
if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1) {
    header('Location: index.php');
    exit;
}

require_once '../conexion/conexion.php';

$nit = isset($_GET['id']) ? $_GET['id'] : '';
$mensaje = '';
$tipo_mensaje = '';
$empresa = [];
$licencias = [];
$usuarios = [];

try {
    $db = new Database();
    $conn = $db->connect();

    // Consultar empresa
    $stmt = $conn->prepare("SELECT * FROM empresa WHERE nit = ?");
    $stmt->execute([$nit]);
    if ($stmt->rowCount() > 0) {
        $empresa = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Consultar licencias
    $stmt = $conn->prepare("
        SELECT l.*, t.licencia AS tipo_licencia, t.duracion AS tipo_duracion
        FROM licencias l
        INNER JOIN tipo_licencia t ON l.id_tipo_licencia = t.id_tipo_licencia
        WHERE l.nit = ?
        ORDER BY l.fecha_ini DESC
    ");
    $stmt->execute([$nit]);
    $licencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Consultar usuarios
    $stmt = $conn->prepare("
        SELECT u.*, r.rol
        FROM usuarios u
        INNER JOIN roles r ON u.id_rol = r.id_rol
        WHERE u.nit = ?
        ORDER BY u.nombres ASC
    ");
    $stmt->execute([$nit]);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $mensaje = "Error al consultar datos: " . $e->getMessage();
    $tipo_mensaje = "danger";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalles de Empresa - Super Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: calc(100vh - 56px);
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-danger">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php"><i class="bi bi-shield-lock"></i> Panel Super Admin</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?php echo $_SESSION['nombres']; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="../logout.php">Cerrar Sesión</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container-fluid mt-4">
    <div class="row">
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

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2>Detalles de Empresa</h2>
                <div class="btn-group">
                    <?php if (!empty($empresa)): ?>
                        <a href="empresas_editar.php?id=<?php echo $empresa['nit']; ?>" class="btn btn-outline-warning btn-sm"><i class="bi bi-pencil"></i> Editar</a>
                        <a href="licencias_crear.php?empresa=<?php echo $empresa['nit']; ?>" class="btn btn-outline-success btn-sm"><i class="bi bi-key-fill"></i> Asignar Licencia</a>
                        <a href="usuarios_crear.php?empresa=<?php echo $empresa['nit']; ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-person-plus"></i> Agregar Usuario</a>
                    <?php endif; ?>
                    <a href="empresas.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Volver</a>
                </div>
            </div>

            <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show">
                <?php echo $mensaje; ?>
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Información de la Empresa -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white"><i class="bi bi-building"></i> Información de la Empresa</div>
                <div class="card-body">
                    <?php if (!empty($empresa)): ?>
                        <p><strong>NIT:</strong> <?php echo htmlspecialchars($empresa['nit']); ?></p>
                        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($empresa['empresa']); ?></p>
                        <p><strong>Total de Licencias:</strong> <?php echo count($licencias); ?></p>
                        <p><strong>Total de Usuarios:</strong> <?php echo count($usuarios); ?></p>
                    <?php else: ?>
                        <div class="alert alert-warning mb-0">La empresa no fue encontrada.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Licencias -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white"><i class="bi bi-key"></i> Licencias Asignadas</div>
                <div class="card-body">
                    <?php if (count($licencias) > 0): ?>
                    <table class="table table-bordered table-hover">
                        <thead><tr><th>ID</th><th>Tipo</th><th>Fecha Inicio</th><th>Fecha Fin</th><th>Estado</th></tr></thead>
                        <tbody>
                            <?php foreach ($licencias as $l): ?>
                            <tr>
                                <td><?php echo $l['id_licencia']; ?></td>
                                <td><?php echo $l['tipo_licencia']; ?></td>
                                <td><?php echo $l['fecha_ini']; ?></td>
                                <td><?php echo $l['fecha_fin']; ?></td>
                                <td><span class="badge bg-<?php echo $l['estado'] == 'Activa' ? 'success' : ($l['estado'] == 'Expirada' ? 'danger' : 'secondary'); ?>"><?php echo $l['estado']; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p class="text-muted">No hay licencias registradas para esta empresa.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Usuarios -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white"><i class="bi bi-people"></i> Usuarios Registrados</div>
                <div class="card-body">
                    <?php if (count($usuarios) > 0): ?>
                    <table class="table table-striped">
                        <thead><tr><th>ID</th><th>Nombre</th><th>Correo</th><th>Rol</th></tr></thead>
                        <tbody>
                            <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td><?php echo $u['id']; ?></td>
                                <td><?php echo htmlspecialchars($u['nombres'] . ' ' . $u['apellidos']); ?></td>
                                <td><?php echo htmlspecialchars($u['correo']); ?></td>
                                <td><span class="badge bg-<?php echo $u['id_rol'] == 1 ? 'danger' : 'primary'; ?>"><?php echo $u['rol']; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p class="text-muted">No hay usuarios registrados para esta empresa.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

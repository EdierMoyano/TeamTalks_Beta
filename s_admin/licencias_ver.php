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

try {
    $db = new Database();
    $conn = $db->connect();

    // Obtener información de la licencia
    $stmt = $conn->prepare("
        SELECT l.*, 
               e.empresa AS empresa_nombre,
               tl.licencia AS tipo_licencia_nombre,
               tl.duracion AS duracion_dias,
               es.estado
        FROM licencias l
        JOIN empresa e ON l.nit = e.nit
        JOIN tipo_licencia tl ON l.id_tipo_licencia = tl.id_tipo_licencia
        JOIN estado es ON l.id_estado = es.id_estado
        WHERE l.id_licencia = ?
    ");
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

// Procesar cambio de estado
if (isset($_GET['cambiar_estado']) && is_numeric($_GET['cambiar_estado'])) {
    $nuevo_estado = intval($_GET['cambiar_estado']);
    if (in_array($nuevo_estado, [1, 2])) {
        try {
            $stmt = $conn->prepare("UPDATE licencias SET id_estado = ? WHERE id_licencia = ?");
            $stmt->execute([$nuevo_estado, $id_licencia]);

            $mensaje = "Estado de la licencia actualizado correctamente.";
            $tipo_mensaje = "success";

            // Obtener nuevo nombre del estado
            $stmt = $conn->prepare("SELECT estado FROM estado WHERE id_estado = ?");
            $stmt->execute([$nuevo_estado]);
            $licencia['estado'] = $stmt->fetchColumn();
            $licencia['id_estado'] = $nuevo_estado;

        } catch (PDOException $e) {
            $mensaje = "Error al actualizar el estado: " . $e->getMessage();
            $tipo_mensaje = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalles de Licencia - Super Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
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
                <li class="nav-item"><a class="nav-link active" href="licencias.php">Licencias</a></li>
                <li class="nav-item"><a class="nav-link" href="usuarios.php">Usuarios</a></li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?php echo $_SESSION['nombres']; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="../logout.php">Cerrar sesión</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container-fluid mt-4">
    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
            <?php echo $mensaje; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white"><i class="bi bi-key"></i> Información de la Licencia</div>
                <div class="card-body">
                    <p><strong>ID:</strong> <?php echo $licencia['id_licencia']; ?></p>
                    <p><strong>Empresa:</strong> <a href="empresas_ver.php?nit=<?php echo $licencia['nit']; ?>"><?php echo htmlspecialchars($licencia['empresa_nombre']); ?></a></p>
                    <p><strong>Tipo de Licencia:</strong> <?php echo htmlspecialchars($licencia['tipo_licencia_nombre']); ?></p>
                    <p><strong>Duración:</strong> <?php echo $licencia['duracion_dias']; ?> días</p>
                    <p><strong>Fecha de Inicio:</strong> <?php echo date('d/m/Y', strtotime($licencia['fecha_ini'])); ?></p>
                    <p><strong>Fecha de Fin:</strong> <?php echo date('d/m/Y', strtotime($licencia['fecha_fin'])); ?></p>
                    <p><strong>Código de Licencia:</strong> <?php echo htmlspecialchars($licencia['codigo_licencia']); ?></p>
                    <p><strong>Estado:</strong>
                        <?php
                        $badge = $licencia['id_estado'] == 1 ? 'success' : 'secondary';
                        ?>
                        <span class="badge bg-<?php echo $badge; ?>"><?php echo $licencia['estado']; ?></span>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Cambiar estado -->
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark"><i class="bi bi-gear"></i> Cambiar Estado</div>
                <div class="card-body">
                    <a href="?id=<?php echo $id_licencia; ?>&cambiar_estado=1" class="btn btn-outline-success w-100 mb-2"><i class="bi bi-check-circle"></i> Marcar como Activa</a>
                    <a href="?id=<?php echo $id_licencia; ?>&cambiar_estado=2" class="btn btn-outline-secondary w-100"><i class="bi bi-x-circle"></i> Marcar como Inactiva</a>
                </div>
            </div>

            <!-- Resumen -->
            <div class="card">
                <div class="card-header bg-success text-white"><i class="bi bi-clock"></i> Resumen</div>
                <div class="card-body">
                    <?php if ($licencia['id_estado'] == 1): ?>
                        <p>Quedan <strong><?php echo $dias_restantes; ?> días</strong> para que expire esta licencia.</p>
                    <?php else: ?>
                        <p>Esta licencia está inactiva.</p>
                    <?php endif; ?>

                    <hr>
                    <p>La empresa tiene <strong><?php echo $total_usuarios; ?> usuarios</strong>.</p>
                    <a href="usuarios.php?nit=<?php echo $licencia['nit']; ?>" class="btn btn-info w-100 mt-2"><i class="bi bi-people"></i> Ver Usuarios</a>
                    <a href="licencias_crear.php?nit=<?php echo $licencia['nit']; ?>" class="btn btn-outline-primary w-100 mt-2"><i class="bi bi-plus-circle"></i> Asignar Nueva Licencia</a>
                    <a href="licencias.php" class="btn btn-outline-dark w-100 mt-2"><i class="bi bi-arrow-left"></i> Volver</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

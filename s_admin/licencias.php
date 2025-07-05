<?php
session_start();

if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1) {
    header('Location: index.php');
    exit;
}

require_once '../conexion/conexion.php';

$mensaje = '';
$tipo_mensaje = '';

// Activar licencia (id_estado = 1)
if (isset($_GET['activar']) && !empty($_GET['activar'])) {
    $id_activar = $_GET['activar'];

    try {
        $db = new Database();
        $conn = $db->connect();
        $stmt = $conn->prepare("UPDATE licencias SET id_estado = 1 WHERE id_licencia = ?");
        $stmt->execute([$id_activar]);
        $mensaje = "Licencia activada correctamente.";
        $tipo_mensaje = "success";
    } catch (PDOException $e) {
        $mensaje = "Error al activar la licencia: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}
// Desactivar licencia (id_estado = 2)
elseif (isset($_GET['desactivar'])) {
    $id_desactivar = $_GET['desactivar'];

    try {
        $db = new Database();
        $conn = $db->connect();
        $stmt = $conn->prepare("UPDATE licencias SET id_estado = 2 WHERE id_licencia = ?");
        $stmt->execute([$id_desactivar]);
        $mensaje = "Licencia desactivada correctamente.";
        $tipo_mensaje = "success";
    } catch (PDOException $e) {
        $mensaje = "Error al desactivar la licencia: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

try {
    $db = new Database();
    $conn = $db->connect();

    $where = [];
    $params = [];

    if (isset($_GET['tipo']) && !empty($_GET['tipo'])) {
        $where[] = "l.id_tipo_licencia = ?";
        $params[] = $_GET['tipo'];
    }

    if (isset($_GET['empresa']) && !empty($_GET['empresa'])) {
        $where[] = "l.nit = ?";
        $params[] = $_GET['empresa'];
    }

    if (isset($_GET['estado']) && $_GET['estado'] !== '') {
        $where[] = "l.id_estado = ?";
        $params[] = $_GET['estado'];
    }

    $busqueda = $_GET['buscar'] ?? '';
    if (!empty($busqueda)) {
        $where[] = "(l.codigo_licencia LIKE ? OR e.empresa LIKE ?)";
        $params[] = "%$busqueda%";
        $params[] = "%$busqueda%";
    }

    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

    $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    $por_pagina = 10;
    $inicio = ($pagina - 1) * $por_pagina;

    $stmt = $conn->prepare("SELECT COUNT(*) FROM licencias l 
                            JOIN tipo_licencia tl ON l.id_tipo_licencia = tl.id_tipo_licencia 
                            JOIN empresa e ON l.nit = e.nit 
                            $whereClause");
    $stmt->execute($params);
    $total_registros = $stmt->fetchColumn();
    $total_paginas = ceil($total_registros / $por_pagina);

    $stmt = $conn->prepare("SELECT l.*, tl.licencia AS tipo_nombre, e.empresa AS empresa_nombre, 
                            DATEDIFF(l.fecha_fin, CURDATE()) AS dias_restantes 
                            FROM licencias l 
                            JOIN tipo_licencia tl ON l.id_tipo_licencia = tl.id_tipo_licencia 
                            JOIN empresa e ON l.nit = e.nit 
                            $whereClause 
                            ORDER BY l.fecha_ini DESC 
                            LIMIT $inicio, $por_pagina");
    $stmt->execute($params);
    $licencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->query("SELECT id_tipo_licencia, licencia FROM tipo_licencia ORDER BY licencia");
    $tipos_licencia = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        <a class="navbar-brand" href="dashboard.php"><i class="bi bi-shield-lock"></i> Panel Super Admin</a>
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
                    <li class="nav-item"><a class="nav-link active" href="licencias.php"><i class="bi bi-key"></i> Licencias</a></li>
                    <li class="nav-item"><a class="nav-link" href="usuarios.php"><i class="bi bi-people"></i> Usuarios</a></li>
                    <li class="nav-item"><a class="nav-link" href="tipos_licencia.php"><i class="bi bi-tags"></i> Tipos de Licencia</a></li>
                </ul>
            </div>
        </div>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Gestión de Licencias</h1>
                <div><a href="licencias_crear.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg"></i> Nueva Licencia</a></div>
            </div>

            <?php if (!empty($mensaje)): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-funnel me-1"></i> Filtros y Búsqueda</div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Tipo de Licencia</label>
                            <select class="form-select" name="tipo">
                                <option value="">Todos</option>
                                <?php foreach ($tipos_licencia as $tipo): ?>
                                    <option value="<?= $tipo['id_tipo_licencia']; ?>" <?= (isset($_GET['tipo']) && $_GET['tipo'] == $tipo['id_tipo_licencia']) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($tipo['licencia']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Empresa</label>
                            <select class="form-select" name="empresa">
                                <option value="">Todas</option>
                                <?php foreach ($empresas as $empresa): ?>
                                    <option value="<?= $empresa['nit']; ?>" <?= (isset($_GET['empresa']) && $_GET['empresa'] == $empresa['nit']) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($empresa['empresa']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Estado</label>
                            <select class="form-select" name="estado">
                                <option value="">Todos</option>
                                <option value="1" <?= (isset($_GET['estado']) && $_GET['estado'] == '1') ? 'selected' : ''; ?>>Activa</option>
                                <option value="2" <?= (isset($_GET['estado']) && $_GET['estado'] == '2') ? 'selected' : ''; ?>>Inactiva</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Buscar</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="buscar" placeholder="Código o empresa..." value="<?= htmlspecialchars($_GET['buscar'] ?? '') ?>">
                                <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Buscar</button>
                            </div>
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <?php if ($_GET): ?>
                                <a href="licencias.php" class="btn btn-secondary">Limpiar filtros</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><i class="bi bi-table me-1"></i> Listado de Licencias</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Código</th>
                                    <th>Tipo</th>
                                    <th>Empresa</th>
                                    <th>Inicio</th>
                                    <th>Fin</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($licencias): foreach ($licencias as $lic): ?>
                                    <tr>
                                        <td><?= $lic['id_licencia'] ?></td>
                                        <td><span class="badge bg-dark"><?= $lic['codigo_licencia'] ?></span></td>
                                        <td><?= htmlspecialchars($lic['tipo_nombre']) ?></td>
                                        <td><a href="empresas_ver.php?id=<?= $lic['nit'] ?>"><?= htmlspecialchars($lic['empresa_nombre']) ?></a></td>
                                        <td><?= date('d/m/Y', strtotime($lic['fecha_ini'])) ?></td>
                                        <td><?= date('d/m/Y', strtotime($lic['fecha_fin'])) ?></td>
                                        <td>
                                            <span class="badge <?= $lic['id_estado'] == 1 ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= $lic['id_estado'] == 1 ? 'Activa' : 'Inactiva' ?>
                                            </span>
                                            <?php if ($lic['id_estado'] == 1 && $lic['dias_restantes'] > 0): ?>
                                                <span class="badge bg-info"><?= $lic['dias_restantes'] ?> días</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="licencias_ver.php?id=<?= $lic['id_licencia'] ?>" class="btn btn-sm btn-info" title="Ver detalles"><i class="bi bi-eye"></i></a>
                                                <?php if ($lic['id_estado'] == 2): ?>
                                                    <a href="licencias.php?activar=<?= $lic['id_licencia'] ?>" class="btn btn-sm btn-success" title="Activar"><i class="bi bi-check-lg"></i></a>
                                                <?php else: ?>
                                                    <a href="licencias.php?desactivar=<?= $lic['id_licencia'] ?>" class="btn btn-sm btn-warning" title="Desactivar"><i class="bi bi-x-lg"></i></a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; else: ?>
                                    <tr><td colspan="8" class="text-center">No se encontraron licencias.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($total_paginas > 1): ?>
                        <nav><ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                <li class="page-item <?= $pagina == $i ? 'active' : '' ?>">
                                    <a class="page-link" href="?pagina=<?= $i ?><?= isset($_GET['tipo']) ? '&tipo=' . $_GET['tipo'] : '' ?><?= isset($_GET['empresa']) ? '&empresa=' . $_GET['empresa'] : '' ?><?= isset($_GET['estado']) ? '&estado=' . $_GET['estado'] : '' ?><?= isset($_GET['buscar']) ? '&buscar=' . urlencode($_GET['buscar']) : '' ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul></nav>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

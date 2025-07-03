<?php
session_start();

// Verificar sesión y rol de administrador
if (!isset($_SESSION['documento']) || $_SESSION['rol'] !== 2) {
    header('Location: ../../includes/exit.php');
    exit;
}

require_once '../../conexion/conexion.php';

// Crear instancia de la conexión
$db = new Database();
$conexion = $db->connect();

if (!$conexion || !($conexion instanceof PDO)) {
    die("Error: No se pudo establecer la conexión a la base de datos");
}

// Obtener NIT del usuario logueado desde la base de datos
$nit_usuario = '';
try {
    $stmt = $conexion->prepare("SELECT nit FROM usuarios WHERE id = ? AND id_estado = 1");
    $stmt->execute([$_SESSION['documento']]);
    $usuario_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario_data && !empty($usuario_data['nit'])) {
        $nit_usuario = $usuario_data['nit'];
    } else {
        die("Error: No se pudo obtener el NIT del usuario. Contacte al administrador.");
    }
} catch (PDOException $e) {
    die("Error al obtener datos del usuario: " . $e->getMessage());
}

// Inicializar mensaje de alerta
$alertMessage = '';
$alertType = '';

// Procesar actualización de datos del aprendiz
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'actualizar_aprendiz':
            $id_aprendiz = $_POST['id_aprendiz'];
            $correo = $_POST['correo'];
            $telefono = $_POST['telefono'];

            try {
                $stmt = $conexion->prepare("UPDATE usuarios SET correo = ?, telefono = ? WHERE id = ? AND id_rol = 4");
                $stmt->execute([$correo, $telefono, $id_aprendiz]);

                $alertMessage = "Datos del aprendiz actualizados correctamente";
                $alertType = "success";
            } catch (PDOException $e) {
                $alertMessage = "Error al actualizar aprendiz: " . $e->getMessage();
                $alertType = "danger";
            }
            break;

        case 'cambiar_estado':
            $id_aprendiz = $_POST['id_aprendiz'];
            $nuevo_estado = $_POST['nuevo_estado'];

            try {
                $stmt = $conexion->prepare("UPDATE usuarios SET id_estado = ? WHERE id = ? AND id_rol = 4");
                $stmt->execute([$nuevo_estado, $id_aprendiz]);

                $alertMessage = "Estado del aprendiz actualizado correctamente";
                $alertType = "success";
            } catch (PDOException $e) {
                $alertMessage = "Error al cambiar estado: " . $e->getMessage();
                $alertType = "danger";
            }
            break;

        case 'cambiar_ficha':
            $id_aprendiz = $_POST['id_aprendiz'];
            $nueva_ficha = $_POST['nueva_ficha'];

            // Obtener la ficha actual y formación del aprendiz
            $stmt = $conexion->prepare("
                SELECT uf.id_ficha, f.id_formacion
                FROM user_ficha uf
                LEFT JOIN fichas f ON uf.id_ficha = f.id_ficha
                WHERE uf.id_user = ? AND uf.id_estado = 1
                LIMIT 1
            ");
            $stmt->execute([$id_aprendiz]);
            $actual = $stmt->fetch(PDO::FETCH_ASSOC);

            // Obtener la formación de la nueva ficha
            $stmt = $conexion->prepare("SELECT id_formacion FROM fichas WHERE id_ficha = ?");
            $stmt->execute([$nueva_ficha]);
            $nueva_formacion = $stmt->fetchColumn();

            if ($actual && $actual['id_ficha']) {
                // Ya tiene ficha, solo permitir cambio si es la misma formación
                if ($actual['id_formacion'] == $nueva_formacion) {
                    try {
                        $conexion->beginTransaction();
                        $stmt = $conexion->prepare("UPDATE user_ficha SET id_ficha = ? WHERE id_user = ?");
                        $stmt->execute([$nueva_ficha, $id_aprendiz]);
                        $conexion->commit();
                        $alertMessage = "Ficha del aprendiz actualizada correctamente";
                        $alertType = "success";
                    } catch (PDOException $e) {
                        $conexion->rollBack();
                        $alertMessage = "Error al cambiar ficha: " . $e->getMessage();
                        $alertType = "danger";
                    }
                } else {
                    $alertMessage = "Solo puedes cambiar a una ficha de la misma formación.";
                    $alertType = "danger";
                }
            } else {
                // No tiene ficha, asignar la ficha seleccionada
                try {
                    $conexion->beginTransaction();
                    $stmt = $conexion->prepare("INSERT INTO user_ficha (id_user, id_ficha, fecha_asig, id_estado) VALUES (?, ?, NOW(), 1)");
                    $stmt->execute([$id_aprendiz, $nueva_ficha]);
                    $conexion->commit();
                    $alertMessage = "Ficha asignada correctamente al aprendiz";
                    $alertType = "success";
                } catch (PDOException $e) {
                    $conexion->rollBack();
                    $alertMessage = "Error al asignar ficha: " . $e->getMessage();
                    $alertType = "danger";
                }
            }
            break;
    }
}

// Obtener parámetros de filtro y paginación
$filtro_ficha = isset($_GET['filtro_ficha']) ? $_GET['filtro_ficha'] : '';
$filtro_estado = isset($_GET['filtro_estado']) ? $_GET['filtro_estado'] : '';
$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$aprendices_por_pagina = 8;
$offset = ($pagina_actual - 1) * $aprendices_por_pagina;

// Construir consulta con filtros
$where_conditions = ["u.id_rol = 4", "u.nit = ?"];
$params = [$nit_usuario];

if (!empty($filtro_ficha)) {
    $where_conditions[] = "uf.id_ficha = ?";
    $params[] = $filtro_ficha;
}

if (!empty($filtro_estado)) {
    $where_conditions[] = "u.id_estado = ?";
    $params[] = $filtro_estado;
}

if (!empty($busqueda)) {
    $where_conditions[] = "(u.nombres LIKE ? OR u.apellidos LIKE ? OR u.correo LIKE ? OR u.id LIKE ?)";
    $busqueda_param = "%$busqueda%";
    $params = array_merge($params, [$busqueda_param, $busqueda_param, $busqueda_param, $busqueda_param]);
}

$where_clause = implode(" AND ", $where_conditions);

// Obtener total de aprendices para paginación
try {
    $count_query = "
        SELECT COUNT(DISTINCT u.id) as total 
        FROM usuarios u
        LEFT JOIN user_ficha uf ON u.id = uf.id_user
        WHERE $where_clause
    ";
    $stmt = $conexion->prepare($count_query);
    $stmt->execute($params);
    $total_aprendices = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_paginas = ceil($total_aprendices / $aprendices_por_pagina);
} catch (PDOException $e) {
    $total_aprendices = 0;
    $total_paginas = 0;
}

// En la sección de consulta SQL (línea ~150), reemplaza la consulta principal con:

// Obtener aprendices con paginación y ordenamiento por información
$aprendices = [];
try {
    $query = "
        SELECT 
            u.id,
            u.nombres,
            u.apellidos,
            u.correo,
            u.telefono,
            u.fecha_registro,
            u.id_estado,
            e.estado,
            uf.id_ficha,
            f.id_ficha as ficha_numero,
            f.id_formacion as id_formacion,
            fo.nombre as programa_formacion,
            tf.tipo_formacion,
            j.jornada,
            -- Calcular promedio general
            COALESCE(AVG(au.nota), 0) as promedio_general,
            -- Contar actividades totales y aprobadas
            COUNT(au.id_actividad_user) as total_actividades,
            SUM(CASE WHEN au.nota >= 4.0 THEN 1 ELSE 0 END) as actividades_aprobadas,
            -- Calcular porcentaje de aprobación
            CASE 
                WHEN COUNT(au.id_actividad_user) > 0 
                THEN ROUND((SUM(CASE WHEN au.nota >= 4.0 THEN 1 ELSE 0 END) * 100.0 / COUNT(au.id_actividad_user)), 2)
                ELSE 0 
            END as porcentaje_aprobacion,
            -- Calcular índice de información (para ordenamiento)
            (
                CASE WHEN u.telefono IS NOT NULL AND u.telefono != '' THEN 1 ELSE 0 END +
                CASE WHEN uf.id_ficha IS NOT NULL THEN 2 ELSE 0 END +
                CASE WHEN COUNT(au.id_actividad_user) > 0 THEN 3 ELSE 0 END +
                CASE WHEN AVG(au.nota) IS NOT NULL THEN 2 ELSE 0 END
            ) as indice_informacion
        FROM usuarios u
        LEFT JOIN user_ficha uf ON u.id = uf.id_user AND uf.id_estado = 1
        LEFT JOIN fichas f ON uf.id_ficha = f.id_ficha
        LEFT JOIN formacion fo ON f.id_formacion = fo.id_formacion
        LEFT JOIN tipo_formacion tf ON fo.id_tipo_formacion = tf.id_tipo_formacion
        LEFT JOIN jornada j ON f.id_jornada = j.id_jornada
        LEFT JOIN estado e ON u.id_estado = e.id_estado
        LEFT JOIN actividades_user au ON u.id = au.id_user AND au.nota IS NOT NULL
        WHERE $where_clause
        GROUP BY u.id, u.nombres, u.apellidos, u.correo, u.telefono, u.fecha_registro, 
                 u.id_estado, e.estado, uf.id_ficha, f.id_ficha, fo.nombre, tf.tipo_formacion, j.jornada
        ORDER BY indice_informacion DESC, total_actividades DESC, u.nombres, u.apellidos
        LIMIT $aprendices_por_pagina OFFSET $offset
    ";

    $stmt = $conexion->prepare($query);
    $stmt->execute($params);
    $aprendices = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $alertMessage = "Error al cargar aprendices: " . $e->getMessage();
    $alertType = "danger";
}

// Obtener estadísticas
$stats = [
    'total_aprendices' => 0,
    'aprendices_activos' => 0,
    'aprendices_suspendidos' => 0,
    'aprendices_expulsados' => 0,
    'fichas_activas' => 0
];

try {
    // Total aprendices
    $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM usuarios WHERE id_rol = 4 AND nit = ?");
    $stmt->execute([$nit_usuario]);
    $stats['total_aprendices'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Aprendices activos
    $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM usuarios WHERE id_rol = 4 AND id_estado = 1 AND nit = ?");
    $stmt->execute([$nit_usuario]);
    $stats['aprendices_activos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Aprendices suspendidos
    $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM usuarios WHERE id_rol = 4 AND id_estado = 6 AND nit = ?");
    $stmt->execute([$nit_usuario]);
    $stats['aprendices_suspendidos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Aprendices expulsados
    $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM usuarios WHERE id_rol = 4 AND id_estado = 7 AND nit = ?");
    $stmt->execute([$nit_usuario]);
    $stats['aprendices_expulsados'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Fichas activas
    $stmt = $conexion->query("SELECT COUNT(*) as total FROM fichas WHERE id_estado = 1");
    $stats['fichas_activas'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
} catch (PDOException $e) {
    // Mantener valores por defecto
}

// Obtener fichas para filtros
$fichas_disponibles = [];
try {
    $stmt = $conexion->query("
        SELECT f.id_ficha, fo.nombre as programa 
        FROM fichas f 
        LEFT JOIN formacion fo ON f.id_formacion = fo.id_formacion 
        WHERE f.id_estado = 1 
        ORDER BY f.id_ficha
    ");
    $fichas_disponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Mantener array vacío
}

// Obtener todas las formaciones activas
$formaciones_disponibles = [];
try {
    $stmt = $conexion->query("SELECT id_formacion, nombre FROM formacion ORDER BY nombre");
    $formaciones_disponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $formaciones_disponibles = [];
}

// Endpoint AJAX para fichas por formación
if (isset($_GET['ajax_fichas']) && isset($_GET['id_formacion'])) {
    $id_formacion = $_GET['id_formacion'];
    $stmt = $conexion->prepare("SELECT id_ficha, id_ficha as ficha_numero FROM fichas WHERE id_formacion = ? AND id_estado = 1 ORDER BY id_ficha");
    $stmt->execute([$id_formacion]);
    $fichas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode($fichas);
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Aprendices - TeamTalks</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../styles/sidebard.css">
    <link rel="stylesheet" href="../styles/main.css">
    <!-- Select2 CSS y JS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>

<body>
    <div class="wrapper">
        <?php include '../includes/sidebard.php'; ?>
        <div class="main-content">
            <div class="container mt-4">
                <!-- Dashboard de estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-3">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="bi bi-people display-6 mb-2"></i>
                                            <h3 class="mb-0"><?php echo $stats['total_aprendices']; ?></h3>
                                            <small>Total Aprendices</small>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="bi bi-person-check display-6 mb-2"></i>
                                            <h3 class="mb-0"><?php echo $stats['aprendices_activos']; ?></h3>
                                            <small>Activos</small>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="bi bi-person-dash display-6 mb-2"></i>
                                            <h3 class="mb-0"><?php echo $stats['aprendices_suspendidos']; ?></h3>
                                            <small>Suspendidos</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="bi bi-person-x display-6 mb-2"></i>
                                            <h3 class="mb-0"><?php echo $stats['aprendices_expulsados']; ?></h3>
                                            <small>Expulsados</small>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="bi bi-folder-check display-6 mb-2"></i>
                                            <h3 class="mb-0"><?php echo $stats['fichas_activas']; ?></h3>
                                            <small>Fichas</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones de navegación -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex gap-3 justify-content-center flex-wrap">
                                    <button class="btn btn-primary active" id="btnTodosAprendices" onclick="mostrarSeccion('todos')">
                                        <i class="bi bi-people"></i> Todos los Aprendices
                                    </button>
                                    
                                    <button class="btn btn-outline-primary" id="btnAprendicesSuspendidos" onclick="mostrarSeccion('suspendidos')">
                                        <i class="bi bi-person-dash"></i> Suspendidos (<?php echo $stats['aprendices_suspendidos']; ?>)
                                    </button>
                                    <button class="btn btn-outline-success" id="btnReportes" onclick="mostrarModalReportes()">
                                        <i class="bi bi-file-earmark-excel"></i> Generar Reportes
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sección: Todos los aprendices -->
                <div id="seccion-todos" class="seccion-aprendices">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0">
                                <i class="bi bi-person-lines-fill"></i> Gestión de Aprendices
                            </h4>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($alertMessage)): ?>
                                <div class="alert alert-<?php echo $alertType; ?> alert-dismissible fade show" role="alert">
                                    <?php echo $alertMessage; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <!-- Filtros y buscador -->
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                                        <input type="text" class="form-control" id="buscarAprendiz"
                                            placeholder="Buscar por nombre, documento o correo..."
                                            value="<?php echo htmlspecialchars($busqueda); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" id="filtroFicha">
                                        <option value="">Todas las fichas</option>
                                        <?php foreach ($fichas_disponibles as $ficha): ?>
                                            <option value="<?php echo $ficha['id_ficha']; ?>" 
                                                <?php echo ($filtro_ficha == $ficha['id_ficha']) ? 'selected' : ''; ?>>
                                                <?php echo $ficha['id_ficha'] . ' - ' . htmlspecialchars($ficha['programa']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" id="filtroEstado">
                                        <option value="">Todos los estados</option>
                                        <option value="1" <?php echo ($filtro_estado == '1') ? 'selected' : ''; ?>>Activo</option>
                                        <option value="6" <?php echo ($filtro_estado == '6') ? 'selected' : ''; ?>>Suspendido</option>
                                        <option value="7" <?php echo ($filtro_estado == '7') ? 'selected' : ''; ?>>Expulsado</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-primary w-100" onclick="limpiarFiltros()">
                                        <i class="bi bi-arrow-clockwise"></i> Limpiar
                                    </button>
                                </div>
                            </div>

                            <!-- Lista de aprendices -->
                            <div class="row" id="aprendicesContainer">
                                <?php foreach ($aprendices as $aprendiz): ?>
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card h-100 border-<?php echo ($aprendiz['id_estado'] == 1) ? 'success' : (($aprendiz['id_estado'] == 6) ? 'warning' : 'danger'); ?>">
                                            <div class="card-header bg-<?php echo ($aprendiz['id_estado'] == 1) ? 'success' : (($aprendiz['id_estado'] == 6) ? 'warning' : 'danger'); ?> text-white">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <h6 class="mb-0 fw-bold">
                                                        <i class="bi bi-person-badge"></i>
                                                        <?php echo htmlspecialchars($aprendiz['nombres'] . ' ' . $aprendiz['apellidos']); ?>
                                                    </h6>
                                                    <span class="badge bg-light text-dark">
                                                        <?php echo htmlspecialchars($aprendiz['estado']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <p class="card-text">
                                                    <small class="text-muted">
                                                        <i class="bi bi-person-vcard"></i> <strong>Documento:</strong> <?php echo $aprendiz['id']; ?><br>
                                                        <i class="bi bi-envelope"></i> <strong>Correo:</strong> <?php echo htmlspecialchars($aprendiz['correo']); ?><br>
                                                        <i class="bi bi-telephone"></i> <strong>Teléfono:</strong> <?php echo htmlspecialchars($aprendiz['telefono'] ?? 'No registrado'); ?><br>
                                                        <i class="bi bi-folder"></i> <strong>Ficha:</strong> <?php echo $aprendiz['ficha_numero'] ?? 'Sin asignar'; ?>
                                                    </small>
                                                </p>

                                                <div class="row text-center mt-3">
                                                    <div class="col-6">
                                                        <div class="border-end">
                                                            <h5 class="text-primary mb-0"><?php echo number_format($aprendiz['promedio_general'], 2); ?></h5>
                                                            <small class="text-muted">Promedio</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <h5 class="text-success mb-0"><?php echo number_format($aprendiz['porcentaje_aprobacion'], 1); ?>%</h5>
                                                        <small class="text-muted">Aprobación</small>
                                                    </div>
                                                </div>

                                                <?php if ($aprendiz['programa_formacion']): ?>
                                                    <div class="mt-2">
                                                        <small class="text-muted">
                                                            <i class="bi bi-book"></i> <?php echo htmlspecialchars($aprendiz['programa_formacion']); ?>
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="card-footer bg-transparent">
                                                <div class="d-grid gap-2">
                                                    <div class="row g-2">
                                                        <div class="col-6">
                                                            <button class="btn btn-outline-primary btn-sm ver-detalles w-100"
                                                                data-aprendiz="<?php echo $aprendiz['id']; ?>">
                                                                <i class="bi bi-eye"></i> Detalles
                                                            </button>
                                                        </div>
                                                        <div class="col-6">
                                                            <button class="btn btn-outline-success btn-sm generar-reporte-individual w-100"
                                                                data-aprendiz="<?php echo $aprendiz['id']; ?>"
                                                                data-nombre="<?php echo htmlspecialchars($aprendiz['nombres'] . ' ' . $aprendiz['apellidos']); ?>">
                                                                <i class="bi bi-file-earmark-excel"></i> Excel
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <!-- En el loop de aprendices, en el botón de cambiar ficha -->
                                                    <button class="btn btn-secondary cambiar-ficha"
                                                        data-id="<?php echo $aprendiz['id']; ?>"
                                                        data-nombre="<?php echo htmlspecialchars($aprendiz['nombres'] . ' ' . $aprendiz['apellidos']); ?>"
                                                        data-ficha="<?php echo $aprendiz['ficha_numero'] ?? ''; ?>"
                                                        data-formacion="<?php echo $aprendiz['id_formacion'] ?? ''; ?>">
                                                        <i class="bi bi-folder-symlink"></i> Cambiar Ficha
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Paginación -->
                            <?php if ($total_paginas > 1): ?>
                                <nav aria-label="Paginación de aprendices">
                                    <ul class="pagination justify-content-center">
                                        <!-- Botón anterior -->
                                        <?php if ($pagina_actual > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?pagina=<?php echo ($pagina_actual - 1); ?>&filtro_ficha=<?php echo $filtro_ficha; ?>&filtro_estado=<?php echo $filtro_estado; ?>&busqueda=<?php echo urlencode($busqueda); ?>">
                                                    <i class="bi bi-chevron-left"></i> Anterior
                                                </a>
                                            </li>
                                        <?php endif; ?>

                                        <!-- Números de página con límite de 5 -->
                                        <?php
                                        $inicio_pag = max(1, $pagina_actual - 2);
                                        $fin_pag = min($total_paginas, $inicio_pag + 4);

                                        // Ajustar inicio si estamos cerca del final
                                        if ($fin_pag - $inicio_pag < 4) {
                                            $inicio_pag = max(1, $fin_pag - 4);
                                        }

                                        for ($i = $inicio_pag; $i <= $fin_pag; $i++): ?>
                                            <li class="page-item <?php echo ($i == $pagina_actual) ? 'active' : ''; ?>">
                                                <a class="page-link" href="?pagina=<?php echo $i; ?>&filtro_ficha=<?php echo $filtro_ficha; ?>&filtro_estado=<?php echo $filtro_estado; ?>&busqueda=<?php echo urlencode($busqueda); ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>

                                        <!-- Botón siguiente -->
                                        <?php if ($pagina_actual < $total_paginas): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?pagina=<?php echo ($pagina_actual + 1); ?>&filtro_ficha=<?php echo $filtro_ficha; ?>&filtro_estado=<?php echo $filtro_estado; ?>&busqueda=<?php echo urlencode($busqueda); ?>">
                                                    Siguiente <i class="bi bi-chevron-right"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>

                            <?php if (empty($aprendices)): ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-person-x display-1 text-muted"></i>
                                    <h5 class="text-muted mt-3">No se encontraron aprendices</h5>
                                    <p class="text-muted">Intenta ajustar los filtros de búsqueda</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Sección: Aprendices activos -->
                <div id="seccion-activos" class="seccion-aprendices" style="display: none;">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h4 class="mb-0">
                                <i class="bi bi-person-check"></i> Aprendices Activos
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-success border-success">
                                <i class="bi bi-info-circle"></i>
                                <strong>Información:</strong> Estos aprendices están actualmente matriculados y activos en el sistema.
                            </div>
                            <div id="aprendicesActivosContainer">
                                <!-- Se cargará dinámicamente -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sección: Aprendices suspendidos -->
                <div id="seccion-suspendidos" class="seccion-aprendices" style="display: none;">
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h4 class="mb-0">
                                <i class="bi bi-person-dash"></i> Aprendices Suspendidos
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning border-warning">
                                <i class="bi bi-exclamation-triangle"></i>
                                <strong>Atención:</strong> Estos aprendices están temporalmente suspendidos del programa.
                            </div>
                            <div id="aprendicesSuspendidosContainer">
                                <!-- Se cargará dinámicamente -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para ver detalles del aprendiz -->
    <div class="modal fade" id="detallesAprendizModal" tabindex="-1" aria-labelledby="detallesAprendizModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="detallesAprendizModalLabel">Detalles del Aprendiz</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detallesAprendizContent">
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para editar aprendiz -->
    <div class="modal fade" id="editarAprendizModal" tabindex="-1" aria-labelledby="editarAprendizModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editarAprendizModalLabel">Editar Datos del Aprendiz</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST" id="editarAprendizForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="actualizar_aprendiz">
                        <input type="hidden" name="id_aprendiz" id="edit_id_aprendiz">

                        <div class="mb-3">
                            <label class="form-label">Aprendiz:</label>
                            <p class="fw-bold" id="edit_aprendiz_nombre"></p>
                        </div>

                        <div class="mb-3">
                            <label for="edit_correo" class="form-label">Correo Electrónico *</label>
                            <input type="email" class="form-control" id="edit_correo" name="correo" required>
                        </div>

                        <div class="mb-3">
                            <label for="edit_telefono" class="form-label">Número de Teléfono</label>
                            <input type="tel" class="form-control" id="edit_telefono" name="telefono">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar Datos</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para cambiar estado -->
    <div class="modal fade" id="cambiarEstadoModal" tabindex="-1" aria-labelledby="cambiarEstadoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="cambiarEstadoModalLabel">Cambiar Estado del Aprendiz</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST" id="cambiarEstadoForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="cambiar_estado">
                        <input type="hidden" name="id_aprendiz" id="estado_id_aprendiz">

                        <div class="mb-3">
                            <label class="form-label">Aprendiz:</label>
                            <p class="fw-bold" id="estado_aprendiz_nombre"></p>
                        </div>

                        <div class="mb-3">
                            <label for="nuevo_estado" class="form-label">Nuevo Estado *</label>
                            <select class="form-select" id="nuevo_estado" name="nuevo_estado" required>
                                <option value="">Seleccionar estado</option>
                                <option value="1">Activo</option>
                                <option value="6">Suspendido</option>
                                <option value="7">Expulsado</option>
                                <option value="3">Aprobado</option>
                                <option value="4">Desaprobado</option>
                            </select>
                        </div>

                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Atención:</strong> Este cambio afectará el acceso del aprendiz al sistema.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Cambiar Estado</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para cambiar/asignar ficha y formación -->
    <div class="modal fade" id="cambiarFichaModal" tabindex="-1" aria-labelledby="cambiarFichaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="cambiarFichaModalLabel">Cambiar/Asignar Ficha y Formación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST" id="cambiarFichaForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="cambiar_ficha">
                        <input type="hidden" name="id_aprendiz" id="ficha_id_aprendiz">

                        <div class="mb-3">
                            <label class="form-label">Aprendiz:</label>
                            <p class="fw-bold" id="ficha_aprendiz_nombre"></p>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ficha Actual:</label>
                            <p class="text-muted" id="ficha_actual"></p>
                        </div>

                        <!-- Select de formación, solo visible si NO tiene ficha -->
                        <div class="mb-3" id="formacion_group" style="display:none;">
                            <label for="nueva_formacion" class="form-label">Formación *</label>
                            <select class="form-select select2" id="nueva_formacion" name="nueva_formacion">
                                <option value="">Buscar formación...</option>
                                <?php foreach ($formaciones_disponibles as $formacion): ?>
                                    <option value="<?php echo $formacion['id_formacion']; ?>">
                                        <?php echo htmlspecialchars($formacion['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Select de ficha, siempre visible -->
                        <div class="mb-3">
                            <label for="nueva_ficha" class="form-label">Nueva Ficha *</label>
                            <select class="form-select select2" id="nueva_ficha" name="nueva_ficha" required>
                                <option value="">Buscar ficha...</option>
                                <!-- Opciones se llenan dinámicamente -->
                            </select>
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Información:</strong> El cambio de ficha trasladará al aprendiz a un nuevo grupo de formación.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-info">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para generar reportes -->
    <div class="modal fade" id="reportesModal" tabindex="-1" aria-labelledby="reportesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="reportesModalLabel">Generar Reportes en Excel</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0"><i class="bi bi-people"></i> Reporte General</h6>
                                </div>
                                <div class="card-body">
                                    <p class="card-text">Generar reporte de todos los aprendices registrados en el sistema.</p>
                                    <ul class="list-unstyled">
                                        <li><i class="bi bi-check text-success"></i> Datos personales</li>
                                        <li><i class="bi bi-check text-success"></i> Promedios por trimestre</li>
                                        <li><i class="bi bi-check text-success"></i> Porcentaje de aprobación</li>
                                        <li><i class="bi bi-check text-success"></i> Estado actual</li>
                                    </ul>
                                </div>
                                <div class="card-footer">
                                    <button class="btn btn-primary w-100" onclick="generarReporte('general')">
                                        <i class="bi bi-download"></i> Descargar Reporte General
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0"><i class="bi bi-folder"></i> Reporte por Ficha</h6>
                                </div>
                                <div class="card-body">
                                    <p class="card-text">Generar reporte de aprendices de una ficha específica.</p>
                                    <div class="mb-3">
                                        <label for="ficha_reporte" class="form-label">Seleccionar Ficha:</label>
                                        <select class="form-select" id="ficha_reporte">
                                            <option value="">Seleccionar ficha</option>
                                            <?php foreach ($fichas_disponibles as $ficha): ?>
                                                <option value="<?php echo $ficha['id_ficha']; ?>">
                                                    <?php echo $ficha['id_ficha'] . ' - ' . htmlspecialchars($ficha['programa']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button class="btn btn-info w-100" onclick="generarReporte('ficha')">
                                        <i class="bi bi-download"></i> Descargar Reporte por Ficha
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/sidebard.js"></script>

    <script>
    let aprendicesData = [];
    let paginaActual = 1;
    const aprendicesPorPagina = 6;
    let debounceTimer = null;
    let isSearching = false;

    // Utilidad: limpiar backdrop huérfano si queda (por modales dinámicos)
    function limpiarBackdrop() {
        setTimeout(() => {
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
            document.body.classList.remove('modal-open');
            document.body.style = '';
        }, 350);
    }

    // Cargar datos iniciales
    document.addEventListener("DOMContentLoaded", function() {
        // Event listeners para filtros con debounce
        document.getElementById('buscarAprendiz').addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                realizarBusquedaGlobal();
            }, 500); // 500ms de debounce
        });

        document.getElementById('filtroFicha').addEventListener('change', realizarBusquedaGlobal);
        document.getElementById('filtroEstado').addEventListener('change', realizarBusquedaGlobal);

        // Inicializar tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Fix: Asociar limpieza de backdrop al cerrar cualquier modal
        document.querySelectorAll('.modal').forEach(modalEl => {
            modalEl.addEventListener('hidden.bs.modal', limpiarBackdrop);
        });
    });

    // Función para realizar búsqueda global with AJAX
    async function realizarBusquedaGlobal() {
        if (isSearching) return;
        
        isSearching = true;
        const busqueda = document.getElementById('buscarAprendiz').value;
        const filtroFicha = document.getElementById('filtroFicha').value;
        const filtroEstado = document.getElementById('filtroEstado').value;

        // Mostrar indicador de carga
        mostrarIndicadorCarga(true);

        try {
            const params = new URLSearchParams({
                busqueda: busqueda,
                filtro_ficha: filtroFicha,
                filtro_estado: filtroEstado,
                pagina: 1,
                ajax: 1
            });

            const response = await fetch(`buscar_aprendices.php?${params}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();

            if (data.success) {
                // Actualizar contenedor de aprendices
                document.getElementById('aprendicesContainer').innerHTML = data.html;
                
                // Actualizar paginación
                actualizarPaginacionGlobal(data.total_paginas, 1);
                
                // Asignar event listeners a los nuevos elementos
                asignarEventListenersDinamicos();
                
                paginaActual = 1;
            } else {
                console.error('Error en la búsqueda:', data.message);
                document.getElementById('aprendicesContainer').innerHTML = 
                    '<div class="alert alert-danger">Error en la búsqueda: ' + data.message + '</div>';
            }
        } catch (error) {
            console.error('Error:', error);
            document.getElementById('aprendicesContainer').innerHTML = 
                '<div class="alert alert-danger">Error de conexión: ' + error.message + '</div>';
        } finally {
            isSearching = false;
            mostrarIndicadorCarga(false);
        }
    }

    // Función para cargar página específica
    async function cargarPagina(pagina) {
        if (isSearching) return;
        
        isSearching = true;
        const busqueda = document.getElementById('buscarAprendiz').value;
        const filtroFicha = document.getElementById('filtroFicha').value;
        const filtroEstado = document.getElementById('filtroEstado').value;

        mostrarIndicadorCarga(true);

        try {
            const params = new URLSearchParams({
                busqueda: busqueda,
                filtro_ficha: filtroFicha,
                filtro_estado: filtroEstado,
                pagina: pagina,
                ajax: 1
            });

            const response = await fetch(`buscar_aprendices.php?${params}`);
            const data = await response.json();

            if (data.success) {
                document.getElementById('aprendicesContainer').innerHTML = data.html;
                actualizarPaginacionGlobal(data.total_paginas, pagina);
                asignarEventListenersDinamicos();
                paginaActual = pagina;
                
                // Scroll suave hacia arriba
                document.querySelector('#aprendicesContainer').scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'start' 
                });
            }
        } catch (error) {
            console.error('Error:', error);
        } finally {
            isSearching = false;
            mostrarIndicadorCarga(false);
        }
    }

    // Mostrar indicador de carga
    function mostrarIndicadorCarga(mostrar) {
        const container = document.getElementById('aprendicesContainer');
        if (mostrar) {
            container.style.opacity = '0.6';
            container.style.pointerEvents = 'none';
            
            // Agregar spinner si no existe
            if (!document.getElementById('loading-spinner')) {
                const spinner = document.createElement('div');
                spinner.id = 'loading-spinner';
                spinner.className = 'text-center py-4';
                spinner.innerHTML = `
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2 text-muted">Buscando aprendices...</p>
                `;
                container.appendChild(spinner);
            }
        } else {
            container.style.opacity = '1';
            container.style.pointerEvents = 'auto';
            
            // Remover spinner
            const spinner = document.getElementById('loading-spinner');
            if (spinner) {
                spinner.remove();
            }
        }
    }

    // Actualizar paginacion global
    function actualizarPaginacionGlobal(totalPaginas, paginaActual) {
        // Ocultar la paginación original de PHP si existe
        const paginacionOriginal = document.querySelector('.pagination');
        if (paginacionOriginal && paginacionOriginal.closest('nav')) {
            paginacionOriginal.closest('nav').style.display = 'none';
        }
        
        let paginacionContainer = document.querySelector('.pagination-container');
        
        if (!paginacionContainer) {
            paginacionContainer = document.createElement('nav');
            paginacionContainer.className = 'pagination-container';
            paginacionContainer.setAttribute('aria-label', 'Paginación de aprendices');
            document.querySelector('#aprendicesContainer').parentNode.appendChild(paginacionContainer);
        }

        if (totalPaginas <= 1) {
            paginacionContainer.innerHTML = '';
            return;
        }

        let paginacionHTML = '<ul class="pagination justify-content-center">';

        // Botón anterior
        if (paginaActual > 1) {
            paginacionHTML += `
                <li class="page-item">
                    <button class="page-link" onclick="cargarPagina(${paginaActual - 1})">
                        <i class="bi bi-chevron-left"></i> Anterior
                    </button>
                </li>
            `;
        }

        // Números de página
        const inicioPag = Math.max(1, paginaActual - 2);
        const finPag = Math.min(totalPaginas, inicioPag + 4);

        for (let i = inicioPag; i <= finPag; i++) {
            paginacionHTML += `
                <li class="page-item ${paginaActual === i ? 'active' : ''}">
                    <button class="page-link" onclick="cargarPagina(${i})">${i}</button>
                </li>
            `;
        }

        // Botón siguiente
        if (paginaActual < totalPaginas) {
            paginacionHTML += `
                <li class="page-item">
                    <button class="page-link" onclick="cargarPagina(${paginaActual + 1})">
                        Siguiente <i class="bi bi-chevron-right"></i>
                    </button>
                </li>
            `;
        }

        paginacionHTML += '</ul>';
        paginacionContainer.innerHTML = paginacionHTML;
    }

    function mostrarPaginacionOriginal() {
        // Mostrar la paginación original de PHP
        const paginacionOriginal = document.querySelector('.pagination');
        if (paginacionOriginal && paginacionOriginal.closest('nav')) {
            paginacionOriginal.closest('nav').style.display = 'block';
        }
        
        // Ocultar la paginación AJAX
        const paginacionContainer = document.querySelector('.pagination-container');
        if (paginacionContainer) {
            paginacionContainer.style.display = 'none';
        }
    }

    function limpiarFiltros() {
        document.getElementById('buscarAprendiz').value = '';
        document.getElementById('filtroFicha').value = '';
        document.getElementById('filtroEstado').value = '';
        
        // Recargar la página para mostrar el estado original
        window.location.href = window.location.pathname;
    }

    // Mostrar sección específica
    function mostrarSeccion(seccion) {
        document.querySelectorAll('.seccion-aprendices').forEach(el => {
            el.style.display = 'none';
        });
        document.querySelectorAll('#btnTodosAprendices, #btnAprendicesActivos, #btnAprendicesSuspendidos').forEach(btn => {
            btn.classList.remove('active');
            btn.classList.add('btn-outline-primary');
            btn.classList.remove('btn-primary', 'btn-success', 'btn-warning');
        });

        if (seccion === 'todos') {
            document.getElementById('seccion-todos').style.display = 'block';
            document.getElementById('btnTodosAprendices').classList.add('active', 'btn-primary');
            document.getElementById('btnTodosAprendices').classList.remove('btn-outline-primary');
        } else if (seccion === 'activos') {
            document.getElementById('seccion-activos').style.display = 'block';
            document.getElementById('btnAprendicesActivos').classList.add('active', 'btn-success');
            document.getElementById('btnAprendicesActivos').classList.remove('btn-outline-primary');
            cargarAprendicesPorEstado(1);
        } else if (seccion === 'suspendidos') {
            document.getElementById('seccion-suspendidos').style.display = 'block';
            document.getElementById('btnAprendicesSuspendidos').classList.add('active', 'btn-warning');
            document.getElementById('btnAprendicesSuspendidos').classList.remove('btn-outline-primary');
            cargarAprendicesPorEstado(6);
        }
    }

    // Cargar aprendices por estado
    async function cargarAprendicesPorEstado(estado) {
        try {
            const response = await fetch(`get_aprendices_por_estado.php?estado=${estado}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();

            let containerId = '';
            if (estado === 1) {
                containerId = 'aprendicesActivosContainer';
            } else if (estado === 6) {
                containerId = 'aprendicesSuspendidosContainer';
            }

            if (data.success) {
                document.getElementById(containerId).innerHTML = data.html;
                asignarEventListenersDinamicos();
            } else {
                document.getElementById(containerId).innerHTML =
                    '<div class="alert alert-info">No hay aprendices en este estado</div>';
            }
        } catch (error) {
            console.error('Error:', error);
            const containerId = estado === 1 ? 'aprendicesActivosContainer' : 'aprendicesSuspendidosContainer';
            document.getElementById(containerId).innerHTML =
                '<div class="alert alert-danger">Error al cargar aprendices: ' + error.message + '</div>';
        }
    }

    // Asignar event listeners a elementos dinámicos
    function asignarEventListenersDinamicos() {
        document.querySelectorAll('.ver-detalles').forEach(button => {
            button.removeEventListener('click', handleVerDetalles);
            button.addEventListener('click', handleVerDetalles);
        });
    }

    function handleVerDetalles(event) {
        const button = event.currentTarget;
        const idAprendiz = button.getAttribute('data-aprendiz');
        cargarDetallesAprendiz(idAprendiz);
    }

    

    // Event listeners para elementos estáticos
    document.addEventListener('click', function(event) {
        if (event.target.closest('.ver-detalles')) {
            const button = event.target.closest('.ver-detalles');
            const idAprendiz = button.getAttribute('data-aprendiz');
            cargarDetallesAprendiz(idAprendiz);
        }
    });

    // Función para cargar detalles del aprendiz
    async function cargarDetallesAprendiz(idAprendiz) {
        try {
            const response = await fetch(`get_aprendiz_details.php?id_aprendiz=${idAprendiz}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();

            if (data.success) {
                document.getElementById('detallesAprendizContent').innerHTML = data.html;
                const modalEl = document.getElementById('detallesAprendizModal');
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
                modalEl.addEventListener('hidden.bs.modal', limpiarBackdrop, { once: true });
            } else {
                console.error('Error from server:', data.message);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Error al cargar los detalles del aprendiz'
                });
            }
        } catch (error) {
            console.error('Error completo:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo conectar con el servidor. Verifique que el archivo get_aprendiz_details.php existe.'
            });
        }
    }

    // Manejar edición de aprendiz
    document.addEventListener('click', function(event) {
        if (event.target.closest('.editar-aprendiz')) {
            const button = event.target.closest('.editar-aprendiz');
            const idAprendiz = button.getAttribute('data-id');
            const nombre = button.getAttribute('data-nombre');
            const correo = button.getAttribute('data-correo');
            const telefono = button.getAttribute('data-telefono');

            document.getElementById('edit_id_aprendiz').value = idAprendiz;
            document.getElementById('edit_aprendiz_nombre').textContent = nombre;
            document.getElementById('edit_correo').value = correo;
            document.getElementById('edit_telefono').value = telefono || '';

            const modalEl = document.getElementById('editarAprendizModal');
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
            modalEl.addEventListener('hidden.bs.modal', limpiarBackdrop, { once: true });
        }

        if (event.target.closest('.cambiar-estado')) {
            const button = event.target.closest('.cambiar-estado');
            const idAprendiz = button.getAttribute('data-id');
            const nombre = button.getAttribute('data-nombre');

            document.getElementById('estado_id_aprendiz').value = idAprendiz;
            document.getElementById('estado_aprendiz_nombre').textContent = nombre;

            const modalEl = document.getElementById('cambiarEstadoModal');
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
            modalEl.addEventListener('hidden.bs.modal', limpiarBackdrop, { once: true });
        }

        if (event.target.closest('.cambiar-ficha')) {
            const button = event.target.closest('.cambiar-ficha');
            const idAprendiz = button.getAttribute('data-id');
            const nombre = button.getAttribute('data-nombre');
            const fichaActual = button.getAttribute('data-ficha');
            const formacionActual = button.getAttribute('data-formacion'); // agrega este data-attr si lo tienes

            document.getElementById('ficha_id_aprendiz').value = idAprendiz;
            document.getElementById('ficha_aprendiz_nombre').textContent = nombre;
            document.getElementById('ficha_actual').textContent = fichaActual || 'Sin asignar';

            // Si NO tiene ficha, mostrar select de formación y limpiar fichas
            if (!fichaActual || fichaActual === 'Sin asignar') {
                $('#formacion_group').show();
                $('#nueva_formacion').val('').trigger('change');
                $('#nueva_ficha').html('<option value="">Buscar ficha...</option>').val('').trigger('change');
            } else {
                // Si tiene ficha, ocultar formación y cargar solo fichas de esa formación
                $('#formacion_group').hide();
                // Puedes obtener la formación actual desde un atributo data-formacion o por AJAX
                let idFormacion = button.getAttribute('data-formacion');
                if (!idFormacion) {
                    // Si no tienes el atributo, puedes hacer un fetch AJAX aquí para obtener la formación
                    // Por simplicidad, asume que lo tienes en data-formacion
                }
                cargarFichasPorFormacion(idFormacion, fichaActual);
            }

            // Mostrar modal
            const modalEl = document.getElementById('cambiarFichaModal');
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
            modalEl.addEventListener('hidden.bs.modal', limpiarBackdrop, { once: true });
        }
    });

    // Cuando cambia la formación, cargar fichas de esa formación
    $('#nueva_formacion').on('change', function() {
        const idFormacion = $(this).val();
        cargarFichasPorFormacion(idFormacion, null);
    });

    function cargarFichasPorFormacion(idFormacion, fichaActual) {
        if (!idFormacion) {
            $('#nueva_ficha').html('<option value="">Buscar ficha...</option>').val('').trigger('change');
            return;
        }
        $.get('gestion_aprendices.php', { ajax_fichas: 1, id_formacion: idFormacion }, function(data) {
            let options = '<option value="">Buscar ficha...</option>';
            data.forEach(function(ficha) {
                options += `<option value="${ficha.id_ficha}" ${fichaActual == ficha.id_ficha ? 'selected' : ''}>${ficha.ficha_numero}</option>`;
            });
            $('#nueva_ficha').html(options).val(fichaActual || '').trigger('change');
        }, 'json');
    }

    // Inicializa Select2 cada vez que se abre el modal (por si se agregan dinámicamente)
    $('#cambiarFichaModal').on('shown.bs.modal', function () {
        if (window.jQuery && $.fn.select2) {
            $('.select2').select2({
                width: '100%',
                dropdownParent: $('#cambiarFichaModal')
            });
        }
    });
    // Mostrar modal dinámico según si tiene ficha o no
    document.addEventListener('click', function(event) {
        if (event.target.closest('.cambiar-ficha')) {
            const button = event.target.closest('.cambiar-ficha');
            const idAprendiz = button.getAttribute('data-id');
            const nombre = button.getAttribute('data-nombre');
            const fichaActual = button.getAttribute('data-ficha');
            const formacionActual = button.getAttribute('data-formacion'); // agrega este data-attr si lo tienes

            document.getElementById('ficha_id_aprendiz').value = idAprendiz;
            document.getElementById('ficha_aprendiz_nombre').textContent = nombre;
            document.getElementById('ficha_actual').textContent = fichaActual || 'Sin asignar';

            // Si NO tiene ficha, mostrar select de formación y limpiar fichas
            if (!fichaActual || fichaActual === 'Sin asignar') {
                $('#formacion_group').show();
                $('#nueva_formacion').val('').trigger('change');
                $('#nueva_ficha').html('<option value="">Buscar ficha...</option>').val('').trigger('change');
            } else {
                // Si tiene ficha, ocultar formación y cargar solo fichas de esa formación
                $('#formacion_group').hide();
                // Puedes obtener la formación actual desde un atributo data-formacion o por AJAX
                let idFormacion = button.getAttribute('data-formacion');
                if (!idFormacion) {
                    // Si no tienes el atributo, puedes hacer un fetch AJAX aquí para obtener la formación
                    // Por simplicidad, asume que lo tienes en data-formacion
                }
                cargarFichasPorFormacion(idFormacion, fichaActual);
            }

            // Mostrar modal
            const modalEl = document.getElementById('cambiarFichaModal');
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
            modalEl.addEventListener('hidden.bs.modal', limpiarBackdrop, { once: true });
        }
    });
    // Generar reportes
    function generarReporte(tipo) {
        if (tipo === 'general') {
            const url = 'generar_reporte_excel.php?tipo=general';
            window.open(url, '_blank');
        } else if (tipo === 'ficha') {
            const fichaSeleccionada = document.getElementById('ficha_reporte').value;
            if (!fichaSeleccionada) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: 'Por favor selecciona una ficha para generar el reporte.'
                });
                return;
            }
            const url = `generar_reporte_excel.php?tipo=ficha&ficha=${fichaSeleccionada}`;
            window.open(url, '_blank');
        }
    }

    // Manejar generación de reporte individual
    document.addEventListener('click', function(event) {
        if (event.target.closest('.generar-reporte-individual')) {
            const button = event.target.closest('.generar-reporte-individual');
            const idAprendiz = button.getAttribute('data-aprendiz');
            const nombreAprendiz = button.getAttribute('data-nombre');
            generarReporteIndividual(idAprendiz, nombreAprendiz);
        }
    });

    // Generar reporte individual
    function generarReporteIndividual(idAprendiz, nombreAprendiz) {
        Swal.fire({
            title: 'Generar Reporte Individual',
            html: `¿Deseas generar el reporte de <strong>${nombreAprendiz}</strong>?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="bi bi-download"></i> Descargar Excel',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const url = `generar_reporte_individual.php?id_aprendiz=${idAprendiz}`;
                window.open(url, '_blank');
            
                Swal.fire({
                    title: 'Descarga iniciada',
                    text: 'El reporte se está generando...',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        });
    }
</script>

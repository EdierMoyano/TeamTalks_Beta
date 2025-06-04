<?php
session_start();
require_once '../../conexion/conexion.php';
require_once '../../includes/functions.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['documento'])) {
    header('Location: ../login/login.php');
    exit;
}

// Inicializar mensaje de alerta
$alertMessage = '';
$alertType = '';
$modalMessage = '';
$modalType = '';

// Crear instancia de la conexión
$db = new Database();
$conexion = $db->connect();

// Verificar que la conexión sea válida
if (!$conexion || !($conexion instanceof PDO)) {
    die("Error: No se pudo establecer la conexión a la base de datos");
}

// Procesar creación de horario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'crear_horario') {
    $nombre_horario = trim($_POST['nombre_horario']);
    $descripcion = trim($_POST['descripcion']);
    $id_jornada = $_POST['id_jornada'] ?? null;
    $id_ficha = !empty($_POST['id_ficha']) ? $_POST['id_ficha'] : null;
    $id_trimestre = !empty($_POST['id_trimestre']) ? $_POST['id_trimestre'] : null;
    $dias_configuracion = $_POST['dias_config'] ?? [];

    if (empty($nombre_horario) || empty($id_jornada)) {
        $alertMessage = "El nombre del horario y la jornada son obligatorios";
        $alertType = "danger";
    } elseif (empty($dias_configuracion)) {
        $alertMessage = "Debe configurar al menos un día de la semana";
        $alertType = "danger";
    } else {
        try {
            $conexion->beginTransaction();

            // Procesar configuración de cada día usando la estructura real de la tabla horario
            foreach ($dias_configuracion as $dia => $config) {
                if (!isset($config['activo']) || $config['activo'] != '1') continue;
                
                $tipo_dia = $config['tipo']; // 'un_bloque' o 'dos_bloques'
                
                if ($tipo_dia == 'un_bloque') {
                    // Un solo bloque - crear un registro en horario
                    if (!empty($config['materia_bloque1'])) {
                        $stmt = $conexion->prepare("
                            INSERT INTO horario (
                                id_materia_ficha, dia_semana, hora_inicio, hora_fin, 
                                id_jornada, nombre_horario, descripcion, id_estado, 
                                fecha_creacion, id_ficha, id_trimestre
                            ) VALUES (
                                :id_materia_ficha, :dia_semana, :hora_inicio, :hora_fin,
                                :id_jornada, :nombre_horario, :descripcion, 1,
                                CURDATE(), :id_ficha, :id_trimestre
                            )
                        ");
                        $stmt->bindValue(':id_materia_ficha', $config['materia_bloque1'], PDO::PARAM_INT);
                        $stmt->bindValue(':dia_semana', $dia, PDO::PARAM_STR);
                        $stmt->bindValue(':hora_inicio', $config['hora_inicio_bloque1'], PDO::PARAM_STR);
                        $stmt->bindValue(':hora_fin', $config['hora_fin_bloque1'], PDO::PARAM_STR);
                        $stmt->bindValue(':id_jornada', $id_jornada, PDO::PARAM_INT);
                        $stmt->bindValue(':nombre_horario', $nombre_horario, PDO::PARAM_STR);
                        $stmt->bindValue(':descripcion', $descripcion, PDO::PARAM_STR);
                        $stmt->bindValue(':id_ficha', $id_ficha, $id_ficha ? PDO::PARAM_INT : PDO::PARAM_NULL);
                        $stmt->bindValue(':id_trimestre', $id_trimestre, $id_trimestre ? PDO::PARAM_INT : PDO::PARAM_NULL);
                        $stmt->execute();
                    }
                } else {
                    // Dos bloques - crear dos registros en horario
                    if (!empty($config['materia_bloque1'])) {
                        $stmt = $conexion->prepare("
                            INSERT INTO horario (
                                id_materia_ficha, dia_semana, hora_inicio, hora_fin, 
                                id_jornada, nombre_horario, descripcion, id_estado, 
                                fecha_creacion, id_ficha, id_trimestre
                            ) VALUES (
                                :id_materia_ficha, :dia_semana, :hora_inicio, :hora_fin,
                                :id_jornada, :nombre_horario, :descripcion, 1,
                                CURDATE(), :id_ficha, :id_trimestre
                            )
                        ");
                        $stmt->bindValue(':id_materia_ficha', $config['materia_bloque1'], PDO::PARAM_INT);
                        $stmt->bindValue(':dia_semana', $dia, PDO::PARAM_STR);
                        $stmt->bindValue(':hora_inicio', $config['hora_inicio_bloque1'], PDO::PARAM_STR);
                        $stmt->bindValue(':hora_fin', $config['hora_fin_bloque1'], PDO::PARAM_STR);
                        $stmt->bindValue(':id_jornada', $id_jornada, PDO::PARAM_INT);
                        $stmt->bindValue(':nombre_horario', $nombre_horario, PDO::PARAM_STR);
                        $stmt->bindValue(':descripcion', $descripcion, PDO::PARAM_STR);
                        $stmt->bindValue(':id_ficha', $id_ficha, $id_ficha ? PDO::PARAM_INT : PDO::PARAM_NULL);
                        $stmt->bindValue(':id_trimestre', $id_trimestre, $id_trimestre ? PDO::PARAM_INT : PDO::PARAM_NULL);
                        $stmt->execute();
                    }
                    
                    if (!empty($config['materia_bloque2'])) {
                        $stmt = $conexion->prepare("
                            INSERT INTO horario (
                                id_materia_ficha, dia_semana, hora_inicio, hora_fin, 
                                id_jornada, nombre_horario, descripcion, id_estado, 
                                fecha_creacion, id_ficha, id_trimestre
                            ) VALUES (
                                :id_materia_ficha, :dia_semana, :hora_inicio, :hora_fin,
                                :id_jornada, :nombre_horario, :descripcion, 1,
                                CURDATE(), :id_ficha, :id_trimestre
                            )
                        ");
                        $stmt->bindValue(':id_materia_ficha', $config['materia_bloque2'], PDO::PARAM_INT);
                        $stmt->bindValue(':dia_semana', $dia, PDO::PARAM_STR);
                        $stmt->bindValue(':hora_inicio', $config['hora_inicio_bloque2'], PDO::PARAM_STR);
                        $stmt->bindValue(':hora_fin', $config['hora_fin_bloque2'], PDO::PARAM_STR);
                        $stmt->bindValue(':id_jornada', $id_jornada, PDO::PARAM_INT);
                        $stmt->bindValue(':nombre_horario', $nombre_horario, PDO::PARAM_STR);
                        $stmt->bindValue(':descripcion', $descripcion, PDO::PARAM_STR);
                        $stmt->bindValue(':id_ficha', $id_ficha, $id_ficha ? PDO::PARAM_INT : PDO::PARAM_NULL);
                        $stmt->bindValue(':id_trimestre', $id_trimestre, $id_trimestre ? PDO::PARAM_INT : PDO::PARAM_NULL);
                        $stmt->execute();
                    }
                }
            }

            $conexion->commit();
            $alertMessage = "Horario creado correctamente";
            $alertType = "success";

        } catch (PDOException $e) {
            $conexion->rollBack();
            $alertMessage = "Error al crear el horario: " . $e->getMessage();
            $alertType = "danger";
        }
    }
}

// Procesar eliminación de horario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'eliminar') {
    $nombre_horario = $_POST['nombre_horario'];
    
    try {
        $stmt = $conexion->prepare("DELETE FROM horario WHERE nombre_horario = ?");
        $stmt->execute([$nombre_horario]);
        
        $alertMessage = "Horario eliminado correctamente";
        $alertType = "success";
        
    } catch (PDOException $e) {
        $alertMessage = "Error: " . $e->getMessage();
        $alertType = "danger";
    }
}

// Obtener jornadas
$jornadas = [];
try {
    $stmt = $conexion->query("SELECT * FROM jornada ORDER BY id_jornada");
    if ($stmt) {
        $jornadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $alertMessage = "Error al cargar jornadas: " . $e->getMessage();
    $alertType = "danger";
}

// Obtener trimestres
$trimestres = [];
try {
    $stmt = $conexion->query("SELECT * FROM trimestre ORDER BY id_trimestre");
    if ($stmt) {
        $trimestres = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // Si hay error, crear trimestres por defecto
    $trimestres = [
        ['id_trimestre' => 1, 'trimestre' => 'Primer Trimestre'],
        ['id_trimestre' => 2, 'trimestre' => 'Segundo Trimestre'],
        ['id_trimestre' => 3, 'trimestre' => 'Tercer Trimestre'],
        ['id_trimestre' => 4, 'trimestre' => 'Cuarto Trimestre'],
        ['id_trimestre' => 5, 'trimestre' => 'Quinto Trimestre'],
        ['id_trimestre' => 6, 'trimestre' => 'Sexto Trimestre']
    ];
}

// Obtener bloques de horario por jornada (si existen)
$bloques_por_jornada = [];
try {
    $stmt = $conexion->query("
        SELECT bh.*, j.jornada 
        FROM bloques_horario bh 
        JOIN jornada j ON bh.id_jornada = j.id_jornada 
        ORDER BY bh.id_jornada, bh.orden_bloque
    ");
    if ($stmt) {
        $bloques = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($bloques as $bloque) {
            $bloques_por_jornada[$bloque['id_jornada']][] = $bloque;
        }
    }
} catch (PDOException $e) {
    // Si no existe la tabla bloques_horario, crear bloques por defecto
    $bloques_por_jornada = [
        1 => [
            ['nombre_bloque' => '1er Bloque', 'hora_inicio' => '06:00:00', 'hora_fin' => '09:00:00'],
            ['nombre_bloque' => '2do Bloque', 'hora_inicio' => '09:30:00', 'hora_fin' => '12:30:00']
        ],
        2 => [
            ['nombre_bloque' => '1er Bloque', 'hora_inicio' => '12:30:00', 'hora_fin' => '15:30:00'],
            ['nombre_bloque' => '2do Bloque', 'hora_inicio' => '16:00:00', 'hora_fin' => '19:00:00']
        ],
        3 => [
            ['nombre_bloque' => '1er Bloque', 'hora_inicio' => '18:00:00', 'hora_fin' => '21:00:00'],
            ['nombre_bloque' => '2do Bloque', 'hora_inicio' => '21:30:00', 'hora_fin' => '22:30:00']
        ]
    ];
}

// Obtener días de la semana (crear array por defecto)
$dias_semana = [
    ['id_dia' => 1, 'nombre_dia' => 'Lunes', 'orden_dia' => 1],
    ['id_dia' => 2, 'nombre_dia' => 'Martes', 'orden_dia' => 2],
    ['id_dia' => 3, 'nombre_dia' => 'Miércoles', 'orden_dia' => 3],
    ['id_dia' => 4, 'nombre_dia' => 'Jueves', 'orden_dia' => 4],
    ['id_dia' => 5, 'nombre_dia' => 'Viernes', 'orden_dia' => 5],
    ['id_dia' => 6, 'nombre_dia' => 'Sábado', 'orden_dia' => 6]
];

// Obtener horarios existentes - CONSULTA CORREGIDA para la estructura real
$horarios = [];
try {
    $stmt = $conexion->query("
        SELECT 
            h.nombre_horario,
            h.descripcion,
            h.fecha_creacion,
            j.jornada,
            e.estado,
            h.id_ficha,
            fo.nombre as nombre_programa,
            t.trimestre,
            COUNT(DISTINCT h.dia_semana) as dias_configurados,
            GROUP_CONCAT(DISTINCT h.dia_semana ORDER BY 
                CASE h.dia_semana 
                    WHEN 'Lunes' THEN 1 
                    WHEN 'Martes' THEN 2 
                    WHEN 'Miércoles' THEN 3 
                    WHEN 'Jueves' THEN 4 
                    WHEN 'Viernes' THEN 5 
                    WHEN 'Sábado' THEN 6 
                END SEPARATOR ', ') as dias_semana
        FROM horario h
        LEFT JOIN jornada j ON h.id_jornada = j.id_jornada
        LEFT JOIN estado e ON h.id_estado = e.id_estado
        LEFT JOIN fichas f ON h.id_ficha = f.id_ficha
        LEFT JOIN formacion fo ON f.id_formacion = fo.id_formacion
        LEFT JOIN trimestre t ON h.id_trimestre = t.id_trimestre
        WHERE h.nombre_horario IS NOT NULL AND h.nombre_horario != ''
        GROUP BY h.nombre_horario, h.descripcion, h.fecha_creacion, j.jornada, e.estado, h.id_ficha, fo.nombre, t.trimestre
        ORDER BY h.fecha_creacion DESC
    ");
    if ($stmt) {
        $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $alertMessage = "Error al cargar horarios: " . $e->getMessage();
    $alertType = "danger";
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Horarios SENA</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../styles/sidebard.css">
    <link rel="stylesheet" href="../styles/main.css">
    <style>
        .jornada-card {
            border-left: 4px solid #0d6efd;
            height: 100%;
        }
        .bloque-time {
            background: #f8f9fa;
            padding: 5px 10px;
            border-radius: 5px;
            margin: 2px 0;
            font-size: 0.9em;
        }
        .info-sistema {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .horarios-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        @media (max-width: 768px) {
            .horarios-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .ficha-dropdown {
            position: relative;
        }
        
        .lista-fichas {
            position: absolute;
            top: auto;
            bottom: 100%;
            left: 0;
            right: 0;
            z-index: 1050;
            background: white;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            max-height: 200px;
            overflow-y: auto;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            transform: translateY(-5px);
        }
        
        .ficha-item {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
            font-size: 0.9em;
        }
        
        .ficha-item:hover {
            background-color: #e9ecef;
        }
        
        .ficha-numero {
            font-weight: bold;
            color: #0d6efd;
        }
        
        .ficha-programa {
            color: #6c757d;
            font-size: 0.85em;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include '../includes/sidebard.php'; ?>
        <div class="main-content">
            <div class="container mt-4">
                <!-- Tarjeta para crear horarios -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Gestión de Horarios SENA</h4>
                        <div>
                            <a href="export_horarios.php" class="btn btn-success me-2" target="_blank">
                                <i class="bi bi-file-earmark-excel"></i> Exportar Excel
                            </a>
                            <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#horarioModal">
                                <i class="bi bi-calendar-plus"></i> Nuevo Horario
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($alertMessage)): ?>
                            <div class="alert alert-<?php echo $alertType; ?> alert-dismissible fade show" role="alert">
                                <?php echo $alertMessage; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Información del Sistema -->
                        <div class="info-sistema">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6><i class="bi bi-info-circle"></i> Tipos de Horario SENA:</h6>
                                    <ul class="mb-0">
                                        <li><strong>Un Bloque:</strong> Una materia por día (3 horas continuas)</li>
                                        <li><strong>Dos Bloques:</strong> Dos materias por día (descanso automático de 30 min)</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6><i class="bi bi-clock"></i> Jornadas Disponibles:</h6>
                                    <ul class="mb-0">
                                        <li><strong>Mañana:</strong> 6:00 AM - 12:30 PM</li>
                                        <li><strong>Tarde:</strong> 12:30 PM - 7:00 PM</li>
                                        <li><strong>Noche:</strong> 6:00 PM - 10:30 PM</li>
                                        <li><strong>Mixta:</strong> Horario flexible</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Horarios Estándar SENA en Grid 2x2 -->
                        <h6>Horarios Estándar SENA por Jornada:</h6>
                        <div class="horarios-grid">
                            <?php foreach ($jornadas as $jornada): ?>
                                <div class="card jornada-card">
                                    <div class="card-header py-2">
                                        <strong><?php echo htmlspecialchars($jornada['jornada']); ?></strong>
                                    </div>
                                    <div class="card-body py-2">
                                        <?php if (isset($bloques_por_jornada[$jornada['id_jornada']])): ?>
                                            <?php foreach ($bloques_por_jornada[$jornada['id_jornada']] as $bloque): ?>
                                                <div class="bloque-time">
                                                    <strong><?php echo htmlspecialchars($bloque['nombre_bloque']); ?>:</strong> 
                                                    <?php echo date('H:i', strtotime($bloque['hora_inicio'])); ?> - 
                                                    <?php echo date('H:i', strtotime($bloque['hora_fin'])); ?>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta para listar horarios -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Horarios Creados (<?php echo count($horarios); ?>)</h4>
                    </div>
                    <div class="table-responsive">
                        <div class="d-flex justify-content-end mt-3 mb-4">
                            <input 
                                type="text" 
                                id="busquedaHorario" 
                                class="form-control me-3" 
                                style="max-width: 350px;" 
                                placeholder="Buscar horario..." 
                                oninput="filtrarHorario()"
                            >
                        </div>
                        <table class="table table-hover" id="tablaHorarios">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Ficha</th>
                                    <th>Programa</th>
                                    <th>Trimestre</th>
                                    <th>Jornada</th>
                                    <th>Días</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($horarios)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No hay horarios registrados</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($horarios as $horario): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($horario['nombre_horario']); ?></strong></td>
                                            <td>
                                                <?php if ($horario['id_ficha']): ?>
                                                    <span class="badge bg-primary">
                                                        <?php echo htmlspecialchars($horario['id_ficha']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">No asignada</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small><?php echo htmlspecialchars($horario['nombre_programa'] ?? 'No definido'); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo htmlspecialchars($horario['trimestre'] ?? 'No definido'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php echo htmlspecialchars($horario['jornada'] ?? 'No definida'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small><?php echo htmlspecialchars($horario['dias_semana'] ?? 'No definidos'); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo ($horario['estado'] == 'Activo') ? 'bg-success' : 'bg-danger'; ?>">
                                                    <?php echo htmlspecialchars($horario['estado'] ?? 'Activo'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-info view-horario" 
                                                            data-nombre="<?php echo htmlspecialchars($horario['nombre_horario']); ?>"
                                                            data-bs-toggle="tooltip" 
                                                            title="Ver detalle del horario">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger delete-horario" 
                                                            data-nombre="<?php echo htmlspecialchars($horario['nombre_horario']); ?>"
                                                            data-bs-toggle="tooltip" 
                                                            title="Eliminar horario">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <nav>
                            <ul class="pagination justify-content-center" id="paginacionHorarios"></ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para crear horario -->
    <div class="modal fade" id="horarioModal" tabindex="-1" aria-labelledby="horarioModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="horarioModalLabel">Crear Nuevo Horario SENA</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST" id="horarioForm">
                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                        <input type="hidden" name="action" value="crear_horario">
                        
                        <!-- Información básica -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nombre_horario" class="form-label">Nombre del Horario *</label>
                                <input type="text" class="form-control" id="nombre_horario" name="nombre_horario" required 
                                       placeholder="Ej: Horario Mañana ADSI">
                            </div>
                            <div class="col-md-6">
                                <label for="id_jornada" class="form-label">Jornada *</label>
                                <select class="form-select" id="id_jornada" name="id_jornada" required>
                                    <option value="">Seleccione una jornada</option>
                                    <?php foreach ($jornadas as $jornada): ?>
                                        <option value="<?php echo $jornada['id_jornada']; ?>">
                                            <?php echo htmlspecialchars($jornada['jornada']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Asignación de ficha y trimestre -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="id_ficha" class="form-label">Ficha (Opcional)</label>
                                <div class="ficha-dropdown">
                                    <input type="text" class="form-control" id="buscar_ficha" placeholder="Buscar ficha por número o programa..." autocomplete="off">
                                    <input type="hidden" id="id_ficha" name="id_ficha">
                                    <div id="lista_fichas" class="lista-fichas" style="display: none;">
                                        <!-- Las fichas se cargarán aquí -->
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="id_trimestre" class="form-label">Trimestre (Opcional)</label>
                                <select class="form-select" id="id_trimestre" name="id_trimestre">
                                    <option value="">Seleccione un trimestre</option>
                                    <?php foreach ($trimestres as $trimestre): ?>
                                        <option value="<?php echo $trimestre['id_trimestre']; ?>">
                                            <?php echo htmlspecialchars($trimestre['trimestre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="2" 
                                      placeholder="Descripción opcional del horario"></textarea>
                        </div>

                        <hr>
                        
                        <!-- Configuración por días -->
                        <h6>Configuración por Días</h6>
                        <div id="dias-configuracion">
                            <?php foreach ($dias_semana as $dia): ?>
                                <div class="card mb-3 dia-card" data-dia="<?php echo $dia['nombre_dia']; ?>">
                                    <div class="card-header">
                                        <div class="form-check">
                                            <input class="form-check-input dia-checkbox" type="checkbox" 
                                                   name="dias_config[<?php echo $dia['nombre_dia']; ?>][activo]" 
                                                   value="1"
                                                   id="dia_activo_<?php echo $dia['id_dia']; ?>">
                                            <label class="form-check-label fw-bold" for="dia_activo_<?php echo $dia['id_dia']; ?>">
                                                <?php echo htmlspecialchars($dia['nombre_dia']); ?>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="card-body dia-config" style="display: none;">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Tipo de día</label>
                                                <div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input tipo-dia" type="radio" 
                                                               name="dias_config[<?php echo $dia['nombre_dia']; ?>][tipo]" 
                                                               value="un_bloque" 
                                                               id="<?php echo $dia['nombre_dia']; ?>_un_bloque">
                                                        <label class="form-check-label" for="<?php echo $dia['nombre_dia']; ?>_un_bloque">
                                                            Un Bloque
                                                        </label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input tipo-dia" type="radio" 
                                                               name="dias_config[<?php echo $dia['nombre_dia']; ?>][tipo]" 
                                                               value="dos_bloques" 
                                                               id="<?php echo $dia['nombre_dia']; ?>_dos_bloques">
                                                        <label class="form-check-label" for="<?php echo $dia['nombre_dia']; ?>_dos_bloques">
                                                            Dos Bloques
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Configuración de bloques -->
                                        <div class="bloques-config">
                                            <!-- Bloque 1 -->
                                            <div class="row mb-3 bloque-1">
                                                <div class="col-md-4">
                                                    <label class="form-label">Materia Bloque 1</label>
                                                    <select class="form-select materia-select" 
                                                            name="dias_config[<?php echo $dia['nombre_dia']; ?>][materia_bloque1]">
                                                        <option value="">Seleccione materia</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Hora Inicio</label>
                                                    <input type="time" class="form-control" 
                                                           name="dias_config[<?php echo $dia['nombre_dia']; ?>][hora_inicio_bloque1]">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Hora Fin</label>
                                                    <input type="time" class="form-control" 
                                                           name="dias_config[<?php echo $dia['nombre_dia']; ?>][hora_fin_bloque1]">
                                                </div>
                                            </div>
                                            
                                            <!-- Bloque 2 (solo visible si se selecciona dos bloques) -->
                                            <div class="row mb-3 bloque-2" style="display: none;">
                                                <div class="col-md-4">
                                                    <label class="form-label">Materia Bloque 2</label>
                                                    <select class="form-select materia-select" 
                                                            name="dias_config[<?php echo $dia['nombre_dia']; ?>][materia_bloque2]">
                                                        <option value="">Seleccione materia</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Hora Inicio</label>
                                                    <input type="time" class="form-control" 
                                                           name="dias_config[<?php echo $dia['nombre_dia']; ?>][hora_inicio_bloque2]">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Hora Fin</label>
                                                    <input type="time" class="form-control" 
                                                           name="dias_config[<?php echo $dia['nombre_dia']; ?>][hora_fin_bloque2]">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> 
                            <strong>Instrucciones:</strong>
                            <ul class="mb-0 mt-2">
                                <li>La ficha y trimestre son opcionales</li>
                                <li>Configure cada día individualmente según sus necesidades</li>
                                <li>Puede asignar materias específicas si selecciona una ficha</li>
                                <li>El descanso de 30 minutos se aplica automáticamente entre bloques</li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear Horario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación para eliminar -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro que desea eliminar el horario <strong id="deleteHorarioNombre"></strong>?</p>
                    <p class="text-muted">Esta acción eliminará todos los registros asociados a este horario.</p>
                </div>
                <div class="modal-footer">
                    <form action="" method="POST" id="deleteForm">
                        <input type="hidden" name="action" value="eliminar">
                        <input type="hidden" name="nombre_horario" id="deleteNombreHorario">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/sidebard.js"></script>

    <script>
    // Variables globales
    let fichasData = [];
    let materiasData = [];

    // Paginación
    let filasPorPaginaHorarios = 6;
    let paginaActualHorarios = 1;

    function obtenerFilasHorariosFiltradas() {
        let filas = Array.from(document.querySelectorAll("#tablaHorarios tbody tr"));
        let filtro = document.getElementById("busquedaHorario").value.trim().toLowerCase();
        if (filtro === "") return filas;
        return filas.filter(fila => {
            let texto = fila.innerText.toLowerCase();
            return texto.includes(filtro);
        });
    }

    function mostrarPaginaHorarios(pagina) {
        let filas = obtenerFilasHorariosFiltradas();
        let totalPaginas = Math.ceil(filas.length / filasPorPaginaHorarios);
        if (pagina < 1) pagina = 1;
        if (pagina > totalPaginas) pagina = totalPaginas;

        document.querySelectorAll("#tablaHorarios tbody tr").forEach(fila => fila.style.display = "none");
        let inicio = (pagina - 1) * filasPorPaginaHorarios;
        let fin = inicio + filasPorPaginaHorarios;
        for (let i = inicio; i < fin && i < filas.length; i++) {
            filas[i].style.display = "";
        }

        let paginacion = document.getElementById("paginacionHorarios");
        paginacion.innerHTML = "";
        if (totalPaginas <= 1) return;

        paginacion.innerHTML += `<li class="page-item ${pagina === 1 ? 'disabled' : ''}">
            <button class="page-link" onclick="cambiarPaginaHorarios(${pagina - 1})">Anterior</button>
        </li>`;

        for (let i = 1; i <= totalPaginas; i++) {
            paginacion.innerHTML += `<li class="page-item ${pagina === i ? 'active' : ''}">
                <button class="page-link" onclick="cambiarPaginaHorarios(${i})">${i}</button>
            </li>`;
        }

        paginacion.innerHTML += `<li class="page-item ${pagina === totalPaginas ? 'disabled' : ''}">
            <button class="page-link" onclick="cambiarPaginaHorarios(${pagina + 1})">Siguiente</button>
        </li>`;

        paginaActualHorarios = pagina;
    }

    function cambiarPaginaHorarios(nuevaPagina) {
        mostrarPaginaHorarios(nuevaPagina);
    }

    function filtrarHorario() {
        paginaActualHorarios = 1;
        mostrarPaginaHorarios(paginaActualHorarios);
    }

    // Cargar fichas al abrir el modal
    document.getElementById('horarioModal').addEventListener('shown.bs.modal', function() {
        cargarFichas();
    });

    // Función para cargar fichas
    async function cargarFichas() {
        try {
            const response = await fetch('get_data.php?action=get_fichas');
            if (response.ok) {
                const data = await response.json();
                fichasData = data;
                mostrarTodasLasFichas();
            }
        } catch (error) {
            console.error('Error cargando fichas:', error);
        }
    }

    // Función para mostrar todas las fichas en el dropdown
    function mostrarTodasLasFichas() {
        const listaFichas = document.getElementById('lista_fichas');
        listaFichas.innerHTML = '';
        
        fichasData.forEach(ficha => {
            const fichaItem = document.createElement('div');
            fichaItem.className = 'ficha-item';
            fichaItem.innerHTML = `
                <div class="ficha-numero">Ficha ${ficha.id_ficha}</div>
                <div class="ficha-programa">${ficha.nombre_programa} (${ficha.tipo_formacion})</div>
            `;
            fichaItem.addEventListener('click', function() {
                seleccionarFicha(ficha);
            });
            listaFichas.appendChild(fichaItem);
        });
    }

    // Función para filtrar fichas
    function filtrarFichas(termino) {
        const listaFichas = document.getElementById('lista_fichas');
        listaFichas.innerHTML = '';
        
        const fichasFiltradas = fichasData.filter(ficha => {
            const textoFicha = `${ficha.id_ficha} ${ficha.nombre_programa} ${ficha.tipo_formacion}`.toLowerCase();
            return textoFicha.includes(termino.toLowerCase());
        });
        
        fichasFiltradas.forEach(ficha => {
            const fichaItem = document.createElement('div');
            fichaItem.className = 'ficha-item';
            fichaItem.innerHTML = `
                <div class="ficha-numero">Ficha ${ficha.id_ficha}</div>
                <div class="ficha-programa">${ficha.nombre_programa} (${ficha.tipo_formacion})</div>
            `;
            fichaItem.addEventListener('click', function() {
                seleccionarFicha(ficha);
            });
            listaFichas.appendChild(fichaItem);
        });
        
        if (fichasFiltradas.length === 0) {
            listaFichas.innerHTML = '<div class="ficha-item">No se encontraron fichas</div>';
        }
    }

    // Función para seleccionar una ficha
    function seleccionarFicha(ficha) {
        document.getElementById('buscar_ficha').value = `Ficha ${ficha.id_ficha} - ${ficha.nombre_programa}`;
        document.getElementById('id_ficha').value = ficha.id_ficha;
        document.getElementById('lista_fichas').style.display = 'none';
        cargarMaterias(ficha.id_ficha);
    }

    // Función para cargar materias de la ficha
    async function cargarMaterias(idFicha) {
        try {
            const response = await fetch(`get_data.php?action=get_materias_ficha&id_ficha=${idFicha}`);
            if (response.ok) {
                materiasData = await response.json();
                
                // Actualizar todos los selects de materias
                document.querySelectorAll('.materia-select').forEach(select => {
                    select.innerHTML = '<option value="">Seleccione materia</option>';
                    materiasData.forEach(materia => {
                        select.innerHTML += `<option value="${materia.id_materia_ficha}">
                            ${materia.materia}
                        </option>`;
                    });
                });
            }
        } catch (error) {
            console.error('Error cargando materias:', error);
        }
    }

    // Event listeners
    document.addEventListener("DOMContentLoaded", function() {
        mostrarPaginaHorarios(paginaActualHorarios);

        // Inicializar tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Manejar el input de búsqueda de fichas
        const buscarFicha = document.getElementById('buscar_ficha');
        if (buscarFicha) {
            buscarFicha.addEventListener('input', function() {
                const termino = this.value.trim();
                const listaFichas = document.getElementById('lista_fichas');
                
                if (termino.length > 0) {
                    filtrarFichas(termino);
                    listaFichas.style.display = 'block';
                } else {
                    mostrarTodasLasFichas();
                    listaFichas.style.display = 'block';
                }
            });

            // Mostrar lista al hacer focus
            buscarFicha.addEventListener('focus', function() {
                if (fichasData.length > 0) {
                    document.getElementById('lista_fichas').style.display = 'block';
                }
            });
        }

        // Ocultar lista al hacer click fuera
        document.addEventListener('click', function(event) {
            const fichaDropdown = document.querySelector('.ficha-dropdown');
            if (fichaDropdown && !fichaDropdown.contains(event.target)) {
                document.getElementById('lista_fichas').style.display = 'none';
            }
        });

        // Manejar activación/desactivación de días
        document.querySelectorAll('.dia-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const diaCard = this.closest('.dia-card');
                const diaConfig = diaCard.querySelector('.dia-config');
                
                if (this.checked) {
                    diaConfig.style.display = 'block';
                } else {
                    diaConfig.style.display = 'none';
                }
            });
        });

        // Manejar cambio de tipo de día
        document.querySelectorAll('.tipo-dia').forEach(radio => {
            radio.addEventListener('change', function() {
                const diaCard = this.closest('.dia-card');
                const bloque2 = diaCard.querySelector('.bloque-2');
                
                if (this.value === 'dos_bloques') {
                    bloque2.style.display = 'block';
                } else {
                    bloque2.style.display = 'none';
                }
            });
        });

        // Manejar eliminación de horarios
        document.addEventListener('click', function(event) {
            if (event.target.closest('.delete-horario')) {
                const button = event.target.closest('.delete-horario');
                const horarioNombre = button.getAttribute('data-nombre');
                
                document.getElementById('deleteNombreHorario').value = horarioNombre;
                document.getElementById('deleteHorarioNombre').textContent = horarioNombre;
                
                const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                deleteModal.show();
            }
        });

        // Resetear formulario cuando se cierra el modal
        document.getElementById('horarioModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('horarioForm').reset();
            document.querySelectorAll('.dia-config').forEach(config => {
                config.style.display = 'none';
            });
            document.querySelectorAll('.bloque-2').forEach(bloque => {
                bloque.style.display = 'none';
            });
            document.getElementById('lista_fichas').style.display = 'none';
            limpiarMaterias();
        });
    });

    // Función para limpiar materias
    function limpiarMaterias() {
        document.querySelectorAll('.materia-select').forEach(select => {
            select.innerHTML = '<option value="">Primero seleccione una ficha</option>';
        });
    }
    </script>
</body>
</html>

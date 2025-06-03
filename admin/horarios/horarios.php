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

// Procesar creación de horario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'crear_horario') {
    $nombre_horario = trim($_POST['nombre_horario']);
    $descripcion = trim($_POST['descripcion']);
    $id_jornada = $_POST['id_jornada'] ?? null;
    $id_ficha = $_POST['id_ficha'] ?? null;
    $id_trimestre = $_POST['id_trimestre'] ?? null;
    $dias_configuracion = $_POST['dias_config'] ?? [];

    if (empty($nombre_horario) || empty($id_jornada) || empty($id_ficha) || empty($id_trimestre)) {
        $alertMessage = "El nombre del horario, jornada, ficha y trimestre son obligatorios";
        $alertType = "danger";
    } elseif (empty($dias_configuracion)) {
        $alertMessage = "Debe configurar al menos un día de la semana";
        $alertType = "danger";
    } else {
        try {
            $conexion->beginTransaction();

            // Crear el horario principal
            $stmt = $conexion->prepare("
                INSERT INTO horario (nombre_horario, descripcion, id_jornada, id_ficha, id_trimestre, id_estado, fecha_creacion) 
                VALUES (:nombre_horario, :descripcion, :id_jornada, :id_ficha, :id_trimestre, 1, CURRENT_DATE)
            ");
            $stmt->bindParam(':nombre_horario', $nombre_horario, PDO::PARAM_STR);
            $stmt->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
            $stmt->bindParam(':id_jornada', $id_jornada, PDO::PARAM_INT);
            $stmt->bindParam(':id_ficha', $id_ficha, PDO::PARAM_INT);
            $stmt->bindParam(':id_trimestre', $id_trimestre, PDO::PARAM_INT);
            $stmt->execute();
            
            $id_horario = $conexion->lastInsertId();

            // Procesar configuración de cada día
            foreach ($dias_configuracion as $dia => $config) {
                if (!isset($config['activo']) || $config['activo'] != '1') continue;
                
                $tipo_dia = $config['tipo']; // 'un_bloque' o 'dos_bloques'
                
                if ($tipo_dia == 'un_bloque') {
                    // Un solo bloque
                    if (!empty($config['materia_bloque1'])) {
                        $stmt = $conexion->prepare("
                            INSERT INTO horario_materia_dia (id_horario, dia_semana, id_materia_ficha, bloque_numero, hora_inicio, hora_fin) 
                            VALUES (:id_horario, :dia_semana, :id_materia_ficha, 1, :hora_inicio, :hora_fin)
                        ");
                        $stmt->bindParam(':id_horario', $id_horario, PDO::PARAM_INT);
                        $stmt->bindParam(':dia_semana', $dia, PDO::PARAM_STR);
                        $stmt->bindParam(':id_materia_ficha', $config['materia_bloque1'], PDO::PARAM_INT);
                        $stmt->bindParam(':hora_inicio', $config['hora_inicio_bloque1'], PDO::PARAM_STR);
                        $stmt->bindParam(':hora_fin', $config['hora_fin_bloque1'], PDO::PARAM_STR);
                        $stmt->execute();
                    }
                } else {
                    // Dos bloques
                    if (!empty($config['materia_bloque1'])) {
                        $stmt = $conexion->prepare("
                            INSERT INTO horario_materia_dia (id_horario, dia_semana, id_materia_ficha, bloque_numero, hora_inicio, hora_fin) 
                            VALUES (:id_horario, :dia_semana, :id_materia_ficha, 1, :hora_inicio, :hora_fin)
                        ");
                        $stmt->bindParam(':id_horario', $id_horario, PDO::PARAM_INT);
                        $stmt->bindParam(':dia_semana', $dia, PDO::PARAM_STR);
                        $stmt->bindParam(':id_materia_ficha', $config['materia_bloque1'], PDO::PARAM_INT);
                        $stmt->bindParam(':hora_inicio', $config['hora_inicio_bloque1'], PDO::PARAM_STR);
                        $stmt->bindParam(':hora_fin', $config['hora_fin_bloque1'], PDO::PARAM_STR);
                        $stmt->execute();
                    }
                    
                    if (!empty($config['materia_bloque2'])) {
                        $stmt = $conexion->prepare("
                            INSERT INTO horario_materia_dia (id_horario, dia_semana, id_materia_ficha, bloque_numero, hora_inicio, hora_fin) 
                            VALUES (:id_horario, :dia_semana, :id_materia_ficha, 2, :hora_inicio, :hora_fin)
                        ");
                        $stmt->bindParam(':id_horario', $id_horario, PDO::PARAM_INT);
                        $stmt->bindParam(':dia_semana', $dia, PDO::PARAM_STR);
                        $stmt->bindParam(':id_materia_ficha', $config['materia_bloque2'], PDO::PARAM_INT);
                        $stmt->bindParam(':hora_inicio', $config['hora_inicio_bloque2'], PDO::PARAM_STR);
                        $stmt->bindParam(':hora_fin', $config['hora_fin_bloque2'], PDO::PARAM_STR);
                        $stmt->execute();
                    }
                }
            }

            $conexion->commit();
            $alertMessage = "Horario creado y asignado correctamente";
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
    $id_horario = $_POST['id_horario'];
    
    try {
        $conexion->beginTransaction();
        
        // Eliminar detalles del horario
        $stmt = $conexion->prepare("DELETE FROM horario_materia_dia WHERE id_horario = :id_horario");
        $stmt->bindParam(':id_horario', $id_horario, PDO::PARAM_INT);
        $stmt->execute();
        
        // Eliminar horario principal
        $stmt = $conexion->prepare("DELETE FROM horario WHERE id_horario = :id_horario");
        $stmt->bindParam(':id_horario', $id_horario, PDO::PARAM_INT);
        $stmt->execute();
        
        $conexion->commit();
        $alertMessage = "Horario eliminado correctamente";
        $alertType = "success";
        
    } catch (PDOException $e) {
        $conexion->rollBack();
        $alertMessage = "Error: " . $e->getMessage();
        $alertType = "danger";
    }
}

// Obtener jornadas
$jornadas = [];
try {
    $stmt = $conexion->query("SELECT * FROM jornada ORDER BY id_jornada");
    $jornadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $alertMessage = "Error al cargar jornadas: " . $e->getMessage();
    $alertType = "danger";
}

// Obtener bloques de horario por jornada (sin descansos)
$bloques_por_jornada = [];
try {
    $stmt = $conexion->query("
        SELECT bh.*, j.jornada 
        FROM bloques_horario bh 
        JOIN jornada j ON bh.id_jornada = j.id_jornada 
        ORDER BY bh.id_jornada, bh.orden_bloque
    ");
    $bloques = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($bloques as $bloque) {
        $bloques_por_jornada[$bloque['id_jornada']][] = $bloque;
    }
} catch (PDOException $e) {
    $alertMessage = "Error al cargar bloques: " . $e->getMessage();
    $alertType = "danger";
}

// Obtener días de la semana
$dias_semana = [];
try {
    $stmt = $conexion->query("SELECT * FROM dias_semana WHERE orden_dia <= 6 ORDER BY orden_dia");
    $dias_semana = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $alertMessage = "Error al cargar días: " . $e->getMessage();
    $alertType = "danger";
}

// Obtener horarios existentes - CONSULTA SIMPLIFICADA
$horarios = [];
try {
    // Primero obtener horarios que tienen nombre_horario (los nuevos)
    $stmt = $conexion->query("
        SELECT h.id_horario, h.nombre_horario, h.descripcion, h.fecha_creacion,
               j.jornada, e.estado, h.id_ficha, fo.nombre as nombre_programa, t.trimestre
        FROM horario h
        LEFT JOIN jornada j ON h.id_jornada = j.id_jornada
        LEFT JOIN estado e ON h.id_estado = e.id_estado
        LEFT JOIN fichas f ON h.id_ficha = f.id_ficha
        LEFT JOIN formacion fo ON f.id_formacion = fo.id_formacion
        LEFT JOIN trimestre t ON h.id_trimestre = t.id_trimestre
        WHERE h.nombre_horario IS NOT NULL AND h.nombre_horario != ''
        ORDER BY h.id_horario DESC
    ");
    $horarios_nuevos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Luego obtener horarios antiguos (sin nombre_horario)
    $stmt = $conexion->query("
        SELECT h.id_horario, 
               CONCAT('Horario #', h.id_horario) as nombre_horario,
               'Horario creado anteriormente' as descripcion,
               NULL as fecha_creacion,
               'No definida' as jornada,
               'Activo' as estado,
               h.id_materia_ficha,
               'Ver detalles' as nombre_programa,
               'No definido' as trimestre
        FROM horario h
        WHERE (h.nombre_horario IS NULL OR h.nombre_horario = '')
        ORDER BY h.id_horario DESC
    ");
    $horarios_antiguos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Combinar ambos arrays
    $horarios = array_merge($horarios_nuevos, $horarios_antiguos);
    
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
        .tipo-badge {
            font-size: 0.8em;
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
                                        <li><strong>Jornada Completa:</strong> Una materia durante toda la jornada (6.5 horas aprox)</li>
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
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Ficha</th>
                                    <th>Programa</th>
                                    <th>Trimestre</th>
                                    <th>Jornada</th>
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
                                            <td><?php echo htmlspecialchars($horario['id_horario']); ?></td>
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
                                                <span class="badge <?php echo ($horario['estado'] == 'Activo') ? 'bg-success' : 'bg-danger'; ?>">
                                                    <?php echo htmlspecialchars($horario['estado'] ?? 'Activo'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-info view-horario" 
                                                            data-id="<?php echo $horario['id_horario']; ?>"
                                                            data-bs-toggle="tooltip" 
                                                            title="Ver detalle del horario">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger delete-horario" 
                                                            data-id="<?php echo $horario['id_horario']; ?>"
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
                            <label for="id_ficha" class="form-label">Ficha *</label>
                            <select class="form-select" id="id_ficha" name="id_ficha" required>
                                <option value="">Seleccione una ficha</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="id_trimestre" class="form-label">Trimestre *</label>
                            <select class="form-select" id="id_trimestre" name="id_trimestre" required disabled>
                                <option value="">Primero seleccione una ficha</option>
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
                                                <select class="form-select hora-inicio-1" 
                                                        name="dias_config[<?php echo $dia['nombre_dia']; ?>][hora_inicio_bloque1]">
                                                    <option value="">Seleccione hora</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Hora Fin</label>
                                                <select class="form-select hora-fin-1" 
                                                        name="dias_config[<?php echo $dia['nombre_dia']; ?>][hora_fin_bloque1]">
                                                    <option value="">Seleccione hora</option>
                                                </select>
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
                                                <select class="form-select hora-inicio-2" 
                                                        name="dias_config[<?php echo $dia['nombre_dia']; ?>][hora_inicio_bloque2]">
                                                    <option value="">Seleccione hora</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Hora Fin</label>
                                                <select class="form-select hora-fin-2" 
                                                        name="dias_config[<?php echo $dia['nombre_dia']; ?>][hora_fin_bloque2]">
                                                    <option value="">Seleccione hora</option>
                                                </select>
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
                            <li>Seleccione primero la ficha para cargar sus materias asignadas</li>
                            <li>Configure cada día individualmente según sus necesidades</li>
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
                    <p class="text-muted">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <form action="" method="POST" id="deleteForm">
                        <input type="hidden" name="action" value="eliminar">
                        <input type="hidden" name="id_horario" id="deleteIdHorario">
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

    document.addEventListener("DOMContentLoaded", function() {
        mostrarPaginaHorarios(paginaActualHorarios);

        // Inicializar tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Manejar eliminación de horarios
        const deleteButtons = document.querySelectorAll('.delete-horario');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const horarioId = this.getAttribute('data-id');
                const horarioNombre = this.getAttribute('data-nombre');
                
                document.getElementById('deleteIdHorario').value = horarioId;
                document.getElementById('deleteHorarioNombre').textContent = horarioNombre;
                
                const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                deleteModal.show();
            });
        });

        // Resetear formulario cuando se cierra el modal
        document.getElementById('horarioModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('horarioForm').reset();
        });
    });
    </script>
<script>
// Variables globales
let fichasData = [];
let materiasData = [];
let bloquesData = [];

// Cargar fichas al abrir el modal
document.getElementById('horarioModal').addEventListener('shown.bs.modal', function() {
    cargarFichas();
});

// Función para cargar fichas
async function cargarFichas() {
    try {
        const response = await fetch('get_data.php?action=get_fichas');
        const data = await response.json();
        
        console.log('Fichas cargadas:', data); // Para debug
        
        fichasData = data;
        
        const selectFicha = document.getElementById('id_ficha');
        selectFicha.innerHTML = '<option value="">Seleccione una ficha</option>';
        
        fichasData.forEach(ficha => {
            selectFicha.innerHTML += `<option value="${ficha.id_ficha}">
                ${ficha.id_ficha} - ${ficha.nombre_programa} (${ficha.tipo_formacion})
            </option>`;
        });
    } catch (error) {
        console.error('Error cargando fichas:', error);
    }
}

// Manejar cambio de ficha
document.getElementById('id_ficha').addEventListener('change', function() {
    const idFicha = this.value;
    if (idFicha) {
        cargarTrimestres(idFicha);
        cargarMaterias(idFicha);
    } else {
        document.getElementById('id_trimestre').innerHTML = '<option value="">Primero seleccione una ficha</option>';
        document.getElementById('id_trimestre').disabled = true;
        limpiarMaterias();
    }
});

// Función para cargar trimestres
async function cargarTrimestres(idFicha) {
    try {
        const response = await fetch(`get_data.php?action=get_trimestres&id_ficha=${idFicha}`);
        const trimestres = await response.json();
        
        console.log('Trimestres cargados:', trimestres); // Para debug
        
        const selectTrimestre = document.getElementById('id_trimestre');
        selectTrimestre.innerHTML = '<option value="">Seleccione un trimestre</option>';
        selectTrimestre.disabled = false;
        
        trimestres.forEach(trimestre => {
            selectTrimestre.innerHTML += `<option value="${trimestre.id_trimestre}">
                ${trimestre.trimestre}
            </option>`;
        });
    } catch (error) {
        console.error('Error cargando trimestres:', error);
    }
}

// Función para cargar materias de la ficha
async function cargarMaterias(idFicha) {
    try {
        const response = await fetch(`get_data.php?action=get_materias_ficha&id_ficha=${idFicha}`);
        materiasData = await response.json();
        
        console.log('Materias cargadas:', materiasData); // Para debug
        
        // Actualizar todos los selects de materias
        document.querySelectorAll('.materia-select').forEach(select => {
            select.innerHTML = '<option value="">Seleccione materia</option>';
            materiasData.forEach(materia => {
                select.innerHTML += `<option value="${materia.id_materia_ficha}">
                    ${materia.materia}
                </option>`;
            });
        });
    } catch (error) {
        console.error('Error cargando materias:', error);
    }
}

// Manejar cambio de jornada
document.getElementById('id_jornada').addEventListener('change', function() {
    const idJornada = this.value;
    if (idJornada) {
        cargarBloques(idJornada);
    }
});

// Función para cargar bloques de horario
async function cargarBloques(idJornada) {
    try {
        const response = await fetch(`get_data.php?action=get_bloques_jornada&id_jornada=${idJornada}`);
        bloquesData = await response.json();
        
        console.log('Bloques cargados:', bloquesData); // Para debug
        
        // Actualizar selects de horas
        actualizarSelectsHoras();
    } catch (error) {
        console.error('Error cargando bloques:', error);
    }
}

// Función para actualizar selects de horas
function actualizarSelectsHoras() {
    document.querySelectorAll('.hora-inicio-1, .hora-fin-1, .hora-inicio-2, .hora-fin-2').forEach(select => {
        select.innerHTML = '<option value="">Seleccione hora</option>';
        bloquesData.forEach(bloque => {
            select.innerHTML += `<option value="${bloque.hora_inicio}">${bloque.hora_inicio}</option>`;
            select.innerHTML += `<option value="${bloque.hora_fin}">${bloque.hora_fin}</option>`;
        });
    });
}

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

// Función para limpiar materias
function limpiarMaterias() {
    document.querySelectorAll('.materia-select').forEach(select => {
        select.innerHTML = '<option value="">Primero seleccione una ficha</option>';
    });
}

// Resetear formulario cuando se cierra el modal
document.getElementById('horarioModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('horarioForm').reset();
    document.querySelectorAll('.dia-config').forEach(config => {
        config.style.display = 'none';
    });
    document.querySelectorAll('.bloque-2').forEach(bloque => {
        bloque.style.display = 'none';
    });
    document.getElementById('id_trimestre').disabled = true;
    limpiarMaterias();
});
</script>
</body>
</html>

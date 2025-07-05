<?php

if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['DOCUMENT_ROOT'], 'htdocs') !== false) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php';
} else {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';
}

require_once __DIR__ . '/../vendor/autoload.php';
include 'session.php';

if ($_SESSION['rol'] !== 3 && $_SESSION['rol'] !== 5) {
    header('Location:' . BASE_URL . '/includes/exit.php?motivo=acceso-denegado');
    exit;
}

use Dompdf\Dompdf;
use Dompdf\Options;

// Función para convertir imagen a base64
function getImageBase64($imagePath)
{
    $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/assets/img/icon2.png';
    if (strpos($_SERVER['HTTP_HOST'], 'localhost') === false) {
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/assets/img/icon2.png';
    }

    if (file_exists($fullPath)) {
        $imageData = file_get_contents($fullPath);
        $imageInfo = getimagesize($fullPath);
        $mimeType = $imageInfo['mime'];
        return 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
    }
    return null;
}

// Obtener parámetros del formulario
$id_ficha = isset($_POST['id_ficha']) ? (int)$_POST['id_ficha'] : 0;
$id_instructor = isset($_SESSION['documento']) ? (int)$_SESSION['documento'] : 0;
$fecha_desde = isset($_POST['fecha_desde']) && !empty($_POST['fecha_desde']) ? $_POST['fecha_desde'] : null;
$fecha_hasta = isset($_POST['fecha_hasta']) && !empty($_POST['fecha_hasta']) ? $_POST['fecha_hasta'] : null;
$estados = isset($_POST['estados']) && is_array($_POST['estados']) ? array_map('intval', $_POST['estados']) : [];
$tipo_reporte = isset($_POST['tipo_reporte']) ? $_POST['tipo_reporte'] : 'resumen';
$materia_filtro = isset($_POST['materia_filtro']) && !empty($_POST['materia_filtro']) ? (int)$_POST['materia_filtro'] : null;
$orden = isset($_POST['orden']) ? $_POST['orden'] : 'apellidos';

// Validar parámetros obligatorios
if (!$id_ficha) {
    die("Error: ID de ficha no válido.");
}

// Validar orden
$ordenes_validos = ['nombres', 'apellidos', 'documento', 'actividades_pendientes'];
if (!in_array($orden, $ordenes_validos)) {
    $orden = 'apellidos';
}

// Estados disponibles
$estados_nombres = [
    3 => 'Aprobado',
    4 => 'Desaprobado',
    8 => 'Entregado',
    9 => 'Pendiente',
    10 => 'No entregado'
];

// Verificar si es instructor principal de la ficha
$sql_instructor = "SELECT COUNT(*) FROM fichas WHERE id_ficha = :id_ficha AND id_instructor = :id_instructor";
$stmt_instructor = $conex->prepare($sql_instructor);
$stmt_instructor->execute(['id_ficha' => $id_ficha, 'id_instructor' => $id_instructor]);
$es_instructor_principal = $stmt_instructor->fetchColumn() > 0;

// Obtener información de la ficha
$sql_ficha = "
    SELECT 
        f.id_ficha, 
        fo.nombre AS formacion, 
        f.fecha_creac,
        f.id_trimestre
    FROM fichas f
    INNER JOIN formacion fo ON f.id_formacion = fo.id_formacion
    WHERE f.id_ficha = :id_ficha
";

$stmt_ficha = $conex->prepare($sql_ficha);
$stmt_ficha->execute(['id_ficha' => $id_ficha]);
$info_ficha = $stmt_ficha->fetch(PDO::FETCH_ASSOC);

if (!$info_ficha) {
    die("Error: No se encontró información de la ficha.");
}

// Obtener información del trimestre por separado
$sql_trimestre = "SELECT trimestre FROM trimestre WHERE id_trimestre = :id_trimestre";
$stmt_trimestre = $conex->prepare($sql_trimestre);
$stmt_trimestre->execute(['id_trimestre' => $info_ficha['id_trimestre']]);
$trimestre_info = $stmt_trimestre->fetch(PDO::FETCH_ASSOC);
$info_ficha['trimestre'] = $trimestre_info ? $trimestre_info['trimestre'] : 'N/A';

// PASO 1: Obtener TODOS los aprendices de la ficha (consulta simple)
$sql_aprendices_base = "
    SELECT DISTINCT
        u.id AS documento,
        u.nombres,
        u.apellidos,
        u.telefono,
        u.correo,
        e.estado,
        u.fecha_registro
    FROM user_ficha uf
    INNER JOIN usuarios u ON uf.id_user = u.id
    INNER JOIN estado e ON u.id_estado = e.id_estado
    WHERE uf.id_ficha = :id_ficha
";

$stmt_aprendices_base = $conex->prepare($sql_aprendices_base);
$stmt_aprendices_base->execute(['id_ficha' => $id_ficha]);
$todos_aprendices = $stmt_aprendices_base->fetchAll(PDO::FETCH_ASSOC);

if (empty($todos_aprendices)) {
    die("Error: No se encontraron aprendices para esta ficha.");
}

// PASO 2: NO filtrar aprendices, solo usar todos los aprendices de la ficha
$aprendices_filtrados = $todos_aprendices;

// PASO 3: Aplicar ordenamiento
if ($orden === 'actividades_pendientes') {
    usort($aprendices_filtrados, function ($a, $b) use ($conex, $id_ficha, $es_instructor_principal, $id_instructor) {
        $sql_pendientes = "
            SELECT COUNT(*) 
            FROM actividades_user au
            INNER JOIN actividades a ON au.id_actividad = a.id_actividad
            INNER JOIN materia_ficha mf ON a.id_materia_ficha = mf.id_materia_ficha
            WHERE au.id_user = :id_user 
            AND mf.id_ficha = :id_ficha
            AND au.id_estado_actividad = 9
        ";

        $params_base = ['id_user' => 0, 'id_ficha' => $id_ficha];
        if (!$es_instructor_principal) {
            $sql_pendientes .= " AND mf.id_instructor = :id_instructor";
            $params_base['id_instructor'] = $id_instructor;
        }

        $stmt = $conex->prepare($sql_pendientes);

        $params_a = $params_base;
        $params_a['id_user'] = $a['documento'];
        $stmt->execute($params_a);
        $pendientes_a = $stmt->fetchColumn();

        $params_b = $params_base;
        $params_b['id_user'] = $b['documento'];
        $stmt->execute($params_b);
        $pendientes_b = $stmt->fetchColumn();

        return $pendientes_b - $pendientes_a;
    });
} else {
    usort($aprendices_filtrados, function ($a, $b) use ($orden) {
        switch ($orden) {
            case 'nombres':
                return strcasecmp($a['nombres'], $b['nombres']);
            case 'documento':
                return (int)$a['documento'] - (int)$b['documento'];
            default:
                return strcasecmp($a['apellidos'], $b['apellidos']);
        }
    });
}

$aprendices = $aprendices_filtrados;

if (empty($aprendices)) {
    die("Error: No se encontraron aprendices que cumplan con los filtros aplicados.");
}

// Obtener logo en base64
$logoBase64 = getImageBase64('icon2.png');

// Título del reporte
$titulo_reporte = "REPORTE ACADÉMICO - FICHA N° " . $info_ficha['id_ficha'];

$subtitulo_reporte = match ($tipo_reporte) {
    'resumen' => 'RESUMEN EJECUTIVO',
    'solo_pendientes' => 'ACTIVIDADES PENDIENTES',
    'por_estado' => 'REPORTE POR ESTADO',
    'completo' => 'REPORTE COMPLETO',
    default => 'REPORTE GENERAL'
};

// Generar HTML para el PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: "Arial", "Helvetica", sans-serif;
        font-size: 13px;
        color: #2c3e50;
        line-height: 1.4;
        background: #ffffff;
    }

    .document-container {
        width: 100%;
        max-width: 1800px;
        margin: 0 auto;
        padding: 0;
    }

    .document-header {
        background-color: #0E4A86;
        color: #ffffff;
        padding: 20px 25px;
        margin-bottom: 20px;
        position: relative;
    }

    .header-content {
        display: table;
        width: 100%;
    }

    .logo-container {
        display: table-cell;
        width: 80px;
        vertical-align: middle;
        padding-right: 20px;
    }

    .logo-circle {
        width: 60px;
        height: 60px;
        background-color: #0E4A86;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }

    .title-container {
        display: table-cell;
        vertical-align: middle;
    }

    .main-title {
        font-size: 22px;
        font-weight: bold;
        margin-bottom: 5px;
        color: #ffffff;
    }

    .subtitle {
        font-size: 16px;
        font-weight: normal;
        color: #e8f2ff;
        margin-bottom: 15px;
    }

    .header-info-grid {
        display: table;
        width: 100%;
        border-top: 1px solid rgba(255,255,255,0.3);
        padding-top: 15px;
        margin-top: 15px;
    }

    .info-item {
        display: table-cell;
        width: 33.33%;
        vertical-align: top;
        padding-right: 15px;
    }

    .info-label {
        font-size: 12px;
        color: #b8d4f0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 3px;
    }

    .info-value {
        font-size: 15px;
        font-weight: 600;
        color: #ffffff;
    }

    .content-section {
        padding: 0 15px;
    }

    .filters-panel {
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        border-left: 4px solid #0E4A86;
        padding: 18px;
        margin-bottom: 20px;
        border-radius: 0 6px 6px 0;
    }

    .filters-title {
        color: #0E4A86;
        font-size: 15px;
        font-weight: bold;
        margin-bottom: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .filters-grid {
        display: table;
        width: 100%;
    }

    .filter-row {
        display: table-row;
    }

    .filter-cell {
        display: table-cell;
        width: 50%;
        padding: 6px 15px 6px 0;
        vertical-align: top;
    }

    .filter-label {
        font-weight: 600;
        color: #495057;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        margin-bottom: 2px;
    }

    .filter-value {
        color: #6c757d;
        font-size: 13px;
    }

    .statistics-panel {
        background-color: #f1f3f4;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .stats-grid {
        display: table;
        width: 100%;
    }

    .stat-item {
        display: table-cell;
        text-align: center;
        padding: 10px;
        vertical-align: top;
        border-right: 1px solid #dee2e6;
    }

    .stat-item:last-child {
        border-right: none;
    }

    .stat-number {
        display: block;
        font-size: 30px;
        font-weight: bold;
        color: #0E4A86;
        margin-bottom: 5px;
    }

    .stat-label {
        font-size: 13px;
        color: #6c757d;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
        font-size: 12px;
        background: #ffffff;
        border: 1px solid #dee2e6;
    }

    .data-table th {
        background-color: #0E4A86;
        color: #ffffff;
        font-weight: bold;
        text-align: center;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 10px 6px;
        border: 1px solid #0a3d73;
    }

    .data-table td {
        padding: 8px 6px;
        border: 1px solid #dee2e6;
        vertical-align: top;
        text-align: left;
    }

    .data-table tbody tr:nth-child(even) {
        background-color: #f8f9fa;
    }

    .data-table tbody tr:hover {
        background-color: #e3f2fd;
    }

    .activity-section {
        margin-bottom: 8px;
        padding: 6px;
        background-color: rgba(14, 74, 134, 0.05);
        border-left: 3px solid #0E4A86;
        border-radius: 3px;
    }

    .activity-subject {
        font-weight: bold;
        color: #0E4A86;
        margin-bottom: 4px;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.2px;
    }

    .activity-detail {
        font-size: 10px;
        margin-left: 10px;
        margin-bottom: 3px;
        line-height: 1.3;
        color: #495057;
    }

    .status-tag {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 10px;
        font-size: 9px;
        font-weight: bold;
        margin-left: 4px;
        text-transform: uppercase;
        letter-spacing: 0.2px;
    }

    .status-pending { 
        background-color: #fff3cd; 
        color: #856404; 
        border: 1px solid #ffeaa7;
    }
    .status-approved { 
        background-color: #d4edda; 
        color: #155724; 
        border: 1px solid #c3e6cb;
    }
    .status-rejected { 
        background-color: #f8d7da; 
        color: #721c24; 
        border: 1px solid #f5c6cb;
    }
    .status-delivered { 
        background-color: #cce5ff; 
        color: #004085; 
        border: 1px solid #b3d7ff;
    }
    .status-not-delivered { 
        background-color: #e2e3e5; 
        color: #383d41; 
        border: 1px solid #d1d3d4;
    }

    .no-data {
        text-align: center;
        padding: 12px;
        font-style: italic;
        color: #6c757d;
        background-color: #f8f9fa;
        border-radius: 4px;
        font-size: 11px;
        border: 1px dashed #dee2e6;
    }

    .document-footer {
        margin-top: 30px;
        padding: 15px;
        border-top: 2px solid #0E4A86;
        background-color: #f8f9fa;
        text-align: center;
        font-size: 11px;
        color: #6c757d;
    }

    .document-footer p {
        margin: 4px 0;
    }

    .document-footer strong {
        color: #0E4A86;
        font-size: 12px;
    }

    .text-primary {
        color: #0E4A86;
        font-weight: bold;
    }

    .text-secondary {
        color: #6c757d;
    }

    .text-center {
        text-align: center;
    }

    .font-weight-bold {
        font-weight: bold;
    }

    @page {
        margin: 15mm;
    }

    @media print {
        body { font-size: 12px; }
        .main-title { font-size: 20px; }
        .stat-number { font-size: 26px; }
    }
</style>

</head>
<body>
    <div class="document-container">';

// Header del documento
$html .= '<div class="document-header">
    <div class="header-content">
        <div class="logo-container">
            <div class="logo-circle">';

if ($logoBase64) {
    $html .= '<img src="' . $logoBase64 . '" alt="TeamTalks" style="max-width: 130px; object-fit: contain; position: relative; right: 32px; bottom: 23px">';
} else {
    $html .= '<div style="width: 80px; height: 80px; background-color: #0E4A86; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 12px;">TT</div>';
}

$html .= '</div>
        </div>
        <div class="title-container">
            <h1 class="main-title">' . htmlspecialchars($titulo_reporte) . '</h1>
            <p class="subtitle">' . htmlspecialchars($subtitulo_reporte) . '</p>
            
            <div class="header-info-grid">
                <div class="info-item">
                    <div class="info-label">Programa de Formación</div>
                    <div class="info-value">' . htmlspecialchars($info_ficha['formacion']) . '</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Trimestre Académico</div>
                    <div class="info-value">' . htmlspecialchars($info_ficha['trimestre']) . '</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Fecha de Generación</div>
                    <div class="info-value">' . date('d/m/Y H:i:s') . '</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="content-section">';

// Sección de filtros aplicados
$tipo_reporte_texto = match ($tipo_reporte) {
    'resumen' => 'Resumen Ejecutivo',
    'solo_pendientes' => 'Solo Actividades Pendientes',
    'por_estado' => 'Filtrado por Estado',
    'completo' => 'Reporte Completo',
    default => 'Reporte General'
};

$orden_texto = match ($orden) {
    'nombres' => 'Nombres',
    'documento' => 'Número de Documento',
    'actividades_pendientes' => 'Actividades Pendientes',
    default => 'Apellidos'
};

$estados_texto = 'Todos los estados';
if (!empty($estados)) {
    $estados_seleccionados = array_intersect_key($estados_nombres, array_flip($estados));
    $estados_texto = implode(', ', $estados_seleccionados);
}

$fechas_texto = 'Sin restricción de fechas';
if ($fecha_desde || $fecha_hasta) {
    $fechas_texto = '';
    if ($fecha_desde) $fechas_texto .= 'Desde ' . date('d/m/Y', strtotime($fecha_desde));
    if ($fecha_desde && $fecha_hasta) $fechas_texto .= ' hasta ';
    if ($fecha_hasta) $fechas_texto .= date('d/m/Y', strtotime($fecha_hasta));
}

$materia_texto = 'Todas las materias';
if ($materia_filtro) {
    $stmt_materia = $conex->prepare("SELECT materia FROM materias WHERE id_materia = :id");
    $stmt_materia->execute(['id' => $materia_filtro]);
    $materia_nombre = $stmt_materia->fetchColumn();
    if ($materia_nombre) {
        $materia_texto = htmlspecialchars($materia_nombre);
    }
}

$html .= '<div class="filters-panel">
    <div class="filters-title">Parámetros de Filtrado Aplicados</div>
    <div class="filters-grid">
        <div class="filter-row">
            <div class="filter-cell">
                <div class="filter-label">Tipo de Reporte:</div>
                <div class="filter-value">' . $tipo_reporte_texto . '</div>
            </div>
            <div class="filter-cell">
                <div class="filter-label">Ordenamiento:</div>
                <div class="filter-value">' . $orden_texto . '</div>
            </div>
        </div>
        <div class="filter-row">
            <div class="filter-cell">
                <div class="filter-label">Estados de Actividades:</div>
                <div class="filter-value">' . $estados_texto . '</div>
            </div>
            <div class="filter-cell">
                <div class="filter-label">Rango de Fechas:</div>
                <div class="filter-value">' . $fechas_texto . '</div>
            </div>
        </div>
        <div class="filter-row">
            <div class="filter-cell">
                <div class="filter-label">Materia Específica:</div>
                <div class="filter-value">' . $materia_texto . '</div>
            </div>
            <div class="filter-cell">
                <div class="filter-label">Fecha Creación Ficha:</div>
                <div class="filter-value">' . ($info_ficha['fecha_creac'] ? date('d/m/Y', strtotime($info_ficha['fecha_creac'])) : 'No disponible') . '</div>
            </div>
        </div>
    </div>
</div>';

// Estadísticas
$html .= '<div class="statistics-panel">
    <div class="stats-grid">
        <div class="stat-item">
            <span class="stat-number">' . count($aprendices) . '</span>
            <div class="stat-label">Total Aprendices</div>
        </div>
        <div class="stat-item">
            <span class="stat-number">' . $info_ficha['id_ficha'] . '</span>
            <div class="stat-label">Número de Ficha</div>
        </div>
        <div class="stat-item">
            <span class="stat-number">' . date('Y') . '</span>
            <div class="stat-label">Año Académico</div>
        </div>
    </div>
</div>';

// Tabla de datos
$html .= '<table class="data-table">';

if ($tipo_reporte === 'resumen') {
    $html .= '<thead>
        <tr>
            <th style="width: 12%">Documento</th>
            <th style="width: 18%">Nombres</th>
            <th style="width: 18%">Apellidos</th>
            <th style="width: 15%">Teléfono</th>
            <th style="width: 20%">Correo Electrónico</th>
            <th style="width: 12%">Estado</th>
            <th style="width: 15%">Fecha Registro</th>
        </tr>
    </thead>
    <tbody>';

    foreach ($aprendices as $aprendiz) {
        $fecha_registro = 'No disponible';
        if (!empty($aprendiz['fecha_registro'])) {
            $fecha_registro = date('d/m/Y', strtotime($aprendiz['fecha_registro']));
        }

        $html .= '<tr>
            <td class="text-primary font-weight-bold">' . htmlspecialchars($aprendiz['documento']) . '</td>
            <td class="font-weight-bold">' . htmlspecialchars($aprendiz['nombres']) . '</td>
            <td class="font-weight-bold">' . htmlspecialchars($aprendiz['apellidos']) . '</td>
            <td class="text-secondary">' . htmlspecialchars($aprendiz['telefono'] ?? 'No disponible') . '</td>
            <td class="text-secondary">' . htmlspecialchars($aprendiz['correo'] ?? 'No disponible') . '</td>
            <td class="text-center font-weight-bold">' . htmlspecialchars($aprendiz['estado']) . '</td>
            <td class="text-center text-secondary">' . $fecha_registro . '</td>
        </tr>';
    }
} else {
    $html .= '<thead>
        <tr>
            <th style="width: 10%">Documento</th>
            <th style="width: 15%">Nombres</th>
            <th style="width: 15%">Apellidos</th>
            <th style="width: 12%">Teléfono</th>
            <th style="width: 18%">Correo Electrónico</th>
            <th style="width: 10%">Estado</th>
            <th style="width: 8%">Trimestre</th>
            <th style="width: 22%">Actividades Académicas</th>
        </tr>
    </thead>
    <tbody>';

    foreach ($aprendices as $aprendiz) {
        $actividades_html = '';

        if (!empty($estados) && $tipo_reporte !== 'resumen') {
            $sql_actividades = "
                SELECT 
                    a.titulo, 
                    a.fecha_entrega, 
                    e.estado AS estado_actividad, 
                    m.materia
                FROM actividades_user au
                INNER JOIN actividades a ON au.id_actividad = a.id_actividad
                INNER JOIN materia_ficha mf ON a.id_materia_ficha = mf.id_materia_ficha
                INNER JOIN materias m ON mf.id_materia = m.id_materia
                INNER JOIN estado e ON au.id_estado_actividad = e.id_estado
                WHERE au.id_user = :id_user 
                AND mf.id_ficha = :id_ficha
                AND au.id_estado_actividad IN (" . implode(',', array_map('intval', $estados)) . ")
            ";

            $params_actividades = [
                'id_user' => $aprendiz['documento'],
                'id_ficha' => $id_ficha
            ];

            if ($fecha_desde) {
                $sql_actividades .= " AND a.fecha_entrega >= :fecha_desde";
                $params_actividades['fecha_desde'] = $fecha_desde;
            }
            if ($fecha_hasta) {
                $sql_actividades .= " AND a.fecha_entrega <= :fecha_hasta";
                $params_actividades['fecha_hasta'] = $fecha_hasta;
            }
            if ($materia_filtro) {
                $sql_actividades .= " AND mf.id_materia = :materia_filtro";
                $params_actividades['materia_filtro'] = $materia_filtro;
            }
            if (!$es_instructor_principal) {
                $sql_actividades .= " AND mf.id_instructor = :id_instructor";
                $params_actividades['id_instructor'] = $id_instructor;
            }

            $sql_actividades .= " ORDER BY m.materia, a.fecha_entrega DESC LIMIT 5";

            $stmt_actividades = $conex->prepare($sql_actividades);
            $stmt_actividades->execute($params_actividades);
            $actividades = $stmt_actividades->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($actividades)) {
                $actividades_por_materia = [];
                foreach ($actividades as $actividad) {
                    $actividades_por_materia[$actividad['materia']][] = $actividad;
                }

                foreach ($actividades_por_materia as $materia => $lista_actividades) {
                    $actividades_html .= '<div class="activity-section">
                        <div class="activity-subject">' . htmlspecialchars($materia) . '</div>';

                    foreach ($lista_actividades as $actividad) {
                        $clase_estado = match ($actividad['estado_actividad']) {
                            'Pendiente' => 'status-pending',
                            'Aprobado' => 'status-approved',
                            'Desaprobado' => 'status-rejected',
                            'Entregado' => 'status-delivered',
                            'No entregado' => 'status-not-delivered',
                            default => ''
                        };

                        $fecha_formateada = $actividad['fecha_entrega'] ?
                            date('d/m/Y', strtotime($actividad['fecha_entrega'])) : 'Sin fecha';

                        $actividades_html .= '<div class="activity-detail">
                            • ' . htmlspecialchars($actividad['titulo']) . ' 
                            <small>(' . $fecha_formateada . ')</small>
                            <span class="status-tag ' . $clase_estado . '">' .
                            htmlspecialchars($actividad['estado_actividad']) . '</span>
                        </div>';
                    }

                    $actividades_html .= '</div>';
                }
            } else {
                $actividades_html = '<div class="no-data">Sin actividades registradas con los filtros aplicados</div>';
            }
        } else {
            $actividades_html = '<div class="no-data">Sin filtros de estado aplicados</div>';
        }

        $html .= '<tr>
            <td class="text-primary font-weight-bold">' . htmlspecialchars($aprendiz['documento']) . '</td>
            <td class="font-weight-bold">' . htmlspecialchars($aprendiz['nombres']) . '</td>
            <td class="font-weight-bold">' . htmlspecialchars($aprendiz['apellidos']) . '</td>
            <td class="text-secondary">' . htmlspecialchars($aprendiz['telefono'] ?? 'No disponible') . '</td>
            <td class="text-secondary">' . htmlspecialchars($aprendiz['correo'] ?? 'No disponible') . '</td>
            <td class="text-center font-weight-bold">' . htmlspecialchars($aprendiz['estado']) . '</td>
            <td class="text-center text-secondary">' . htmlspecialchars($info_ficha['trimestre']) . '</td>
            <td>' . $actividades_html . '</td>
        </tr>';
    }
}

$html .= '</tbody>
</table>

</div>';

// Footer del documento
$html .= '<div class="document-footer">
    <p><strong>TeamTalks - Sistema de Gestión Académica</strong></p>
    <p><strong>Instructor Responsable:</strong> ' . htmlspecialchars($_SESSION['nombres'] ?? 'No disponible') . ' ' .
    htmlspecialchars($_SESSION['apellidos'] ?? '') . '</p>
    <p><strong>Fecha y Hora de Generación:</strong> ' . date('d/m/Y H:i:s') . '</p>
    <p><strong>Documento Confidencial</strong> - Uso exclusivo para fines académicos y administrativos</p>
    <p>Este reporte contiene información sensible y debe ser tratado con la debida confidencialidad</p>
</div>

    </div>
</body>
</html>';

// Configurar DomPDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');
$options->set('dpi', 150);
$options->set('defaultPaperSize', 'A4');
$options->set('isPhpEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// Generar nombre del archivo
$filename = "Reporte_Academico_Ficha_{$id_ficha}_" . date('Y-m-d_H-i-s') . ".pdf";

// Limpiar buffer de salida
if (ob_get_level()) {
    ob_end_clean();
}

// Headers para descarga
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

echo $dompdf->output();
exit;

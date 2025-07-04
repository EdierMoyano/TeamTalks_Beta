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

$id_ficha = $_POST['id_ficha'] ?? 0;
$id_instructor = $_SESSION['documento'] ?? 0;
$fecha_desde = $_POST['fecha_desde'] ?? '';
$fecha_hasta = $_POST['fecha_hasta'] ?? '';
$estados = $_POST['estados'] ?? [];
$tipo_reporte = $_POST['tipo_reporte'] ?? 'completo';
$materia_filtro = $_POST['materia_filtro'] ?? '';
$orden = $_POST['orden'] ?? 'apellidos';

if (!$id_ficha || !$id_instructor) {
    die("Falta información para generar el reporte.");
}

if (empty($estados) && $tipo_reporte !== 'resumen') {
    die("Debes seleccionar al menos un estado de actividad.");
}

// Verificar si es gerente
$sql_gerente = "SELECT COUNT(*) FROM fichas WHERE id_ficha = :id_ficha AND id_instructor = :id_instructor";
$stmt_gerente = $conex->prepare($sql_gerente);
$stmt_gerente->execute([
    'id_ficha' => $id_ficha,
    'id_instructor' => $id_instructor
]);
$es_gerente = $stmt_gerente->fetchColumn() > 0;

// Obtener aprendices
$sql = "
  SELECT u.nombres, u.apellidos, u.id AS documento, e.estado, t.trimestre, f.id_ficha
  FROM user_ficha uf
  INNER JOIN usuarios u ON uf.id_user = u.id
  INNER JOIN estado e ON u.id_estado = e.id_estado
  INNER JOIN fichas f ON uf.id_ficha = f.id_ficha
  INNER JOIN trimestre t ON f.id_trimestre = t.id_trimestre
  WHERE uf.id_ficha = :id_ficha
";

switch ($orden) {
    case 'nombres':
        $sql .= " ORDER BY u.nombres ASC";
        break;
    case 'documento':
        $sql .= " ORDER BY u.id ASC";
        break;
    default:
        $sql .= " ORDER BY u.apellidos ASC";
        break;
}

$stmt = $conex->prepare($sql);
$stmt->execute(['id_ficha' => $id_ficha]);
$aprendices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Info de la ficha (usando tabla formacion)
$sql_ficha = "
    SELECT f.id_ficha, fo.nombre AS formacion, t.trimestre
    FROM fichas f
    INNER JOIN formacion fo ON f.id_formacion = fo.id_formacion
    INNER JOIN trimestre t ON f.id_trimestre = t.id_trimestre
    WHERE f.id_ficha = :id_ficha
";

$stmt_ficha = $conex->prepare($sql_ficha);
$stmt_ficha->execute(['id_ficha' => $id_ficha]);
$info_ficha = $stmt_ficha->fetch(PDO::FETCH_ASSOC);

$num_aprendices = count($aprendices);

$estados_nombres = [
    3 => 'Aprobado',
    4 => 'Desaprobado',
    8 => 'Entregado',
    9 => 'Pendiente',
    10 => 'No entregado'
];

$estados_incluidos = array_intersect_key($estados_nombres, array_flip($estados));

// Título
$titulo_reporte = "Reporte de Ficha N° " . htmlspecialchars($info_ficha['id_ficha'] ?? 'N/A');

switch ($tipo_reporte) {
    case 'solo_pendientes':
        $titulo_reporte .= " - Solo Pendientes";
        break;
    case 'por_estado':
        $titulo_reporte .= " - Por Estado";
        break;
    case 'resumen':
        $titulo_reporte .= " - Resumen Ejecutivo";
        break;
}

// HTML inicial
$html = '
<style>
body {
    font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
    font-size: 11px;
    padding: 20px;
    color: #334155;
    background: #fff;
    line-height: 1.5;
}

/* Header */
.header {
    text-align: center;
    margin-bottom: 30px;
    padding: 25px;
    border-radius: 12px;
    background: linear-gradient(135deg, #0E4A86, #1e5a9e);
    position: relative;
    color: black;
}

.header::before {
    content: "";
    position: absolute;
    top: -50%;
    right: -20%;
    width: 200px;
    height: 200px;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
    border-radius: 50%;
}

.header h1 {
    font-size: 20px;
    font-weight: bold;
    z-index: 2;
    position: relative;
}

.header-info {
    font-size: 11px;
    position: relative;
    z-index: 2;
    opacity: 0.95;
}

.filters-info {
    background: #f7fafc;
    padding: 15px;
    border-left: 4px solid #0E4A86;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 10px;
}

/* Stats */
.stats {
    display: table;
    table-layout: fixed;
    width: 100%;
    margin-bottom: 15px;
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    padding: 15px;
    font-size: 10px;
}
.stat-item {
    display: table-cell;
    text-align: center;
    padding: 8px;
}
.stat-item strong {
    display: block;
    font-size: 14px;
    font-weight: bold;
    color: #0E4A86;
    margin-bottom: 4px;
}

/* Tabla principal */
table {
    width: 100%;
    border-collapse: collapse;
    font-size: 9px;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    margin-top: 15px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}
th, td {
    border: 1px solid #e2e8f0;
    padding: 8px;
    vertical-align: top;
}
th {
    background: linear-gradient(135deg, #0E4A86, #1e5a9e);
    color: black;
    font-weight: bold;
    text-align: center;
}
tbody tr:nth-child(even) {
    background: #f8fafc;
}
tbody tr:hover {
    background: #edf2f7;
}

/* Actividades por aprendiz */
.actividad-materia {
    font-weight: bold;
    color: #0E4A86;
    padding: 4px 0;
    border-bottom: 1px solid #e2e8f0;
}
.actividad-item {
    font-size: 8px;
    margin-left: 12px;
    line-height: 1.4;
    padding: 3px 0;
}

/* Badges de estado */
.estado-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 7px;
    font-weight: bold;
    margin-left: 4px;
}
.estado-pendiente {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #f59e0b;
}
.estado-aprobado {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #059669;
}
.estado-desaprobado {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #dc2626;
}
.estado-entregado {
    background: #dbeafe;
    color: #1e40af;
    border: 1px solid #2563eb;
}
.estado-no-entregado {
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #6b7280;
}

.no-activities {
    text-align: center;
    padding: 12px;
    background: #f8fafc;
    font-style: italic;
    color: #718096;
    border-radius: 6px;
    border: 2px dashed #cbd5e1;
}

/* Footer */
.footer {
    margin-top: 30px;
    text-align: center;
    font-size: 9px;
    padding-top: 15px;
    border-top: 2px solid #0E4A86;
    color: #64748b;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 8px;
    padding: 15px;
}
.footer strong {
    color: #0E4A86;
}
</style>

<div class="header">
  <h1>' . $titulo_reporte . '</h1>
  <div class="header-info">
    <strong>Formación:</strong> ' . htmlspecialchars($info_ficha['formacion'] ?? 'N/A') . ' |
    <strong>Trimestre:</strong> ' . htmlspecialchars($info_ficha['trimestre'] ?? 'N/A') . ' |
    <strong>Generado:</strong> ' . date('d/m/Y H:i:s') . '
  </div>
</div>';

if (!empty($estados_incluidos) || $fecha_desde || $fecha_hasta || $materia_filtro) {
    $html .= '<div class="filters-info">
      <strong>Filtros Aplicados:</strong> ';

    if (!empty($estados_incluidos)) {
        $html .= '<strong>Estados:</strong> ' . implode(', ', $estados_incluidos);
    }

    if ($fecha_desde || $fecha_hasta) {
        $html .= ' | <strong>Fechas:</strong> ';
        if ($fecha_desde) $html .= 'Desde ' . date('d/m/Y', strtotime($fecha_desde));
        if ($fecha_desde && $fecha_hasta) $html .= ' - ';
        if ($fecha_hasta) $html .= 'Hasta ' . date('d/m/Y', strtotime($fecha_hasta));
    }

    if ($materia_filtro) {
        $stmt_materia = $conex->prepare("SELECT materia FROM materias WHERE id_materia = :id");
        $stmt_materia->execute(['id' => $materia_filtro]);
        $materia = $stmt_materia->fetchColumn();
        $html .= ' | <strong>Materia:</strong> ' . htmlspecialchars($materia);
    }

    $html .= '</div>';
}

// Estadísticas
$total_actividades = 0;
$actividades_por_estado = array_fill_keys($estados, 0);

if ($tipo_reporte !== 'resumen') {
    foreach ($aprendices as $aprendiz) {
        $sql_count = "
            SELECT au.id_estado_actividad, COUNT(*) as total
            FROM actividades_user au
            INNER JOIN actividades a ON au.id_actividad = a.id_actividad
            INNER JOIN materia_ficha mf ON a.id_materia_ficha = mf.id_materia_ficha
            WHERE au.id_user = :id_user AND mf.id_ficha = :id_ficha
            AND au.id_estado_actividad IN (" . implode(',', $estados) . ")
        ";

        $params = ['id_user' => $aprendiz['documento'], 'id_ficha' => $id_ficha];

        if ($fecha_desde) {
            $sql_count .= " AND a.fecha_entrega >= :fecha_desde";
            $params['fecha_desde'] = $fecha_desde;
        }

        if ($fecha_hasta) {
            $sql_count .= " AND a.fecha_entrega <= :fecha_hasta";
            $params['fecha_hasta'] = $fecha_hasta;
        }

        if ($materia_filtro) {
            $sql_count .= " AND mf.id_materia = :materia_filtro";
            $params['materia_filtro'] = $materia_filtro;
        }

        $sql_count .= " GROUP BY au.id_estado_actividad";

        $stmt_count = $conex->prepare($sql_count);
        $stmt_count->execute($params);
        $res = $stmt_count->fetchAll(PDO::FETCH_ASSOC);

        foreach ($res as $r) {
            $actividades_por_estado[$r['id_estado_actividad']] += $r['total'];
            $total_actividades += $r['total'];
        }
    }
}

$html .= '<div class="stats">
<div class="stat-item"><strong>' . $num_aprendices . '</strong><br>Aprendices</div>';

if ($tipo_reporte !== 'resumen') {
    $html .= '<div class="stat-item"><strong>' . $total_actividades . '</strong><br>Total Actividades</div>';
    foreach ($estados_incluidos as $id_estado => $nombre_estado) {
        $html .= '<div class="stat-item"><strong>' . $actividades_por_estado[$id_estado] . '</strong><br>' . $nombre_estado . '</div>';
    }
}

$html .= '</div>';

// Tabla de aprendices
$html .= '<table><thead><tr>
<th style="width: 20%">Nombres</th>
<th style="width: 20%">Apellidos</th>
<th style="width: 15%">Documento</th>
<th style="width: 15%">Estado</th>';

if ($tipo_reporte !== 'resumen') $html .= '<th style="width: 30%">Actividades</th>';

$html .= '</tr></thead><tbody>';

foreach ($aprendices as $a) {
    $id_user = $a['documento'];
    $actividadText = '';

    if ($tipo_reporte !== 'resumen') {
        $sql_actividades = "
            SELECT m.materia, a.titulo, e.estado AS estado_actividad, a.fecha_entrega
            FROM actividades_user au
            INNER JOIN actividades a ON au.id_actividad = a.id_actividad
            INNER JOIN materia_ficha mf ON a.id_materia_ficha = mf.id_materia_ficha
            INNER JOIN materias m ON mf.id_materia = m.id_materia
            INNER JOIN estado e ON au.id_estado_actividad = e.id_estado
            WHERE au.id_user = :id_user AND mf.id_ficha = :id_ficha
            AND au.id_estado_actividad IN (" . implode(',', $estados) . ")
        ";

        $params = ['id_user' => $id_user, 'id_ficha' => $id_ficha];

        if ($fecha_desde) {
            $sql_actividades .= " AND a.fecha_entrega >= :fecha_desde";
            $params['fecha_desde'] = $fecha_desde;
        }

        if ($fecha_hasta) {
            $sql_actividades .= " AND a.fecha_entrega <= :fecha_hasta";
            $params['fecha_hasta'] = $fecha_hasta;
        }

        if ($materia_filtro) {
            $sql_actividades .= " AND mf.id_materia = :materia_filtro";
            $params['materia_filtro'] = $materia_filtro;
        }

        if (!$es_gerente) {
            $sql_actividades .= " AND mf.id_instructor = :id_instructor";
            $params['id_instructor'] = $id_instructor;
        }

        $stmt_act = $conex->prepare($sql_actividades);
        $stmt_act->execute($params);
        $acts = $stmt_act->fetchAll(PDO::FETCH_ASSOC);

        $agrupadas = [];
        foreach ($acts as $act) $agrupadas[$act['materia']][] = $act;

        if ($agrupadas) {
            foreach ($agrupadas as $materia => $lista) {
                $actividadText .= '<div class="actividad-materia">• ' . htmlspecialchars($materia) . '</div>';
                foreach ($lista as $act) {
                    $clase = match ($act['estado_actividad']) {
                        'Pendiente' => 'estado-pendiente',
                        'Aprobado' => 'estado-aprobado',
                        'Desaprobado' => 'estado-desaprobado',
                        'Entregado' => 'estado-entregado',
                        'No entregado' => 'estado-no-entregado',
                        default => ''
                    };

                    $actividadText .= '<div class="actividad-item">- ' . htmlspecialchars($act['titulo']) .
                        ' <small>(' . date('d/m/Y', strtotime($act['fecha_entrega'])) . ')</small>' .
                        ' <span class="estado-badge ' . $clase . '">' . htmlspecialchars($act['estado_actividad']) . '</span></div>';
                }
            }
        } else {
            $actividadText = '<div class="no-activities">Sin actividades en los filtros seleccionados</div>';
        }
    }

    $html .= '<tr>
        <td>' . htmlspecialchars($a['nombres']) . '</td>
        <td>' . htmlspecialchars($a['apellidos']) . '</td>
        <td>' . htmlspecialchars($a['documento']) . '</td>
        <td>' . htmlspecialchars($a['estado']) . '</td>';

    if ($tipo_reporte !== 'resumen') $html .= '<td>' . $actividadText . '</td>';

    $html .= '</tr>';
}

$html .= '</tbody></table>';

$html .= '<div class="footer">
<p><strong>Reporte generado por TeamTalks - Sistema de Gestión Académica</strong></p>
<p>Instructor: ' . htmlspecialchars($_SESSION['nombres'] ?? '') . ' ' . htmlspecialchars($_SESSION['apellidos'] ?? '') . '</p>
<p>Fecha de generación: ' . date('d/m/Y H:i:s') . '</p>
</div>';

// Generar PDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'Arial');
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$filename = "Reporte_Ficha_{$id_ficha}_" . date('Y-m-d_H-i-s') . ".pdf";
$dompdf->stream($filename, ['Attachment' => true]);
exit;

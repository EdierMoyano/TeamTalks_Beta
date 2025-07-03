<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';
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

if (empty($estados)) {
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

switch($orden) {
    case 'nombres': $sql .= " ORDER BY u.nombres ASC"; break;
    case 'documento': $sql .= " ORDER BY u.id ASC"; break;
    default: $sql .= " ORDER BY u.apellidos ASC"; break;
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
    3 => 'En Proceso',
    4 => 'Completada',
    8 => 'Retrasada',
    9 => 'Pendiente',
    10 => 'Cancelada'
];

$estados_incluidos = array_intersect_key($estados_nombres, array_flip($estados));

// Título
$titulo_reporte = "Reporte de Ficha N° " . htmlspecialchars($info_ficha['id_ficha'] ?? 'N/A');
switch($tipo_reporte) {
    case 'solo_pendientes': $titulo_reporte .= " - Solo Pendientes"; break;
    case 'por_estado': $titulo_reporte .= " - Por Estado"; break;
    case 'resumen': $titulo_reporte .= " - Resumen Ejecutivo"; break;
}

// HTML inicial
$html = '
<style>
/* estilos resumidos para espacio */
body { font-family: Arial; font-size: 11px; padding: 20px; }
.header { text-align: center; border-bottom: 2px solid #667eea; margin-bottom: 20px; }
.header h1 { color: #667eea; font-size: 18px; margin: 0 0 10px 0; }
.filters-info { background: #f9f9f9; padding: 10px; border-left: 4px solid #667eea; font-size: 10px; margin-bottom: 10px; }
.stats { display: flex; justify-content: space-around; margin-bottom: 10px; font-size: 10px; }
.stat-item { text-align: center; }
table { border-collapse: collapse; width: 100%; font-size: 9px; margin-top: 10px; }
th, td { border: 1px solid #ccc; padding: 6px; vertical-align: top; }
th { background-color: #667eea; color: white; }
.actividad-materia { font-weight: bold; color: #667eea; margin-top: 5px; }
.actividad-item { margin-left: 10px; font-size: 8px; }
.estado-badge { padding: 2px 6px; border-radius: 3px; font-size: 8px; font-weight: bold; }
.estado-pendiente { background: #fff3cd; color: #856404; }
.estado-proceso { background: #d1ecf1; color: #0c5460; }
.estado-completada { background: #d4edda; color: #155724; }
.estado-retrasada { background: #f8d7da; color: #721c24; }
.estado-cancelada { background: #e2e3e5; color: #383d41; }
.footer { text-align: center; font-size: 8px; margin-top: 30px; border-top: 1px solid #ddd; padding-top: 10px; color: #666; }
</style>

<div class="header">
  <h1>' . $titulo_reporte . '</h1>
  <div>
    <strong>Formación:</strong> ' . htmlspecialchars($info_ficha['formacion'] ?? 'N/A') . ' |
    <strong>Trimestre:</strong> ' . htmlspecialchars($info_ficha['trimestre'] ?? 'N/A') . ' |
    <strong>Generado:</strong> ' . date('d/m/Y H:i:s') . '
  </div>
</div>

<div class="filters-info">
  <strong>Estados:</strong> ' . implode(', ', $estados_incluidos);

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

// Estadísticas
$total_actividades = 0;
$actividades_por_estado = array_fill_keys($estados, 0);

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

$html .= '<div class="stats">
<div class="stat-item"><strong>' . $num_aprendices . '</strong><br>Aprendices</div>
<div class="stat-item"><strong>' . $total_actividades . '</strong><br>Total Actividades</div>';

foreach ($estados_incluidos as $id_estado => $nombre_estado) {
    $html .= '<div class="stat-item"><strong>' . $actividades_por_estado[$id_estado] . '</strong><br>' . $nombre_estado . '</div>';
}
$html .= '</div>';

// Tabla de aprendices
$html .= '<table><thead><tr>
<th>Nombres</th><th>Apellidos</th><th>Documento</th><th>Estado</th>';
if ($tipo_reporte !== 'resumen') $html .= '<th>Actividades</th>';
$html .= '</tr></thead><tbody>';

foreach ($aprendices as $a) {
    $id_user = $a['documento'];
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
    if ($fecha_desde) { $sql_actividades .= " AND a.fecha_entrega >= :fecha_desde"; $params['fecha_desde'] = $fecha_desde; }
    if ($fecha_hasta) { $sql_actividades .= " AND a.fecha_entrega <= :fecha_hasta"; $params['fecha_hasta'] = $fecha_hasta; }
    if ($materia_filtro) { $sql_actividades .= " AND mf.id_materia = :materia_filtro"; $params['materia_filtro'] = $materia_filtro; }
    if (!$es_gerente) { $sql_actividades .= " AND mf.id_instructor = :id_instructor"; $params['id_instructor'] = $id_instructor; }

    $stmt_act = $conex->prepare($sql_actividades);
    $stmt_act->execute($params);
    $acts = $stmt_act->fetchAll(PDO::FETCH_ASSOC);

    $agrupadas = [];
    foreach ($acts as $act) $agrupadas[$act['materia']][] = $act;

    $actividadText = '';
    if ($tipo_reporte !== 'resumen') {
        if ($agrupadas) {
            foreach ($agrupadas as $materia => $lista) {
                $actividadText .= '<div class="actividad-materia">• ' . htmlspecialchars($materia) . '</div>';
                foreach ($lista as $act) {
                    $clase = match($act['estado_actividad']) {
                        'Pendiente' => 'estado-pendiente',
                        'En Proceso' => 'estado-proceso',
                        'Completada' => 'estado-completada',
                        'Retrasada' => 'estado-retrasada',
                        'Cancelada' => 'estado-cancelada',
                        default => ''
                    };
                    $actividadText .= '<div class="actividad-item">- ' . htmlspecialchars($act['titulo']) .
                        ' <small>(' . date('d/m/Y', strtotime($act['fecha_entrega'])) . ')</small>' .
                        ' <span class="estado-badge ' . $clase . '">' . htmlspecialchars($act['estado_actividad']) . '</span></div>';
                }
            }
        } else {
            $actividadText = '<em>Sin actividades en los filtros seleccionados</em>';
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
<p>Reporte generado por TeamTalks - Sistema de Gestión Académica</p>
<p>Instructor: ' . htmlspecialchars($_SESSION['nombres'] ?? '') . ' ' . htmlspecialchars($_SESSION['apellidos'] ?? '') . '</p>
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
?>

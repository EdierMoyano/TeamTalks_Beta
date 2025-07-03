<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';
require_once __DIR__ . '/../vendor/autoload.php';
include 'session.php';

if ($_SESSION['rol'] !== 3 && $_SESSION['rol'] !== 5) {
    header('Location:' . BASE_URL . '/includes/exit.php?motivo=acceso-denegado');
    exit;
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

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

// Información de la ficha
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

$estados_nombres = [
    3 => 'En Proceso',
    4 => 'Completada',
    8 => 'Retrasada',
    9 => 'Pendiente',
    10 => 'Cancelada'
];

$estados_incluidos = array_intersect_key($estados_nombres, array_flip($estados));

// Crear Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Configurar propiedades del documento
$spreadsheet->getProperties()
    ->setCreator("TeamTalks")
    ->setTitle("Reporte Ficha " . $id_ficha)
    ->setSubject("Reporte de Aprendices")
    ->setDescription("Reporte generado desde TeamTalks");

// HEADER PRINCIPAL - Ajustar al número de columnas de la tabla
$num_columnas = ($tipo_reporte !== 'resumen') ? 6 : 5;
$end_col_letter = chr(64 + $num_columnas); // F o E

$titulo_reporte = "Reporte de Ficha N° " . $info_ficha['id_ficha'];
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

$sheet->setCellValue('A1', $titulo_reporte);
$sheet->mergeCells("A1:{$end_col_letter}1");
$sheet->getStyle("A1:{$end_col_letter}1")->applyFromArray([
    'font' => [
        'bold' => true,
        'size' => 18,
        'color' => ['rgb' => 'FFFFFF']
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '667eea']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_MEDIUM,
            'color' => ['rgb' => '667eea']
        ]
    ]
]);
$sheet->getRowDimension(1)->setRowHeight(30);

// INFORMACIÓN DE LA FICHA - Ajustada al ancho de la tabla
$row = 3;
$sheet->setCellValue("A$row", "Formación:");
$sheet->setCellValue("B$row", $info_ficha['formacion'] ?? 'N/A');
$sheet->mergeCells("B$row:C$row");

$col_trimestre = ($num_columnas >= 5) ? 'D' : 'D';
$sheet->setCellValue("{$col_trimestre}$row", "Trimestre:");
$sheet->setCellValue("{$end_col_letter}$row", $info_ficha['trimestre'] ?? 'N/A');

$row++;
$sheet->setCellValue("A$row", "Generado:");
$sheet->setCellValue("B$row", date('d/m/Y H:i:s'));
$sheet->mergeCells("B$row:{$end_col_letter}$row");

// Estilo para información de ficha
$sheet->getStyle("A3:{$end_col_letter}4")->applyFromArray([
    'font' => ['bold' => true, 'size' => 11],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'F8F9FA']
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'DEE2E6']
        ]
    ]
]);

// FILTROS APLICADOS - Ajustados al ancho de la tabla
$row = 6;
$sheet->setCellValue("A$row", "Filtros Aplicados:");
$sheet->mergeCells("A$row:{$end_col_letter}$row");
$sheet->getStyle("A$row:{$end_col_letter}$row")->applyFromArray([
    'font' => ['bold' => true, 'size' => 12],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'E3F2FD']
    ]
]);

$row++;
$filtros = 'Estados: ' . implode(', ', $estados_incluidos);

if ($fecha_desde || $fecha_hasta) {
    $filtros .= ' | Fechas: ';
    if ($fecha_desde) $filtros .= 'Desde ' . date('d/m/Y', strtotime($fecha_desde));
    if ($fecha_desde && $fecha_hasta) $filtros .= ' - ';
    if ($fecha_hasta) $filtros .= 'Hasta ' . date('d/m/Y', strtotime($fecha_hasta));
}

if ($materia_filtro) {
    $stmt_materia = $conex->prepare("SELECT materia FROM materias WHERE id_materia = :id");
    $stmt_materia->execute(['id' => $materia_filtro]);
    $materia = $stmt_materia->fetchColumn();
    $filtros .= ' | Materia: ' . $materia;
}

$sheet->setCellValue("A$row", $filtros);
$sheet->mergeCells("A$row:{$end_col_letter}$row");
$sheet->getStyle("A$row:{$end_col_letter}$row")->applyFromArray([
    'font' => ['size' => 10],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'F9F9F9']
    ],
    'borders' => [
        'outline' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '667eea']
        ]
    ],
    'alignment' => ['wrapText' => true]
]);

// ESTADÍSTICAS - Organizadas en el ancho de la tabla
$row += 2;
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

// Título de estadísticas
$sheet->setCellValue("A$row", "Estadísticas del Reporte");
$sheet->mergeCells("A$row:{$end_col_letter}$row");
$sheet->getStyle("A$row:{$end_col_letter}$row")->applyFromArray([
    'font' => ['bold' => true, 'size' => 12],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'E8F5E8']
    ],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
]);

$row++;

// Primera fila de estadísticas
$sheet->setCellValue("A$row", "Total Aprendices:");
$sheet->setCellValue("B$row", count($aprendices));
$sheet->setCellValue("C$row", "Total Actividades:");
$sheet->setCellValue("D$row", $total_actividades);

// Si hay espacio, agregar más estadísticas en la misma fila
if ($num_columnas >= 6) {
    $sheet->setCellValue("E$row", "Fecha Reporte:");
    $sheet->setCellValue("F$row", date('d/m/Y'));
}

$row++;

// Segunda fila - Estados de actividades (distribuidos en las columnas disponibles)
$estados_texto = "";
$col_actual = 1;
foreach ($estados_incluidos as $id_estado => $nombre_estado) {
    if ($col_actual <= $num_columnas && $col_actual % 2 == 1) {
        $colLetter = chr(64 + $col_actual);
        $sheet->setCellValue("{$colLetter}$row", $nombre_estado . ":");
        if ($col_actual + 1 <= $num_columnas) {
            $colLetter2 = chr(64 + $col_actual + 1);
            $sheet->setCellValue("{$colLetter2}$row", $actividades_por_estado[$id_estado]);
        }
        $col_actual += 2;
    } else {
        // Si no cabe en la fila actual, crear nueva fila
        if ($col_actual > $num_columnas) {
            $row++;
            $col_actual = 1;
            $colLetter = chr(64 + $col_actual);
            $sheet->setCellValue("{$colLetter}$row", $nombre_estado . ":");
            $colLetter2 = chr(64 + $col_actual + 1);
            $sheet->setCellValue("{$colLetter2}$row", $actividades_por_estado[$id_estado]);
            $col_actual += 2;
        }
    }
}

// Estilo para toda la sección de estadísticas
$stats_start_row = $row - (count($estados_incluidos) > 2 ? 2 : 1);
$sheet->getStyle("A$stats_start_row:{$end_col_letter}$row")->applyFromArray([
    'font' => ['bold' => true, 'size' => 10],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'E8F5E8']
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '4CAF50']
        ]
    ],
    'alignment' => [
        'vertical' => Alignment::VERTICAL_CENTER
    ]
]);

// TABLA DE DATOS
$row += 4;
$table_start_row = $row;

// Encabezados de la tabla
$headers = ['Nombres', 'Apellidos', 'Documento', 'Estado', 'Trimestre'];
if ($tipo_reporte !== 'resumen') {
    $headers[] = 'Actividades Pendientes';
}

// Encabezados de la tabla
foreach ($headers as $i => $header) {
    $colLetter = chr(65 + $i);
    $sheet->setCellValue("{$colLetter}$row", $header);
}

// Estilo para encabezados - usar el ancho correcto
$sheet->getStyle("A$row:{$end_col_letter}$row")->applyFromArray([
    'font' => [
        'bold' => true,
        'size' => 11,
        'color' => ['rgb' => 'FFFFFF']
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '667eea']
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_MEDIUM,
            'color' => ['rgb' => '667eea']
        ]
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ]
]);

$row++;

// Datos de aprendices
foreach ($aprendices as $a) {
    $id_user = $a['documento'];

    // Obtener actividades agrupadas por materia (como en el PDF)
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

    // Agrupar actividades por materia (como en el PDF)
    $agrupadas = [];
    foreach ($acts as $act) {
        $agrupadas[$act['materia']][] = $act;
    }

    // Formato de actividades agrupadas
    $pendientesText = '';
    if ($tipo_reporte !== 'resumen') {
        if (count($agrupadas)) {
            foreach ($agrupadas as $materia => $actividades) {
                $pendientesText .= "• " . $materia . "\n";
                foreach ($actividades as $act) {
                    $fecha_formateada = date('d/m/Y', strtotime($act['fecha_entrega']));
                    $pendientesText .= "    - " . $act['titulo'] . " (" . $fecha_formateada . ") [" . $act['estado_actividad'] . "]\n";
                }
                $pendientesText .= "\n";
            }
        } else {
            $pendientesText = 'Todo al día';
        }
    }

    // Insertar datos en la fila
    $sheet->setCellValue("A$row", $a['nombres']);
    $sheet->setCellValue("B$row", $a['apellidos']);
    $sheet->setCellValue("C$row", $a['documento']);
    $sheet->setCellValue("D$row", $a['estado']);
    $sheet->setCellValue("E$row", $a['trimestre']);

    if ($tipo_reporte !== 'resumen') {
        $sheet->setCellValue("F$row", $pendientesText);
        $sheet->getStyle("F$row")->getAlignment()->setWrapText(true);
        $sheet->getStyle("F$row")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
    }

    // Estilo para filas de datos (alternando colores) - usar el ancho correcto
    $bgColor = ($row % 2 == 0) ? 'F8F9FA' : 'FFFFFF';

    $sheet->getStyle("A$row:{$end_col_letter}$row")->applyFromArray([
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => $bgColor]
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'DEE2E6']
            ]
        ],
        'alignment' => [
            'vertical' => Alignment::VERTICAL_TOP
        ]
    ]);

    // Ajustar altura de fila para actividades
    if ($tipo_reporte !== 'resumen' && !empty($agrupadas)) {
        $sheet->getRowDimension($row)->setRowHeight(-1);
    }

    $row++;
}

// FOOTER - Ajustado al ancho de la tabla
$row += 2;
$sheet->setCellValue("A$row", "Reporte generado por TeamTalks - Sistema de Gestión Académica");
$sheet->mergeCells("A$row:{$end_col_letter}$row");
$sheet->getStyle("A$row:{$end_col_letter}$row")->applyFromArray([
    'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '6C757D']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => [
        'top' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'DEE2E6']
        ]
    ]
]);

$row++;
$instructor_nombre = ($_SESSION['nombres'] ?? '') . ' ' . ($_SESSION['apellidos'] ?? '');
$sheet->setCellValue("A$row", "Instructor: $instructor_nombre");
$sheet->mergeCells("A$row:{$end_col_letter}$row");
$sheet->getStyle("A$row:{$end_col_letter}$row")->applyFromArray([
    'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '6C757D']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
]);

// AJUSTAR ANCHOS DE COLUMNAS - Solo las columnas que se usan
$sheet->getColumnDimension('A')->setWidth(18); // Nombres
$sheet->getColumnDimension('B')->setWidth(18); // Apellidos
$sheet->getColumnDimension('C')->setWidth(15); // Documento
$sheet->getColumnDimension('D')->setWidth(15); // Estado
$sheet->getColumnDimension('E')->setWidth(12); // Trimestre
if ($tipo_reporte !== 'resumen') {
    $sheet->getColumnDimension('F')->setWidth(60); // Actividades
}

// Ocultar columnas no utilizadas si las hay
for ($col = $num_columnas + 1; $col <= 10; $col++) {
    $colLetter = chr(64 + $col);
    $sheet->getColumnDimension($colLetter)->setVisible(false);
}

// CONFIGURACIÓN DE PÁGINA
$sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
$sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
$sheet->getPageMargins()->setTop(0.75);
$sheet->getPageMargins()->setRight(0.25);
$sheet->getPageMargins()->setLeft(0.25);
$sheet->getPageMargins()->setBottom(0.75);

// Configurar encabezado y pie de página para impresión
$sheet->getHeaderFooter()->setOddHeader('&C&B' . $titulo_reporte);
$sheet->getHeaderFooter()->setOddFooter('&L&D &T&C&BTeamTalks&R&P de &N');

// Generar nombre de archivo
$tipo_suffix = '';
switch ($tipo_reporte) {
    case 'solo_pendientes':
        $tipo_suffix = '_Pendientes';
        break;
    case 'por_estado':
        $tipo_suffix = '_PorEstado';
        break;
    case 'resumen':
        $tipo_suffix = '_Resumen';
        break;
}

$filename = "Reporte_Ficha_{$id_ficha}{$tipo_suffix}_" . date('Y-m-d_H-i-s') . ".xlsx";

// Enviar archivo al navegador
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');
header('Cache-Control: max-age=1');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: cache, must-revalidate');
header('Pragma: public');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;

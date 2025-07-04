<?php

if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['DOCUMENT_ROOT'], 'htdocs') !== false) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php';
} else {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';
}

require_once __DIR__ . '/../vendor/autoload.php';
include 'session.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

if ($_SESSION['rol'] !== 3 && $_SESSION['rol'] !== 5) {
    header('Location:' . BASE_URL . '/includes/exit.php?motivo=acceso-denegado');
    exit;
}

$id_ficha = $_POST['id_ficha'] ?? 0;
$id_instructor = $_SESSION['documento'] ?? 0;
$fecha_desde = $_POST['fecha_desde'] ?? '';
$fecha_hasta = $_POST['fecha_hasta'] ?? '';
$estados = $_POST['estados'] ?? [];
$tipo_reporte = $_POST['tipo_reporte'] ?? 'completo';
$materia_filtro = $_POST['materia_filtro'] ?? '';
$orden = $_POST['orden'] ?? 'apellidos';

$orden_valido = ['apellidos', 'nombres', 'id'];
$orden = in_array($orden, $orden_valido) ? $orden : 'apellidos';

if (!$id_ficha || !$id_instructor) die("Falta información para generar el reporte.");

if (empty($estados) && $tipo_reporte !== 'resumen') {
    die("Debes seleccionar al menos un estado de actividad.");
}

// Verificar si es gerente
$sql_gerente = "SELECT COUNT(*) FROM fichas WHERE id_ficha = :id_ficha AND id_instructor = :id_instructor";
$stmt_gerente = $conex->prepare($sql_gerente);
$stmt_gerente->execute(['id_ficha' => $id_ficha, 'id_instructor' => $id_instructor]);
$es_gerente = $stmt_gerente->fetchColumn() > 0;

// Si no es gerente y no hay materia, obtenerla automáticamente
if (!$es_gerente && !$materia_filtro) {
    $stmt_m = $conex->prepare("
        SELECT id_materia FROM materia_ficha 
        WHERE id_ficha = :id_ficha AND id_instructor = :id_instructor LIMIT 1
    ");
    $stmt_m->execute([
        'id_ficha' => $id_ficha,
        'id_instructor' => $id_instructor
    ]);
    $materia_filtro = $stmt_m->fetchColumn();
}

// Estados válidos
$estados_nombres = [
    3 => 'Aprobado',
    4 => 'Desaprobado',
    8 => 'Entregado',
    9 => 'Pendiente',
    10 => 'No entregado'
];

$estados_incluidos = array_intersect_key($estados_nombres, array_flip($estados));

// Obtener datos de la ficha
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

// Obtener aprendices
if ($tipo_reporte === 'resumen') {
    $sql_aprendices = "
        SELECT u.id AS documento, u.nombres, u.apellidos, u.telefono, u.correo, u.fecha_registro, e.estado
        FROM user_ficha uf
        INNER JOIN usuarios u ON uf.id_user = u.id
        INNER JOIN estado e ON u.id_estado = e.id_estado
        WHERE uf.id_ficha = :id_ficha
        ORDER BY u.$orden ASC
    ";
} else {
    $sql_aprendices = "
        SELECT u.nombres, u.apellidos, u.id AS documento, e.estado, t.trimestre
        FROM user_ficha uf
        INNER JOIN usuarios u ON uf.id_user = u.id
        INNER JOIN estado e ON u.id_estado = e.id_estado
        INNER JOIN fichas f ON uf.id_ficha = f.id_ficha
        INNER JOIN trimestre t ON f.id_trimestre = t.id_trimestre
        WHERE uf.id_ficha = :id_ficha
        ORDER BY u.$orden ASC
    ";
}

$stmt = $conex->prepare($sql_aprendices);
$stmt->execute(['id_ficha' => $id_ficha]);
$aprendices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// INICIO DEL EXCEL
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

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

// Título principal con diseño mejorado
$sheet->setCellValue('A1', $titulo_reporte);
if ($tipo_reporte === 'resumen') {
    $sheet->mergeCells('A1:G1');
    $lastCol = 'G';
} else {
    $sheet->mergeCells('A1:F1');
    $lastCol = 'F';
}

$sheet->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0E4A86']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '0E4A86']]]
]);
$sheet->getRowDimension(1)->setRowHeight(35);

// Información de la ficha con mejor diseño
$row = 3;
$sheet->setCellValue('A' . $row, "INFORMACIÓN DE LA FICHA");
$sheet->mergeCells("A{$row}:{$lastCol}{$row}");
$sheet->getStyle("A{$row}")->applyFromArray([
    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '0E4A86']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F2FF']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1E7FF']]]
]);

$row++;
$sheet->setCellValue('A' . $row, "Formación:");
$sheet->setCellValue('B' . $row, $info_ficha['formacion'] ?? 'N/A');
$sheet->setCellValue('D' . $row, "Trimestre:");
$sheet->setCellValue('E' . $row, $info_ficha['trimestre'] ?? 'N/A');

$sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
    'font' => ['size' => 10],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E5E5']]]
]);

$row++;
$sheet->setCellValue('A' . $row, "Fecha de generación:");
$sheet->setCellValue('B' . $row, date('d/m/Y H:i:s'));
$sheet->setCellValue('D' . $row, "Total aprendices:");
$sheet->setCellValue('E' . $row, count($aprendices));

$sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
    'font' => ['size' => 10],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E5E5']]]
]);

// Filtros aplicados
$row += 2;
$sheet->setCellValue('A' . $row, "FILTROS APLICADOS");
$sheet->mergeCells("A{$row}:{$lastCol}{$row}");
$sheet->getStyle("A{$row}")->applyFromArray([
    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '0E4A86']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F8FF']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1E7FF']]]
]);

$row++;
$filtros_texto = '';
if (!empty($estados_incluidos)) {
    $filtros_texto = 'Estados: ' . implode(', ', $estados_incluidos);
}

if ($fecha_desde || $fecha_hasta) {
    if ($filtros_texto) $filtros_texto .= ' | ';
    $filtros_texto .= 'Fechas: ';
    if ($fecha_desde) $filtros_texto .= 'Desde ' . date('d/m/Y', strtotime($fecha_desde));
    if ($fecha_desde && $fecha_hasta) $filtros_texto .= ' hasta ';
    if ($fecha_hasta) $filtros_texto .= date('d/m/Y', strtotime($fecha_hasta));
}

if ($materia_filtro) {
    $stmt_materia = $conex->prepare("SELECT materia FROM materias WHERE id_materia = :id");
    $stmt_materia->execute(['id' => $materia_filtro]);
    $materia = $stmt_materia->fetchColumn();
    if ($filtros_texto) $filtros_texto .= ' | ';
    $filtros_texto .= 'Materia: ' . $materia;
}

if (!$filtros_texto) $filtros_texto = 'Sin filtros específicos aplicados';

$sheet->setCellValue('A' . $row, $filtros_texto);
$sheet->mergeCells("A{$row}:{$lastCol}{$row}");
$sheet->getStyle("A{$row}")->applyFromArray([
    'font' => ['size' => 9, 'italic' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'wrapText' => true],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E5E5']]]
]);

// Encabezados de la tabla
$row += 2;
if ($tipo_reporte === 'resumen') {
    $headers = ['Documento', 'Nombres', 'Apellidos', 'Teléfono', 'Correo', 'Estado', 'Fecha Registro'];
    $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
} else {
    $headers = ['Nombres', 'Apellidos', 'Documento', 'Estado', 'Trimestre', 'Actividades'];
    $columns = ['A', 'B', 'C', 'D', 'E', 'F'];
}

foreach ($headers as $i => $header) {
    $col = $columns[$i];
    $sheet->setCellValue($col . $row, $header);

    // Ajustar ancho de columnas
    if ($header === 'Actividades') {
        $sheet->getColumnDimension($col)->setWidth(50);
    } elseif ($header === 'Correo') {
        $sheet->getColumnDimension($col)->setWidth(25);
    } elseif (in_array($header, ['Nombres', 'Apellidos'])) {
        $sheet->getColumnDimension($col)->setWidth(20);
    } else {
        $sheet->getColumnDimension($col)->setWidth(15);
    }
}

$headerRange = 'A' . $row . ':' . end($columns) . $row;
$sheet->getStyle($headerRange)->applyFromArray([
    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0E4A86']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '0E4A86']]]
]);
$sheet->getRowDimension($row)->setRowHeight(25);

// Datos de los aprendices
$row++;
$dataStartRow = $row;

foreach ($aprendices as $aprendiz) {
    if ($tipo_reporte === 'resumen') {
        $sheet->setCellValue('A' . $row, $aprendiz['documento']);
        $sheet->setCellValue('B' . $row, $aprendiz['nombres']);
        $sheet->setCellValue('C' . $row, $aprendiz['apellidos']);
        $sheet->setCellValue('D' . $row, $aprendiz['telefono']);
        $sheet->setCellValue('E' . $row, $aprendiz['correo']);
        $sheet->setCellValue('F' . $row, $aprendiz['estado']);
        $sheet->setCellValue('G' . $row, date('d/m/Y', strtotime($aprendiz['fecha_registro'])));
    } else {
        $sheet->setCellValue('A' . $row, $aprendiz['nombres']);
        $sheet->setCellValue('B' . $row, $aprendiz['apellidos']);
        $sheet->setCellValue('C' . $row, $aprendiz['documento']);
        $sheet->setCellValue('D' . $row, $aprendiz['estado']);
        $sheet->setCellValue('E' . $row, $aprendiz['trimestre']);

        // Obtener actividades del aprendiz
        $sql_act = "
            SELECT a.titulo, a.fecha_entrega, e.estado AS estado_actividad, m.materia
            FROM actividades_user au
            INNER JOIN actividades a ON au.id_actividad = a.id_actividad
            INNER JOIN materia_ficha mf ON a.id_materia_ficha = mf.id_materia_ficha
            INNER JOIN materias m ON mf.id_materia = m.id_materia
            INNER JOIN estado e ON au.id_estado_actividad = e.id_estado
            WHERE au.id_user = :id_user AND mf.id_ficha = :id_ficha
              AND au.id_estado_actividad IN (" . implode(',', $estados) . ")
        ";

        $params = ['id_user' => $aprendiz['documento'], 'id_ficha' => $id_ficha];

        if ($fecha_desde) {
            $sql_act .= " AND a.fecha_entrega >= :fecha_desde";
            $params['fecha_desde'] = $fecha_desde;
        }
        if ($fecha_hasta) {
            $sql_act .= " AND a.fecha_entrega <= :fecha_hasta";
            $params['fecha_hasta'] = $fecha_hasta;
        }
        if ($materia_filtro) {
            $sql_act .= " AND mf.id_materia = :materia_filtro";
            $params['materia_filtro'] = $materia_filtro;
        }

        $stmt_act = $conex->prepare($sql_act);
        $stmt_act->execute($params);
        $acts = $stmt_act->fetchAll(PDO::FETCH_ASSOC);

        $detalle_actividades = '';
        if ($acts) {
            foreach ($acts as $act) {
                $detalle_actividades .= "• {$act['materia']}: {$act['titulo']}\n";
                $detalle_actividades .= "  Estado: {$act['estado_actividad']} | Fecha: " . date('d/m/Y', strtotime($act['fecha_entrega'])) . "\n\n";
            }
        } else {
            $detalle_actividades = 'Sin actividades registradas con los filtros aplicados';
        }

        $sheet->setCellValue('F' . $row, trim($detalle_actividades));
        $sheet->getStyle('F' . $row)->getAlignment()->setWrapText(true);
    }

    // Aplicar estilos a las filas de datos
    $rowRange = 'A' . $row . ':' . end($columns) . $row;
    $sheet->getStyle($rowRange)->applyFromArray([
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E5E5']]],
        'alignment' => ['vertical' => Alignment::VERTICAL_TOP]
    ]);

    // Alternar colores de fila
    if (($row - $dataStartRow) % 2 == 1) {
        $sheet->getStyle($rowRange)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FBFF']]
        ]);
    }

    $sheet->getRowDimension($row)->setRowHeight(30);
    $row++;
}

// Footer
$row += 2;
$sheet->setCellValue('A' . $row, "Reporte generado por TeamTalks - Sistema de Gestión Académica");
$sheet->mergeCells("A{$row}:{$lastCol}{$row}");
$sheet->getStyle("A{$row}")->applyFromArray([
    'font' => ['size' => 9, 'italic' => true, 'color' => ['rgb' => '666666']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => ['top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]]
]);

// Configurar página para impresión
$sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
$sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
$sheet->getPageMargins()->setTop(0.75);
$sheet->getPageMargins()->setRight(0.25);
$sheet->getPageMargins()->setLeft(0.25);
$sheet->getPageMargins()->setBottom(0.75);

// Descarga
$filename = "Reporte_Ficha_{$id_ficha}_" . date('Y-m-d_H-i-s') . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;

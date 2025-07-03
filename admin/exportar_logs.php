<?php
session_start();
if (!isset($_SESSION['documento']) || $_SESSION['rol'] !== 2) {
    die("Acceso denegado");
}

require_once '../conexion/conexion.php';

// PhpSpreadsheet
require '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

$db = new Database();
$conexion = $db->connect();

$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin    = $_GET['fecha_fin'] ?? '';
if (!$fecha_inicio || !$fecha_fin) {
    die("Debes seleccionar ambas fechas.");
}

// Añade día completo para la fecha fin
$fecha_inicio .= ' 00:00:00';
$fecha_fin    .= ' 23:59:59';

// Consulta logs
$stmt = $conexion->prepare("
    SELECT logs.*, u.nombres, u.apellidos, u.id AS usuario_id, r.rol AS nombre_rol
    FROM logs_acciones logs
    LEFT JOIN usuarios u ON logs.usuario_accion = u.id
    LEFT JOIN roles r ON u.id_rol = r.id_rol
    WHERE logs.fecha BETWEEN ? AND ?
    ORDER BY logs.fecha DESC
");
$stmt->execute([$fecha_inicio, $fecha_fin]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Crear Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Logs');

// Encabezado grande
$sheet->mergeCells('A1:F1');
$sheet->setCellValue('A1', 'REPORTE DE LOGS DEL SISTEMA');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setARGB('FFFFFFFF');
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0e4a86');

// Fecha de generación
$sheet->mergeCells('A2:F2');
$sheet->setCellValue('A2', 'Generado el: ' . date('d/m/Y H:i:s'));
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A2')->getFont()->setItalic(true);

// Cabecera
$cabecera = ['Fecha', 'Usuario (ID)', 'Rol', 'Acción', 'Entidad', 'Descripción'];
$sheet->fromArray($cabecera, NULL, 'A4');
$sheet->getStyle('A4:F4')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
$sheet->getStyle('A4:F4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A4:F4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0e4a86');

// Datos
$fila = 5;
foreach ($logs as $log) {
    $sheet->setCellValue("A{$fila}", date('Y-m-d H:i:s', strtotime($log['fecha'])));
    $sheet->setCellValue("B{$fila}", "{$log['nombres']} {$log['apellidos']} ({$log['usuario_id']})");
    $sheet->setCellValue("C{$fila}", $log['nombre_rol']);
    $sheet->setCellValue("D{$fila}", $log['accion']);
    $sheet->setCellValue("E{$fila}", $log['entidad']);
    $sheet->setCellValue("F{$fila}", $log['descripcion']);
    $fila++;
}

// Estilo para las filas de datos
$max_col = 'F';
$last_row = $fila - 1;
$sheet->getStyle("A4:{$max_col}{$last_row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle("A5:A{$last_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle("B5:B{$last_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
$sheet->getStyle("C5:C{$last_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle("D5:D{$last_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle("E5:E{$last_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle("F5:F{$last_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

// Autoajustar columnas
foreach (range('A', $max_col) as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Descargar
$filename = 'reporte_logs_' . date('Y-m-d_H-i-s') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment;filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>

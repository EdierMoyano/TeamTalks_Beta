<?php
session_start();

// Verificar sesión y rol de administrador
if (!isset($_SESSION['documento']) || $_SESSION['rol'] !== 2) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

require '../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

require_once '../../../conexion/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$db = new Database();
$conexion = $db->connect();

if (!$conexion || !($conexion instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit;
}

$id_instructor = $_POST['id_instructor'] ?? '';
$id_trimestre = $_POST['id_trimestre'] ?? '';

if (empty($id_instructor) || !is_numeric($id_instructor)) {
    echo json_encode(['success' => false, 'message' => 'ID de instructor inválido']);
    exit;
}

if (empty($id_trimestre) || !is_numeric($id_trimestre)) {
    echo json_encode(['success' => false, 'message' => 'ID de trimestre inválido']);
    exit;
}

try {
    // Obtener datos del instructor
    $stmt = $conexion->prepare("
        SELECT nombres, apellidos
        FROM usuarios 
        WHERE id = ? AND id_rol IN (3, 5)
    ");
    $stmt->execute([$id_instructor]);
    $instructor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$instructor) {
        throw new Exception('Instructor no encontrado');
    }

    // Obtener datos del trimestre
    $stmt = $conexion->prepare("
        SELECT trimestre
        FROM trimestre 
        WHERE id_trimestre = ?
    ");
    $stmt->execute([$id_trimestre]);
    $trimestre = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trimestre) {
        throw new Exception('Trimestre no encontrado');
    }

    // Obtener horarios del instructor en el trimestre específico
    // Flujo correcto: horario tiene directamente id_trimestre
    $stmt = $conexion->prepare("
        SELECT 
            h.dia_semana,
            h.hora_inicio,
            h.hora_fin,
            m.materia,
            f.id_ficha,
            fo.nombre as programa,
            j.jornada,
            a.ambiente,
            TIMESTAMPDIFF(MINUTE, h.hora_inicio, h.hora_fin) / 60 as horas_clase,
            COUNT(DISTINCT uf.id_user) as total_aprendices
        FROM horario h
        INNER JOIN materia_ficha mf ON h.id_materia_ficha = mf.id_materia_ficha
        INNER JOIN materias m ON mf.id_materia = m.id_materia
        INNER JOIN fichas f ON h.id_ficha = f.id_ficha
        LEFT JOIN formacion fo ON f.id_formacion = fo.id_formacion
        LEFT JOIN jornada j ON f.id_jornada = j.id_jornada
        LEFT JOIN ambientes a ON f.id_ambiente = a.id_ambiente
        LEFT JOIN user_ficha uf ON f.id_ficha = uf.id_ficha AND uf.id_estado = 1
        WHERE mf.id_instructor = ? 
        AND h.id_trimestre = ? 
        AND h.id_estado = 1
        GROUP BY h.id_horario, h.dia_semana, h.hora_inicio, h.hora_fin, m.materia, f.id_ficha, fo.nombre, j.jornada, a.ambiente
        ORDER BY 
            CASE h.dia_semana 
                WHEN 'Lunes' THEN 1
                WHEN 'Martes' THEN 2
                WHEN 'Miércoles' THEN 3
                WHEN 'Jueves' THEN 4
                WHEN 'Viernes' THEN 5
                WHEN 'Sábado' THEN 6
                WHEN 'Domingo' THEN 7
                ELSE 8
            END,
            h.hora_inicio
    ");
    $stmt->execute([$id_instructor, $id_trimestre]);
    $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($horarios)) {
        throw new Exception('El instructor no tiene horarios asignados en este trimestre');
    }

    // Organizar horarios por día de la semana
    $horariosPorDia = [
        'Lunes' => [],
        'Martes' => [],
        'Miércoles' => [],
        'Jueves' => [],
        'Viernes' => [],
        'Sábado' => [],
        'Domingo' => []
    ];

    $totalHorasSemanales = 0;
    $fichasUnicas = [];
    $materiasUnicas = [];

    foreach ($horarios as $horario) {
        $dia = $horario['dia_semana'];
        if (isset($horariosPorDia[$dia])) {
            $horariosPorDia[$dia][] = $horario;
        }
        $totalHorasSemanales += $horario['horas_clase'];
        $fichasUnicas[$horario['id_ficha']] = $horario['programa'];
        $materiasUnicas[$horario['materia']] = true;
    }

    // Crear el archivo Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Horario Semanal');

    // Configurar estilos
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 14],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0E4A86']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];

    $subHeaderStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1765B4']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];

    $diaHeaderStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '28A745']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];

    $dataStyle = [
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        'alignment' => ['vertical' => Alignment::VERTICAL_TOP]
    ];

    $resumenStyle = [
        'font' => ['bold' => true],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8F9FA']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];

    // Título principal
    $sheet->setCellValue('A1', 'HORARIO SEMANAL DEL INSTRUCTOR POR TRIMESTRE');
    $sheet->mergeCells('A1:H1');
    $sheet->getStyle('A1')->applyFromArray($headerStyle);
    $sheet->getRowDimension(1)->setRowHeight(25);

    $sheet->setCellValue('A2', 'Instructor: ' . $instructor['nombres'] . ' ' . $instructor['apellidos']);
    $sheet->mergeCells('A2:H2');
    $sheet->getStyle('A2')->getFont()->setBold(true);

    $sheet->setCellValue('A3', 'Trimestre: ' . $trimestre['trimestre']);
    $sheet->mergeCells('A3:H3');
    $sheet->getStyle('A3')->getFont()->setBold(true);

    $sheet->setCellValue('A4', 'Fecha de generación: ' . date('d/m/Y H:i:s'));
    $sheet->mergeCells('A4:H4');
    $sheet->getStyle('A4')->getFont()->setItalic(true);

    $row = 6;

    // Resumen general
    $sheet->setCellValue('A' . $row, 'RESUMEN GENERAL DEL TRIMESTRE');
    $sheet->mergeCells('A' . $row . ':H' . $row);
    $sheet->getStyle('A' . $row)->applyFromArray($subHeaderStyle);
    $row++;

    $resumen = [
        ['Total de Horas Semanales:', number_format($totalHorasSemanales, 1) . ' horas'],
        ['Fichas Asignadas en este Trimestre:', count($fichasUnicas) . ' fichas'],
        ['Materias que Imparte:', count($materiasUnicas) . ' materias'],
        ['Días de Trabajo:', count(array_filter($horariosPorDia, function($horarios) { return !empty($horarios); })) . ' días']
    ];

    foreach ($resumen as $item) {
        $sheet->setCellValue('A' . $row, $item[0]);
        $sheet->setCellValue('B' . $row, $item[1]);
        $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray($resumenStyle);
        $row++;
    }

    $row += 2;

    // Horario semanal detallado
    $sheet->setCellValue('A' . $row, 'HORARIO SEMANAL DETALLADO - ' . strtoupper($trimestre['trimestre']));
    $sheet->mergeCells('A' . $row . ':H' . $row);
    $sheet->getStyle('A' . $row)->applyFromArray($subHeaderStyle);
    $row++;

    // Procesar cada día de la semana
    foreach ($horariosPorDia as $dia => $horariosDelDia) {
        if (empty($horariosDelDia)) {
            continue; // Saltar días sin horarios
        }

        // Encabezado del día
        $sheet->setCellValue('A' . $row, strtoupper($dia));
        $sheet->mergeCells('A' . $row . ':H' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray($diaHeaderStyle);
        $row++;

        // Encabezados de columnas para el día
        $headers = ['Hora Inicio', 'Hora Fin', 'Materia', 'Ficha', 'Programa', 'Jornada', 'Ambiente', 'Aprendices'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $col++;
        }
        $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray($subHeaderStyle);
        $row++;

        // Horarios del día
        $horasDelDia = 0;
        foreach ($horariosDelDia as $horario) {
            $sheet->setCellValue('A' . $row, $horario['hora_inicio']);
            $sheet->setCellValue('B' . $row, $horario['hora_fin']);
            $sheet->setCellValue('C' . $row, $horario['materia']);
            $sheet->setCellValue('D' . $row, $horario['id_ficha']);
            $sheet->setCellValue('E' . $row, $horario['programa']);
            $sheet->setCellValue('F' . $row, $horario['jornada']);
            $sheet->setCellValue('G' . $row, $horario['ambiente'] ?? 'No asignado');
            $sheet->setCellValue('H' . $row, $horario['total_aprendices']);
            
            $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray($dataStyle);
            $horasDelDia += $horario['horas_clase'];
            $row++;
        }

        // Total de horas del día
        $sheet->setCellValue('A' . $row, 'TOTAL HORAS ' . strtoupper($dia));
        $sheet->setCellValue('B' . $row, number_format($horasDelDia, 1) . ' horas');
        $sheet->mergeCells('C' . $row . ':H' . $row);
        $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray($resumenStyle);
        $row += 2;
    }

    // Resumen de fichas en este trimestre
    $sheet->setCellValue('A' . $row, 'FICHAS ASIGNADAS EN ESTE TRIMESTRE');
    $sheet->mergeCells('A' . $row . ':H' . $row);
    $sheet->getStyle('A' . $row)->applyFromArray($subHeaderStyle);
    $row++;

    $fichasHeaders = ['Ficha', 'Programa'];
    $col = 'A';
    foreach ($fichasHeaders as $header) {
        $sheet->setCellValue($col . $row, $header);
        $col++;
    }
    $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray($subHeaderStyle);
    $row++;

    foreach ($fichasUnicas as $idFicha => $programa) {
        $sheet->setCellValue('A' . $row, $idFicha);
        $sheet->setCellValue('B' . $row, $programa);
        $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray($dataStyle);
        $row++;
    }

    // Ajustar ancho de columnas
    foreach (range('A', 'H') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Generar nombre del archivo
    $nombreInstructor = str_replace(' ', '_', $instructor['nombres'] . '_' . $instructor['apellidos']);
    $nombreTrimestre = str_replace(' ', '_', $trimestre['trimestre']);
    $fecha = date('Y-m-d_H-i-s');
    $nombreArchivo = "Reporte_Horarios_{$nombreTrimestre}_{$nombreInstructor}_{$fecha}.xlsx";

    // ENVIAR DIRECTAMENTE AL NAVEGADOR SIN GUARDAR EN SERVIDOR
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
    header('Cache-Control: max-age=0');
    header('Cache-Control: max-age=1');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: cache, must-revalidate');
    header('Pragma: public');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al generar el reporte: ' . $e->getMessage()
    ]);
}
?>

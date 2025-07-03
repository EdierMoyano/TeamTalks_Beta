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
$id_ficha = $_POST['id_ficha'] ?? '';

if (empty($id_instructor) || !is_numeric($id_instructor)) {
    echo json_encode(['success' => false, 'message' => 'ID de instructor inválido']);
    exit;
}

if (empty($id_ficha) || !is_numeric($id_ficha)) {
    echo json_encode(['success' => false, 'message' => 'ID de ficha inválido']);
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

    // Verificar que el instructor esté asignado a esta ficha
    $stmt = $conexion->prepare("
        SELECT COUNT(*) 
        FROM materia_ficha mf
        WHERE mf.id_instructor = ? AND mf.id_ficha = ?
    ");
    $stmt->execute([$id_instructor, $id_ficha]);
    $asignacionExiste = $stmt->fetchColumn() > 0;

    if (!$asignacionExiste) {
        throw new Exception('El instructor no está asignado a esta ficha');
    }

    // Obtener información completa de la ficha
    $stmt = $conexion->prepare("
        SELECT 
            f.id_ficha,
            fo.nombre as programa,
            tf.tipo_formacion,
            j.jornada,
            f.fecha_creac,
            a.ambiente,
            t.trimestre
        FROM fichas f
        LEFT JOIN formacion fo ON f.id_formacion = fo.id_formacion
        LEFT JOIN tipo_formacion tf ON fo.id_tipo_formacion = tf.id_tipo_formacion
        LEFT JOIN jornada j ON f.id_jornada = j.id_jornada
        LEFT JOIN ambientes a ON f.id_ambiente = a.id_ambiente
        LEFT JOIN trimestre t ON f.id_trimestre = t.id_trimestre
        WHERE f.id_ficha = ? AND f.id_estado = 1
    ");
    $stmt->execute([$id_ficha]);
    $ficha = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ficha) {
        throw new Exception('Ficha no encontrada');
    }

    // Obtener materias que el instructor imparte en esta ficha
    $stmt = $conexion->prepare("
        SELECT DISTINCT
            m.materia,
            m.descripcion
        FROM materia_ficha mf
        INNER JOIN materias m ON mf.id_materia = m.id_materia
        WHERE mf.id_instructor = ? AND mf.id_ficha = ?
        ORDER BY m.materia
    ");
    $stmt->execute([$id_instructor, $id_ficha]);
    $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener horarios del instructor en esta ficha
    $stmt = $conexion->prepare("
        SELECT 
            h.dia_semana,
            h.hora_inicio,
            h.hora_fin,
            m.materia,
            t.trimestre,
            TIMESTAMPDIFF(MINUTE, h.hora_inicio, h.hora_fin) / 60 as horas_clase
        FROM horario h
        INNER JOIN materia_ficha mf ON h.id_materia_ficha = mf.id_materia_ficha
        INNER JOIN materias m ON mf.id_materia = m.id_materia
        LEFT JOIN trimestre t ON h.id_trimestre = t.id_trimestre
        WHERE mf.id_instructor = ? AND h.id_ficha = ? AND h.id_estado = 1
        ORDER BY h.dia_semana, h.hora_inicio
    ");
    $stmt->execute([$id_instructor, $id_ficha]);
    $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener aprendices de la ficha
    $stmt = $conexion->prepare("
        SELECT 
            u.id,
            u.nombres,
            u.apellidos,
            u.correo,
            u.telefono,
            e.estado
        FROM user_ficha uf
        INNER JOIN usuarios u ON uf.id_user = u.id
        LEFT JOIN estado e ON u.id_estado = e.id_estado
        WHERE uf.id_ficha = ? AND uf.id_estado = 1
        ORDER BY u.apellidos, u.nombres
    ");
    $stmt->execute([$id_ficha]);
    $aprendices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcular estadísticas
    $totalHoras = 0;
    foreach ($horarios as $horario) {
        $totalHoras += $horario['horas_clase'];
    }

    // Crear el archivo Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Reporte Ficha Individual');

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

    $fichaHeaderStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DC3545']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];

    $dataStyle = [
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        'alignment' => ['vertical' => Alignment::VERTICAL_TOP]
    ];

    // Título principal
    $sheet->setCellValue('A1', 'REPORTE INDIVIDUAL DE FICHA');
    $sheet->mergeCells('A1:H1');
    $sheet->getStyle('A1')->applyFromArray($headerStyle);
    $sheet->getRowDimension(1)->setRowHeight(25);

    $sheet->setCellValue('A2', 'Instructor: ' . $instructor['nombres'] . ' ' . $instructor['apellidos']);
    $sheet->mergeCells('A2:H2');
    $sheet->getStyle('A2')->getFont()->setBold(true);

    $sheet->setCellValue('A3', 'Ficha: ' . $ficha['id_ficha'] . ' - ' . $ficha['programa']);
    $sheet->mergeCells('A3:H3');
    $sheet->getStyle('A3')->getFont()->setBold(true);

    $sheet->setCellValue('A4', 'Fecha de generación: ' . date('d/m/Y H:i:s'));
    $sheet->mergeCells('A4:H4');
    $sheet->getStyle('A4')->getFont()->setItalic(true);

    $row = 6;

    // Información de la ficha
    $sheet->setCellValue('A' . $row, 'INFORMACIÓN DE LA FICHA');
    $sheet->mergeCells('A' . $row . ':H' . $row);
    $sheet->getStyle('A' . $row)->applyFromArray($fichaHeaderStyle);
    $row++;

    $infoFicha = [
        ['Número de Ficha:', $ficha['id_ficha']],
        ['Programa de Formación:', $ficha['programa']],
        ['Tipo de Formación:', $ficha['tipo_formacion']],
        ['Jornada:', $ficha['jornada']],
        ['Ambiente:', $ficha['ambiente'] ?? 'No asignado'],
        ['Trimestre:', $ficha['trimestre'] ?? 'No asignado'],
        ['Fecha de Creación:', date('d/m/Y', strtotime($ficha['fecha_creac']))],
        ['Total de Aprendices:', count($aprendices)],
        ['Horas Semanales del Instructor:', number_format($totalHoras, 1) . ' horas']
    ];

    foreach ($infoFicha as $info) {
        $sheet->setCellValue('A' . $row, $info[0]);
        $sheet->setCellValue('B' . $row, $info[1]);
        $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray($dataStyle);
        $row++;
    }

    $row += 2;

    // Materias que imparte el instructor en esta ficha
    $sheet->setCellValue('A' . $row, 'MATERIAS QUE IMPARTE EL INSTRUCTOR EN ESTA FICHA');
    $sheet->mergeCells('A' . $row . ':H' . $row);
    $sheet->getStyle('A' . $row)->applyFromArray($subHeaderStyle);
    $row++;

    if (!empty($materias)) {
        $materiasHeaders = ['Materia', 'Descripción'];
        $col = 'A';
        foreach ($materiasHeaders as $header) {
            $sheet->setCellValue($col . $row, $header);
            $col++;
        }
        $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray($subHeaderStyle);
        $row++;

        foreach ($materias as $materia) {
            $sheet->setCellValue('A' . $row, $materia['materia']);
            $sheet->setCellValue('B' . $row, $materia['descripcion'] ?? 'Sin descripción');
            $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray($dataStyle);
            $row++;
        }
    } else {
        $sheet->setCellValue('A' . $row, 'No hay materias asignadas');
        $sheet->mergeCells('A' . $row . ':H' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray($dataStyle);
        $row++;
    }

    $row += 2;

    // Horarios del instructor en esta ficha
    $sheet->setCellValue('A' . $row, 'HORARIOS DEL INSTRUCTOR EN ESTA FICHA');
    $sheet->mergeCells('A' . $row . ':H' . $row);
    $sheet->getStyle('A' . $row)->applyFromArray($subHeaderStyle);
    $row++;

    if (!empty($horarios)) {
        $horariosHeaders = ['Día', 'Hora Inicio', 'Hora Fin', 'Materia', 'Horas', 'Trimestre'];
        $col = 'A';
        foreach ($horariosHeaders as $header) {
            $sheet->setCellValue($col . $row, $header);
            $col++;
        }
        $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray($subHeaderStyle);
        $row++;

        foreach ($horarios as $horario) {
            $sheet->setCellValue('A' . $row, $horario['dia_semana']);
            $sheet->setCellValue('B' . $row, $horario['hora_inicio']);
            $sheet->setCellValue('C' . $row, $horario['hora_fin']);
            $sheet->setCellValue('D' . $row, $horario['materia']);
            $sheet->setCellValue('E' . $row, number_format($horario['horas_clase'], 1));
            $sheet->setCellValue('F' . $row, $horario['trimestre'] ?? 'No asignado');
            
            $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray($dataStyle);
            $row++;
        }

        // Total de horas
        $sheet->setCellValue('A' . $row, 'TOTAL HORAS SEMANALES');
        $sheet->setCellValue('E' . $row, number_format($totalHoras, 1));
        $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray($fichaHeaderStyle);
        $row++;
    } else {
        $sheet->setCellValue('A' . $row, 'No hay horarios asignados');
        $sheet->mergeCells('A' . $row . ':F' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray($dataStyle);
        $row++;
    }

    $row += 2;

    // Lista de aprendices
    $sheet->setCellValue('A' . $row, 'APRENDICES DE LA FICHA');
    $sheet->mergeCells('A' . $row . ':H' . $row);
    $sheet->getStyle('A' . $row)->applyFromArray($subHeaderStyle);
    $row++;

    if (!empty($aprendices)) {
        $aprendicesHeaders = ['Documento', 'Nombres', 'Apellidos', 'Correo', 'Teléfono', 'Estado'];
        $col = 'A';
        foreach ($aprendicesHeaders as $header) {
            $sheet->setCellValue($col . $row, $header);
            $col++;
        }
        $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray($subHeaderStyle);
        $row++;

        foreach ($aprendices as $aprendiz) {
            $sheet->setCellValue('A' . $row, $aprendiz['id']);
            $sheet->setCellValue('B' . $row, $aprendiz['nombres']);
            $sheet->setCellValue('C' . $row, $aprendiz['apellidos']);
            $sheet->setCellValue('D' . $row, $aprendiz['correo']);
            $sheet->setCellValue('E' . $row, $aprendiz['telefono'] ?? 'No registrado');
            $sheet->setCellValue('F' . $row, $aprendiz['estado']);
            
            $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray($dataStyle);
            $row++;
        }
    } else {
        $sheet->setCellValue('A' . $row, 'No hay aprendices asignados a esta ficha');
        $sheet->mergeCells('A' . $row . ':F' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray($dataStyle);
    }

    // Ajustar ancho de columnas
    foreach (range('A', 'H') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Generar nombre del archivo
    $nombreInstructor = str_replace(' ', '_', $instructor['nombres'] . '_' . $instructor['apellidos']);
    $fecha = date('Y-m-d_H-i-s');
    $nombreArchivo = "Reporte_Ficha_{$id_ficha}_{$nombreInstructor}_{$fecha}.xlsx";

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

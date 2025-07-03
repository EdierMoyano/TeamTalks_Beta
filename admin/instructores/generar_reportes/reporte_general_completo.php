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

if (empty($id_instructor) || !is_numeric($id_instructor)) {
    echo json_encode(['success' => false, 'message' => 'ID de instructor inválido']);
    exit;
}

try {
    // Obtener datos personales del instructor
    $stmt = $conexion->prepare("
        SELECT 
            u.id,
            u.nombres,
            u.apellidos,
            u.correo,
            u.telefono,
            u.fecha_registro,
            r.rol,
            e.estado
        FROM usuarios u
        LEFT JOIN roles r ON u.id_rol = r.id_rol
        LEFT JOIN estado e ON u.id_estado = e.id_estado
        WHERE u.id = ? AND u.id_rol IN (3, 5)
    ");
    $stmt->execute([$id_instructor]);
    $instructor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$instructor) {
        throw new Exception('Instructor no encontrado');
    }

    // Obtener fichas asignadas con aprendices
    $stmt = $conexion->prepare("
        SELECT DISTINCT
            f.id_ficha,
            fo.nombre as programa,
            tf.tipo_formacion,
            j.jornada,
            f.fecha_creac,
            COUNT(DISTINCT uf.id_user) as total_aprendices
        FROM fichas f
        INNER JOIN materia_ficha mf ON f.id_ficha = mf.id_ficha
        LEFT JOIN formacion fo ON f.id_formacion = fo.id_formacion
        LEFT JOIN tipo_formacion tf ON fo.id_tipo_formacion = tf.id_tipo_formacion
        LEFT JOIN jornada j ON f.id_jornada = j.id_jornada
        LEFT JOIN user_ficha uf ON f.id_ficha = uf.id_ficha AND uf.id_estado = 1
        WHERE mf.id_instructor = ? AND f.id_estado = 1
        GROUP BY f.id_ficha, fo.nombre, tf.tipo_formacion, j.jornada, f.fecha_creac
        ORDER BY f.id_ficha
    ");
    $stmt->execute([$id_instructor]);
    $fichas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener horarios detallados
    $stmt = $conexion->prepare("
        SELECT 
            h.dia_semana,
            h.hora_inicio,
            h.hora_fin,
            m.materia,
            f.id_ficha,
            t.trimestre,
            TIMESTAMPDIFF(MINUTE, h.hora_inicio, h.hora_fin) / 60 as horas_clase
        FROM horario h
        INNER JOIN materia_ficha mf ON h.id_materia_ficha = mf.id_materia_ficha
        INNER JOIN materias m ON mf.id_materia = m.id_materia
        INNER JOIN fichas f ON h.id_ficha = f.id_ficha
        LEFT JOIN trimestre t ON h.id_trimestre = t.id_trimestre
        WHERE mf.id_instructor = ? AND h.id_estado = 1
        ORDER BY f.id_ficha, h.dia_semana, h.hora_inicio
    ");
    $stmt->execute([$id_instructor]);
    $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcular horas por trimestre
    $stmt = $conexion->prepare("
        SELECT 
            t.trimestre,
            SUM(TIMESTAMPDIFF(MINUTE, h.hora_inicio, h.hora_fin)) / 60 as total_horas,
            COUNT(DISTINCT h.dia_semana) as dias_semana
        FROM horario h
        INNER JOIN materia_ficha mf ON h.id_materia_ficha = mf.id_materia_ficha
        INNER JOIN trimestre t ON h.id_trimestre = t.id_trimestre
        WHERE mf.id_instructor = ? AND h.id_estado = 1
        GROUP BY t.id_trimestre, t.trimestre
        ORDER BY t.id_trimestre
    ");
    $stmt->execute([$id_instructor]);
    $horas_trimestre = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Crear el archivo Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Reporte General Completo');

    // Configurar estilos
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
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

    $dataStyle = [
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        'alignment' => ['vertical' => Alignment::VERTICAL_TOP]
    ];

    // Título principal
    $sheet->setCellValue('A1', 'REPORTE GENERAL COMPLETO - INSTRUCTOR');
    $sheet->mergeCells('A1:H1');
    $sheet->getStyle('A1')->applyFromArray($headerStyle);
    $sheet->getRowDimension(1)->setRowHeight(25);

    $row = 3;

    // Sección: Datos Personales
    $sheet->setCellValue('A' . $row, 'DATOS PERSONALES DEL INSTRUCTOR');
    $sheet->mergeCells('A' . $row . ':H' . $row);
    $sheet->getStyle('A' . $row)->applyFromArray($subHeaderStyle);
    $row++;

    $datosPersonales = [
        ['Campo', 'Información'],
        ['Documento', $instructor['id']],
        ['Nombres', $instructor['nombres']],
        ['Apellidos', $instructor['apellidos']],
        ['Correo Electrónico', $instructor['correo']],
        ['Teléfono', $instructor['telefono'] ?? 'No registrado'],
        ['Tipo de Instructor', $instructor['rol']],
        ['Estado', $instructor['estado']],
        ['Fecha de Registro', date('d/m/Y', strtotime($instructor['fecha_registro']))]
    ];

    foreach ($datosPersonales as $index => $dato) {
        $sheet->setCellValue('A' . $row, $dato[0]);
        $sheet->setCellValue('B' . $row, $dato[1]);
        
        if ($index === 0) {
            $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray($headerStyle);
        } else {
            $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray($dataStyle);
        }
        $row++;
    }

    $row += 2;

    // Sección: Fichas Asignadas
    $sheet->setCellValue('A' . $row, 'FICHAS ASIGNADAS');
    $sheet->mergeCells('A' . $row . ':H' . $row);
    $sheet->getStyle('A' . $row)->applyFromArray($subHeaderStyle);
    $row++;

    if (!empty($fichas)) {
        $fichasHeaders = ['Ficha', 'Programa', 'Tipo', 'Jornada', 'Fecha Creación', 'Aprendices'];
        $col = 'A';
        foreach ($fichasHeaders as $header) {
            $sheet->setCellValue($col . $row, $header);
            $col++;
        }
        $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray($headerStyle);
        $row++;

        foreach ($fichas as $ficha) {
            $sheet->setCellValue('A' . $row, $ficha['id_ficha']);
            $sheet->setCellValue('B' . $row, $ficha['programa']);
            $sheet->setCellValue('C' . $row, $ficha['tipo_formacion']);
            $sheet->setCellValue('D' . $row, $ficha['jornada']);
            $sheet->setCellValue('E' . $row, date('d/m/Y', strtotime($ficha['fecha_creac'])));
            $sheet->setCellValue('F' . $row, $ficha['total_aprendices']);
            
            $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray($dataStyle);
            $row++;
        }
    } else {
        $sheet->setCellValue('A' . $row, 'No hay fichas asignadas');
        $sheet->mergeCells('A' . $row . ':F' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray($dataStyle);
        $row++;
    }

    $row += 2;

    // Sección: Horarios
    $sheet->setCellValue('A' . $row, 'HORARIOS DE CLASES');
    $sheet->mergeCells('A' . $row . ':H' . $row);
    $sheet->getStyle('A' . $row)->applyFromArray($subHeaderStyle);
    $row++;

    if (!empty($horarios)) {
        $horariosHeaders = ['Ficha', 'Materia', 'Día', 'Hora Inicio', 'Hora Fin', 'Horas', 'Trimestre'];
        $col = 'A';
        foreach ($horariosHeaders as $header) {
            $sheet->setCellValue($col . $row, $header);
            $col++;
        }
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($headerStyle);
        $row++;

        foreach ($horarios as $horario) {
            $sheet->setCellValue('A' . $row, $horario['id_ficha']);
            $sheet->setCellValue('B' . $row, $horario['materia']);
            $sheet->setCellValue('C' . $row, $horario['dia_semana']);
            $sheet->setCellValue('D' . $row, $horario['hora_inicio']);
            $sheet->setCellValue('E' . $row, $horario['hora_fin']);
            $sheet->setCellValue('F' . $row, number_format($horario['horas_clase'], 1));
            $sheet->setCellValue('G' . $row, $horario['trimestre'] ?? 'No asignado');
            
            $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($dataStyle);
            $row++;
        }
    } else {
        $sheet->setCellValue('A' . $row, 'No hay horarios asignados');
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray($dataStyle);
        $row++;
    }

    $row += 2;

    // Sección: Horas por Trimestre
    $sheet->setCellValue('A' . $row, 'CÁLCULO DE HORAS POR TRIMESTRE');
    $sheet->mergeCells('A' . $row . ':H' . $row);
    $sheet->getStyle('A' . $row)->applyFromArray($subHeaderStyle);
    $row++;

    if (!empty($horas_trimestre)) {
        $horasHeaders = ['Trimestre', 'Total Horas', 'Días por Semana', 'Horas Semanales'];
        $col = 'A';
        foreach ($horasHeaders as $header) {
            $sheet->setCellValue($col . $row, $header);
            $col++;
        }
        $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray($headerStyle);
        $row++;

        $totalHorasGeneral = 0;
        foreach ($horas_trimestre as $trimestre) {
            $horasSemanales = $trimestre['total_horas'] / ($trimestre['dias_semana'] ?: 1);
            $totalHorasGeneral += $trimestre['total_horas'];
            
            $sheet->setCellValue('A' . $row, $trimestre['trimestre']);
            $sheet->setCellValue('B' . $row, number_format($trimestre['total_horas'], 1));
            $sheet->setCellValue('C' . $row, $trimestre['dias_semana']);
            $sheet->setCellValue('D' . $row, number_format($horasSemanales, 1));
            
            $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray($dataStyle);
            $row++;
        }

        // Total general
        $sheet->setCellValue('A' . $row, 'TOTAL GENERAL');
        $sheet->setCellValue('B' . $row, number_format($totalHorasGeneral, 1));
        $sheet->mergeCells('C' . $row . ':D' . $row);
        $sheet->setCellValue('C' . $row, 'Horas académicas totales');
        $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray($headerStyle);
    } else {
        $sheet->setCellValue('A' . $row, 'No hay horas calculadas por trimestre');
        $sheet->mergeCells('A' . $row . ':D' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray($dataStyle);
    }

    // Ajustar ancho de columnas
    foreach (range('A', 'H') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Generar nombre del archivo
    $nombreInstructor = str_replace(' ', '_', $instructor['nombres'] . '_' . $instructor['apellidos']);
    $fecha = date('Y-m-d_H-i-s');
    $nombreArchivo = "Reporte_General_Completo_{$nombreInstructor}_{$fecha}.xlsx";

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

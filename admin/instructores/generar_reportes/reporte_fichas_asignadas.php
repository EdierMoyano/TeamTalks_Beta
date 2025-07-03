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
    // Obtener datos básicos del instructor
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

    // Obtener fichas asignadas con detalles completos
    $stmt = $conexion->prepare("
        SELECT DISTINCT
            f.id_ficha,
            fo.nombre as programa,
            tf.tipo_formacion,
            j.jornada,
            f.fecha_creac,
            a.ambiente,
            COUNT(DISTINCT uf.id_user) as total_aprendices,
            GROUP_CONCAT(DISTINCT m.materia ORDER BY m.materia SEPARATOR ', ') as materias_asignadas
        FROM fichas f
        INNER JOIN materia_ficha mf ON f.id_ficha = mf.id_ficha
        LEFT JOIN formacion fo ON f.id_formacion = fo.id_formacion
        LEFT JOIN tipo_formacion tf ON fo.id_tipo_formacion = tf.id_tipo_formacion
        LEFT JOIN jornada j ON f.id_jornada = j.id_jornada
        LEFT JOIN ambientes a ON f.id_ambiente = a.id_ambiente
        LEFT JOIN user_ficha uf ON f.id_ficha = uf.id_ficha AND uf.id_estado = 1
        LEFT JOIN materias m ON mf.id_materia = m.id_materia
        WHERE mf.id_instructor = ? AND f.id_estado = 1
        GROUP BY f.id_ficha, fo.nombre, tf.tipo_formacion, j.jornada, f.fecha_creac, a.ambiente
        ORDER BY f.id_ficha
    ");
    $stmt->execute([$id_instructor]);
    $fichas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener aprendices por ficha
    $aprendicesPorFicha = [];
    foreach ($fichas as $ficha) {
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
        $stmt->execute([$ficha['id_ficha']]);
        $aprendicesPorFicha[$ficha['id_ficha']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener horarios por ficha
    $horariosPorFicha = [];
    foreach ($fichas as $ficha) {
        $stmt = $conexion->prepare("
            SELECT 
                h.dia_semana,
                h.hora_inicio,
                h.hora_fin,
                m.materia,
                t.trimestre
            FROM horario h
            INNER JOIN materia_ficha mf ON h.id_materia_ficha = mf.id_materia_ficha
            INNER JOIN materias m ON mf.id_materia = m.id_materia
            LEFT JOIN trimestre t ON h.id_trimestre = t.id_trimestre
            WHERE h.id_ficha = ? AND mf.id_instructor = ? AND h.id_estado = 1
            ORDER BY h.dia_semana, h.hora_inicio
        ");
        $stmt->execute([$ficha['id_ficha'], $id_instructor]);
        $horariosPorFicha[$ficha['id_ficha']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Crear el archivo Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Fichas Asignadas');

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
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '28A745']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];

    $dataStyle = [
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        'alignment' => ['vertical' => Alignment::VERTICAL_TOP]
    ];

    // Título principal
    $sheet->setCellValue('A1', 'REPORTE DE FICHAS ASIGNADAS - ' . strtoupper($instructor['nombres'] . ' ' . $instructor['apellidos']));
    $sheet->mergeCells('A1:I1');
    $sheet->getStyle('A1')->applyFromArray($headerStyle);
    $sheet->getRowDimension(1)->setRowHeight(25);

    $sheet->setCellValue('A2', 'Fecha de generación: ' . date('d/m/Y H:i:s'));
    $sheet->mergeCells('A2:I2');
    $sheet->getStyle('A2')->getFont()->setItalic(true);

    $row = 4;

    if (!empty($fichas)) {
        // Resumen general
        $sheet->setCellValue('A' . $row, 'RESUMEN GENERAL');
        $sheet->mergeCells('A' . $row . ':I' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray($subHeaderStyle);
        $row++;

        $totalAprendices = array_sum(array_column($fichas, 'total_aprendices'));
        $resumen = [
            ['Total de Fichas Asignadas:', count($fichas)],
            ['Total de Aprendices:', $totalAprendices],
            ['Promedio de Aprendices por Ficha:', round($totalAprendices / count($fichas), 1)]
        ];

        foreach ($resumen as $item) {
            $sheet->setCellValue('A' . $row, $item[0]);
            $sheet->setCellValue('B' . $row, $item[1]);
            $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray($dataStyle);
            $row++;
        }

        $row += 2;

        // Detalles por ficha
        foreach ($fichas as $ficha) {
            // Encabezado de la ficha
            $sheet->setCellValue('A' . $row, 'FICHA: ' . $ficha['id_ficha'] . ' - ' . $ficha['programa']);
            $sheet->mergeCells('A' . $row . ':I' . $row);
            $sheet->getStyle('A' . $row)->applyFromArray($fichaHeaderStyle);
            $row++;

            // Información básica de la ficha
            $infoFicha = [
                ['Programa:', $ficha['programa']],
                ['Tipo de Formación:', $ficha['tipo_formacion']],
                ['Jornada:', $ficha['jornada']],
                ['Ambiente:', $ficha['ambiente'] ?? 'No asignado'],
                ['Fecha de Creación:', date('d/m/Y', strtotime($ficha['fecha_creac']))],
                ['Materias Asignadas:', $ficha['materias_asignadas']],
                ['Total Aprendices:', $ficha['total_aprendices']]
            ];

            foreach ($infoFicha as $info) {
                $sheet->setCellValue('A' . $row, $info[0]);
                $sheet->setCellValue('B' . $row, $info[1]);
                $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray($dataStyle);
                $row++;
            }

            $row++;

            // Aprendices de la ficha
            if (!empty($aprendicesPorFicha[$ficha['id_ficha']])) {
                $sheet->setCellValue('A' . $row, 'APRENDICES ASIGNADOS');
                $sheet->mergeCells('A' . $row . ':F' . $row);
                $sheet->getStyle('A' . $row)->applyFromArray($subHeaderStyle);
                $row++;

                $aprendicesHeaders = ['Documento', 'Nombres', 'Apellidos', 'Correo', 'Teléfono', 'Estado'];
                $col = 'A';
                foreach ($aprendicesHeaders as $header) {
                    $sheet->setCellValue($col . $row, $header);
                    $col++;
                }
                $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray($subHeaderStyle);
                $row++;

                foreach ($aprendicesPorFicha[$ficha['id_ficha']] as $aprendiz) {
                    $sheet->setCellValue('A' . $row, $aprendiz['id']);
                    $sheet->setCellValue('B' . $row, $aprendiz['nombres']);
                    $sheet->setCellValue('C' . $row, $aprendiz['apellidos']);
                    $sheet->setCellValue('D' . $row, $aprendiz['correo']);
                    $sheet->setCellValue('E' . $row, $aprendiz['telefono'] ?? 'No registrado');
                    $sheet->setCellValue('F' . $row, $aprendiz['estado']);
                    
                    $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray($dataStyle);
                    $row++;
                }
            }

            $row++;

            // Horarios de la ficha
            if (!empty($horariosPorFicha[$ficha['id_ficha']])) {
                $sheet->setCellValue('A' . $row, 'HORARIOS DE CLASES');
                $sheet->mergeCells('A' . $row . ':E' . $row);
                $sheet->getStyle('A' . $row)->applyFromArray($subHeaderStyle);
                $row++;

                $horariosHeaders = ['Día', 'Hora Inicio', 'Hora Fin', 'Materia', 'Trimestre'];
                $col = 'A';
                foreach ($horariosHeaders as $header) {
                    $sheet->setCellValue($col . $row, $header);
                    $col++;
                }
                $sheet->getStyle('A' . $row . ':E' . $row)->applyFromArray($subHeaderStyle);
                $row++;

                foreach ($horariosPorFicha[$ficha['id_ficha']] as $horario) {
                    $sheet->setCellValue('A' . $row, $horario['dia_semana']);
                    $sheet->setCellValue('B' . $row, $horario['hora_inicio']);
                    $sheet->setCellValue('C' . $row, $horario['hora_fin']);
                    $sheet->setCellValue('D' . $row, $horario['materia']);
                    $sheet->setCellValue('E' . $row, $horario['trimestre'] ?? 'No asignado');
                    
                    $sheet->getStyle('A' . $row . ':E' . $row)->applyFromArray($dataStyle);
                    $row++;
                }
            }

            $row += 3; // Espacio entre fichas
        }
    } else {
        $sheet->setCellValue('A' . $row, 'No hay fichas asignadas a este instructor');
        $sheet->mergeCells('A' . $row . ':I' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray($dataStyle);
    }

    // Ajustar ancho de columnas
    foreach (range('A', 'I') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Generar nombre del archivo
    $nombreInstructor = str_replace(' ', '_', $instructor['nombres'] . '_' . $instructor['apellidos']);
    $fecha = date('Y-m-d_H-i-s');
    $nombreArchivo = "Reporte_Fichas_Asignadas_{$nombreInstructor}_{$fecha}.xlsx";

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

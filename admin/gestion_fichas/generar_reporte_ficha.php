<?php
session_start();

// Verificar sesión y rol de administrador
if (!isset($_SESSION['documento']) || $_SESSION['rol'] !== 2) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

require_once '../../conexion/conexion.php';

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

$id_ficha = $_POST['id_ficha'] ?? '';
$tipo_reporte = $_POST['tipo_reporte'] ?? '';

if (empty($id_ficha) || !is_numeric($id_ficha)) {
    echo json_encode(['success' => false, 'message' => 'ID de ficha inválido']);
    exit;
}

if (empty($tipo_reporte)) {
    echo json_encode(['success' => false, 'message' => 'Tipo de reporte no especificado']);
    exit;
}

try {
    // Obtener información básica de la ficha
    $stmt = $conexion->prepare("
        SELECT 
            f.id_ficha,
            f.fecha_creac,
            fo.nombre as programa,
            fo.descripcion as descripcion_programa,
            tf.tipo_formacion,
            tf.Duracion,
            j.jornada,
            e.estado,
            CONCAT(u.nombres, ' ', u.apellidos) as instructor_lider,
            u.correo as correo_instructor,
            u.telefono as telefono_instructor
        FROM fichas f
        LEFT JOIN formacion fo ON f.id_formacion = fo.id_formacion
        LEFT JOIN tipo_formacion tf ON fo.id_tipo_formacion = tf.id_tipo_formacion
        LEFT JOIN jornada j ON f.id_jornada = j.id_jornada
        LEFT JOIN estado e ON f.id_estado = e.id_estado
        LEFT JOIN usuarios u ON f.id_instructor = u.id
        WHERE f.id_ficha = ?
    ");
    $stmt->execute([$id_ficha]);
    $ficha = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ficha) {
        throw new Exception('Ficha no encontrada');
    }

    // Crear el archivo Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

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

    $labelStyle = [
        'font' => ['bold' => true],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8F9FA']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
    ];

    $dataStyle = [
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        'alignment' => ['vertical' => Alignment::VERTICAL_TOP]
    ];

    switch ($tipo_reporte) {
        case 'historia_materias':
            generarReporteHistoriaMaterias($conexion, $ficha, $spreadsheet, $sheet, $headerStyle, $subHeaderStyle, $labelStyle, $dataStyle);
            break;
        case 'horarios':
            generarReporteHorarios($conexion, $ficha, $spreadsheet, $sheet, $headerStyle, $subHeaderStyle, $labelStyle, $dataStyle);
            break;
        case 'aprendices':
            generarReporteAprendices($conexion, $ficha, $spreadsheet, $sheet, $headerStyle, $subHeaderStyle, $labelStyle, $dataStyle);
            break;
        case 'completo':
            generarReporteCompleto($conexion, $ficha, $spreadsheet, $sheet, $headerStyle, $subHeaderStyle, $labelStyle, $dataStyle);
            break;
        default:
            throw new Exception('Tipo de reporte no válido');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al generar el reporte: ' . $e->getMessage()
    ]);
}

function generarReporteHistoriaMaterias($conexion, $ficha, $spreadsheet, $sheet, $headerStyle, $subHeaderStyle, $labelStyle, $dataStyle)
{
    $sheet->setCellValue('A1', 'HISTORIA DE MATERIAS - FICHA ' . $ficha['id_ficha']);
    $sheet->mergeCells('A1:H1');  // Aquí combinamos las primeras columnas hasta la H para el título principal
    $sheet->getStyle('A1')->applyFromArray($headerStyle);
    $sheet->getRowDimension(1)->setRowHeight(30); // Establecer una altura para la fila

    $sheet->setCellValue('A2', 'Programa: ' . $ficha['programa']);
    $sheet->mergeCells('A2:H2');
    $sheet->getStyle('A2')->getFont()->setBold(true);

    $sheet->setCellValue('A3', 'Fecha de generación: ' . date('d/m/Y H:i:s'));
    $sheet->mergeCells('A3:H3');
    $sheet->getStyle('A3')->getFont()->setItalic(true);

    $row = 5;

    // Obtener historia de materias con cambios de instructores
    $stmt = $conexion->prepare("
    SELECT 
        mf.id_materia_ficha,
        m.materia,
        m.descripcion as descripcion_materia,
        t.trimestre,
        CONCAT(u.nombres, ' ', u.apellidos) as instructor_actual,
        u.correo as correo_instructor,
        mf.id_estado,
        e.estado as estado_asignacion,
        -- Obtener instructor anterior y su información desde la tabla historial_modificaciones_materias
        hm.id_instructor_anterior,
        CONCAT(ui.nombres, ' ', ui.apellidos) as instructor_anterior,
        -- Obtener cambios de instructores desde logs
        (SELECT GROUP_CONCAT(
            CONCAT('Fecha: ', DATE_FORMAT(l.fecha, '%d/%m/%Y %H:%i'), 
                   ' - Instructor anterior: ', 
                   COALESCE(JSON_UNQUOTE(JSON_EXTRACT(l.datos_nuevos, '$.id_instructor')), 'Sin asignar'))
            SEPARATOR '; '
        ) FROM logs_acciones l 
         WHERE l.entidad = 'materia_ficha' 
         AND l.id_entidad = mf.id_materia_ficha 
         AND l.accion = 'EDITAR'
         ORDER BY l.fecha DESC) as historial_cambios
    FROM materia_ficha mf
    JOIN materias m ON mf.id_materia = m.id_materia
    LEFT JOIN trimestre t ON mf.id_trimestre = t.id_trimestre
    LEFT JOIN usuarios u ON mf.id_instructor = u.id
    LEFT JOIN estado e ON mf.id_estado = e.id_estado
    LEFT JOIN historial_modificaciones_materias hm ON mf.id_materia_ficha = hm.id_materia_ficha
    LEFT JOIN usuarios ui ON hm.id_instructor_anterior = ui.id
    WHERE mf.id_ficha = ? 
    ORDER BY t.id_trimestre, m.materia
");

    $stmt->execute([$ficha['id_ficha']]);
    $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Encabezados
    // Encabezados
    $headers = ['Materia', 'Descripción', 'Trimestre', 'Instructor Actual', 'Correo', 'Estado', 'Instructor Anterior', 'Historial de Cambios'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . $row, $header);
        $col++;
    }
    $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray($subHeaderStyle);
    $row++;

    // Datos de materias
    foreach ($materias as $materia) {
    $sheet->setCellValue('A' . $row, $materia['materia']);
    $sheet->setCellValue('B' . $row, $materia['descripcion_materia'] ?? '');
    $sheet->setCellValue('C' . $row, $materia['trimestre'] ?? 'No definido');
    $sheet->setCellValue('D' . $row, $materia['instructor_actual'] ?? 'Sin asignar');
    $sheet->setCellValue('E' . $row, $materia['correo_instructor'] ?? '');
    $sheet->setCellValue('F' . $row, $materia['estado_asignacion']);
    $sheet->setCellValue('G' . $row, $materia['instructor_anterior'] ?? 'No disponible');
    $sheet->setCellValue('H' . $row, $materia['historial_cambios'] ?? 'Sin cambios registrados');
    
    $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray($dataStyle);  // Aplicamos estilo a todas las columnas de la fila
    $row++;
}



    // Ajustar ancho de columnas
    $sheet->getColumnDimension('A')->setWidth(25);
    $sheet->getColumnDimension('B')->setWidth(30);
    $sheet->getColumnDimension('C')->setWidth(15);
    $sheet->getColumnDimension('D')->setWidth(25);
    $sheet->getColumnDimension('E')->setWidth(30);
    $sheet->getColumnDimension('F')->setWidth(15);
    $sheet->getColumnDimension('G')->setWidth(50);

    // Generar nombre del archivo
    $fecha = date('Y-m-d_H-i-s');
    $nombreArchivo = "Historia_Materias_Ficha_{$ficha['id_ficha']}_{$fecha}.xlsx";

    // ENVIAR DIRECTAMENTE AL NAVEGADOR
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

function generarReporteHorarios($conexion, $ficha, $spreadsheet, $sheet, $headerStyle, $subHeaderStyle, $labelStyle, $dataStyle)
{
    $sheet->setTitle('Horarios');

    // Título principal
    $sheet->setCellValue('A1', 'HORARIOS DE CLASES - FICHA ' . $ficha['id_ficha']);
    $sheet->mergeCells('A1:G1');
    $sheet->getStyle('A1')->applyFromArray($headerStyle);
    $sheet->getRowDimension(1)->setRowHeight(30);

    $sheet->setCellValue('A2', 'Programa: ' . $ficha['programa']);
    $sheet->mergeCells('A2:H2');
    $sheet->getStyle('A2')->getFont()->setBold(true);


    $sheet->setCellValue('A3', 'Fecha de generación: ' . date('d/m/Y H:i:s'));
    $sheet->mergeCells('A3:H3');
    $sheet->getStyle('A3')->getFont()->setItalic(true);


    $row = 5;

    // Obtener horarios
    $stmt = $conexion->prepare("
        SELECT 
            h.nombre_horario,
            h.descripcion,
            h.dia_semana,
            h.hora_inicio,
            h.hora_fin,
            m.materia,
            t.trimestre,
            CONCAT(u.nombres, ' ', u.apellidos) as instructor
        FROM horario h
        LEFT JOIN materia_ficha mf ON h.id_materia_ficha = mf.id_materia_ficha
        LEFT JOIN materias m ON mf.id_materia = m.id_materia
        LEFT JOIN trimestre t ON h.id_trimestre = t.id_trimestre
        LEFT JOIN usuarios u ON mf.id_instructor = u.id
        WHERE h.id_ficha = ?
        ORDER BY 
            CASE h.dia_semana 
                WHEN 'Lunes' THEN 1 
                WHEN 'Martes' THEN 2 
                WHEN 'Miércoles' THEN 3 
                WHEN 'Jueves' THEN 4 
                WHEN 'Viernes' THEN 5 
                WHEN 'Sábado' THEN 6 
            END, h.hora_inicio
    ");
    $stmt->execute([$ficha['id_ficha']]);
    $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Encabezados
    $headers = ['Materia', 'Descripción', 'Trimestre', 'Instructor Actual', 'Correo', 'Estado', 'Instructor Anterior', 'Historial de Cambios'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . $row, $header);
        $col++;
    }
    $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray($subHeaderStyle);  // Aplicamos estilo a todas las columnas
    $row++;


    // Datos de horarios
    foreach ($horarios as $horario) {
        $sheet->setCellValue('A' . $row, $horario['dia_semana'] ?? '-');
        $sheet->setCellValue('B' . $row, $horario['hora_inicio'] ? date('H:i', strtotime($horario['hora_inicio'])) : '-');
        $sheet->setCellValue('C' . $row, $horario['hora_fin'] ? date('H:i', strtotime($horario['hora_fin'])) : '-');
        $sheet->setCellValue('D' . $row, $horario['materia'] ?? 'Sin materia');
        $sheet->setCellValue('E' . $row, $horario['instructor'] ?? 'Sin instructor');
        $sheet->setCellValue('F' . $row, $horario['trimestre'] ?? 'Sin trimestre');
        $sheet->setCellValue('G' . $row, $horario['descripcion'] ?? '');

        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($dataStyle);
        $row++;
    }

    // Ajustar ancho de columnas
    foreach (range('A', 'G') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Generar nombre del archivo
    $fecha = date('Y-m-d_H-i-s');
    $nombreArchivo = "Horarios_Ficha_{$ficha['id_ficha']}_{$fecha}.xlsx";

    // ENVIAR DIRECTAMENTE AL NAVEGADOR
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

function generarReporteAprendices($conexion, $ficha, $spreadsheet, $sheet, $headerStyle, $subHeaderStyle, $labelStyle, $dataStyle)
{
    $sheet->setTitle('Aprendices');

    // Título principal
    $sheet->setCellValue('A1', 'APRENDICES ASIGNADOS - FICHA ' . $ficha['id_ficha']);
    $sheet->mergeCells('A1:G1');
    $sheet->getStyle('A1')->applyFromArray($headerStyle);
    $sheet->getRowDimension(1)->setRowHeight(30);

    $sheet->setCellValue('A2', 'Programa: ' . $ficha['programa']);
    $sheet->mergeCells('A2:G2');
    $sheet->getStyle('A2')->getFont()->setBold(true);

    $sheet->setCellValue('A3', 'Fecha de generación: ' . date('d/m/Y H:i:s'));
    $sheet->mergeCells('A3:G3');
    $sheet->getStyle('A3')->getFont()->setItalic(true);

    $row = 5;

    // Obtener aprendices
    $stmt = $conexion->prepare("
        SELECT 
            u.id,
            td.tipo_doc,
            CONCAT(u.nombres, ' ', u.apellidos) as nombre_completo,
            u.nombres,
            u.apellidos,
            u.correo,
            u.telefono,
            uf.fecha_asig,
            e.estado,
            u.fecha_registro
        FROM user_ficha uf
        JOIN usuarios u ON uf.id_user = u.id
        LEFT JOIN estado e ON uf.id_estado = e.id_estado
        LEFT JOIN tipo_documento td ON u.id_tipo = td.id_tipo
        WHERE uf.id_ficha = ? AND uf.id_estado = 1
        ORDER BY u.nombres, u.apellidos
    ");
    $stmt->execute([$ficha['id_ficha']]);
    $aprendices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Encabezados
    $headers = ['Documento', 'Tipo Doc', 'Nombres', 'Apellidos', 'Correo', 'Teléfono', 'Fecha Asignación', 'Estado', 'Fecha Registro'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . $row, $header);
        $col++;
    }
    $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray($subHeaderStyle);
    $row++;

    // Datos de aprendices
    foreach ($aprendices as $aprendiz) {
        $sheet->setCellValue('A' . $row, $aprendiz['id']);
        $sheet->setCellValue('B' . $row, $aprendiz['tipo_doc'] ?? 'CC');
        $sheet->setCellValue('C' . $row, $aprendiz['nombres']);
        $sheet->setCellValue('D' . $row, $aprendiz['apellidos']);
        $sheet->setCellValue('E' . $row, $aprendiz['correo']);
        $sheet->setCellValue('F' . $row, $aprendiz['telefono'] ?? 'No registrado');
        $sheet->setCellValue('G' . $row, date('d/m/Y', strtotime($aprendiz['fecha_asig'])));
        $sheet->setCellValue('H' . $row, $aprendiz['estado']);
        $sheet->setCellValue('I' . $row, date('d/m/Y', strtotime($aprendiz['fecha_registro'])));

        $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray($dataStyle);
        $row++;
    }

    // Ajustar ancho de columnas
    foreach (range('A', 'I') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Generar nombre del archivo
    $fecha = date('Y-m-d_H-i-s');
    $nombreArchivo = "Aprendices_Ficha_{$ficha['id_ficha']}_{$fecha}.xlsx";

    // ENVIAR DIRECTAMENTE AL NAVEGADOR
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

function generarReporteCompleto($conexion, $ficha, $spreadsheet, $sheet, $headerStyle, $subHeaderStyle, $labelStyle, $dataStyle)
{
    $sheet->setTitle('Reporte Completo');

    // Título principal
    $sheet->setCellValue('A1', 'REPORTE COMPLETO - FICHA ' . $ficha['id_ficha']);
    $sheet->mergeCells('A1:F1');
    $sheet->getStyle('A1')->applyFromArray($headerStyle);
    $sheet->getRowDimension(1)->setRowHeight(30);

    $sheet->setCellValue('A2', 'Fecha de generación: ' . date('d/m/Y H:i:s'));
    $sheet->mergeCells('A2:F2');
    $sheet->getStyle('A2')->getFont()->setItalic(true);

    $row = 4;

    // Sección: Información de la Ficha
    $sheet->setCellValue('A' . $row, 'INFORMACIÓN DE LA FICHA');
    $sheet->mergeCells('A' . $row . ':F' . $row);
    $sheet->getStyle('A' . $row)->applyFromArray($subHeaderStyle);
    $row++;

    $infoFicha = [
        ['Número de Ficha:', $ficha['id_ficha']],
        ['Programa:', $ficha['programa']],
        ['Tipo de Formación:', $ficha['tipo_formacion']],
        ['Duración:', $ficha['Duracion']],
        ['Jornada:', $ficha['jornada']],
        ['Estado:', $ficha['estado']],
        ['Fecha de Creación:', date('d/m/Y', strtotime($ficha['fecha_creac']))],
        ['Instructor Líder:', $ficha['instructor_lider'] ?? 'Sin asignar']
    ];

    foreach ($infoFicha as $info) {
        $sheet->setCellValue('A' . $row, $info[0]);
        $sheet->setCellValue('B' . $row, $info[1]);
        $sheet->getStyle('A' . $row)->applyFromArray($labelStyle);
        $sheet->getStyle('B' . $row)->applyFromArray($dataStyle);
        $row++;
    }

    $row += 2;

    // Sección: Resumen de Materias
    $stmt = $conexion->prepare("
        SELECT COUNT(*) as total_materias,
               COUNT(CASE WHEN mf.id_instructor IS NOT NULL THEN 1 END) as materias_con_instructor,
               COUNT(CASE WHEN mf.id_estado = 1 THEN 1 END) as materias_activas
        FROM materia_ficha mf WHERE mf.id_ficha = ?
    ");
    $stmt->execute([$ficha['id_ficha']]);
    $resumenMaterias = $stmt->fetch(PDO::FETCH_ASSOC);

    $sheet->setCellValue('A' . $row, 'RESUMEN DE MATERIAS');
    $sheet->mergeCells('A' . $row . ':F' . $row);
    $sheet->getStyle('A' . $row)->applyFromArray($subHeaderStyle);
    $row++;

    $resumenData = [
        ['Total de Materias:', $resumenMaterias['total_materias']],
        ['Materias con Instructor:', $resumenMaterias['materias_con_instructor']],
        ['Materias Activas:', $resumenMaterias['materias_activas']]
    ];

    foreach ($resumenData as $data) {
        $sheet->setCellValue('A' . $row, $data[0]);
        $sheet->setCellValue('B' . $row, $data[1]);
        $sheet->getStyle('A' . $row)->applyFromArray($labelStyle);
        $sheet->getStyle('B' . $row)->applyFromArray($dataStyle);
        $row++;
    }

    $row += 2;

    // Sección: Resumen de Aprendices
    $stmt = $conexion->prepare("
        SELECT COUNT(*) as total_aprendices
        FROM user_ficha uf WHERE uf.id_ficha = ? AND uf.id_estado = 1
    ");
    $stmt->execute([$ficha['id_ficha']]);
    $resumenAprendices = $stmt->fetch(PDO::FETCH_ASSOC);

    $sheet->setCellValue('A' . $row, 'RESUMEN DE APRENDICES');
    $sheet->mergeCells('A' . $row . ':F' . $row);
    $sheet->getStyle('A' . $row)->applyFromArray($subHeaderStyle);
    $row++;

    $sheet->setCellValue('A' . $row, 'Total de Aprendices:');
    $sheet->setCellValue('B' . $row, $resumenAprendices['total_aprendices']);
    $sheet->getStyle('A' . $row)->applyFromArray($labelStyle);
    $sheet->getStyle('B' . $row)->applyFromArray($dataStyle);

    // Ajustar ancho de columnas
    $sheet->getColumnDimension('A')->setWidth(25);
    $sheet->getColumnDimension('B')->setWidth(30);
    foreach (range('C', 'F') as $col) {
        $sheet->getColumnDimension($col)->setWidth(15);
    }

    // Generar nombre del archivo
    $fecha = date('Y-m-d_H-i-s');
    $nombreArchivo = "Reporte_Completo_Ficha_{$ficha['id_ficha']}_{$fecha}.xlsx";

    // ENVIAR DIRECTAMENTE AL NAVEGADOR
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

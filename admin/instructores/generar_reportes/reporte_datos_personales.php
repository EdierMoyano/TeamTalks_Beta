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
            e.estado,
            td.tipo_doc
        FROM usuarios u
        LEFT JOIN roles r ON u.id_rol = r.id_rol
        LEFT JOIN estado e ON u.id_estado = e.id_estado
        LEFT JOIN tipo_documento td ON u.id_tipo = td.id_tipo
        WHERE u.id = ? AND u.id_rol IN (3, 5)
    ");
    $stmt->execute([$id_instructor]);
    $instructor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$instructor) {
        throw new Exception('Instructor no encontrado');
    }

    // Obtener materias especializadas
    $stmt = $conexion->prepare("
        SELECT m.materia, m.descripcion
        FROM materia_instructor mi
        INNER JOIN materias m ON mi.id_materia = m.id_materia
        WHERE mi.id_instructor = ?
        ORDER BY m.materia
    ");
    $stmt->execute([$id_instructor]);
    $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Crear el archivo Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Datos Personales');

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

    // Título principal
    $sheet->setCellValue('A1', 'REPORTE DE DATOS PERSONALES - INSTRUCTOR');
    $sheet->mergeCells('A1:D1');
    $sheet->getStyle('A1')->applyFromArray($headerStyle);
    $sheet->getRowDimension(1)->setRowHeight(30);

    // Información de generación
    $sheet->setCellValue('A2', 'Fecha de generación: ' . date('d/m/Y H:i:s'));
    $sheet->mergeCells('A2:D2');
    $sheet->getStyle('A2')->getFont()->setItalic(true);

    $row = 4;

    // Sección: Información Personal
    $sheet->setCellValue('A' . $row, 'INFORMACIÓN PERSONAL');
    $sheet->mergeCells('A' . $row . ':D' . $row);
    $sheet->getStyle('A' . $row)->applyFromArray($subHeaderStyle);
    $row++;

    $datosPersonales = [
        ['Tipo de Documento:', $instructor['tipo_doc'] ?? 'Cédula'],
        ['Número de Documento:', $instructor['id']],
        ['Nombres:', $instructor['nombres']],
        ['Apellidos:', $instructor['apellidos']],
        ['Nombre Completo:', $instructor['nombres'] . ' ' . $instructor['apellidos']]
    ];

    foreach ($datosPersonales as $dato) {
        $sheet->setCellValue('A' . $row, $dato[0]);
        $sheet->setCellValue('B' . $row, $dato[1]);
        $sheet->getStyle('A' . $row)->applyFromArray($labelStyle);
        $sheet->getStyle('B' . $row)->applyFromArray($dataStyle);
        $row++;
    }

    $row++;

    // Sección: Información de Contacto
    $sheet->setCellValue('A' . $row, 'INFORMACIÓN DE CONTACTO');
    $sheet->mergeCells('A' . $row . ':D' . $row);
    $sheet->getStyle('A' . $row)->applyFromArray($subHeaderStyle);
    $row++;

    $datosContacto = [
        ['Correo Electrónico:', $instructor['correo']],
        ['Teléfono:', $instructor['telefono'] ?? 'No registrado'],
        ['Estado de Contacto:', $instructor['estado']]
    ];

    foreach ($datosContacto as $dato) {
        $sheet->setCellValue('A' . $row, $dato[0]);
        $sheet->setCellValue('B' . $row, $dato[1]);
        $sheet->getStyle('A' . $row)->applyFromArray($labelStyle);
        $sheet->getStyle('B' . $row)->applyFromArray($dataStyle);
        $row++;
    }

    $row++;

    // Sección: Información Institucional
    $sheet->setCellValue('A' . $row, 'INFORMACIÓN INSTITUCIONAL');
    $sheet->mergeCells('A' . $row . ':D' . $row);
    $sheet->getStyle('A' . $row)->applyFromArray($subHeaderStyle);
    $row++;

    $datosInstitucionales = [
        ['Tipo de Instructor:', $instructor['rol']],
        ['Estado en el Sistema:', $instructor['estado']],
        ['Fecha de Registro:', date('d/m/Y', strtotime($instructor['fecha_registro']))]
    ];

    foreach ($datosInstitucionales as $dato) {
        $sheet->setCellValue('A' . $row, $dato[0]);
        $sheet->setCellValue('B' . $row, $dato[1]);
        $sheet->getStyle('A' . $row)->applyFromArray($labelStyle);
        $sheet->getStyle('B' . $row)->applyFromArray($dataStyle);
        $row++;
    }

    $row++;

    // Sección: Materias Especializadas
    $sheet->setCellValue('A' . $row, 'MATERIAS ESPECIALIZADAS');
    $sheet->mergeCells('A' . $row . ':D' . $row);
    $sheet->getStyle('A' . $row)->applyFromArray($subHeaderStyle);
    $row++;

    if (!empty($materias)) {
        foreach ($materias as $index => $materia) {
            $sheet->setCellValue('A' . $row, 'Materia ' . ($index + 1) . ':');
            $sheet->setCellValue('B' . $row, $materia['materia']);
            $sheet->getStyle('A' . $row)->applyFromArray($labelStyle);
            $sheet->getStyle('B' . $row)->applyFromArray($dataStyle);
            $row++;

            if (!empty($materia['descripcion'])) {
                $sheet->setCellValue('A' . $row, 'Descripción:');
                $sheet->setCellValue('B' . $row, $materia['descripcion']);
                $sheet->getStyle('A' . $row)->applyFromArray($labelStyle);
                $sheet->getStyle('B' . $row)->applyFromArray($dataStyle);
                $row++;
            }
            $row++;
        }
    } else {
        $sheet->setCellValue('A' . $row, 'Sin materias especializadas asignadas');
        $sheet->mergeCells('A' . $row . ':D' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray($dataStyle);
        $row++;
    }

    // Ajustar ancho de columnas
    $sheet->getColumnDimension('A')->setWidth(25);
    $sheet->getColumnDimension('B')->setWidth(40);
    $sheet->getColumnDimension('C')->setWidth(15);
    $sheet->getColumnDimension('D')->setWidth(15);

    // Generar nombre del archivo
    $nombreInstructor = str_replace(' ', '_', $instructor['nombres'] . '_' . $instructor['apellidos']);
    $fecha = date('Y-m-d_H-i-s');
    $nombreArchivo = "Reporte_Datos_Personales_{$nombreInstructor}_{$fecha}.xlsx";

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

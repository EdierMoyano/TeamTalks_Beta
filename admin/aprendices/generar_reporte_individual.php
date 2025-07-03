<?php
session_start();

// Verificar sesión y rol de administrador
if (!isset($_SESSION['documento']) || $_SESSION['rol'] !== 2) {
    header('Location: ../../includes/exit.php');
    exit;
}

require_once '../../conexion/conexion.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Crear instancia de la conexión
$db = new Database();
$conexion = $db->connect();

if (!$conexion || !($conexion instanceof PDO)) {
    die("Error: No se pudo establecer la conexión a la base de datos");
}

// Obtener NIT del usuario logueado
$nit_usuario = '';
try {
    $stmt = $conexion->prepare("SELECT nit FROM usuarios WHERE id = ? AND id_estado = 1");
    $stmt->execute([$_SESSION['documento']]);
    $usuario_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario_data && !empty($usuario_data['nit'])) {
        $nit_usuario = $usuario_data['nit'];
    } else {
        die("Error: No se pudo obtener el NIT del usuario.");
    }
} catch (PDOException $e) {
    die("Error al obtener datos del usuario: " . $e->getMessage());
}

$id_aprendiz = $_GET['id_aprendiz'] ?? '';

if (empty($id_aprendiz)) {
    die("ID de aprendiz requerido");
}

try {
    // Obtener datos completos del aprendiz
    $stmt = $conexion->prepare("
        SELECT 
            u.id as documento,
            u.nombres,
            u.apellidos,
            u.correo,
            u.telefono,
            u.fecha_registro,
            e.estado,
            uf.id_ficha,
            f.id_ficha as ficha_numero,
            fo.nombre as programa_formacion,
            tf.tipo_formacion,
            j.jornada,
            COALESCE(AVG(au.nota), 0) as promedio_general,
            COUNT(au.id_actividad_user) as total_actividades,
            SUM(CASE WHEN au.nota >= 4.0 THEN 1 ELSE 0 END) as actividades_aprobadas,
            CASE 
                WHEN COUNT(au.id_actividad_user) > 0 
                THEN ROUND((SUM(CASE WHEN au.nota >= 4.0 THEN 1 ELSE 0 END) * 100.0 / COUNT(au.id_actividad_user)), 2)
                ELSE 0 
            END as porcentaje_aprobacion,
            CASE 
                WHEN AVG(au.nota) >= 4.0 THEN 'APROBADO'
                WHEN AVG(au.nota) IS NULL THEN 'SIN CALIFICAR'
                ELSE 'REPROBADO'
            END as estado_academico
        FROM usuarios u
        LEFT JOIN user_ficha uf ON u.id = uf.id_user AND uf.id_estado = 1
        LEFT JOIN fichas f ON uf.id_ficha = f.id_ficha
        LEFT JOIN formacion fo ON f.id_formacion = fo.id_formacion
        LEFT JOIN tipo_formacion tf ON fo.id_tipo_formacion = tf.id_tipo_formacion
        LEFT JOIN jornada j ON f.id_jornada = j.id_jornada
        LEFT JOIN estado e ON u.id_estado = e.id_estado
        LEFT JOIN actividades_user au ON u.id = au.id_user AND au.nota IS NOT NULL
        WHERE u.id = ? AND u.id_rol = 4 AND u.nit = ?
        GROUP BY u.id, u.nombres, u.apellidos, u.correo, u.telefono, u.fecha_registro, 
                u.id_estado, e.estado, uf.id_ficha, f.id_ficha, fo.nombre, tf.tipo_formacion, j.jornada
    ");
    $stmt->execute([$id_aprendiz, $nit_usuario]);
    $aprendiz = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$aprendiz) {
        die("Aprendiz no encontrado o no tiene permisos para acceder a este registro");
    }

    // Obtener notas por trimestre
    $stmt = $conexion->prepare("
        SELECT 
            t.trimestre,
            t.id_trimestre,
            AVG(au.nota) as promedio_trimestre,
            COUNT(au.id_actividad_user) as total_actividades_trimestre,
            SUM(CASE WHEN au.nota >= 4.0 THEN 1 ELSE 0 END) as actividades_aprobadas_trimestre,
            CASE 
                WHEN AVG(au.nota) >= 4.0 THEN 'APROBADO'
                WHEN AVG(au.nota) IS NULL THEN 'SIN CALIFICAR'
                ELSE 'REPROBADO'
            END as estado_trimestre
        FROM trimestre t
        LEFT JOIN materia_ficha mf ON t.id_trimestre = mf.id_trimestre AND mf.id_ficha = ?
        LEFT JOIN actividades a ON mf.id_materia_ficha = a.id_materia_ficha
        LEFT JOIN actividades_user au ON a.id_actividad = au.id_actividad AND au.id_user = ?
        GROUP BY t.id_trimestre, t.trimestre
        ORDER BY t.id_trimestre
    ");
    $stmt->execute([$aprendiz['id_ficha'], $id_aprendiz]);
    $trimestres = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener todas las actividades del aprendiz
    $stmt = $conexion->prepare("
        SELECT 
            a.titulo,
            a.fecha_entrega,
            au.nota,
            au.fecha_entrega as fecha_entrega_estudiante,
            au.comentario_inst,
            m.materia,
            t.trimestre,
            CASE 
                WHEN au.nota IS NULL THEN 'SIN CALIFICAR'
                WHEN au.nota >= 4.0 THEN 'APROBADO'
                ELSE 'REPROBADO'
            END as estado_actividad
        FROM actividades a
        LEFT JOIN actividades_user au ON a.id_actividad = au.id_actividad AND au.id_user = ?
        LEFT JOIN materia_ficha mf ON a.id_materia_ficha = mf.id_materia_ficha
        LEFT JOIN materias m ON mf.id_materia = m.id_materia
        LEFT JOIN trimestre t ON mf.id_trimestre = t.id_trimestre
        WHERE mf.id_ficha = ?
        ORDER BY t.id_trimestre, m.materia, a.fecha_entrega
    ");
    $stmt->execute([$id_aprendiz, $aprendiz['id_ficha']]);
    $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Crear nuevo Spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Reporte Individual');

    // Configurar encabezado principal
    $sheet->setCellValue('A1', 'REPORTE INDIVIDUAL - TEAMTALKS');
    $sheet->mergeCells('A1:H1');
    $sheet->getStyle('A1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0e4a86']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
    ]);

    $sheet->setCellValue('A2', 'Generado el: ' . date('d/m/Y H:i:s'));
    $sheet->mergeCells('A2:H2');
    $sheet->getStyle('A2')->applyFromArray([
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'f8f9fa']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
    ]);

    $row = 4;

    // Información personal
    $sheet->setCellValue('A' . $row, 'INFORMACIÓN PERSONAL');
    $sheet->mergeCells('A' . $row . ':H' . $row);
    $sheet->getStyle('A' . $row)->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '28a745']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
    ]);
    $row++;

    $sheet->setCellValue('A' . $row, 'Documento:');
    $sheet->setCellValue('B' . $row, $aprendiz['documento']);
    $sheet->setCellValue('C' . $row, 'Nombres:');
    $sheet->setCellValue('D' . $row, $aprendiz['nombres']);
    $sheet->setCellValue('E' . $row, 'Apellidos:');
    $sheet->setCellValue('F' . $row, $aprendiz['apellidos']);
    $sheet->setCellValue('G' . $row, 'Estado:');
    $sheet->setCellValue('H' . $row, $aprendiz['estado']);
    $row++;

    $sheet->setCellValue('A' . $row, 'Correo:');
    $sheet->setCellValue('B' . $row, $aprendiz['correo']);
    $sheet->mergeCells('B' . $row . ':D' . $row);
    $sheet->setCellValue('E' . $row, 'Teléfono:');
    $sheet->setCellValue('F' . $row, $aprendiz['telefono'] ?? 'No registrado');
    $sheet->setCellValue('G' . $row, 'Fecha Registro:');
    $sheet->setCellValue('H' . $row, date('d/m/Y', strtotime($aprendiz['fecha_registro'])));
    $row++;

    if ($aprendiz['ficha_numero']) {
        $sheet->setCellValue('A' . $row, 'Ficha:');
        $sheet->setCellValue('B' . $row, $aprendiz['ficha_numero']);
        $sheet->setCellValue('C' . $row, 'Programa:');
        $sheet->setCellValue('D' . $row, $aprendiz['programa_formacion']);
        $sheet->mergeCells('D' . $row . ':F' . $row);
        $sheet->setCellValue('G' . $row, 'Jornada:');
        $sheet->setCellValue('H' . $row, $aprendiz['jornada']);
        $row++;
    }

    $row++;

    // Resumen académico
    $sheet->setCellValue('A' . $row, 'RESUMEN ACADÉMICO');
    $sheet->mergeCells('A' . $row . ':H' . $row);
    $sheet->getStyle('A' . $row)->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '17a2b8']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
    ]);
    $row++;

    $sheet->setCellValue('A' . $row, 'Promedio General:');
    $sheet->setCellValue('B' . $row, number_format($aprendiz['promedio_general'], 2));
    $sheet->setCellValue('C' . $row, 'Total Actividades:');
    $sheet->setCellValue('D' . $row, $aprendiz['total_actividades']);
    $sheet->setCellValue('E' . $row, 'Actividades Aprobadas:');
    $sheet->setCellValue('F' . $row, $aprendiz['actividades_aprobadas']);
    $sheet->setCellValue('G' . $row, '% Aprobación:');
    $sheet->setCellValue('H' . $row, number_format($aprendiz['porcentaje_aprobacion'], 2) . '%');
    $row++;

    $sheet->setCellValue('A' . $row, 'Estado Académico:');
    $sheet->setCellValue('B' . $row, $aprendiz['estado_academico']);
    $sheet->mergeCells('B' . $row . ':H' . $row);
    $row++;

    $row++;

    // Rendimiento por trimestre
    if (!empty($trimestres)) {
        $sheet->setCellValue('A' . $row, 'RENDIMIENTO POR TRIMESTRE');
        $sheet->mergeCells('A' . $row . ':H' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'ffc107']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        $row++;

        $headers = ['TRIMESTRE', 'PROMEDIO', 'TOTAL ACTIVIDADES', 'ACTIVIDADES APROBADAS', '% APROBACIÓN', 'ESTADO', 'OBSERVACIONES'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $col++;
        }
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'f8f9fa']]
        ]);
        $row++;

        foreach ($trimestres as $trimestre) {
            $porcentaje_trimestre = $trimestre['total_actividades_trimestre'] > 0 ? 
                round(($trimestre['actividades_aprobadas_trimestre'] / $trimestre['total_actividades_trimestre']) * 100, 2) : 0;
            
            $sheet->setCellValue('A' . $row, $trimestre['trimestre']);
            $sheet->setCellValue('B' . $row, $trimestre['promedio_trimestre'] ? number_format($trimestre['promedio_trimestre'], 2) : 'N/A');
            $sheet->setCellValue('C' . $row, $trimestre['total_actividades_trimestre']);
            $sheet->setCellValue('D' . $row, $trimestre['actividades_aprobadas_trimestre']);
            $sheet->setCellValue('E' . $row, $porcentaje_trimestre . '%');
            $sheet->setCellValue('F' . $row, $trimestre['estado_trimestre']);
            
            $observacion = ($trimestre['estado_trimestre'] === 'APROBADO') ? 'Rendimiento satisfactorio' : 
                          (($trimestre['estado_trimestre'] === 'SIN CALIFICAR') ? 'Pendiente de evaluación' : 'Requiere refuerzo');
            $sheet->setCellValue('G' . $row, $observacion);
            $row++;
        }
    }

    $row++;

    // Detalle de actividades
    if (!empty($actividades)) {
        $sheet->setCellValue('A' . $row, 'DETALLE DE ACTIVIDADES');
        $sheet->mergeCells('A' . $row . ':H' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6f42c1']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        $row++;

        $headers = ['TRIMESTRE', 'MATERIA', 'ACTIVIDAD', 'FECHA ENTREGA', 'FECHA ENTREGADO', 'NOTA', 'ESTADO', 'COMENTARIOS'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $col++;
        }
        $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'f8f9fa']]
        ]);
        $row++;

        foreach ($actividades as $actividad) {
            $sheet->setCellValue('A' . $row, $actividad['trimestre'] ?? 'N/A');
            $sheet->setCellValue('B' . $row, $actividad['materia'] ?? 'N/A');
            $sheet->setCellValue('C' . $row, $actividad['titulo']);
            $sheet->setCellValue('D' . $row, date('d/m/Y', strtotime($actividad['fecha_entrega'])));
            $sheet->setCellValue('E' . $row, $actividad['fecha_entrega_estudiante'] ? date('d/m/Y', strtotime($actividad['fecha_entrega_estudiante'])) : 'No entregado');
            $sheet->setCellValue('F' . $row, $actividad['nota'] ? number_format($actividad['nota'], 1) : 'N/A');
            $sheet->setCellValue('G' . $row, $actividad['estado_actividad']);
            $sheet->setCellValue('H' . $row, $actividad['comentario_inst'] ?? '');
            $row++;
        }
    }

    // Ajustar ancho de columnas
    foreach (range('A', 'H') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Aplicar bordes a toda la tabla
    $sheet->getStyle('A1:H' . ($row - 1))->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ]);

    // Configurar headers para descarga
    $nombre_archivo = "reporte_" . strtolower(str_replace(' ', '_', $aprendiz['nombres'] . '_' . $aprendiz['apellidos'])) . '_' . date('Y-m-d');
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $nombre_archivo . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');

} catch (PDOException $e) {
    die("Error al generar reporte: " . $e->getMessage());
}
?>

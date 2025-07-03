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
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

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
        die("Error: No se pudo obtener el NIT del usuario. Contacte al administrador.");
    }
} catch (PDOException $e) {
    die("Error al obtener datos del usuario: " . $e->getMessage());
}

// Obtener tipo de reporte
$tipo = isset($_POST['tipo']) ? $_POST['tipo'] : (isset($_GET['tipo']) ? $_GET['tipo'] : '');
$ficha = isset($_POST['ficha']) ? $_POST['ficha'] : (isset($_GET['ficha']) ? $_GET['ficha'] : '');

if (empty($tipo)) {
    die("Error: Tipo de reporte no especificado");
}

// Crear nuevo spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Configurar propiedades del documento
$spreadsheet->getProperties()
    ->setCreator("TeamTalks")
    ->setLastModifiedBy("TeamTalks")
    ->setTitle("Reporte de Aprendices")
    ->setSubject("Reporte de Aprendices")
    ->setDescription("Reporte generado automáticamente");

if ($tipo === 'general') {
    // Reporte general de todos los aprendices
    $sheet->setTitle('Reporte General Aprendices');
    
    // Encabezados
    $headers = [
        'Documento', 'Nombres', 'Apellidos', 'Correo', 'Teléfono', 'Estado',
        'Ficha', 'Programa Formación', 'Tipo Formación', 'Jornada',
        'Promedio General', 'Total Actividades', 'Actividades Aprobadas',
        'Porcentaje Aprobación', 'Fecha Registro'
    ];
    
    // Agregar encabezados para trimestres
    for ($i = 1; $i <= 4; $i++) {
        $headers[] = "Trimestre $i";
    }
    
    // Escribir encabezados
    $col = 1;
    foreach ($headers as $header) {
        $sheet->setCellValue(chr(64 + $col) . '1', $header);
        $col++;
    }
    
    // Consulta para obtener datos de aprendices
    try {
        $query = "
            SELECT 
                u.id,
                u.nombres,
                u.apellidos,
                u.correo,
                u.telefono,
                u.fecha_registro,
                e.estado,
                f.id_ficha,
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
                END as porcentaje_aprobacion
            FROM usuarios u
            LEFT JOIN user_ficha uf ON u.id = uf.id_user AND uf.id_estado = 1
            LEFT JOIN fichas f ON uf.id_ficha = f.id_ficha
            LEFT JOIN formacion fo ON f.id_formacion = fo.id_formacion
            LEFT JOIN tipo_formacion tf ON fo.id_tipo_formacion = tf.id_tipo_formacion
            LEFT JOIN jornada j ON f.id_jornada = j.id_jornada
            LEFT JOIN estado e ON u.id_estado = e.id_estado
            LEFT JOIN actividades_user au ON u.id = au.id_user AND au.nota IS NOT NULL
            WHERE u.id_rol = 4 AND u.nit = ?
            GROUP BY u.id, u.nombres, u.apellidos, u.correo, u.telefono, u.fecha_registro, 
                     e.estado, f.id_ficha, fo.nombre, tf.tipo_formacion, j.jornada
            ORDER BY u.nombres, u.apellidos
        ";
        
        $stmt = $conexion->prepare($query);
        $stmt->execute([$nit_usuario]);
        $aprendices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $row = 2;
        foreach ($aprendices as $aprendiz) {
            $col = 1;
            
            // Datos básicos
            $sheet->setCellValue(chr(64 + $col++) . $row, $aprendiz['id']);
            $sheet->setCellValue(chr(64 + $col++) . $row, $aprendiz['nombres']);
            $sheet->setCellValue(chr(64 + $col++) . $row, $aprendiz['apellidos']);
            $sheet->setCellValue(chr(64 + $col++) . $row, $aprendiz['correo']);
            $sheet->setCellValue(chr(64 + $col++) . $row, $aprendiz['telefono'] ?? 'No registrado');
            $sheet->setCellValue(chr(64 + $col++) . $row, $aprendiz['estado']);
            $sheet->setCellValue(chr(64 + $col++) . $row, $aprendiz['id_ficha'] ?? 'Sin asignar');
            $sheet->setCellValue(chr(64 + $col++) . $row, $aprendiz['programa_formacion'] ?? 'N/A');
            $sheet->setCellValue(chr(64 + $col++) . $row, $aprendiz['tipo_formacion'] ?? 'N/A');
            $sheet->setCellValue(chr(64 + $col++) . $row, $aprendiz['jornada'] ?? 'N/A');
            $sheet->setCellValue(chr(64 + $col++) . $row, number_format($aprendiz['promedio_general'], 2));
            $sheet->setCellValue(chr(64 + $col++) . $row, $aprendiz['total_actividades']);
            $sheet->setCellValue(chr(64 + $col++) . $row, $aprendiz['actividades_aprobadas']);
            $sheet->setCellValue(chr(64 + $col++) . $row, number_format($aprendiz['porcentaje_aprobacion'], 2) . '%');
            $sheet->setCellValue(chr(64 + $col++) . $row, $aprendiz['fecha_registro']);
            
            // Obtener promedios por trimestre
            $stmt_trimestre = $conexion->prepare("
                SELECT 
                    t.id_trimestre,
                    AVG(au.nota) as promedio,
                    COUNT(au.id_actividad_user) as total_actividades,
                    CASE 
                        WHEN AVG(au.nota) >= 4.0 THEN 'Aprobado'
                        WHEN AVG(au.nota) IS NULL THEN 'Sin actividades'
                        ELSE 'Desaprobado'
                    END as estado
                FROM trimestre t
                LEFT JOIN horario h ON t.id_trimestre = h.id_trimestre
                LEFT JOIN actividades a ON h.id_horario = a.id_horario
                LEFT JOIN actividades_user au ON a.id_actividad = au.id_actividad AND au.id_user = ?
                WHERE t.id_trimestre BETWEEN 1 AND 4
                GROUP BY t.id_trimestre
                ORDER BY t.id_trimestre
            ");
            $stmt_trimestre->execute([$aprendiz['id']]);
            $trimestres = $stmt_trimestre->fetchAll(PDO::FETCH_ASSOC);
            
            // Llenar datos de trimestres
            for ($i = 1; $i <= 4; $i++) {
                $trimestre_data = null;
                foreach ($trimestres as $trim) {
                    if ($trim['id_trimestre'] == $i) {
                        $trimestre_data = $trim;
                        break;
                    }
                }
                
                if ($trimestre_data && $trimestre_data['promedio'] !== null) {
                    $promedio = number_format($trimestre_data['promedio'], 2);
                    $estado = $trimestre_data['estado'];
                    $sheet->setCellValue(chr(64 + $col) . $row, $promedio . ' (' . $estado . ')');
                } else {
                    $sheet->setCellValue(chr(64 + $col) . $row, 'N/A');
                }
                $col++;
            }
            
            $row++;
        }
        
    } catch (PDOException $e) {
        die("Error al obtener datos: " . $e->getMessage());
    }
    
} elseif ($tipo === 'ficha' && !empty($ficha)) {
    // Reporte por ficha específica
    $sheet->setTitle("Reporte Ficha $ficha");
    
    // Encabezados
    $headers = [
        'Documento', 'Nombres', 'Apellidos', 'Correo', 'Teléfono', 'Estado',
        'Programa Formación', 'Tipo Formación', 'Jornada',
        'Promedio General', 'Total Actividades', 'Actividades Aprobadas',
        'Porcentaje Aprobación', 'Fecha Registro'
    ];
    
    // Agregar encabezados para trimestres
    for ($i = 1; $i <= 4; $i++) {
        $headers[] = "Trimestre $i";
    }
    
    // Escribir encabezados
    $col = 1;
    foreach ($headers as $header) {
        $sheet->setCellValue(chr(64 + $col) . '1', $header);
        $col++;
    }
    
    // Consulta para obtener datos de aprendices de la ficha específica
    try {
        $query = "
            SELECT 
                u.id,
                u.nombres,
                u.apellidos,
                u.correo,
                u.telefono,
                u.fecha_registro,
                e.estado,
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
                END as porcentaje_aprobacion
            FROM usuarios u
            INNER JOIN user_ficha uf ON u.id = uf.id_user AND uf.id_estado = 1
            INNER JOIN fichas f ON uf.id_ficha = f.id_ficha
            LEFT JOIN formacion fo ON f.id_formacion = fo.id_formacion
            LEFT JOIN tipo_formacion tf ON fo.id_tipo_formacion = tf.id_tipo_formacion
            LEFT JOIN jornada j ON f.id_jornada = j.id_jornada
            LEFT JOIN estado e ON u.id_estado = e.id_estado
            LEFT JOIN actividades_user au ON u.id = au.id_user AND au.nota IS NOT NULL
            WHERE u.id_rol = 4 AND u.nit = ? AND f.id_ficha = ?
            GROUP BY u.id, u.nombres, u.apellidos, u.correo, u.telefono, u.fecha_registro, 
                     e.estado, fo.nombre, tf.tipo_formacion, j.jornada
            ORDER BY u.nombres, u.apellidos
        ";
        
        $stmt = $conexion->prepare($query);
        $stmt->execute([$nit_usuario, $ficha]);
        $aprendices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $row = 2;
        foreach ($aprendices as $aprendiz) {
            $col = 1;
            
            // Datos básicos
            $sheet->setCellValue(chr(64 + $col++) . $row, $aprendiz['id']);
            $sheet->setCellValue(chr(64 + $col++) . $row, $aprendiz['nombres']);
            $sheet->setCellValue(chr(64 + $col++) . $row, $aprendiz['apellidos']);
            $sheet->setCellValue(chr(64 + $col++) . $row, $aprendiz['correo']);
            $sheet->setCellValue(chr(64 + $col++) . $row, $aprendiz['telefono'] ?? 'No registrado');
            $sheet->setCellValue(chr(64 + $col++) . $row, $aprendiz['estado']);
            $sheet->setCellValue(chr(64 + $col++) . $row, $aprendiz['programa_formacion'] ?? 'N/A');
            $sheet->setCellValue(chr(64 + $col++) . $row, $aprendiz['tipo_formacion'] ?? 'N/A');
            $sheet->setCellValue(chr(64 + $col++) . $row, $aprendiz['jornada'] ?? 'N/A');
            $sheet->setCellValue(chr(64 + $col++) . $row, number_format($aprendiz['promedio_general'], 2));
            $sheet->setCellValue(chr(64 + $col++) . $row, $aprendiz['total_actividades']);
            $sheet->setCellValue(chr(64 + $col++) . $row, $aprendiz['actividades_aprobadas']);
            $sheet->setCellValue(chr(64 + $col++) . $row, number_format($aprendiz['porcentaje_aprobacion'], 2) . '%');
            $sheet->setCellValue(chr(64 + $col++) . $row, $aprendiz['fecha_registro']);
            
            // Obtener promedios por trimestre
            $stmt_trimestre = $conexion->prepare("
                SELECT 
                    t.id_trimestre,
                    AVG(au.nota) as promedio,
                    COUNT(au.id_actividad_user) as total_actividades,
                    CASE 
                        WHEN AVG(au.nota) >= 4.0 THEN 'Aprobado'
                        WHEN AVG(au.nota) IS NULL THEN 'Sin actividades'
                        ELSE 'Desaprobado'
                    END as estado
                FROM trimestre t
                LEFT JOIN horario h ON t.id_trimestre = h.id_trimestre
                LEFT JOIN actividades a ON h.id_horario = a.id_horario
                LEFT JOIN actividades_user au ON a.id_actividad = au.id_actividad AND au.id_user = ?
                WHERE t.id_trimestre BETWEEN 1 AND 4
                GROUP BY t.id_trimestre
                ORDER BY t.id_trimestre
            ");
            $stmt_trimestre->execute([$aprendiz['id']]);
            $trimestres = $stmt_trimestre->fetchAll(PDO::FETCH_ASSOC);
            
            // Llenar datos de trimestres
            for ($i = 1; $i <= 4; $i++) {
                $trimestre_data = null;
                foreach ($trimestres as $trim) {
                    if ($trim['id_trimestre'] == $i) {
                        $trimestre_data = $trim;
                        break;
                    }
                }
                
                if ($trimestre_data && $trimestre_data['promedio'] !== null) {
                    $promedio = number_format($trimestre_data['promedio'], 2);
                    $estado = $trimestre_data['estado'];
                    $sheet->setCellValue(chr(64 + $col) . $row, $promedio . ' (' . $estado . ')');
                } else {
                    $sheet->setCellValue(chr(64 + $col) . $row, 'N/A');
                }
                $col++;
            }
            
            $row++;
        }
        
    } catch (PDOException $e) {
        die("Error al obtener datos: " . $e->getMessage());
    }
    
} else {
    die("Error: Parámetros de reporte inválidos");
}

// Aplicar estilos
$lastColumn = chr(64 + count($headers));
$lastRow = $row - 1;

// Estilo para encabezados
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];

$sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray($headerStyle);

// Estilo para datos
$dataStyle = [
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]
];

if ($lastRow > 1) {
    $sheet->getStyle('A2:' . $lastColumn . $lastRow)->applyFromArray($dataStyle);
}

// Ajustar ancho de columnas
foreach (range('A', $lastColumn) as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Configurar headers para descarga
$filename = ($tipo === 'general') ? 'reporte_general_aprendices' : "reporte_ficha_{$ficha}_aprendices";
$filename .= '_' . date('Y-m-d_H-i-s') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Crear writer y enviar archivo
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

// Limpiar memoria
$spreadsheet->disconnectWorksheets();
unset($spreadsheet);
exit;
?>

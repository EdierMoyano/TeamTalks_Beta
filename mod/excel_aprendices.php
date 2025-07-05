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

// Verificar permisos
if ($_SESSION['rol'] !== 3 && $_SESSION['rol'] !== 5) {
    header('Location:' . BASE_URL . '/includes/exit.php?motivo=acceso-denegado');
    exit;
}

// Obtener parámetros del formulario
$id_ficha = isset($_POST['id_ficha']) ? (int)$_POST['id_ficha'] : 0;
$id_instructor = isset($_SESSION['documento']) ? (int)$_SESSION['documento'] : 0;
$fecha_desde = isset($_POST['fecha_desde']) && !empty($_POST['fecha_desde']) ? $_POST['fecha_desde'] : null;
$fecha_hasta = isset($_POST['fecha_hasta']) && !empty($_POST['fecha_hasta']) ? $_POST['fecha_hasta'] : null;
$estados = isset($_POST['estados']) && is_array($_POST['estados']) ? array_map('intval', $_POST['estados']) : [];
$tipo_reporte = isset($_POST['tipo_reporte']) ? $_POST['tipo_reporte'] : 'resumen';
$materia_filtro = isset($_POST['materia_filtro']) && !empty($_POST['materia_filtro']) ? (int)$_POST['materia_filtro'] : null;
$orden = isset($_POST['orden']) ? $_POST['orden'] : 'apellidos';

// Validar parámetros obligatorios
if (!$id_ficha) {
    die("Error: ID de ficha no válido.");
}

// Validar orden
$ordenes_validos = ['nombres', 'apellidos', 'documento', 'actividades_pendientes'];
if (!in_array($orden, $ordenes_validos)) {
    $orden = 'apellidos';
}

// Estados disponibles
$estados_nombres = [
    3 => 'Aprobado',
    4 => 'Desaprobado',
    8 => 'Entregado',
    9 => 'Pendiente',
    10 => 'No entregado'
];

// Verificar si es instructor principal de la ficha
$sql_instructor = "SELECT COUNT(*) FROM fichas WHERE id_ficha = :id_ficha AND id_instructor = :id_instructor";
$stmt_instructor = $conex->prepare($sql_instructor);
$stmt_instructor->execute(['id_ficha' => $id_ficha, 'id_instructor' => $id_instructor]);
$es_instructor_principal = $stmt_instructor->fetchColumn() > 0;

// Obtener información de la ficha
$sql_ficha = "
    SELECT 
        f.id_ficha, 
        fo.nombre AS formacion, 
        f.fecha_creac,
        f.id_trimestre
    FROM fichas f
    INNER JOIN formacion fo ON f.id_formacion = fo.id_formacion
    WHERE f.id_ficha = :id_ficha
";

$stmt_ficha = $conex->prepare($sql_ficha);
$stmt_ficha->execute(['id_ficha' => $id_ficha]);
$info_ficha = $stmt_ficha->fetch(PDO::FETCH_ASSOC);

if (!$info_ficha) {
    die("Error: No se encontró información de la ficha.");
}

// Obtener información del trimestre por separado
$sql_trimestre = "SELECT trimestre FROM trimestre WHERE id_trimestre = :id_trimestre";
$stmt_trimestre = $conex->prepare($sql_trimestre);
$stmt_trimestre->execute(['id_trimestre' => $info_ficha['id_trimestre']]);
$trimestre_info = $stmt_trimestre->fetch(PDO::FETCH_ASSOC);
$info_ficha['trimestre'] = $trimestre_info ? $trimestre_info['trimestre'] : 'N/A';

// PASO 1: Obtener TODOS los aprendices de la ficha (consulta simple)
$sql_aprendices_base = "
    SELECT DISTINCT
        u.id AS documento,
        u.nombres,
        u.apellidos,
        u.telefono,
        u.correo,
        e.estado,
        u.fecha_registro
    FROM user_ficha uf
    INNER JOIN usuarios u ON uf.id_user = u.id
    INNER JOIN estado e ON u.id_estado = e.id_estado
    WHERE uf.id_ficha = :id_ficha
";

$stmt_aprendices_base = $conex->prepare($sql_aprendices_base);
$stmt_aprendices_base->execute(['id_ficha' => $id_ficha]);
$todos_aprendices = $stmt_aprendices_base->fetchAll(PDO::FETCH_ASSOC);

if (empty($todos_aprendices)) {
    die("Error: No se encontraron aprendices para esta ficha.");
}

// PASO 2: NO filtrar aprendices, solo usar todos los aprendices de la ficha
// Los filtros de actividades se aplicarán solo al mostrar las actividades, no para excluir aprendices
$aprendices_filtrados = $todos_aprendices;

// Debug: Log para verificar
error_log("Total aprendices encontrados: " . count($todos_aprendices));
error_log("Estados seleccionados: " . implode(', ', $estados));
error_log("Tipo de reporte: " . $tipo_reporte);

// PASO 3: Aplicar ordenamiento
if ($orden === 'actividades_pendientes') {
    // Ordenar por cantidad de actividades pendientes
    usort($aprendices_filtrados, function($a, $b) use ($conex, $id_ficha, $es_instructor_principal, $id_instructor) {
        $sql_pendientes = "
            SELECT COUNT(*) 
            FROM actividades_user au
            INNER JOIN actividades a ON au.id_actividad = a.id_actividad
            INNER JOIN materia_ficha mf ON a.id_materia_ficha = mf.id_materia_ficha
            WHERE au.id_user = :id_user 
            AND mf.id_ficha = :id_ficha
            AND au.id_estado_actividad = 9
        ";
        
        $params_base = ['id_user' => 0, 'id_ficha' => $id_ficha];
        if (!$es_instructor_principal) {
            $sql_pendientes .= " AND mf.id_instructor = :id_instructor";
            $params_base['id_instructor'] = $id_instructor;
        }
        
        $stmt = $conex->prepare($sql_pendientes);
        
        $params_a = $params_base;
        $params_a['id_user'] = $a['documento'];
        $stmt->execute($params_a);
        $pendientes_a = $stmt->fetchColumn();
        
        $params_b = $params_base;
        $params_b['id_user'] = $b['documento'];
        $stmt->execute($params_b);
        $pendientes_b = $stmt->fetchColumn();
        
        return $pendientes_b - $pendientes_a; // Descendente (más pendientes primero)
    });
} else {
    // Ordenamiento normal
    usort($aprendices_filtrados, function($a, $b) use ($orden) {
        switch ($orden) {
            case 'nombres':
                return strcasecmp($a['nombres'], $b['nombres']);
            case 'documento':
                return (int)$a['documento'] - (int)$b['documento'];
            default: // apellidos
                return strcasecmp($a['apellidos'], $b['apellidos']);
        }
    });
}

$aprendices = $aprendices_filtrados;

if (empty($aprendices)) {
    die("Error: No se encontraron aprendices que cumplan con los filtros aplicados.");
}

// Crear el archivo Excel
try {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Configurar propiedades del documento
    $spreadsheet->getProperties()
        ->setCreator("TeamTalks")
        ->setTitle("Reporte de Ficha " . $info_ficha['id_ficha'])
        ->setSubject("Reporte Académico")
        ->setDescription("Reporte generado por TeamTalks");

    // Título del reporte
    $titulo_reporte = "Reporte de Ficha N° " . $info_ficha['id_ficha'];
    
    switch ($tipo_reporte) {
        case 'resumen':
            $titulo_reporte .= " - Resumen Ejecutivo";
            break;
        case 'solo_pendientes':
            $titulo_reporte .= " - Solo Pendientes";
            break;
        case 'por_estado':
            $titulo_reporte .= " - Por Estado";
            break;
        case 'completo':
            $titulo_reporte .= " - Reporte Completo";
            break;
    }

    // Configurar título principal
    $sheet->setCellValue('A1', $titulo_reporte);
    
    if ($tipo_reporte === 'resumen') {
        $sheet->mergeCells('A1:G1');
        $lastCol = 'G';
    } else {
        $sheet->mergeCells('A1:H1');
        $lastCol = 'H';
    }

    $sheet->getStyle('A1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0E4A86']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '0E4A86']]]
    ]);
    $sheet->getRowDimension(1)->setRowHeight(35);

    // Información de la ficha
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
    $sheet->setCellValue('B' . $row, $info_ficha['formacion']);
    $sheet->setCellValue('D' . $row, "Trimestre:");
    $sheet->setCellValue('E' . $row, $info_ficha['trimestre']);

    $row++;
    $sheet->setCellValue('A' . $row, "Fecha de creación ficha:");
    $fecha_creacion = $info_ficha['fecha_creac'] ? date('d/m/Y', strtotime($info_ficha['fecha_creac'])) : 'N/A';
    $sheet->setCellValue('B' . $row, $fecha_creacion);
    $sheet->setCellValue('D' . $row, "Total aprendices:");
    $sheet->setCellValue('E' . $row, count($aprendices));

    $row++;
    $sheet->setCellValue('A' . $row, "Fecha de generación:");
    $sheet->setCellValue('B' . $row, date('d/m/Y H:i:s'));

    // Información de filtros aplicados
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
    $filtros_texto = "Tipo de reporte: " . ucfirst(str_replace('_', ' ', $tipo_reporte));
    
    $orden_texto = match($orden) {
        'nombres' => 'Nombres',
        'documento' => 'Documento',
        'actividades_pendientes' => 'Actividades Pendientes',
        default => 'Apellidos'
    };
    $filtros_texto .= " | Ordenado por: " . $orden_texto;
    
    if (!empty($estados)) {
        $estados_seleccionados = array_intersect_key($estados_nombres, array_flip($estados));
        $filtros_texto .= " | Estados: " . implode(', ', $estados_seleccionados);
    }
    
    if ($fecha_desde || $fecha_hasta) {
        $filtros_texto .= " | Fechas: ";
        if ($fecha_desde) $filtros_texto .= "Desde " . date('d/m/Y', strtotime($fecha_desde));
        if ($fecha_desde && $fecha_hasta) $filtros_texto .= " hasta ";
        if ($fecha_hasta) $filtros_texto .= date('d/m/Y', strtotime($fecha_hasta));
    }

    if ($materia_filtro) {
        $stmt_materia = $conex->prepare("SELECT materia FROM materias WHERE id_materia = :id");
        $stmt_materia->execute(['id' => $materia_filtro]);
        $materia_nombre = $stmt_materia->fetchColumn();
        if ($materia_nombre) {
            $filtros_texto .= " | Materia: " . $materia_nombre;
        }
    }

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
        $widths = [15, 20, 20, 15, 25, 15, 15];
    } else {
        $headers = ['Documento', 'Nombres', 'Apellidos', 'Teléfono', 'Correo', 'Estado', 'Trimestre', 'Actividades'];
        $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        $widths = [15, 20, 20, 15, 25, 15, 15, 50];
    }

    foreach ($headers as $i => $header) {
        $col = $columns[$i];
        $sheet->setCellValue($col . $row, $header);
        $sheet->getColumnDimension($col)->setWidth($widths[$i]);
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
        $sheet->setCellValue('A' . $row, $aprendiz['documento']);
        $sheet->setCellValue('B' . $row, $aprendiz['nombres']);
        $sheet->setCellValue('C' . $row, $aprendiz['apellidos']);
        $sheet->setCellValue('D' . $row, $aprendiz['telefono'] ?? 'N/A');
        $sheet->setCellValue('E' . $row, $aprendiz['correo'] ?? 'N/A');
        $sheet->setCellValue('F' . $row, $aprendiz['estado']);
        
        if ($tipo_reporte === 'resumen') {
            $fecha_registro = 'N/A';
            if (!empty($aprendiz['fecha_registro'])) {
                $fecha_registro = date('d/m/Y', strtotime($aprendiz['fecha_registro']));
            }
            $sheet->setCellValue('G' . $row, $fecha_registro);
        } else {
            $sheet->setCellValue('G' . $row, $info_ficha['trimestre']);
            
            // Obtener actividades del aprendiz si hay estados seleccionados
            $detalle_actividades = '';
            if (!empty($estados) && $tipo_reporte !== 'resumen') {
                $sql_actividades = "
                    SELECT 
                        a.titulo, 
                        a.fecha_entrega, 
                        e.estado AS estado_actividad, 
                        m.materia
                    FROM actividades_user au
                    INNER JOIN actividades a ON au.id_actividad = a.id_actividad
                    INNER JOIN materia_ficha mf ON a.id_materia_ficha = mf.id_materia_ficha
                    INNER JOIN materias m ON mf.id_materia = m.id_materia
                    INNER JOIN estado e ON au.id_estado_actividad = e.id_estado
                    WHERE au.id_user = :id_user 
                    AND mf.id_ficha = :id_ficha
                    AND au.id_estado_actividad IN (" . implode(',', array_map('intval', $estados)) . ")
                ";

                $params_actividades = [
                    'id_user' => $aprendiz['documento'], 
                    'id_ficha' => $id_ficha
                ];

                if ($fecha_desde) {
                    $sql_actividades .= " AND a.fecha_entrega >= :fecha_desde";
                    $params_actividades['fecha_desde'] = $fecha_desde;
                }
                if ($fecha_hasta) {
                    $sql_actividades .= " AND a.fecha_entrega <= :fecha_hasta";
                    $params_actividades['fecha_hasta'] = $fecha_hasta;
                }
                if ($materia_filtro) {
                    $sql_actividades .= " AND mf.id_materia = :materia_filtro";
                    $params_actividades['materia_filtro'] = $materia_filtro;
                }
                if (!$es_instructor_principal) {
                    $sql_actividades .= " AND mf.id_instructor = :id_instructor";
                    $params_actividades['id_instructor'] = $id_instructor;
                }

                $sql_actividades .= " ORDER BY m.materia, a.fecha_entrega DESC";

                $stmt_actividades = $conex->prepare($sql_actividades);
                $stmt_actividades->execute($params_actividades);
                $actividades = $stmt_actividades->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($actividades)) {
                    $actividades_por_materia = [];
                    foreach ($actividades as $actividad) {
                        $actividades_por_materia[$actividad['materia']][] = $actividad;
                    }

                    foreach ($actividades_por_materia as $materia => $lista_actividades) {
                        $detalle_actividades .= "• {$materia}:\n";
                        foreach ($lista_actividades as $actividad) {
                            $fecha_formateada = $actividad['fecha_entrega'] ? 
                                date('d/m/Y', strtotime($actividad['fecha_entrega'])) : 'Sin fecha';
                            $detalle_actividades .= "  - {$actividad['titulo']}\n";
                            $detalle_actividades .= "    Estado: {$actividad['estado_actividad']} | Fecha: {$fecha_formateada}\n";
                        }
                        $detalle_actividades .= "\n";
                    }
                } else {
                    $detalle_actividades = 'Sin actividades registradas';
                }
            } else {
                $detalle_actividades = 'Sin filtros de estado aplicados';
            }

            $sheet->setCellValue('H' . $row, trim($detalle_actividades));
            $sheet->getStyle('H' . $row)->getAlignment()->setWrapText(true);
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

    // Configuración de página
    $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
    $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);

    // Generar nombre del archivo
    $filename = "Reporte_Ficha_{$id_ficha}_" . date('Y-m-d_H-i-s') . ".xlsx";

    // Limpiar buffer de salida
    if (ob_get_level()) {
        ob_end_clean();
    }

    // Headers para descarga
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Cache-Control: max-age=1');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: cache, must-revalidate');
    header('Pragma: public');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    
} catch (Exception $e) {
    error_log("Error generando Excel: " . $e->getMessage());
    die("Error al generar el archivo Excel: " . $e->getMessage());
}

exit;
?>

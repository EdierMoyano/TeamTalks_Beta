<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';
require_once __DIR__ . '/../vendor/autoload.php';
include 'session.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

if ($_SESSION['rol'] !== 3 && $_SESSION['rol'] !== 5) {
    header('Location: /teamtalks/includes/exit.php?motivo=acceso-denegado');
    exit;
}

$id_aprendiz = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Obtener aprendiz
$sql_aprendiz = "
    SELECT u.nombres, u.apellidos, u.id, f.id_ficha, fo.nombre AS nombre_formacion
    FROM usuarios u
    JOIN user_ficha uf ON u.id = uf.id_user
    JOIN fichas f ON uf.id_ficha = f.id_ficha
    JOIN formacion fo ON f.id_formacion = fo.id_formacion
    WHERE u.id = :id_aprendiz
";

$stmt_aprendiz = $conex->prepare($sql_aprendiz);
$stmt_aprendiz->execute(['id_aprendiz' => $id_aprendiz]);
$aprendiz = $stmt_aprendiz->fetch(PDO::FETCH_ASSOC);

if (!$aprendiz) {
    die('Aprendiz no encontrado');
}

// Obtener actividades
$sql = "
    SELECT
        a.titulo,
        a.descripcion,
        a.fecha_entrega,
        m.materia,
        e.estado AS estado_actividad,
        au.nota,
        au.comentario_inst,
        au.fecha_entrega AS fecha_entregada
    FROM actividades_user au
    JOIN actividades a ON au.id_actividad = a.id_actividad
    JOIN estado e ON au.id_estado_actividad = e.id_estado
    JOIN materia_ficha mf ON a.id_materia_ficha = mf.id_materia_ficha
    JOIN materias m ON mf.id_materia = m.id_materia
    WHERE au.id_user = :id_aprendiz
    ORDER BY a.fecha_entrega DESC
";

$stmt = $conex->prepare($sql);
$stmt->execute(['id_aprendiz' => $id_aprendiz]);
$actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Crear Excel con diseño profesional
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Reporte de Actividades');

// ===== CONFIGURACIÓN DE COLORES =====
$colorPrimario = '0E4A86';      // Azul principal
$colorSecundario = 'E8F1FF';    // Azul claro
$colorExito = '10B981';         // Verde
$colorAdvertencia = 'F59E0B';   // Amarillo
$colorPeligro = 'EF4444';       // Rojo
$colorGris = 'F8FAFC';          // Gris claro
$colorTexto = '1E293B';         // Gris oscuro

// ===== ENCABEZADO PRINCIPAL =====
$sheet->setCellValue('A1', 'REPORTE DE ACTIVIDADES ACADÉMICAS');
$sheet->mergeCells('A1:H1');
$sheet->getStyle('A1')->applyFromArray([
    'font' => [
        'bold' => true,
        'size' => 18,
        'color' => ['rgb' => 'FFFFFF']
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => $colorPrimario]
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ]
]);
$sheet->getRowDimension(1)->setRowHeight(35);

// ===== FECHA DE GENERACIÓN =====
$sheet->setCellValue('A2', 'Generado el: ' . date('d/m/Y H:i:s'));
$sheet->mergeCells('A2:H2');
$sheet->getStyle('A2')->applyFromArray([
    'font' => [
        'size' => 10,
        'italic' => true,
        'color' => ['rgb' => '64748B']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => $colorGris]
    ]
]);

// ===== INFORMACIÓN DEL ESTUDIANTE =====
$sheet->setCellValue('A4', 'INFORMACIÓN DEL ESTUDIANTE');
$sheet->mergeCells('A4:H4');
$sheet->getStyle('A4')->applyFromArray([
    'font' => [
        'bold' => true,
        'size' => 14,
        'color' => ['rgb' => $colorPrimario]
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => $colorSecundario]
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => $colorPrimario]
        ]
    ]
]);
$sheet->getRowDimension(4)->setRowHeight(25);

// Datos del estudiante en formato de tabla
$datosEstudiante = [
    ['Nombre Completo:', $aprendiz['nombres'] . ' ' . $aprendiz['apellidos']],
    ['Documento:', $aprendiz['id']],
    ['Ficha:', $aprendiz['id_ficha']],
    ['Programa de Formación:', $aprendiz['nombre_formacion']]
];

$fila = 5;
foreach ($datosEstudiante as $dato) {
    $sheet->setCellValue('A' . $fila, $dato[0]);
    $sheet->setCellValue('B' . $fila, $dato[1]);

    // Estilo para etiquetas
    $sheet->getStyle('A' . $fila)->applyFromArray([
        'font' => [
            'bold' => true,
            'color' => ['rgb' => $colorTexto]
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => $colorGris]
        ],

        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_LEFT,
            'vertical' => Alignment::VERTICAL_CENTER
        ],

        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'CBD5E1']
            ]
        ]
    ]);

    // Estilo para valores
    $sheet->setCellValue('B' . $fila, $dato[1]);
    $sheet->mergeCells('B' . $fila . ':H' . $fila);
    $sheet->getStyle('B' . $fila . ':H' . $fila)->applyFromArray([
        'font' => [
            'color' => ['rgb' => $colorTexto]
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'CBD5E1']
            ]
        ]
    ]);

    $fila++;
}

// ===== RESUMEN ESTADÍSTICO =====
$totalActividades = count($actividades);
$aprobadas = count(array_filter($actividades, fn($a) => strtolower($a['estado_actividad']) === 'aprobado'));
$pendientes = count(array_filter($actividades, fn($a) => strtolower($a['estado_actividad']) === 'pendiente'));
$entregadas = count(array_filter($actividades, fn($a) => strtolower($a['estado_actividad']) === 'entregado'));

$fila += 1; // Espacio
$sheet->setCellValue('A' . $fila, 'RESUMEN ESTADÍSTICO');
$sheet->mergeCells('A' . $fila . ':H' . $fila);
$sheet->getStyle('A' . $fila)->applyFromArray([
    'font' => [
        'bold' => true,
        'size' => 14,
        'color' => ['rgb' => $colorPrimario]
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => $colorSecundario]
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => $colorPrimario]
        ]
    ]
]);

$fila++;
$estadisticas = [
    ['Total de Actividades:', $totalActividades, $colorPrimario],
    ['Actividades Aprobadas:', $aprobadas, $colorExito],
    ['Actividades Entregadas:', $entregadas, $colorAdvertencia],
    ['Actividades Pendientes:', $pendientes, $colorPeligro]
];

foreach ($estadisticas as $stat) {
    $sheet->setCellValue('A' . $fila, $stat[0]);
    $sheet->setCellValue('B' . $fila, $stat[1]);

    $sheet->getStyle('A' . $fila)->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => $colorTexto]],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $colorGris]],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]]
    ]);

    $sheet->getStyle('B' . $fila)->applyFromArray([
        'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => $stat[2]]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]]
    ]);

    $fila++;
}

// ===== TABLA DE ACTIVIDADES =====
$fila += 2; // Espacio
$sheet->setCellValue('A' . $fila, 'DETALLE DE ACTIVIDADES');
$sheet->mergeCells('A' . $fila . ':H' . $fila);
$sheet->getStyle('A' . $fila)->applyFromArray([
    'font' => [
        'bold' => true,
        'size' => 14,
        'color' => ['rgb' => $colorPrimario]
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => $colorSecundario]
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => $colorPrimario]
        ]
    ]
]);

$fila += 2;
// Encabezados de la tabla
$headers = ['Materia', 'Título', 'Descripción', 'Fecha Entrega', 'Estado', 'Nota', 'Fecha Entregada', 'Comentario Instructor'];
$columnas = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

for ($i = 0; $i < count($headers); $i++) {
    $sheet->setCellValue($columnas[$i] . $fila, $headers[$i]);
    $sheet->getStyle($columnas[$i] . $fila)->applyFromArray([
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 11
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => $colorPrimario]
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
            'wrapText' => true
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'FFFFFF']
            ]
        ]
    ]);
}

$sheet->getRowDimension($fila)->setRowHeight(30);
$filaInicial = $fila + 1;

// Contenido de las actividades
$fila++;
foreach ($actividades as $index => $act) {
    $datos = [
        $act['materia'],
        $act['titulo'],
        $act['descripcion'],
        $act['fecha_entrega'] ? date('d/m/Y', strtotime($act['fecha_entrega'])) : '-',
        $act['estado_actividad'],
        $act['nota'] ?? 'Sin calificar',
        $act['fecha_entregada'] ? date('d/m/Y H:i', strtotime($act['fecha_entregada'])) : 'No entregada',
        $act['comentario_inst'] ?? 'Sin comentarios'
    ];

    for ($i = 0; $i < count($datos); $i++) {
        $sheet->setCellValue($columnas[$i] . $fila, $datos[$i]);

        // Color de fondo alternado
        $colorFondo = ($index % 2 == 0) ? 'FFFFFF' : $colorGris;

        $sheet->getStyle($columnas[$i] . $fila)->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $colorFondo]
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1']
                ]
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_TOP,
                'wrapText' => true
            ]
        ]);

        // Formato especial para columna de estado
        if ($i == 4) { // Columna Estado
            $colorEstado = $colorTexto;
            $fondoEstado = $colorFondo;

            switch (strtolower($act['estado_actividad'])) {
                case 'aprobado':
                    $colorEstado = $colorExito;
                    $fondoEstado = 'ECFDF5';
                    break;
                case 'desaprobado':
                    $colorEstado = $colorPeligro;
                    $fondoEstado = 'FEF2F2';
                    break;
                case 'entregado':
                    $colorEstado = $colorAdvertencia;
                    $fondoEstado = 'FFFBEB';
                    break;
                case 'pendiente':
                    $colorEstado = '6B7280';
                    $fondoEstado = 'F9FAFB';
                    break;
            }

            $sheet->getStyle($columnas[$i] . $fila)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => $colorEstado]
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $fondoEstado]
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ]);
        }

        // Formato especial para columna de nota
        if ($i == 5) { // Columna Nota
            if (is_numeric($act['nota'])) {
                $nota = floatval($act['nota']);
                $colorNota = $colorTexto;
                $fondoNota = $colorFondo;

                if ($nota >= 4.0) {
                    $colorNota = $colorExito;
                    $fondoNota = 'ECFDF5';
                } elseif ($nota >= 3.0) {
                    $colorNota = $colorAdvertencia;
                    $fondoNota = 'FFFBEB';
                } else {
                    $colorNota = $colorPeligro;
                    $fondoNota = 'FEF2F2';
                }

                $sheet->getStyle($columnas[$i] . $fila)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => $colorNota]
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $fondoNota]
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ]
                ]);
            }
        }
    }

    $sheet->getRowDimension($fila)->setRowHeight(25);
    $fila++;
}

// ===== PIE DE PÁGINA =====
$fila += 2;
$sheet->setCellValue('A' . $fila, 'Documento generado por TeamTalks - Sistema de Gestión Académica');
$sheet->mergeCells('A' . $fila . ':H' . $fila);
$sheet->getStyle('A' . $fila)->applyFromArray([
    'font' => [
        'size' => 9,
        'italic' => true,
        'color' => ['rgb' => '64748B']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER
    ]
]);

$fila++;
$sheet->setCellValue('A' . $fila, 'Este documento contiene información confidencial de uso exclusivo para fines educativos');
$sheet->mergeCells('A' . $fila . ':H' . $fila);
$sheet->getStyle('A' . $fila)->applyFromArray([
    'font' => [
        'size' => 8,
        'italic' => true,
        'color' => ['rgb' => '94A3B8']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER
    ]
]);

// ===== AJUSTAR ANCHO DE COLUMNAS =====
$anchos = [
    'A' => 25,  // Materia
    'B' => 25,  // Título
    'C' => 35,  // Descripción
    'D' => 15,  // Fecha Entrega
    'E' => 15,  // Estado
    'F' => 15,  // Nota
    'G' => 18,  // Fecha Entregada
    'H' => 30   // Comentario
];

foreach ($anchos as $columna => $ancho) {
    $sheet->getColumnDimension($columna)->setWidth($ancho);
}

// ===== CONFIGURACIONES ADICIONALES =====
// Congelar paneles
$sheet->freezePane('A' . ($filaInicial));

// Configurar impresión
$sheet->getPageSetup()
    ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
    ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
    ->setFitToPage(true)
    ->setFitToWidth(1)
    ->setFitToHeight(0);

// Márgenes
$sheet->getPageMargins()
    ->setTop(0.75)
    ->setRight(0.25)
    ->setLeft(0.25)
    ->setBottom(0.75);

// Encabezado y pie de página de impresión
$sheet->getHeaderFooter()
    ->setOddHeader('&C&B' . 'Reporte de Actividades - ' . $aprendiz['nombres'] . ' ' . $aprendiz['apellidos'])
    ->setOddFooter('&L&D &T&C&BTeamTalks&R&P de &N');

// ===== DESCARGAR EL ARCHIVO =====
$filename = 'reporte_actividades_' . preg_replace('/[^a-zA-Z0-9]/', '_', $aprendiz['nombres'] . '_' . $aprendiz['apellidos']) . '_' . date('Ymd_His') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');
header('Cache-Control: max-age=1');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: cache, must-revalidate');
header('Pragma: public');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;

<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';
require_once __DIR__ . '/../vendor/autoload.php';
include 'session.php';

date_default_timezone_set('America/Bogota');

use Dompdf\Dompdf;

if ($_SESSION['rol'] !== 3 && $_SESSION['rol'] !== 5) {
    header('Location: /teamtalks/includes/exit.php?motivo=acceso-denegado');
    exit;
}

$id_instructor = $_SESSION['documento'];
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

// Obtener actividades del aprendiz, SOLO de materias que dicta el instructor
$sql = "
    SELECT
    a.id_actividad,
    a.titulo,
    a.descripcion,
    a.fecha_entrega,
    m.materia,
    e.estado AS estado_actividad,
    au.nota,
    au.comentario_inst,
    au.fecha_entrega AS fecha_entregada,
    au.archivo1,
    au.archivo2,
    au.archivo3
  FROM actividades_user au
  JOIN actividades a ON au.id_actividad = a.id_actividad
  JOIN estado e ON au.id_estado_actividad = e.id_estado
  JOIN materia_ficha mf ON a.id_materia_ficha = mf.id_materia_ficha
  JOIN materias m ON mf.id_materia = m.id_materia
  WHERE au.id_user = :id_aprendiz
  ORDER BY a.fecha_entrega DESC
";

$stmt = $conex->prepare($sql);
$stmt->execute([
    'id_aprendiz' => $id_aprendiz
]);
$actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Crear HTML profesional y minimalista
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
            background: #ffffff;
            color: #1a202c;
            line-height: 1.5;
            padding: 20px;
        }
        
        .document {
            max-width: 100%;
            background: #ffffff;
        }
        
        /* Header minimalista */
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #0E4A86;
        }
        
        .title {
            font-size: 28px;
            font-weight: 300;
            color: #0E4A86;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }
        
        .subtitle {
            font-size: 12px;
            color: #718096;
            font-weight: 400;
        }
        
        /* Información del estudiante */
        .student-info {
            background: #f7fafc;
            border-left: 4px solid #0E4A86;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 0 8px 8px 0;
        }
        
        .student-name {
            font-size: 22px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 12px;
        }
        
        .student-details {
            display: flex;
            gap: 40px;
            font-size: 14px;
        }
        
        .detail-item {
            color: #4a5568;
        }
        
        .detail-label {
            font-weight: 600;
            color: #0E4A86;
        }
        
        /* Tabla profesional */
        .table-container {
            margin-bottom: 30px;
        }
        
        .table-title {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 15px;
            text-align: left;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .data-table thead {
            background: #0E4A86;
        }
        
        .data-table th {
            color: #ffffff;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 15px 12px;
            text-align: left;
            border-bottom: none;
        }
        
        .data-table td {
            padding: 15px 12px;
            font-size: 11px;
            border-bottom: 1px solid #f7fafc;
            vertical-align: top;
            color: #4a5568;
        }
        
        .data-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }
        
        .data-table tbody tr:hover {
            background: #edf2f7;
        }
        
        .data-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        /* Estilos específicos de celdas */
        .cell-materia {
            font-weight: 600;
            color: #0E4A86;
            font-size: 10px;
        }
        
        .cell-titulo {
            font-weight: 500;
            color: #2d3748;
            max-width: 150px;
        }
        
        .cell-descripcion {
            color: #718096;
            font-size: 10px;
            line-height: 1.4;
            max-width: 200px;
        }
        
        .cell-fecha {
            font-size: 10px;
            color: #4a5568;
            white-space: nowrap;
        }
        
        .cell-estado {
            text-align: center;
        }
        
        .estado-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .estado-entregada {
            background: #c6f6d5;
            color: #22543d;
        }
        
        .estado-pendiente {
            background: #fed7aa;
            color: #9c4221;
        }
        
        .estado-calificada {
            background: #bee3f8;
            color: #2a4365;
        }
        
        .estado-default {
            background: #e2e8f0;
            color: #4a5568;
        }
        
        .cell-nota {
            text-align: center;
            font-weight: 700;
            font-size: 12px;
        }
        
        .nota-alta {
            color: #38a169;
        }
        
        .nota-media {
            color: #d69e2e;
        }
        
        .nota-baja {
            color: #e53e3e;
        }
        
        .nota-sin {
            color: #a0aec0;
        }
        
        .cell-comentario {
            font-size: 9px;
            color: #718096;
            font-style: italic;
            max-width: 180px;
        }
        
        /* Estado vacío */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: #f7fafc;
            border: 2px dashed #cbd5e1;
            border-radius: 8px;
        }
        
        .empty-icon {
            font-size: 48px;
            color: #cbd5e1;
            margin-bottom: 16px;
        }
        
        .empty-title {
            font-size: 18px;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 8px;
        }
        
        .empty-text {
            font-size: 14px;
            color: #718096;
            max-width: 400px;
            margin: 0 auto;
        }
        
        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 10px;
            color: #718096;
        }
        
        .footer-line {
            margin-bottom: 4px;
        }
        
        .footer-brand {
            font-weight: 600;
            color: #0E4A86;
        }
        
        /* Utilidades */
        .text-center { text-align: center; }
        .font-medium { font-weight: 500; }
        .font-semibold { font-weight: 600; }
    </style>
</head>
<body>
    <div class="document">
        <!-- Header -->
        <div class="header">
            <h1 class="title">Reporte de Actividades Académicas</h1>
            <p class="subtitle">Generado el ' . date('d/m/Y') . ' a las ' . date('H:i') . '</p>
        </div>
        
        <!-- Información del estudiante -->
        <div class="student-info">
            <h2 class="student-name">' . htmlspecialchars($aprendiz['nombres'] . ' ' . $aprendiz['apellidos']) . '</h2>
            <div class="student-details">
                <div class="detail-item">
                    <span class="detail-label">Ficha:</span> ' . htmlspecialchars($aprendiz['id_ficha']) . '
                </div>
                <div class="detail-item">
                    <span class="detail-label">Programa de Formación:</span> ' . htmlspecialchars($aprendiz['nombre_formacion']) . '
                </div>
            </div>
        </div>
        
        <!-- Tabla de actividades -->
        <div class="table-container">
            <h3 class="table-title">Registro de Actividades</h3>';

if (count($actividades) === 0) {
    $html .= '
            <div class="empty-state">
                <div class="empty-icon">📋</div>
                <h4 class="empty-title">No hay actividades registradas</h4>
                <p class="empty-text">No se encontraron actividades asignadas por este instructor para el estudiante seleccionado.</p>
            </div>';
} else {
    $html .= '
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Materia</th>
                        <th>Título</th>
                        <th>Descripción</th>
                        <th>Fecha Entrega</th>
                        <th>Estado</th>
                        <th>Nota</th>
                        <th>Fecha Entregada</th>
                        <th>Comentario Instructor</th>
                    </tr>
                </thead>
                <tbody>';
    
    foreach ($actividades as $act) {
        // Determinar clase del estado
        $estado_class = 'estado-default';
        $estado_lower = strtolower($act['estado_actividad']);
        if (strpos($estado_lower, 'entregada') !== false) {
            $estado_class = 'estado-entregada';
        } elseif (strpos($estado_lower, 'pendiente') !== false) {
            $estado_class = 'estado-pendiente';
        } elseif (strpos($estado_lower, 'calificada') !== false) {
            $estado_class = 'estado-calificada';
        }
        
        // Determinar clase de la nota
        $nota_class = 'nota-sin';
        if ($act['nota'] !== null && $act['nota'] !== '') {
            $nota_num = floatval($act['nota']);
            if ($nota_num >= 4.0) {
                $nota_class = 'nota-alta';
            } elseif ($nota_num >= 3.0) {
                $nota_class = 'nota-media';
            } else {
                $nota_class = 'nota-baja';
            }
        }
        
        $html .= '
                    <tr>
                        <td class="cell-materia">' . htmlspecialchars($act['materia']) . '</td>
                        <td class="cell-titulo">' . htmlspecialchars($act['titulo']) . '</td>
                        <td class="cell-descripcion">' . htmlspecialchars($act['descripcion']) . '</td>
                        <td class="cell-fecha">' . htmlspecialchars($act['fecha_entrega']) . '</td>
                        <td class="cell-estado">
                            <span class="estado-badge ' . $estado_class . '">' . htmlspecialchars($act['estado_actividad']) . '</span>
                        </td>
                        <td class="cell-nota ' . $nota_class . '">' . ($act['nota'] ?? '-') . '</td>
                        <td class="cell-fecha">' . ($act['fecha_entregada'] ?? '-') . '</td>
                        <td class="cell-comentario">' . (htmlspecialchars($act['comentario_inst']) ?? '-') . '</td>
                    </tr>';
    }
    
    $html .= '
                </tbody>
            </table>';
}

$html .= '
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div class="footer-line">Documento generado por <span class="footer-brand">TeamTalks</span> - Sistema de Gestión Académica</div>
            <div class="footer-line">Este documento contiene información confidencial de uso exclusivo para fines educativos</div>
        </div>
    </div>
</body>
</html>';

// Generar PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("reporte_aprendiz_{$id_aprendiz}.pdf", ["Attachment" => false]); // false = mostrar en navegador
?>

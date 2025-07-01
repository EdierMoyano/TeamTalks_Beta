<?php
session_start();

// Verificar sesión y rol de administrador
if (!isset($_SESSION['documento']) || $_SESSION['rol'] !== 2) {
    header('Location: ../../includes/exit.php');
    exit;
}

require_once '../../conexion/conexion.php';

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
            -- Promedio general
            COALESCE(AVG(au.nota), 0) as promedio_general,
            -- Total actividades
            COUNT(au.id_actividad_user) as total_actividades,
            -- Actividades aprobadas
            SUM(CASE WHEN au.nota >= 4.0 THEN 1 ELSE 0 END) as actividades_aprobadas,
            -- Porcentaje de aprobación
            CASE 
                WHEN COUNT(au.id_actividad_user) > 0 
                THEN ROUND((SUM(CASE WHEN au.nota >= 4.0 THEN 1 ELSE 0 END) * 100.0 / COUNT(au.id_actividad_user)), 2)
                ELSE 0 
            END as porcentaje_aprobacion,
            -- Estado de aprobación general
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

    // Generar archivo Excel
    $nombre_archivo = "reporte_" . strtolower(str_replace(' ', '_', $aprendiz['nombres'] . '_' . $aprendiz['apellidos']));
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $nombre_archivo . '_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="UTF-8"><title>Reporte Individual - ' . htmlspecialchars($aprendiz['nombres'] . ' ' . $aprendiz['apellidos']) . '</title></head>';
    echo '<body>';
    
    echo '<table border="1">';
    
    // Encabezado principal
    echo '<tr style="background-color: #0e4a86; color: white; font-weight: bold;">';
    echo '<td colspan="8" style="text-align: center; font-size: 16px;">REPORTE INDIVIDUAL - TEAMTALKS</td>';
    echo '</tr>';
    echo '<tr style="background-color: #f8f9fa;">';
    echo '<td colspan="8" style="text-align: center;">Generado el: ' . date('d/m/Y H:i:s') . '</td>';
    echo '</tr>';
    echo '<tr><td colspan="8"></td></tr>'; // Fila vacía
    
    // Información personal
    echo '<tr style="background-color: #28a745; color: white; font-weight: bold;">';
    echo '<td colspan="8" style="text-align: center;">INFORMACIÓN PERSONAL</td>';
    echo '</tr>';
    
    echo '<tr>';
    echo '<td style="font-weight: bold;">Documento:</td>';
    echo '<td>' . htmlspecialchars($aprendiz['documento']) . '</td>';
    echo '<td style="font-weight: bold;">Nombres:</td>';
    echo '<td>' . htmlspecialchars($aprendiz['nombres']) . '</td>';
    echo '<td style="font-weight: bold;">Apellidos:</td>';
    echo '<td>' . htmlspecialchars($aprendiz['apellidos']) . '</td>';
    echo '<td style="font-weight: bold;">Estado:</td>';
    echo '<td>' . htmlspecialchars($aprendiz['estado']) . '</td>';
    echo '</tr>';
    
    echo '<tr>';
    echo '<td style="font-weight: bold;">Correo:</td>';
    echo '<td colspan="3">' . htmlspecialchars($aprendiz['correo']) . '</td>';
    echo '<td style="font-weight: bold;">Teléfono:</td>';
    echo '<td>' . htmlspecialchars($aprendiz['telefono'] ?? 'No registrado') . '</td>';
    echo '<td style="font-weight: bold;">Fecha Registro:</td>';
    echo '<td>' . date('d/m/Y', strtotime($aprendiz['fecha_registro'])) . '</td>';
    echo '</tr>';
    
    if ($aprendiz['ficha_numero']) {
        echo '<tr>';
        echo '<td style="font-weight: bold;">Ficha:</td>';
        echo '<td>' . htmlspecialchars($aprendiz['ficha_numero']) . '</td>';
        echo '<td style="font-weight: bold;">Programa:</td>';
        echo '<td colspan="3">' . htmlspecialchars($aprendiz['programa_formacion']) . '</td>';
        echo '<td style="font-weight: bold;">Jornada:</td>';
        echo '<td>' . htmlspecialchars($aprendiz['jornada']) . '</td>';
        echo '</tr>';
    }
    
    echo '<tr><td colspan="8"></td></tr>'; // Fila vacía
    
    // Resumen académico
    echo '<tr style="background-color: #17a2b8; color: white; font-weight: bold;">';
    echo '<td colspan="8" style="text-align: center;">RESUMEN ACADÉMICO</td>';
    echo '</tr>';
    
    echo '<tr>';
    echo '<td style="font-weight: bold;">Promedio General:</td>';
    echo '<td>' . number_format($aprendiz['promedio_general'], 2) . '</td>';
    echo '<td style="font-weight: bold;">Total Actividades:</td>';
    echo '<td>' . $aprendiz['total_actividades'] . '</td>';
    echo '<td style="font-weight: bold;">Actividades Aprobadas:</td>';
    echo '<td>' . $aprendiz['actividades_aprobadas'] . '</td>';
    echo '<td style="font-weight: bold;">% Aprobación:</td>';
    echo '<td>' . number_format($aprendiz['porcentaje_aprobacion'], 2) . '%</td>';
    echo '</tr>';
    
    echo '<tr>';
    echo '<td style="font-weight: bold;">Estado Académico:</td>';
    echo '<td colspan="7" style="font-weight: bold; color: ' . 
         ($aprendiz['estado_academico'] === 'APROBADO' ? 'green' : 
          ($aprendiz['estado_academico'] === 'SIN CALIFICAR' ? 'orange' : 'red')) . ';">' . 
         $aprendiz['estado_academico'] . '</td>';
    echo '</tr>';
    
    echo '<tr><td colspan="8"></td></tr>'; // Fila vacía
    
    // Rendimiento por trimestre
    if (!empty($trimestres)) {
        echo '<tr style="background-color: #ffc107; color: black; font-weight: bold;">';
        echo '<td colspan="8" style="text-align: center;">RENDIMIENTO POR TRIMESTRE</td>';
        echo '</tr>';
        
        echo '<tr style="background-color: #f8f9fa; font-weight: bold;">';
        echo '<td>TRIMESTRE</td>';
        echo '<td>PROMEDIO</td>';
        echo '<td>TOTAL ACTIVIDADES</td>';
        echo '<td>ACTIVIDADES APROBADAS</td>';
        echo '<td>% APROBACIÓN</td>';
        echo '<td>ESTADO</td>';
        echo '<td colspan="2">OBSERVACIONES</td>';
        echo '</tr>';
        
        foreach ($trimestres as $trimestre) {
            $porcentaje_trimestre = $trimestre['total_actividades_trimestre'] > 0 ? 
                round(($trimestre['actividades_aprobadas_trimestre'] / $trimestre['total_actividades_trimestre']) * 100, 2) : 0;
            
            echo '<tr>';
            echo '<td>' . htmlspecialchars($trimestre['trimestre']) . '</td>';
            echo '<td>' . ($trimestre['promedio_trimestre'] ? number_format($trimestre['promedio_trimestre'], 2) : 'N/A') . '</td>';
            echo '<td>' . $trimestre['total_actividades_trimestre'] . '</td>';
            echo '<td>' . $trimestre['actividades_aprobadas_trimestre'] . '</td>';
            echo '<td>' . $porcentaje_trimestre . '%</td>';
            echo '<td>' . $trimestre['estado_trimestre'] . '</td>';
            echo '<td colspan="2">' . 
                 ($trimestre['estado_trimestre'] === 'APROBADO' ? 'Rendimiento satisfactorio' : 
                  ($trimestre['estado_trimestre'] === 'SIN CALIFICAR' ? 'Pendiente de evaluación' : 'Requiere refuerzo')) . 
                 '</td>';
            echo '</tr>';
        }
    }
    
    echo '<tr><td colspan="8"></td></tr>'; // Fila vacía
    
    // Detalle de actividades
    if (!empty($actividades)) {
        echo '<tr style="background-color: #6f42c1; color: white; font-weight: bold;">';
        echo '<td colspan="8" style="text-align: center;">DETALLE DE ACTIVIDADES</td>';
        echo '</tr>';
        
        echo '<tr style="background-color: #f8f9fa; font-weight: bold;">';
        echo '<td>TRIMESTRE</td>';
        echo '<td>MATERIA</td>';
        echo '<td>ACTIVIDAD</td>';
        echo '<td>FECHA ENTREGA</td>';
        echo '<td>FECHA ENTREGADO</td>';
        echo '<td>NOTA</td>';
        echo '<td>ESTADO</td>';
        echo '<td>COMENTARIOS</td>';
        echo '</tr>';
        
        foreach ($actividades as $actividad) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($actividad['trimestre'] ?? 'N/A') . '</td>';
            echo '<td>' . htmlspecialchars($actividad['materia'] ?? 'N/A') . '</td>';
            echo '<td>' . htmlspecialchars($actividad['titulo']) . '</td>';
            echo '<td>' . date('d/m/Y', strtotime($actividad['fecha_entrega'])) . '</td>';
            echo '<td>' . ($actividad['fecha_entrega_estudiante'] ? date('d/m/Y', strtotime($actividad['fecha_entrega_estudiante'])) : 'No entregado') . '</td>';
            echo '<td>' . ($actividad['nota'] ? number_format($actividad['nota'], 1) : 'N/A') . '</td>';
            echo '<td>' . $actividad['estado_actividad'] . '</td>';
            echo '<td>' . htmlspecialchars($actividad['comentario_inst'] ?? '') . '</td>';
            echo '</tr>';
        }
    }
    
    // Pie de página
    echo '<tr><td colspan="8"></td></tr>'; // Fila vacía
    echo '<tr style="background-color: #6c757d; color: white; font-weight: bold;">';
    echo '<td colspan="8" style="text-align: center;">REPORTE GENERADO POR TEAMTALKS - ' . date('d/m/Y H:i:s') . '</td>';
    echo '</tr>';
    
    echo '</table>';
    echo '</body></html>';

} catch (PDOException $e) {
    die("Error al generar reporte: " . $e->getMessage());
}
?>

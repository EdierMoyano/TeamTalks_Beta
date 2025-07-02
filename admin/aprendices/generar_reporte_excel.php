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

$tipo = $_GET['tipo'] ?? '';
$ficha = $_GET['ficha'] ?? '';

if (empty($tipo)) {
    die("Tipo de reporte requerido");
}

// Construir consulta según el tipo de reporte
$where_conditions = ["u.id_rol = 4", "u.nit = ?"];
$params = [$nit_usuario];
$nombre_archivo = "reporte_aprendices_general";

if ($tipo === 'ficha' && !empty($ficha)) {
    $where_conditions[] = "uf.id_ficha = ?";
    $params[] = $ficha;
    $nombre_archivo = "reporte_aprendices_ficha_" . $ficha;
}

$where_clause = implode(" AND ", $where_conditions);

try {
    // Obtener datos de aprendices
    $query = "
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
        WHERE $where_clause
        GROUP BY u.id, u.nombres, u.apellidos, u.correo, u.telefono, u.fecha_registro, 
                 u.id_estado, e.estado, uf.id_ficha, f.id_ficha, fo.nombre, tf.tipo_formacion, j.jornada
        ORDER BY u.nombres, u.apellidos
    ";

    $stmt = $conexion->prepare($query);
    $stmt->execute($params);
    $aprendices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener notas por trimestre para cada aprendiz
    $aprendices_con_trimestres = [];
    foreach ($aprendices as $aprendiz) {
        $stmt_trimestres = $conexion->prepare("
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
        $stmt_trimestres->execute([$aprendiz['id_ficha'], $aprendiz['documento']]);
        $trimestres = $stmt_trimestres->fetchAll(PDO::FETCH_ASSOC);
        
        $aprendiz['trimestres'] = $trimestres;
        $aprendices_con_trimestres[] = $aprendiz;
    }

    // Generar archivo Excel
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $nombre_archivo . '_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="UTF-8"><title>Reporte de Aprendices</title></head>';
    echo '<body>';
    
    echo '<table border="1">';
    echo '<tr style="background-color: #0e4a86; color: white; font-weight: bold;">';
    echo '<td colspan="20" style="text-align: center; font-size: 16px;">REPORTE DE APRENDICES - TEAMTALKS</td>';
    echo '</tr>';
    echo '<tr style="background-color: #f8f9fa;">';
    echo '<td colspan="20" style="text-align: center;">Generado el: ' . date('d/m/Y H:i:s') . '</td>';
    echo '</tr>';
    echo '<tr><td colspan="20"></td></tr>'; // Fila vacía
    
    // Encabezados
    echo '<tr style="background-color: #0e4a86; color: white; font-weight: bold;">';
    echo '<td>DOCUMENTO</td>';
    echo '<td>NOMBRES</td>';
    echo '<td>APELLIDOS</td>';
    echo '<td>CORREO</td>';
    echo '<td>TELÉFONO</td>';
    echo '<td>FICHA</td>';
    echo '<td>PROGRAMA</td>';
    echo '<td>TIPO FORMACIÓN</td>';
    echo '<td>JORNADA</td>';
    echo '<td>ESTADO</td>';
    echo '<td>FECHA REGISTRO</td>';
    echo '<td>PROMEDIO GENERAL</td>';
    echo '<td>TOTAL ACTIVIDADES</td>';
    echo '<td>ACTIVIDADES APROBADAS</td>';
    echo '<td>% APROBACIÓN</td>';
    echo '<td>ESTADO ACADÉMICO</td>';
    echo '<td>PRIMER TRIMESTRE</td>';
    echo '<td>SEGUNDO TRIMESTRE</td>';
    echo '<td>TERCER TRIMESTRE</td>';
    echo '<td>CUARTO TRIMESTRE</td>';
    echo '</tr>';

    // Datos
    foreach ($aprendices_con_trimestres as $aprendiz) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($aprendiz['documento']) . '</td>';
        echo '<td>' . htmlspecialchars($aprendiz['nombres']) . '</td>';
        echo '<td>' . htmlspecialchars($aprendiz['apellidos']) . '</td>';
        echo '<td>' . htmlspecialchars($aprendiz['correo']) . '</td>';
        echo '<td>' . htmlspecialchars($aprendiz['telefono'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($aprendiz['ficha_numero'] ?? 'Sin asignar') . '</td>';
        echo '<td>' . htmlspecialchars($aprendiz['programa_formacion'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($aprendiz['tipo_formacion'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($aprendiz['jornada'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($aprendiz['estado']) . '</td>';
        echo '<td>' . date('d/m/Y', strtotime($aprendiz['fecha_registro'])) . '</td>';
        echo '<td>' . number_format($aprendiz['promedio_general'], 2) . '</td>';
        echo '<td>' . $aprendiz['total_actividades'] . '</td>';
        echo '<td>' . $aprendiz['actividades_aprobadas'] . '</td>';
        echo '<td>' . number_format($aprendiz['porcentaje_aprobacion'], 2) . '%</td>';
        echo '<td>' . $aprendiz['estado_academico'] . '</td>';
        
        // Notas por trimestre
        for ($i = 1; $i <= 4; $i++) {
            $trimestre_encontrado = false;
            foreach ($aprendiz['trimestres'] as $trimestre) {
                if ($trimestre['id_trimestre'] == $i) {
                    $promedio = $trimestre['promedio_trimestre'] ? number_format($trimestre['promedio_trimestre'], 2) : 'N/A';
                    $estado = $trimestre['estado_trimestre'];
                    echo '<td>' . $promedio . ' (' . $estado . ')</td>';
                    $trimestre_encontrado = true;
                    break;
                }
            }
            if (!$trimestre_encontrado) {
                echo '<td>N/A</td>';
            }
        }
        
        echo '</tr>';
    }

    // Resumen estadístico
    echo '<tr><td colspan="20"></td></tr>'; // Fila vacía
    echo '<tr style="background-color: #28a745; color: white; font-weight: bold;">';
    echo '<td colspan="20" style="text-align: center;">RESUMEN ESTADÍSTICO</td>';
    echo '</tr>';
    
    $total_aprendices = count($aprendices_con_trimestres);
    $aprendices_activos = count(array_filter($aprendices_con_trimestres, function($a) { return $a['estado'] === 'Activo'; }));
    $aprendices_aprobados = count(array_filter($aprendices_con_trimestres, function($a) { return $a['estado_academico'] === 'APROBADO'; }));
    $promedio_general_total = $total_aprendices > 0 ? array_sum(array_column($aprendices_con_trimestres, 'promedio_general')) / $total_aprendices : 0;
    
    echo '<tr>';
    echo '<td colspan="2" style="font-weight: bold;">Total Aprendices:</td>';
    echo '<td>' . $total_aprendices . '</td>';
    echo '<td colspan="2" style="font-weight: bold;">Aprendices Activos:</td>';
    echo '<td>' . $aprendices_activos . '</td>';
    echo '<td colspan="2" style="font-weight: bold;">Aprendices Aprobados:</td>';
    echo '<td>' . $aprendices_aprobados . '</td>';
    echo '<td colspan="2" style="font-weight: bold;">Promedio General:</td>';
    echo '<td>' . number_format($promedio_general_total, 2) . '</td>';
    echo '<td colspan="8"></td>';
    echo '</tr>';

    echo '</table>';
    echo '</body></html>';

} catch (PDOException $e) {
    die("Error al generar reporte: " . $e->getMessage());
}
?>

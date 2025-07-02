<?php
session_start();

// Verificar sesión y rol de administrador
if (!isset($_SESSION['documento']) || $_SESSION['rol'] !== 2) {
    header('Location: ../../includes/exit.php');
    exit;
}

require_once '../../conexion/conexion.php';

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

$id_instructor = $_GET['id_instructor'] ?? '';
$tipo_reporte = $_GET['tipo_reporte'] ?? '';
$id_ficha = $_GET['id_ficha'] ?? null;

// DEPURACIÓN: Mostrar qué parámetros están llegando
error_log("DEBUG - Parámetros recibidos:");
error_log("id_instructor: " . $id_instructor);
error_log("tipo_reporte: " . $tipo_reporte);
error_log("id_ficha: " . $id_ficha);
error_log("GET completo: " . print_r($_GET, true));

// Si estamos en modo debug, mostrar los parámetros
if (isset($_GET['debug'])) {
    echo "<pre>";
    echo "Parámetros recibidos:\n";
    echo "id_instructor: " . $id_instructor . "\n";
    echo "tipo_reporte: " . $tipo_reporte . "\n";
    echo "id_ficha: " . $id_ficha . "\n";
    echo "GET completo: " . print_r($_GET, true);
    echo "</pre>";
    exit;
}

// Verificar que el tipo de reporte sea válido
$tipos_validos = ['general', 'personal', 'fichas', 'ficha_individual'];
if (!in_array($tipo_reporte, $tipos_validos)) {
    die('Tipo de reporte no válido. Recibido: "' . $tipo_reporte . '". Válidos: ' . implode(', ', $tipos_validos));
}

// Para reportes de ficha individual, verificar que se proporcione el ID de ficha
if ($tipo_reporte === 'ficha_individual' && empty($id_ficha)) {
    die('ID de ficha requerido para reporte individual');
}

if (empty($id_instructor) || empty($tipo_reporte)) {
    die("Parámetros incompletos. id_instructor: '$id_instructor', tipo_reporte: '$tipo_reporte'");
}

try {
    // Obtener datos del instructor
    $stmt = $conexion->prepare("
        SELECT 
            u.id,
            u.nombres,
            u.apellidos,
            u.correo,
            u.telefono,
            u.id_rol,
            r.rol,
            u.fecha_registro,
            u.id_estado,
            e.estado as estado_nombre
        FROM usuarios u
        LEFT JOIN roles r ON u.id_rol = r.id_rol
        LEFT JOIN estado e ON u.id_estado = e.id_estado
        WHERE u.id = ? AND u.id_rol IN (3, 5) AND u.nit = ?
    ");
    $stmt->execute([$id_instructor, $nit_usuario]);
    $instructor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$instructor) {
        die("Instructor no encontrado");
    }

    // Generar reporte según el tipo
    switch ($tipo_reporte) {
        case 'general':
            generarReporteGeneral($conexion, $instructor);
            break;
        case 'personal':
            generarReportePersonal($instructor);
            break;
        case 'fichas':
            generarReporteFichas($conexion, $instructor);
            break;
        case 'ficha_individual':
            generarReporteFichaIndividual($conexion, $instructor, $id_ficha);
            break;
        default:
            die('Tipo de reporte no válido en switch: ' . $tipo_reporte);
    }

} catch (Exception $e) {
    die('Error al generar reporte: ' . $e->getMessage());
}

function generarReporteGeneral($conexion, $instructor) {
    // Obtener fichas asignadas con detalles
    $stmt = $conexion->prepare("
        SELECT DISTINCT
            f.id_ficha,
            fo.nombre as programa,
            tf.tipo_formacion,
            j.jornada,
            f.fecha_creac,
            COUNT(DISTINCT uf.id_user) as aprendices_asignados,
            COUNT(DISTINCT mf2.id_materia) as materias_asignadas,
            COALESCE(SUM(TIMESTAMPDIFF(MINUTE, h.hora_inicio, h.hora_fin)), 0) / 60 as horas_semanales
        FROM fichas f
        INNER JOIN materia_ficha mf ON f.id_ficha = mf.id_ficha
        LEFT JOIN formacion fo ON f.id_formacion = fo.id_formacion
        LEFT JOIN tipo_formacion tf ON fo.id_tipo_formacion = tf.id_tipo_formacion
        LEFT JOIN jornada j ON f.id_jornada = j.id_jornada
        LEFT JOIN materia_ficha mf2 ON f.id_ficha = mf2.id_ficha AND mf2.id_instructor = ?
        LEFT JOIN user_ficha uf ON f.id_ficha = uf.id_ficha AND uf.id_estado = 1
        LEFT JOIN horario h ON mf2.id_materia_ficha = h.id_materia_ficha AND h.id_estado = 1
        WHERE mf.id_instructor = ? AND f.id_estado = 1
        GROUP BY f.id_ficha, fo.nombre, tf.tipo_formacion, j.jornada, f.fecha_creac
        ORDER BY f.id_ficha DESC
    ");
    $stmt->execute([$instructor['id'], $instructor['id']]);
    $fichas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcular horas del trimestre actual
    $stmt = $conexion->prepare("
        SELECT 
            COALESCE(SUM(TIMESTAMPDIFF(MINUTE, h.hora_inicio, h.hora_fin)), 0) / 60 as total_horas_trimestre
        FROM horario h
        INNER JOIN materia_ficha mf ON h.id_materia_ficha = mf.id_materia_ficha
        INNER JOIN trimestre t ON h.id_trimestre = t.id_trimestre
        WHERE mf.id_instructor = ? 
        AND h.id_estado = 1
        AND MONTH(CURDATE()) BETWEEN t.mes_inicio AND t.mes_fin
    ");
    $stmt->execute([$instructor['id']]);
    $horas_trimestre = $stmt->fetch(PDO::FETCH_ASSOC)['total_horas_trimestre'] ?? 0;

    // Obtener materias especializadas
    $stmt = $conexion->prepare("
        SELECT m.materia, m.descripcion
        FROM materia_instructor mi
        INNER JOIN materias m ON mi.id_materia = m.id_materia
        WHERE mi.id_instructor = ?
        ORDER BY m.materia
    ");
    $stmt->execute([$instructor['id']]);
    $materias_especializadas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener horarios detallados
    $stmt = $conexion->prepare("
        SELECT 
            h.dia_semana,
            h.hora_inicio,
            h.hora_fin,
            m.materia,
            f.id_ficha,
            t.trimestre,
            TIMESTAMPDIFF(MINUTE, h.hora_inicio, h.hora_fin) / 60 as horas_clase
        FROM horario h
        INNER JOIN materia_ficha mf ON h.id_materia_ficha = mf.id_materia_ficha
        INNER JOIN materias m ON mf.id_materia = m.id_materia
        INNER JOIN fichas f ON mf.id_ficha = f.id_ficha
        LEFT JOIN trimestre t ON h.id_trimestre = t.id_trimestre
        WHERE mf.id_instructor = ? AND h.id_estado = 1
        ORDER BY h.dia_semana, h.hora_inicio
    ");
    $stmt->execute([$instructor['id']]);
    $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $nombre_archivo = "reporte_general_instructor_" . $instructor['id'];
    
    // Configurar headers para Excel
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $nombre_archivo . '_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="UTF-8"><title>Reporte General - Instructor</title></head>';
    echo '<body>';
    
    echo '<table border="1">';
    
    // Encabezado principal
    echo '<tr style="background-color: #0e4a86; color: white; font-weight: bold;">';
    echo '<td colspan="12" style="text-align: center; font-size: 16px;">REPORTE GENERAL DE INSTRUCTOR - TEAMTALKS</td>';
    echo '</tr>';
    echo '<tr style="background-color: #f8f9fa;">';
    echo '<td colspan="12" style="text-align: center;">Generado el: ' . date('d/m/Y H:i:s') . '</td>';
    echo '</tr>';
    echo '<tr><td colspan="12"></td></tr>';

    // Información personal
    echo '<tr style="background-color: #28a745; color: white; font-weight: bold;">';
    echo '<td colspan="12" style="text-align: center;">INFORMACIÓN PERSONAL</td>';
    echo '</tr>';
    
    echo '<tr>';
    echo '<td style="font-weight: bold;">Documento:</td>';
    echo '<td>' . htmlspecialchars($instructor['id']) . '</td>';
    echo '<td style="font-weight: bold;">Nombres:</td>';
    echo '<td colspan="3">' . htmlspecialchars($instructor['nombres'] . ' ' . $instructor['apellidos']) . '</td>';
    echo '<td style="font-weight: bold;">Rol:</td>';
    echo '<td>' . htmlspecialchars($instructor['rol']) . '</td>';
    echo '<td style="font-weight: bold;">Estado:</td>';
    echo '<td>' . htmlspecialchars($instructor['estado_nombre']) . '</td>';
    echo '<td colspan="2"></td>';
    echo '</tr>';
    
    echo '<tr>';
    echo '<td style="font-weight: bold;">Correo:</td>';
    echo '<td colspan="4">' . htmlspecialchars($instructor['correo']) . '</td>';
    echo '<td style="font-weight: bold;">Teléfono:</td>';
    echo '<td>' . htmlspecialchars($instructor['telefono'] ?? 'No registrado') . '</td>';
    echo '<td style="font-weight: bold;">Fecha Registro:</td>';
    echo '<td>' . date('d/m/Y', strtotime($instructor['fecha_registro'])) . '</td>';
    echo '<td colspan="3"></td>';
    echo '</tr>';

    echo '<tr><td colspan="12"></td></tr>';

    // Resumen de carga académica
    echo '<tr style="background-color: #17a2b8; color: white; font-weight: bold;">';
    echo '<td colspan="12" style="text-align: center;">RESUMEN DE CARGA ACADÉMICA</td>';
    echo '</tr>';
    
    $total_horas_semanales = array_sum(array_column($fichas, 'horas_semanales'));
    $total_aprendices = array_sum(array_column($fichas, 'aprendices_asignados'));
    
    echo '<tr>';
    echo '<td style="font-weight: bold;">Total Fichas:</td>';
    echo '<td>' . count($fichas) . '</td>';
    echo '<td style="font-weight: bold;">Total Aprendices:</td>';
    echo '<td>' . $total_aprendices . '</td>';
    echo '<td style="font-weight: bold;">Horas Semanales:</td>';
    echo '<td>' . number_format($total_horas_semanales, 1) . 'h</td>';
    echo '<td style="font-weight: bold;">Horas Trimestre:</td>';
    echo '<td>' . number_format($horas_trimestre, 1) . 'h</td>';
    echo '<td style="font-weight: bold;">Materias Especializadas:</td>';
    echo '<td>' . count($materias_especializadas) . '</td>';
    echo '<td colspan="2"></td>';
    echo '</tr>';

    echo '<tr><td colspan="12"></td></tr>';

    // Materias especializadas
    if (!empty($materias_especializadas)) {
        echo '<tr style="background-color: #ffc107; color: black; font-weight: bold;">';
        echo '<td colspan="12" style="text-align: center;">MATERIAS ESPECIALIZADAS</td>';
        echo '</tr>';
        
        echo '<tr style="background-color: #f8f9fa; font-weight: bold;">';
        echo '<td>MATERIA</td>';
        echo '<td colspan="11">DESCRIPCIÓN</td>';
        echo '</tr>';
        
        foreach ($materias_especializadas as $materia) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($materia['materia']) . '</td>';
            echo '<td colspan="11">' . htmlspecialchars($materia['descripcion'] ?? '') . '</td>';
            echo '</tr>';
        }
    }

    echo '<tr><td colspan="12"></td></tr>';

    // Fichas asignadas
    if (!empty($fichas)) {
        echo '<tr style="background-color: #6f42c1; color: white; font-weight: bold;">';
        echo '<td colspan="12" style="text-align: center;">FICHAS ASIGNADAS</td>';
        echo '</tr>';
        
        echo '<tr style="background-color: #f8f9fa; font-weight: bold;">';
        echo '<td>FICHA</td>';
        echo '<td>PROGRAMA</td>';
        echo '<td>TIPO</td>';
        echo '<td>JORNADA</td>';
        echo '<td>FECHA CREACIÓN</td>';
        echo '<td>APRENDICES</td>';
        echo '<td>MATERIAS</td>';
        echo '<td>HORAS/SEM</td>';
        echo '<td colspan="4">OBSERVACIONES</td>';
        echo '</tr>';
        
        foreach ($fichas as $ficha) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($ficha['id_ficha']) . '</td>';
            echo '<td>' . htmlspecialchars($ficha['programa']) . '</td>';
            echo '<td>' . htmlspecialchars($ficha['tipo_formacion']) . '</td>';
            echo '<td>' . htmlspecialchars($ficha['jornada']) . '</td>';
            echo '<td>' . date('d/m/Y', strtotime($ficha['fecha_creac'])) . '</td>';
            echo '<td>' . $ficha['aprendices_asignados'] . '</td>';
            echo '<td>' . $ficha['materias_asignadas'] . '</td>';
            echo '<td>' . number_format($ficha['horas_semanales'], 1) . 'h</td>';
            echo '<td colspan="4">Ficha activa con ' . $ficha['aprendices_asignados'] . ' aprendices</td>';
            echo '</tr>';
        }
    }

    echo '<tr><td colspan="12"></td></tr>';

    // Horarios detallados
    if (!empty($horarios)) {
        echo '<tr style="background-color: #dc3545; color: white; font-weight: bold;">';
        echo '<td colspan="12" style="text-align: center;">HORARIOS DETALLADOS</td>';
        echo '</tr>';
        
        echo '<tr style="background-color: #f8f9fa; font-weight: bold;">';
        echo '<td>DÍA</td>';
        echo '<td>HORA INICIO</td>';
        echo '<td>HORA FIN</td>';
        echo '<td>MATERIA</td>';
        echo '<td>FICHA</td>';
        echo '<td>TRIMESTRE</td>';
        echo '<td>HORAS CLASE</td>';
        echo '<td colspan="5">OBSERVACIONES</td>';
        echo '</tr>';
        
        foreach ($horarios as $horario) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($horario['dia_semana']) . '</td>';
            echo '<td>' . date('H:i', strtotime($horario['hora_inicio'])) . '</td>';
            echo '<td>' . date('H:i', strtotime($horario['hora_fin'])) . '</td>';
            echo '<td>' . htmlspecialchars($horario['materia']) . '</td>';
            echo '<td>' . htmlspecialchars($horario['id_ficha']) . '</td>';
            echo '<td>' . htmlspecialchars($horario['trimestre'] ?? 'No asignado') . '</td>';
            echo '<td>' . number_format($horario['horas_clase'], 1) . 'h</td>';
            echo '<td colspan="5">Clase programada</td>';
            echo '</tr>';
        }
    }

    // Pie de página
    echo '<tr><td colspan="12"></td></tr>';
    echo '<tr style="background-color: #6c757d; color: white; font-weight: bold;">';
    echo '<td colspan="12" style="text-align: center;">REPORTE GENERADO POR TEAMTALKS - ' . date('d/m/Y H:i:s') . '</td>';
    echo '</tr>';
    
    echo '</table>';
    echo '</body></html>';
}

function generarReportePersonal($instructor) {
    $nombre_archivo = "reporte_personal_instructor_" . $instructor['id'];
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $nombre_archivo . '_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="UTF-8"><title>Reporte Personal - Instructor</title></head>';
    echo '<body>';
    
    echo '<table border="1">';
    
    echo '<tr style="background-color: #0e4a86; color: white; font-weight: bold;">';
    echo '<td colspan="6" style="text-align: center; font-size: 16px;">REPORTE DE DATOS PERSONALES - TEAMTALKS</td>';
    echo '</tr>';
    echo '<tr style="background-color: #f8f9fa;">';
    echo '<td colspan="6" style="text-align: center;">Generado el: ' . date('d/m/Y H:i:s') . '</td>';
    echo '</tr>';
    echo '<tr><td colspan="6"></td></tr>';

    echo '<tr style="background-color: #28a745; color: white; font-weight: bold;">';
    echo '<td colspan="6" style="text-align: center;">INFORMACIÓN PERSONAL</td>';
    echo '</tr>';
    
    echo '<tr>';
    echo '<td style="font-weight: bold; background-color: #f8f9fa;">Documento de Identidad:</td>';
    echo '<td>' . htmlspecialchars($instructor['id']) . '</td>';
    echo '<td style="font-weight: bold; background-color: #f8f9fa;">Estado:</td>';
    echo '<td>' . htmlspecialchars($instructor['estado_nombre']) . '</td>';
    echo '<td colspan="2"></td>';
    echo '</tr>';
    
    echo '<tr>';
    echo '<td style="font-weight: bold; background-color: #f8f9fa;">Nombres Completos:</td>';
    echo '<td colspan="3">' . htmlspecialchars($instructor['nombres'] . ' ' . $instructor['apellidos']) . '</td>';
    echo '<td colspan="2"></td>';
    echo '</tr>';
    
    echo '<tr>';
    echo '<td style="font-weight: bold; background-color: #f8f9fa;">Correo Electrónico:</td>';
    echo '<td colspan="3">' . htmlspecialchars($instructor['correo']) . '</td>';
    echo '<td colspan="2"></td>';
    echo '</tr>';
    
    echo '<tr>';
    echo '<td style="font-weight: bold; background-color: #f8f9fa;">Número de Teléfono:</td>';
    echo '<td>' . htmlspecialchars($instructor['telefono'] ?? 'No registrado') . '</td>';
    echo '<td style="font-weight: bold; background-color: #f8f9fa;">Fecha de Registro:</td>';
    echo '<td>' . date('d/m/Y', strtotime($instructor['fecha_registro'])) . '</td>';
    echo '<td colspan="2"></td>';
    echo '</tr>';
    
    echo '<tr>';
    echo '<td style="font-weight: bold; background-color: #f8f9fa;">Rol en el Sistema:</td>';
    echo '<td colspan="5">' . htmlspecialchars($instructor['rol']) . '</td>';
    echo '</tr>';

    echo '<tr><td colspan="6"></td></tr>';
    echo '<tr style="background-color: #6c757d; color: white; font-weight: bold;">';
    echo '<td colspan="6" style="text-align: center;">REPORTE GENERADO POR TEAMTALKS - ' . date('d/m/Y H:i:s') . '</td>';
    echo '</tr>';
    
    echo '</table>';
    echo '</body></html>';
}

function generarReporteFichas($conexion, $instructor) {
    // Obtener fichas con detalles completos
    $stmt = $conexion->prepare("
        SELECT DISTINCT
            f.id_ficha,
            fo.nombre as programa,
            tf.tipo_formacion,
            j.jornada,
            f.fecha_creac,
            COUNT(DISTINCT uf.id_user) as aprendices_asignados,
            COUNT(DISTINCT mf2.id_materia) as materias_asignadas,
            -- Obtener nombres de aprendices
            GROUP_CONCAT(DISTINCT CONCAT(u_ap.nombres, ' ', u_ap.apellidos) SEPARATOR ', ') as nombres_aprendices
        FROM fichas f
        INNER JOIN materia_ficha mf ON f.id_ficha = mf.id_ficha
        LEFT JOIN formacion fo ON f.id_formacion = fo.id_formacion
        LEFT JOIN tipo_formacion tf ON fo.id_tipo_formacion = tf.id_tipo_formacion
        LEFT JOIN jornada j ON f.id_jornada = j.id_jornada
        LEFT JOIN materia_ficha mf2 ON f.id_ficha = mf2.id_ficha AND mf2.id_instructor = ?
        LEFT JOIN user_ficha uf ON f.id_ficha = uf.id_ficha AND uf.id_estado = 1
        LEFT JOIN usuarios u_ap ON uf.id_user = u_ap.id
        WHERE mf.id_instructor = ? AND f.id_estado = 1
        GROUP BY f.id_ficha, fo.nombre, tf.tipo_formacion, j.jornada, f.fecha_creac
        ORDER BY f.id_ficha DESC
    ");
    $stmt->execute([$instructor['id'], $instructor['id']]);
    $fichas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $nombre_archivo = "reporte_fichas_instructor_" . $instructor['id'];
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $nombre_archivo . '_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="UTF-8"><title>Reporte Fichas - Instructor</title></head>';
    echo '<body>';
    
    echo '<table border="1">';
    
    echo '<tr style="background-color: #0e4a86; color: white; font-weight: bold;">';
    echo '<td colspan="10" style="text-align: center; font-size: 16px;">REPORTE DE FICHAS ASIGNADAS - TEAMTALKS</td>';
    echo '</tr>';
    echo '<tr style="background-color: #f8f9fa;">';
    echo '<td colspan="10" style="text-align: center;">Instructor: ' . htmlspecialchars($instructor['nombres'] . ' ' . $instructor['apellidos']) . ' - Documento: ' . $instructor['id'] . '</td>';
    echo '</tr>';
    echo '<tr style="background-color: #f8f9fa;">';
    echo '<td colspan="10" style="text-align: center;">Generado el: ' . date('d/m/Y H:i:s') . '</td>';
    echo '</tr>';
    echo '<tr><td colspan="10"></td></tr>';

    if (!empty($fichas)) {
        echo '<tr style="background-color: #28a745; color: white; font-weight: bold;">';
        echo '<td colspan="10" style="text-align: center;">FICHAS ASIGNADAS (' . count($fichas) . ' fichas)</td>';
        echo '</tr>';
        
        echo '<tr style="background-color: #f8f9fa; font-weight: bold;">';
        echo '<td>FICHA</td>';
        echo '<td>PROGRAMA DE FORMACIÓN</td>';
        echo '<td>TIPO</td>';
        echo '<td>JORNADA</td>';
        echo '<td>FECHA CREACIÓN</td>';
        echo '<td>APRENDICES</td>';
        echo '<td>MATERIAS</td>';
        echo '<td colspan="3">NOMBRES DE APRENDICES</td>';
        echo '</tr>';
        
        foreach ($fichas as $ficha) {
            echo '<tr>';
            echo '<td><strong>' . htmlspecialchars($ficha['id_ficha']) . '</strong></td>';
            echo '<td>' . htmlspecialchars($ficha['programa']) . '</td>';
            echo '<td>' . htmlspecialchars($ficha['tipo_formacion']) . '</td>';
            echo '<td>' . htmlspecialchars($ficha['jornada']) . '</td>';
            echo '<td>' . date('d/m/Y', strtotime($ficha['fecha_creac'])) . '</td>';
            echo '<td>' . $ficha['aprendices_asignados'] . '</td>';
            echo '<td>' . $ficha['materias_asignadas'] . '</td>';
            echo '<td colspan="3">' . htmlspecialchars($ficha['nombres_aprendices'] ?? 'Sin aprendices asignados') . '</td>';
            echo '</tr>';
        }

        // Resumen estadístico
        echo '<tr><td colspan="10"></td></tr>';
        echo '<tr style="background-color: #17a2b8; color: white; font-weight: bold;">';
        echo '<td colspan="10" style="text-align: center;">RESUMEN ESTADÍSTICO</td>';
        echo '</tr>';
        
        $total_aprendices = array_sum(array_column($fichas, 'aprendices_asignados'));
        $total_materias = array_sum(array_column($fichas, 'materias_asignadas'));
        
        echo '<tr>';
        echo '<td colspan="2" style="font-weight: bold;">Total de Fichas:</td>';
        echo '<td>' . count($fichas) . '</td>';
        echo '<td colspan="2" style="font-weight: bold;">Total de Aprendices:</td>';
        echo '<td>' . $total_aprendices . '</td>';
        echo '<td colspan="2" style="font-weight: bold;">Total de Materias:</td>';
        echo '<td>' . $total_materias . '</td>';
        echo '<td></td>';
        echo '</tr>';
    } else {
        echo '<tr style="background-color: #ffc107; color: black; font-weight: bold;">';
        echo '<td colspan="10" style="text-align: center;">NO HAY FICHAS ASIGNADAS A ESTE INSTRUCTOR</td>';
        echo '</tr>';
    }

    echo '<tr><td colspan="10"></td></tr>';
    echo '<tr style="background-color: #6c757d; color: white; font-weight: bold;">';
    echo '<td colspan="10" style="text-align: center;">REPORTE GENERADO POR TEAMTALKS - ' . date('d/m/Y H:i:s') . '</td>';
    echo '</tr>';
    
    echo '</table>';
    echo '</body></html>';
}

function generarReporteFichaIndividual($conexion, $instructor, $id_ficha) {
    // Obtener detalles de la ficha específica
    $stmt = $conexion->prepare("
        SELECT DISTINCT
            f.id_ficha,
            fo.nombre as programa,
            tf.tipo_formacion,
            j.jornada,
            f.fecha_creac,
            COUNT(DISTINCT uf.id_user) as aprendices_asignados
        FROM fichas f
        INNER JOIN materia_ficha mf ON f.id_ficha = mf.id_ficha
        LEFT JOIN formacion fo ON f.id_formacion = fo.id_formacion
        LEFT JOIN tipo_formacion tf ON fo.id_tipo_formacion = tf.id_tipo_formacion
        LEFT JOIN jornada j ON f.id_jornada = j.id_jornada
        LEFT JOIN user_ficha uf ON f.id_ficha = uf.id_ficha AND uf.id_estado = 1
        WHERE mf.id_instructor = ? AND f.id_ficha = ? AND f.id_estado = 1
        GROUP BY f.id_ficha, fo.nombre, tf.tipo_formacion, j.jornada, f.fecha_creac
    ");
    $stmt->execute([$instructor['id'], $id_ficha]);
    $ficha = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ficha) {
        die('Ficha no encontrada o no asignada a este instructor.');
    }

    // Obtener aprendices de la ficha
    $stmt = $conexion->prepare("
        SELECT 
            u.id as documento,
            u.nombres,
            u.apellidos,
            u.correo,
            u.telefono,
            e.estado
        FROM user_ficha uf
        INNER JOIN usuarios u ON uf.id_user = u.id
        LEFT JOIN estado e ON u.id_estado = e.id_estado
        WHERE uf.id_ficha = ? AND uf.id_estado = 1
        ORDER BY u.nombres, u.apellidos
    ");
    $stmt->execute([$id_ficha]);
    $aprendices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener horarios de la ficha
    $stmt = $conexion->prepare("
        SELECT 
            h.dia_semana,
            h.hora_inicio,
            h.hora_fin,
            m.materia,
            t.trimestre,
            TIMESTAMPDIFF(MINUTE, h.hora_inicio, h.hora_fin) / 60 as horas_clase
        FROM horario h
        INNER JOIN materia_ficha mf ON h.id_materia_ficha = mf.id_materia_ficha
        INNER JOIN materias m ON mf.id_materia = m.id_materia
        LEFT JOIN trimestre t ON h.id_trimestre = t.id_trimestre
        WHERE mf.id_instructor = ? AND mf.id_ficha = ? AND h.id_estado = 1
        ORDER BY h.dia_semana, h.hora_inicio
    ");
    $stmt->execute([$instructor['id'], $id_ficha]);
    $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $nombre_archivo = "reporte_ficha_" . $id_ficha . "_instructor_" . $instructor['id'];
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $nombre_archivo . '_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="UTF-8"><title>Reporte Ficha Individual</title></head>';
    echo '<body>';
    
    echo '<table border="1">';
    
    echo '<tr style="background-color: #0e4a86; color: white; font-weight: bold;">';
    echo '<td colspan="8" style="text-align: center; font-size: 16px;">REPORTE DE FICHA INDIVIDUAL - TEAMTALKS</td>';
    echo '</tr>';
    echo '<tr style="background-color: #f8f9fa;">';
    echo '<td colspan="8" style="text-align: center;">Ficha: ' . $ficha['id_ficha'] . ' - Instructor: ' . htmlspecialchars($instructor['nombres'] . ' ' . $instructor['apellidos']) . '</td>';
    echo '</tr>';
    echo '<tr style="background-color: #f8f9fa;">';
    echo '<td colspan="8" style="text-align: center;">Generado el: ' . date('d/m/Y H:i:s') . '</td>';
    echo '</tr>';
    echo '<tr><td colspan="8"></td></tr>';

    // Información de la ficha
    echo '<tr style="background-color: #28a745; color: white; font-weight: bold;">';
    echo '<td colspan="8" style="text-align: center;">INFORMACIÓN DE LA FICHA</td>';
    echo '</tr>';
    
    echo '<tr>';
    echo '<td style="font-weight: bold; background-color: #f8f9fa;">Número de Ficha:</td>';
    echo '<td>' . htmlspecialchars($ficha['id_ficha']) . '</td>';
    echo '<td style="font-weight: bold; background-color: #f8f9fa;">Programa:</td>';
    echo '<td colspan="3">' . htmlspecialchars($ficha['programa']) . '</td>';
    echo '<td style="font-weight: bold; background-color: #f8f9fa;">Jornada:</td>';
    echo '<td>' . htmlspecialchars($ficha['jornada']) . '</td>';
    echo '</tr>';
    
    echo '<tr>';
    echo '<td style="font-weight: bold; background-color: #f8f9fa;">Tipo de Formación:</td>';
    echo '<td>' . htmlspecialchars($ficha['tipo_formacion']) . '</td>';
    echo '<td style="font-weight: bold; background-color: #f8f9fa;">Fecha Creación:</td>';
    echo '<td>' . date('d/m/Y', strtotime($ficha['fecha_creac'])) . '</td>';
    echo '<td style="font-weight: bold; background-color: #f8f9fa;">Aprendices:</td>';
    echo '<td>' . $ficha['aprendices_asignados'] . '</td>';
    echo '<td colspan="2"></td>';
    echo '</tr>';

    echo '<tr><td colspan="8"></td></tr>';

    // Instructor asignado
    echo '<tr style="background-color: #17a2b8; color: white; font-weight: bold;">';
    echo '<td colspan="8" style="text-align: center;">INSTRUCTOR ASIGNADO</td>';
    echo '</tr>';
    
    echo '<tr>';
    echo '<td style="font-weight: bold; background-color: #f8f9fa;">Nombre:</td>';
    echo '<td colspan="3">' . htmlspecialchars($instructor['nombres'] . ' ' . $instructor['apellidos']) . '</td>';
    echo '<td style="font-weight: bold; background-color: #f8f9fa;">Documento:</td>';
    echo '<td>' . htmlspecialchars($instructor['id']) . '</td>';
    echo '<td style="font-weight: bold; background-color: #f8f9fa;">Correo:</td>';
    echo '<td>' . htmlspecialchars($instructor['correo']) . '</td>';
    echo '</tr>';

    echo '<tr><td colspan="8"></td></tr>';

    // Aprendices asignados
    if (!empty($aprendices)) {
        echo '<tr style="background-color: #ffc107; color: black; font-weight: bold;">';
        echo '<td colspan="8" style="text-align: center;">APRENDICES ASIGNADOS (' . count($aprendices) . ' aprendices)</td>';
        echo '</tr>';
        
        echo '<tr style="background-color: #f8f9fa; font-weight: bold;">';
        echo '<td>DOCUMENTO</td>';
        echo '<td>NOMBRES</td>';
        echo '<td>APELLIDOS</td>';
        echo '<td>CORREO</td>';
        echo '<td>TELÉFONO</td>';
        echo '<td>ESTADO</td>';
        echo '<td colspan="2">OBSERVACIONES</td>';
        echo '</tr>';
        
        foreach ($aprendices as $aprendiz) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($aprendiz['documento']) . '</td>';
            echo '<td>' . htmlspecialchars($aprendiz['nombres']) . '</td>';
            echo '<td>' . htmlspecialchars($aprendiz['apellidos']) . '</td>';
            echo '<td>' . htmlspecialchars($aprendiz['correo']) . '</td>';
            echo '<td>' . htmlspecialchars($aprendiz['telefono'] ?? 'No registrado') . '</td>';
            echo '<td>' . htmlspecialchars($aprendiz['estado']) . '</td>';
            echo '<td colspan="2">Aprendiz activo en la ficha</td>';
            echo '</tr>';
        }
    }

    echo '<tr><td colspan="8"></td></tr>';

    // Horarios asignados
    if (!empty($horarios)) {
        echo '<tr style="background-color: #6f42c1; color: white; font-weight: bold;">';
        echo '<td colspan="8" style="text-align: center;">HORARIOS ASIGNADOS</td>';
        echo '</tr>';
        
        echo '<tr style="background-color: #f8f9fa; font-weight: bold;">';
        echo '<td>DÍA</td>';
        echo '<td>HORA INICIO</td>';
        echo '<td>HORA FIN</td>';
        echo '<td>MATERIA</td>';
        echo '<td>TRIMESTRE</td>';
        echo '<td>HORAS CLASE</td>';
        echo '<td colspan="2">OBSERVACIONES</td>';
        echo '</tr>';
        
        $total_horas_semanales = 0;
        foreach ($horarios as $horario) {
            $total_horas_semanales += $horario['horas_clase'];
            echo '<tr>';
            echo '<td>' . htmlspecialchars($horario['dia_semana']) . '</td>';
            echo '<td>' . date('H:i', strtotime($horario['hora_inicio'])) . '</td>';
            echo '<td>' . date('H:i', strtotime($horario['hora_fin'])) . '</td>';
            echo '<td>' . htmlspecialchars($horario['materia']) . '</td>';
            echo '<td>' . htmlspecialchars($horario['trimestre'] ?? 'No asignado') . '</td>';
            echo '<td>' . number_format($horario['horas_clase'], 1) . 'h</td>';
            echo '<td colspan="2">Clase programada</td>';
            echo '</tr>';
        }
        
        echo '<tr style="background-color: #e9ecef; font-weight: bold;">';
        echo '<td colspan="5">TOTAL HORAS SEMANALES:</td>';
        echo '<td>' . number_format($total_horas_semanales, 1) . 'h</td>';
        echo '<td colspan="2"></td>';
        echo '</tr>';
    }

    echo '<tr><td colspan="8"></td></tr>';
    echo '<tr style="background-color: #6c757d; color: white; font-weight: bold;">';
    echo '<td colspan="8" style="text-align: center;">REPORTE GENERADO POR TEAMTALKS - ' . date('d/m/Y H:i:s') . '</td>';
    echo '</tr>';
    
    echo '</table>';
    echo '</body></html>';
}
?>

<?php
session_start();
require_once '../../conexion/conexion.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['documento'])) {
    header('Location: ../login/login.php');
    exit;
}

// Crear instancia de la conexión
$db = new Database();
$conexion = $db->connect();

// Configurar headers para descarga de Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="reporte_horarios_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');

// Obtener datos de horarios con información completa
try {
    $stmt = $conexion->query("
        SELECT 
            h.id_horario,
            h.nombre_horario,
            h.descripcion,
            h.fecha_creacion,
            j.jornada,
            e.estado,
            COUNT(h.id_ficha) as fichas_asignadas,
            GROUP_CONCAT(DISTINCT f.id_fichaORDER BY f.id_ficha SEPARATOR ', ') as numeros_fichas,
            GROUP_CONCAT(DISTINCT hd.dia_semana ORDER BY 
                CASE hd.dia_semana 
                    WHEN 'Lunes' THEN 1 
                    WHEN 'Martes' THEN 2 
                    WHEN 'Miércoles' THEN 3 
                    WHEN 'Jueves' THEN 4 
                    WHEN 'Viernes' THEN 5 
                    WHEN 'Sábado' THEN 6 
                END SEPARATOR ', ') as dias_semana,
            MIN(hd.hora_inicio) as hora_inicio,
            MAX(hd.hora_fin) as hora_fin
        FROM horario h
        LEFT JOIN jornada j ON h.id_jornada = j.id_jornada
        LEFT JOIN estado e ON h.id_estado = e.id_estado
        LEFT JOIN horario hf ON h.id_horario = h.id_horario
        LEFT JOIN fichas f ON hf.id_ficha = f.id_ficha
        LEFT JOIN horario hd ON h.id_horario = hd.id_horario_padre
        WHERE h.nombre_horario IS NOT NULL
        GROUP BY h.id_horario
        ORDER BY h.id_horario DESC
    ");
    $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener datos: " . $e->getMessage());
}

// Generar contenido Excel
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
    xmlns:o="urn:schemas-microsoft-com:office:office"
    xmlns:x="urn:schemas-microsoft-com:office:excel"
    xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
    xmlns:html="http://www.w3.org/TR/REC-html40">

    <Worksheet ss:Name="Reporte Horarios">
        <Table>
            <Row>
                <Cell><Data ss:Type="String">REPORTE DE HORARIOS SENA</Data></Cell>
            </Row>
            <Row>
                <Cell><Data ss:Type="String">Fecha de generación: <?php echo date('d/m/Y H:i:s'); ?></Data></Cell>
            </Row>
            <Row></Row>

            <!-- Encabezados -->
            <Row>
                <Cell><Data ss:Type="String">ID</Data></Cell>
                <Cell><Data ss:Type="String">Nombre Horario</Data></Cell>
                <Cell><Data ss:Type="String">Descripción</Data></Cell>
                <Cell><Data ss:Type="String">Jornada</Data></Cell>
                <Cell><Data ss:Type="String">Días</Data></Cell>
                <Cell><Data ss:Type="String">Hora Inicio</Data></Cell>
                <Cell><Data ss:Type="String">Hora Fin</Data></Cell>
                <Cell><Data ss:Type="String">Fichas Asignadas</Data></Cell>
                <Cell><Data ss:Type="String">Números de Fichas</Data></Cell>
                <Cell><Data ss:Type="String">Estado</Data></Cell>
                <Cell><Data ss:Type="String">Fecha Creación</Data></Cell>
            </Row>

            <!-- Datos -->
            <?php foreach ($horarios as $horario): ?>
                <Row>
                    <Cell><Data ss:Type="Number"><?php echo htmlspecialchars($horario['id_horario']); ?></Data></Cell>
                    <Cell><Data ss:Type="String"><?php echo htmlspecialchars($horario['nombre_horario']); ?></Data></Cell>
                    <Cell><Data ss:Type="String"><?php echo htmlspecialchars($horario['descripcion'] ?? ''); ?></Data></Cell>
                    <Cell><Data ss:Type="String"><?php echo htmlspecialchars($horario['jornada'] ?? ''); ?></Data></Cell>
                    <Cell><Data ss:Type="String"><?php echo htmlspecialchars($horario['dias_semana'] ?? ''); ?></Data></Cell>
                    <Cell><Data ss:Type="String"><?php echo htmlspecialchars($horario['hora_inicio'] ?? ''); ?></Data></Cell>
                    <Cell><Data ss:Type="String"><?php echo htmlspecialchars($horario['hora_fin'] ?? ''); ?></Data></Cell>
                    <Cell><Data ss:Type="Number"><?php echo $horario['fichas_asignadas']; ?></Data></Cell>
                    <Cell><Data ss:Type="String"><?php echo htmlspecialchars($horario['numeros_fichas'] ?? 'Sin asignar'); ?></Data></Cell>
                    <Cell><Data ss:Type="String"><?php echo htmlspecialchars($horario['estado'] ?? 'Activo'); ?></Data></Cell>
                    <Cell><Data ss:Type="String"><?php echo htmlspecialchars($horario['fecha_creacion']); ?></Data></Cell>
                </Row>
            <?php endforeach; ?>

            <Row></Row>
            <Row>
                <Cell><Data ss:Type="String">Total de horarios: <?php echo count($horarios); ?></Data></Cell>
            </Row>
        </Table>
    </Worksheet>
</Workbook>
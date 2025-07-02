<?php
// Archivo de prueba para verificar reportes
session_start();

// Simular sesión para pruebas (REMOVER EN PRODUCCIÓN)
if (!isset($_SESSION['documento'])) {
    $_SESSION['documento'] = 'test';
    $_SESSION['rol'] = 2;
}

$tipo = $_GET['tipo'] ?? 'test';
$id_instructor = $_GET['id_instructor'] ?? '123456';

// Headers para Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="test_reporte_' . $tipo . '_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');

echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
echo '<head><meta charset="UTF-8"><title>Test Reporte</title></head>';
echo '<body>';

echo '<table border="1">';
echo '<tr style="background-color: #0e4a86; color: white; font-weight: bold;">';
echo '<td colspan="4" style="text-align: center; font-size: 16px;">TEST REPORTE - TEAMTALKS</td>';
echo '</tr>';
echo '<tr>';
echo '<td>Tipo:</td>';
echo '<td>' . htmlspecialchars($tipo) . '</td>';
echo '<td>Instructor:</td>';
echo '<td>' . htmlspecialchars($id_instructor) . '</td>';
echo '</tr>';
echo '<tr>';
echo '<td>Fecha:</td>';
echo '<td>' . date('d/m/Y H:i:s') . '</td>';
echo '<td>Estado:</td>';
echo '<td>FUNCIONANDO</td>';
echo '</tr>';
echo '</table>';

echo '</body></html>';
?>

<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php';
include 'session.php';

// Obtener el ID de la ficha desde la URL, asegurándose de que sea un entero
$id_ficha = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Consulta SQL para obtener todos los detalles de la ficha
$sql = "
    SELECT 
        f.id_ficha, 
        fo.nombre AS nombre_formacion, 
        f.id_ambiente,
        u.nombres AS nom_instru, 
        u.apellidos AS ape_instru, 
        j.jornada, 
        tf.tipo_ficha,
        tfo.tipo_formacion,
        f.id_trimestre
    FROM fichas f
    JOIN formacion fo ON f.id_formacion = fo.id_formacion
    JOIN tipo_formacion tfo ON fo.id_tipo_formacion = tfo.id_tipo_formacion
    JOIN usuarios u ON f.id_instructor = u.id
    JOIN jornada j ON f.id_jornada = j.id_jornada
    JOIN tipo_ficha tf ON f.id_tipo_ficha = tf.id_tipo_ficha
    WHERE f.id_ficha = :id
";

$stmt = $conex->prepare($sql);
$stmt->execute(['id' => $id_ficha]);
$ficha = $stmt->fetch(PDO::FETCH_ASSOC);


// Si se encuentra la ficha, mostrar los datos en una tabla para el modal
if ($ficha) {
    echo "
    <div class='container-fluid'>
        <table class='table table-bordered table-hover'>
            <tbody>
                <tr>
                    <th scope='row'>Número de ficha</th>
                    <td>{$ficha['id_ficha']}</td>
                </tr>
                <tr>
                    <th scope='row'>Nombre de la formación</th>
                    <td>{$ficha['nombre_formacion']}</td>
                </tr>
                <tr>
                    <th scope='row'>Ambiente</th>
                    <td>{$ficha['id_ambiente']}</td>
                </tr>
                <tr>
                    <th scope='row'>Instructor gerente</th>
                    <td>{$ficha['nom_instru']} {$ficha['ape_instru']}</td>
                </tr>
                <tr>
                    <th scope='row'>Jornada</th>
                    <td>{$ficha['jornada']}</td>
                </tr>
                <tr>
                    <th scope='row'>Tipo de ficha</th>
                    <td>{$ficha['tipo_ficha']}</td>
                </tr>
                <tr>
                    <th scope='row'>Tipo de formación</th>
                    <td>{$ficha['tipo_formacion']}</td>
                </tr>
                <tr>
                    <th scope='row'>Trimestre</th>
                    <td>{$ficha['id_trimestre']}</td>
                </tr>
            </tbody>
        </table>
    </div>
    ";
} else {
     // Si no se encuentra la ficha, mostrar un mensaje de advertencia
    echo "<div class='alert alert-warning'>No se encontraron detalles.</div>";
}
?>

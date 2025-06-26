<?php
// Configura tus datos de conexión:
$host = "localhost";
$user = "u148394603_teamtalks";
$pass = "TeamTalks2901879";
$db   = "u148394603_teamtalks";

// Conexión a MySQL
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Desactivar todas las materias
$conn->query("UPDATE materia_ficha SET id_estado = 2");

// Activar materias del trimestre actual
$conn->query("UPDATE materia_ficha mf
    JOIN trimestre t ON mf.id_trimestre = t.id_trimestre
    SET mf.id_estado = 1
    WHERE MONTH(CURDATE()) BETWEEN t.mes_inicio AND t.mes_fin");

$conn->close();
?>

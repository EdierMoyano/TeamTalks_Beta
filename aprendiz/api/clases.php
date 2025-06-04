<?php
header('Content-Type: application/json');

/*
 * API: clases.php
 * Descripción:
 * Obtiene la lista de clases creadas, mostrando el nombre de la materia, el nombre del profesor,
 * el número de ficha al que pertenece y una imagen simulada para cada clase.
 * 
 * Tablas involucradas:
 * - materia_ficha: Relaciona materias, instructores y fichas.
 * - materias: Contiene los nombres de las materias.
 * - usuarios: Contiene los datos de los instructores.
 * - fichas: Contiene los números de ficha.
 */

// Configuración de la base de datos
$host = 'localhost';
$db = 'teamtalks';
$user = 'root';
$pass = ''; 
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    // Conexión a la base de datos
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Consulta SQL para obtener la información de las clases
    $sql = "SELECT 
            mf.id_materia_ficha AS id_clase,
            m.materia AS nombre_clase,
            CONCAT(TRIM(u.nombres), ' ', TRIM(u.apellidos)) AS nombre_profesor,
            f.id_ficha AS numero_fichas
        FROM materia_ficha mf
        JOIN materias m ON mf.id_materia = m.id_materia
        JOIN usuarios u ON mf.id_instructor = u.id
        JOIN fichas f ON mf.id_ficha = f.id_ficha
    ";

    $stmt = $pdo->query($sql);
    $clases = $stmt->fetchAll();

    // Agregar imagen simulada a cada clase
    foreach ($clases as &$clase) {
        $clase['imagen'] = "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSLlPxXwu6GBz2YNT0kRZhPElAeyZArGF2evQ&s" . urlencode($clase['nombre_clase']);
    }

    // Devolver el resultado en formato JSON
    echo json_encode($clases);

} catch (PDOException $e) {
    // Manejo de errores de conexión o consulta
    echo json_encode(['error' => 'Error de conexión o consulta: ' . $e->getMessage()]);
}

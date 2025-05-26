<?php
header('Content-Type: application/json');

// Conexión
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
    $pdo = new PDO($dsn, $user, $pass, $options);

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

    // Agregar imagen simulada
    foreach ($clases as &$clase) {
        $clase['imagen'] = "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSLlPxXwu6GBz2YNT0kRZhPElAeyZArGF2evQ&s" . urlencode($clase['nombre_clase']);
    }

    echo json_encode($clases);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Error de conexión o consulta: ' . $e->getMessage()]);
}

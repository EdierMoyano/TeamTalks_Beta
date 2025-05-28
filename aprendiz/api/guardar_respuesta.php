<?php
header('Content-Type: application/json');

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

    // Leer entrada JSON
    $input = json_decode(file_get_contents('php://input'), true);

    // Validar campos requeridos
    if (
        !isset($input['id_tema_foro']) ||
        !isset($input['descripcion']) ||
        !isset($input['id_user'])
    ) {
        echo json_encode(['error' => 'Faltan datos obligatorios']);
        exit;
    }

    $id_tema_foro = $input['id_tema_foro'];
    $descripcion = trim($input['descripcion']);
    $id_user = $input['id_user'];
    $fecha = date('Y-m-d H:i:s');

    $sql = "INSERT INTO respuesta_foro (id_tema_foro, descripcion, fecha_respuesta, id_user)
            VALUES (:id_tema_foro, :descripcion, :fecha_respuesta, :id_user)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id_tema_foro' => $id_tema_foro,
        ':descripcion' => $descripcion,
        ':fecha_respuesta' => $fecha,
        ':id_user' => $id_user
    ]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error al guardar la respuesta: ' . $e->getMessage()]);
}

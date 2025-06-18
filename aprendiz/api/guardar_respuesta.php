<?php
header('Content-Type: application/json');

/*
 * API: guardar_respuesta.php
 * Descripción:
 * Recibe una petición POST en formato JSON para guardar una respuesta en un foro.
 * Inserta la respuesta en la tabla 'respuesta_foro' con el id del tema, la descripción,
 * la fecha y el usuario que responde.
 * 
 * Campos requeridos en el JSON de entrada:
 * - id_tema_foro: ID del tema del foro al que se responde.
 * - descripcion: Texto de la respuesta.
 * - id_user: ID del usuario que responde.
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

    // Insertar la respuesta en la base de datos
    $sql = "INSERT INTO respuesta_foro (id_tema_foro, descripcion, fecha_respuesta, id_user)
            VALUES (:id_tema_foro, :descripcion, :fecha_respuesta, :id_user)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id_tema_foro' => $id_tema_foro,
        ':descripcion' => $descripcion,
        ':fecha_respuesta' => $fecha,
        ':id_user' => $id_user
    ]);

    // Respuesta exitosa
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    // Manejo de errores de conexión o consulta
    echo json_encode(['error' => 'Error al guardar la respuesta: ' . $e->getMessage()]);
}

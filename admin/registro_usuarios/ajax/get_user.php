<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['documento'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Verificar si se recibió el ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de usuario no especificado']);
    exit;
}

$userId = $_GET['id'];

// Crear instancia de la conexión
$db = new Database();
$conexion = $db->connect();

try {
    // Obtener datos del usuario
    $stmt = $conexion->prepare("
        SELECT u.*, uf.id_ficha 
        FROM usuarios u
        LEFT JOIN (
            SELECT id_user, id_ficha 
            FROM user_ficha 
            WHERE id_estado = 1 
            ORDER BY fecha_asig DESC 
            LIMIT 1
        ) uf ON u.id = uf.id_user
        WHERE u.id = :id
    ");
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>

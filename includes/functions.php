<?php
/**
 * Funciones de utilidad para el sistema
 */

/**
 * Formatea una fecha en formato legible
 * @param string $date Fecha en formato Y-m-d
 * @return string Fecha formateada
 */
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

/**
 * Verifica si un usuario tiene un rol específico
 * @param mysqli $conexion Conexión a la base de datos
 * @param int $userId ID del usuario
 * @param array $roles Array de roles permitidos
 * @return bool True si el usuario tiene alguno de los roles, false en caso contrario
 */
function hasRole($conexion, $userId, $roles = []) {
    if (empty($roles)) {
        return true;
    }
    
    $roles_str = implode(',', array_map(function($role) use ($conexion) {
        return $conexion->real_escape_string($role);
    }, $roles));
    
    $query = "SELECT COUNT(*) as count FROM usuarios u 
              JOIN roles r ON u.id_rol = r.id_rol 
              WHERE u.id = ? AND r.id_rol IN ($roles_str)";
    
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'] > 0;
}

/**
 * Genera un número de ficha único
 * @param mysqli $conexion Conexión a la base de datos
 * @return string Número de ficha
 */
function generateFichaNumber($conexion) {
    $prefix = date('Ym');
    
    $query = "SELECT MAX(CAST(SUBSTRING(ficha_nom, 8) AS UNSIGNED)) as last_number 
              FROM fichas 
              WHERE ficha_nom LIKE '{$prefix}%'";
    
    $result = $conexion->query($query);
    $row = $result->fetch_assoc();
    
    $lastNumber = $row['last_number'] ?? 0;
    $newNumber = $lastNumber + 1;
    
    return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
}

/**
 * Obtiene el nombre del usuario por su ID
 * @param mysqli $conexion Conexión a la base de datos
 * @param int $userId ID del usuario
 * @return string Nombre completo del usuario
 */
function getUserName($conexion, $userId) {
    $stmt = $conexion->prepare("SELECT nombres, apellidos FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['nombres'] . ' ' . $row['apellidos'];
    }
    
    return 'Usuario desconocido';
}

/**
 * Limpia y valida los datos de entrada
 * @param string $data Datos a limpiar
 * @return string Datos limpios
 */
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Verifica si una ficha existe
 * @param mysqli $conexion Conexión a la base de datos
 * @param int $fichaId ID de la ficha
 * @return bool True si la ficha existe, false en caso contrario
 */
function fichaExists($conexion, $fichaId) {
    $stmt = $conexion->prepare("SELECT COUNT(*) as count FROM fichas WHERE id_ficha = ?");
    $stmt->bind_param("i", $fichaId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'] > 0;
}

/**
 * Verifica si una formación existe
 * @param mysqli $conexion Conexión a la base de datos
 * @param int $formacionId ID de la formación
 * @return bool True si la formación existe, false en caso contrario
 */
function formacionExists($conexion, $formacionId) {
    $stmt = $conexion->prepare("SELECT COUNT(*) as count FROM formacion WHERE id_formacion = ?");
    $stmt->bind_param("i", $formacionId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'] > 0;
}

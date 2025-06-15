<?php
session_start();

// Eliminar todas las variables de sesión
unset($_SESSION['empresa']);
unset($_SESSION['documento']);
unset($_SESSION['estado']);
unset($_SESSION['rol']);
unset($_SESSION['nombre']);


// Destruir la sesión
session_destroy();

// Borrar la cookie de sesión (esto es importante para asegurarte de que la sesión no persista)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], 
        $params["domain"], 
        $params["secure"], 
        $params["httponly"]
    );
}

// Cerrar la escritura de la sesión
session_write_close();

// Redirección después de cerrar sesión
$motivo = $_GET['motivo'] ?? '';

if ($motivo === 'acceso-denegado') {
    header("Location: ../includes/404/404.html");
    exit;
} else {
    header("Location: ../index.php");
    exit;
}
?>

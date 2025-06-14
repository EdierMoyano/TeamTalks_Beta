<?php
session_start();
unset($_SESSION['empresa']);
unset($_SESSION['documento']);
unset($_SESSION['estado']);
unset($_SESSION['rol']);
unset($_SESSION['nombre']);
session_destroy();
session_write_close();

$motivo = $_GET['motivo'] ?? '';

if ($motivo === 'acceso-denegado') {
    header("Location: 404/404.html");
    exit;
} else {
    header("Location: ../index.php");
    exit;
}

?>
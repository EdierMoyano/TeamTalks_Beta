<?php

session_start();

$timeout = 2000000;

if (!isset($_SESSION['documento'])) {
    
    echo '<script>alert("Credenciales incorrectas.")</script>';
    echo '<script>window.location = "../index.php"</script>';
    exit();
}

if (isset($_SESSION['afk']) && (time() - $_SESSION['afk']) > $timeout) {
    
    unset($_SESSION['documento']);
    unset($_SESSION['estado']);
    unset($_SESSION['empresa']);
    unset($_SESSION['rol']);
    session_destroy();
    session_write_close();

    
    echo '<script>alert("Session expired. Log in again.")</script>';
    echo '<script>window.location = "../login/login.php"</script>';
    exit();
}


$_SESSION['afk'] = time();
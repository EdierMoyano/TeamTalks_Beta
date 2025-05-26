<?php

session_start();

$timeout = 2000000;

if (!isset($_SESSION['documento'])) {
    
    echo '<script>alert("Credenciales incorrectas.")</script>';
<<<<<<< HEAD
    echo '<script>window.location = "../index.html"</script>';
=======
    echo '<script>window.location = "../index.php"</script>';
>>>>>>> 346b133f6a8dc17d05d4315ef4562bf1dc391b62
    exit();
}

if (isset($_SESSION['afk']) && (time() - $_SESSION['afk']) > $timeout) {
    
    unset($_SESSION['documento']);
<<<<<<< HEAD
    unset($_SESSION['tipo']);
    unset($_SESSION['estado']);
    unset($_SESSION['rol']);
=======
    unset($_SESSION['estado']);
    unset($_SESSION['empresa']);
    unset($_SESSION['rol']);
    unset($_SESSION['nombre']);
>>>>>>> 346b133f6a8dc17d05d4315ef4562bf1dc391b62
    session_destroy();
    session_write_close();

    
    echo '<script>alert("Session expired. Log in again.")</script>';
    echo '<script>window.location = "../login/login.php"</script>';
    exit();
}


$_SESSION['afk'] = time();
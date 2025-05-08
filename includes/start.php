<?php
session_start();
require_once('../conexion/conexion.php');
$conexion = new database();
$conex = $conexion->connect();


if (isset($_POST['iniciar'])) {
    $documento = $_POST['documento']; 
    $tipo = $_POST['tipo'];
    $contra_desc = $_POST['contraseña']; 

    if ($tipo == '' || $documento == '' || $contra_desc == '') {
        echo '<script>alert ("Ningún dato puede estar vacío")</script>';
        echo '<script>window.location = "../login.php"</script>';
    
    }


    
    $sql = $conex->prepare("SELECT * FROM usuarios WHERE id = $documento AND id_tipo = $tipo");
    $sql->execute();

    $fila = $sql->fetch(PDO::FETCH_ASSOC);

    
    if ($fila) {
        
        if (password_verify($contra_desc, $fila['contraseña']) && ($fila['id_estado'] == 1)) {
            
            $_SESSION ['documento'] = $fila ['id'];
            $_SESSION ['estado'] = $fila ['id_estado'];
            $_SESSION ['rol'] = $fila ['id_rol'];
            $_SESSION ['empresa'] = $fila['nit'];

            if ($_SESSION ['rol'] == 1) {
                header("Location: ../s_admin/index.php");
                exit();
            }

            if ($_SESSION ['rol'] == 2) {
                header("Location: ../admin/index.php");
                exit();
            }

            if ($_SESSION ['rol'] == 3) {
                header("Location: ../instructor/index.php");
                exit();
            }

            if ($_SESSION ['rol'] == 4) {
                header("Location: ../aprendiz/index.php");
                exit();
            }


        } else {
            
            echo '<script>alert ("Credenciales inválidas o Usuario inactivo")</script>';
            echo '<script>window.location = "../login/login.php"</script>';
        }
        }
        else {
            echo '<script>alert ("No se encontró el usuario")</script>';
            echo '<script>window.location = "../login/login.php"</script>';
        } 
}
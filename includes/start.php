<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php'; // Carga todo: rutas, conexión, sesión

if (isset($_POST['submit'])) {
    $documento = $_POST['documento']; 
    $tipo = $_POST['tipo'];
    $contra_desc = $_POST['contraseña']; 

    if ($tipo == '' || $documento == '' || $contra_desc == '') {
        echo '<script>alert("Ningún dato puede estar vacío")</script>';
        echo '<script>window.location = "../login.php"</script>';
        exit();
    }

    $sql = $conex->prepare("SELECT * FROM usuarios WHERE id = :id AND id_tipo = :tipo");
    $sql->execute(['id' => $documento, 'tipo' => $tipo]);
    $fila = $sql->fetch(PDO::FETCH_ASSOC);

    if ($fila && password_verify($contra_desc, $fila['contraseña']) && $fila['id_estado'] == 1) {
        $_SESSION['documento'] = $fila['id'];
        $_SESSION['estado'] = $fila['id_estado'];
        $_SESSION['rol'] = $fila['id_rol'];
        $_SESSION['empresa'] = $fila['nit'];
        $_SESSION['nombres'] = $fila['nombres'];

        switch ($_SESSION['rol']) {
            case 1: header("Location: ../s_admin/index.php"); break;
            case 2: header("Location: ../admin/index.php"); break;
            case 3: header("Location: ../instructor/index.php"); break;
            case 4: header("Location: ../aprendiz/index.php"); break;
            default: echo '<script>alert("Rol no reconocido")</script>';
        }

        exit();
    } else {
        echo '<script>alert("Credenciales inválidas o usuario inactivo")</script>';
        echo '<script>window.location = "../login/login.php"</script>';
        exit();
    }
}

<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';

if (isset($_POST['submit'])) {
    $documento = $_POST['documento']; 
    $tipo = $_POST['tipo'];
    $contra_desc = $_POST['contraseña']; 

    if (empty($documento) || empty($tipo) || empty($contra_desc)) {
        echo '<script>alert("Ningún dato puede estar vacío")</script>';
        echo '<script>window.location = "' . BASE_URL . '/login/login.php"</script>';
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

        // Redirige según el rol
        switch ($_SESSION['rol']) {
            case 1:
                header("Location: " . BASE_URL . "/s_admin/index.php");
                break;
            case 2:
                header("Location: " . BASE_URL . "/admin/index.php");
                break;
            case 3:
                header("Location: " . BASE_URL . "/instructor/index.php");
                break;
            case 4:
                header("Location: " . BASE_URL . "/aprendiz/index.php");
                break;
            case 5:
                header("Location: " . BASE_URL . "/transversal/index.php");
                break;    
            default:
                echo '<script>alert("Rol no reconocido")</script>';
                echo '<script>window.location = "' . BASE_URL . '/login/login.php"</script>';
        }

        exit();
    } else {
        echo '<script>alert("Credenciales inválidas o usuario inactivo")</script>';
        echo '<script>window.location = "' . BASE_URL . '/login/login.php"</script>';
        exit();
    }
}

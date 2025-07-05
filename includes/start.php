<?php
if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['DOCUMENT_ROOT'], 'htdocs') !== false) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php';
} else {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';
}

if (isset($_POST['submit'])) {
    $documento = $_POST['documento']; 
    $tipo = $_POST['tipo'];
    $contra_desc = $_POST['contraseña']; 

    if (empty($documento) || empty($tipo) || empty($contra_desc)) {
        echo '<script>alert("Ningún dato puede estar vacío")</script>';
        echo '<script>window.location = "' . BASE_URL . '/login/login.php"</script>';
        exit();
    }

    // Buscar usuario
    $sql = $conex->prepare("SELECT * FROM usuarios WHERE id = :id AND id_tipo = :tipo");
    $sql->execute(['id' => $documento, 'tipo' => $tipo]);
    $fila = $sql->fetch(PDO::FETCH_ASSOC);

    if ($fila && password_verify($contra_desc, $fila['contraseña'])) {

        if ($fila['id_estado'] != 1) {
            echo '<script>alert("El usuario se encuentra inactivo")</script>';
            echo '<script>window.location = "' . BASE_URL . '/login/login.php"</script>';
            exit();
        }

        // Validar si la empresa tiene licencia ACTIVA (id_estado = 1)
        $stmtLicencia = $conex->prepare("SELECT COUNT(*) FROM licencias WHERE nit = :nit AND id_estado = 1");
        $stmtLicencia->execute(['nit' => $fila['nit']]);
        $empresaConLicenciaActiva = $stmtLicencia->fetchColumn();

        if ($empresaConLicenciaActiva > 0) {
            // Inicio de sesión correcto
            $_SESSION['documento'] = $fila['id'];
            $_SESSION['estado'] = $fila['id_estado'];
            $_SESSION['rol'] = $fila['id_rol'];
            $_SESSION['empresa'] = $fila['nit'];
            $_SESSION['nombres'] = $fila['nombres'];

            // Redirección por rol
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
                    header("Location: " . BASE_URL . "/aprendiz/tarjeta_formacion/index.php");
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
            echo '<script>alert("La empresa no tiene una licencia activa")</script>';
            echo '<script>window.location = "' . BASE_URL . '/login/login.php"</script>';
            exit();
        }
    } else {
        echo '<script>alert("Credenciales inválidas")</script>';
        echo '<script>window.location = "' . BASE_URL . '/login/login.php"</script>';
        exit();
    }
}

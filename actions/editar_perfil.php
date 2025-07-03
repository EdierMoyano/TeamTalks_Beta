<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';
include 'session.php';

$id_usuario = $_SESSION['documento'] ?? null;
$rol = $_SESSION['rol'] ?? '';

$redirecciones = [
    3 => '/instructor/index.php',
    4 => '/aprendiz/index.php',
    5 => '/transversal/index.php',
];

$destino = BASE_URL . ($redirecciones[$rol] ?? '/index.php');

if (!$id_usuario) {
    header("Location: ../login/login.php");
    exit;
}

// Función para eliminar avatar anterior si existe y no es el predeterminado
function eliminarAvatarAnterior($id_usuario, $conex)
{
    $stmt = $conex->prepare("SELECT avatar FROM usuarios WHERE id = ?");
    $stmt->execute([$id_usuario]);
    $usuario = $stmt->fetch();

    if (!empty($usuario['avatar']) && strpos($usuario['avatar'], 'default.jpg') === false) {
        $ruta_anterior = realpath($_SERVER['DOCUMENT_ROOT'] . '/' . $usuario['avatar']);
        $uploads_dir = realpath($_SERVER['DOCUMENT_ROOT'] . '/uploads/avatar');

        if ($ruta_anterior && strpos($ruta_anterior, $uploads_dir) === 0 && file_exists($ruta_anterior)) {
            unlink($ruta_anterior); // Elimina archivo físico
        }
    }
}

$email     = trim($_POST['email'] ?? '');
$telefono  = trim($_POST['telefono'] ?? '');
$password  = $_POST['password'] ?? '';
$confirmar = $_POST['confirmar_password'] ?? '';
$avatar    = $_FILES['avatar'] ?? null;

$errores = [];

// Validaciones
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errores[] = "Correo electrónico inválido.";
}

if (!empty($password)) {
    if ($password !== $confirmar) {
        $errores[] = "Las contraseñas no coinciden.";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
        $errores[] = "La contraseña debe tener mínimo 8 caracteres, una mayúscula, una minúscula y un número.";
    }
}

// Procesar avatar si se subió
$ruta_avatar_final = null;

if ($avatar && $avatar['error'] === UPLOAD_ERR_OK) {
    $info = getimagesize($avatar['tmp_name']);

    if (!$info) {
        $errores[] = "El archivo no es una imagen válida.";
    } elseif ($info[0] > 800 || $info[1] > 800) {
        $errores[] = "La imagen no debe superar 800x800 píxeles.";
    } elseif ($avatar['size'] > 1024 * 1024) {
        $errores[] = "El archivo no debe superar 1MB.";
    } else {
        // Eliminar avatar anterior
        eliminarAvatarAnterior($id_usuario, $conex);

        // Crear carpeta si no existe
        $directorio_destino = '../uploads/avatar/';
        if (!is_dir($directorio_destino)) {
            if (!mkdir($directorio_destino, 0755, true)) {
                $errores[] = "No se pudo crear la carpeta de destino para el avatar.";
            }
        }

        $ext = pathinfo($avatar['name'], PATHINFO_EXTENSION);
        $nombre_archivo = 'avatar_' . $id_usuario . '_' . time() . '.' . $ext;
        $ruta_destino = $directorio_destino . $nombre_archivo;

        if (move_uploaded_file($avatar['tmp_name'], $ruta_destino)) {
            $ruta_avatar_final = 'uploads/avatar/' . $nombre_archivo;
        } else {
            $errores[] = "Error al subir la imagen.";
        }
    }
}

if (empty($errores)) {
    $campos = [];
    $parametros = [];

    if (!empty($email)) {
        $campos[] = "correo = ?";
        $parametros[] = $email;
        $_SESSION['correo'] = $email;
    }

    if (!empty($telefono)) {
        $campos[] = "telefono = ?";
        $parametros[] = $telefono;
        $_SESSION['telefono'] = $telefono;
    }

    if (!empty($password)) {
        $campos[] = "contraseña = ?";
        $parametros[] = password_hash($password, PASSWORD_DEFAULT);
    }

    if ($ruta_avatar_final) {
        $campos[] = "avatar = ?";
        $parametros[] = $ruta_avatar_final;
        $_SESSION['avatar'] = $ruta_avatar_final;
    }

    if (!empty($campos)) {
        $sql = "UPDATE usuarios SET " . implode(", ", $campos) . " WHERE id = ?";
        $parametros[] = $id_usuario;

        $stmt = $conex->prepare($sql);
        $stmt->execute($parametros);
    }

    header("Location: {$destino}?actualizado=1");
    exit;
} else {
    $_SESSION['errores_editar'] = $errores;
    header("Location: {$destino}?error=1");
    exit;
}

<?php
session_start();
require_once('../conexion/conexion.php');

// Verificar si el usuario ha verificado el OTP
if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true || !isset($_SESSION['recovery_user_id'])) {
    header("Location: recovery_form.php");
    exit;
}

$userId = $_SESSION['recovery_user_id'];
$conexion = new database();
$conex = $conexion->connect();
$error = "";
$success = false;

if (isset($_POST['submit'])) {
    $password1 = $_POST['password1'];
    $password2 = $_POST['password2'];

    if (strlen($password1) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    } elseif ($password1 !== $password2) {
        $error = "Las contraseñas no coinciden.";
    } else {
        $hashedPassword = password_hash($password2, PASSWORD_DEFAULT, array("cost" => 12));
        
        // Actualizar la contraseña en la tabla de usuarios
        $update = $conex->prepare("UPDATE usuarios SET contraseña = ? WHERE id = ?");
        $update->execute([$hashedPassword, $userId]);

        if ($update) {
            // Eliminar el token de recuperación después de usarlo
            $deleteToken = $conex->prepare("DELETE FROM recuperacion WHERE id_usuario = ?");
            $deleteToken->execute([$userId]);
            
            // Limpiar variables de sesión
            unset($_SESSION['otp_verified']);
            unset($_SESSION['recovery_user_id']);
            unset($_SESSION['recovery_email']);
            
            $success = true;
        } else {
            $error = "Error al actualizar la contraseña.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="icon" href="../assets/img/logo.png">
    <style>
        .bx {
            position: absolute;
            font-size: 1.7rem;
            left: 280px;
            top: 75%;
            transform: translateY(-50%);
            cursor: pointer;
        }
    </style>
</head>
<body class="bg-light d-flex justify-content-center align-items-center vh-100" style="background-image: url(../assets/img/background.jpg); background-size: cover;">

    <div class="card shadow-lg border-0" style="width: 55%; background-color: transparent;">
        <div class="row g-0">

            <!-- Logo arriba a la izquierda (posicionado absolutamente) -->
            <div class="img" style="position:absolute; z-index: 1;">
                <img src="../assets/img/logo.png" alt="Logo" style="width: 120px;" class="m-2">
            </div>

            <!-- Lado izquierdo: Imagen -->
            <div class="col-md-6 p-5 d-flex justify-content-center align-items-center bg-white" style="border-top-left-radius: 10px; border-bottom-left-radius: 10px;">
                <img src="../assets/img/2.png" alt="Imagen" class="img-fluid" style="max-height: 300px; width: 300px;">
            </div>

            <!-- Lado derecho: Formulario -->
            <div class="col-md-6 p-5 d-flex flex-column justify-content-center" style="background: #8ac5fe; border-top-right-radius: 10px; border-bottom-right-radius: 10px;">
                <?php if ($success): ?>
                    <h2 class="mb-3">¡Contraseña actualizada!</h2>
                    <p class="text-success">Tu contraseña ha sido actualizada exitosamente.</p>
                    <div class="text-center mt-4">
                        <a href="../login/login.php" class="btn btn-primary">Iniciar sesión</a>
                    </div>
                <?php else: ?>
                    <h2 class="mb-3">Ingresa una nueva contraseña</h2>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form action="" method="POST" autocomplete="off">
                        <div class="mb-3 position-relative">
                            <label for="password1" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="password1" name="password1" placeholder="Ingresa la nueva contraseña" required>
                            <i class='bx bx-show end-0 translate-middle-y me-3' id="showpass1" onclick="togglePass('password1', 'showpass1')"></i>
                        </div>

                        <div class="mb-2 position-relative">
                            <label for="password2" class="form-label">Confirmar contraseña</label>
                            <input type="password" class="form-control" id="password2" name="password2" placeholder="Vuelve a ingresar la nueva contraseña" required>
                            <i class='bx bx-show end-0 translate-middle-y me-3' id="showpass2" onclick="togglePass('password2', 'showpass2')"></i>
                        </div>

                        <p class="text-danger" id="coincide" style="display: none;">¡Las contraseñas no coinciden!</p>

                        <div class="d-flex justify-content-between mt-3">
                            <a href="verify_otp.php" class="btn btn-secondary">Regresar</a>
                            <button type="submit" name="submit" class="btn" style="background-color: #0E4A86; color: white;">Confirmar</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function togglePass(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);

            if (input.type === "password") {
                input.type = "text";
                icon.classList.replace("bx-show", "bx-hide");
            } else {
                input.type = "password";
                icon.classList.replace("bx-hide", "bx-show");
            }
        }

        // Verificar si las contraseñas coinciden
        document.getElementById('password2').addEventListener('input', function () {
            const pass1 = document.getElementById('password1').value;
            const pass2 = document.getElementById('password2').value;
            const coincide = document.getElementById('coincide');

            coincide.style.display = (pass1 !== pass2) ? 'block' : 'none';
        });
    </script>
</body>
</html>


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
    <link rel="stylesheet" href="../styles/change.css">
    <link rel="icon" href="../assets/img/logo.png">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        /* Estilos adicionales para mantener consistencia */
        .container {
            display: flex;
            height: 100vh;
            background-color: #f5f5f5;
        }
        
        .welcome {
            flex: 1;
            background-color: #007bff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            position: relative;
        }
        
        .login {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 2rem;
            background-color: white;
        }
        
        .logo {
            width: 120px;
            margin-bottom: 2rem;
        }
        
        .img1 {
            max-width: 80%;
            max-height: 60%;
        }
        
        h2 {
            color: #333;
            margin-bottom: 1.5rem;
        }
        
        form {
            display: flex;
            flex-direction: column;
            width: 100%;
        }
        
        label {
            margin-bottom: 0.5rem;
            color: #555;
        }
        
        input {
            padding: 0.8rem;
            margin-bottom: 1.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
        }
        
        .primary-btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .secondary-btn {
            background-color: transparent;
            color: #007bff;
            border: 1px solid #007bff;
            padding: 0.8rem 1.5rem;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .coincide {
            color: red;
            margin-top: -1rem;
            margin-bottom: 1rem;
            display: none;
        }
        
        i.bx {
            position: absolute;
            right: 10px;
            transform: translateY(-38px);
            cursor: pointer;
            color: #555;
        }
        
        .error-message {
            color: red;
            margin-bottom: 1rem;
        }
        
        .success-message {
            color: green;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="welcome">
            <img src="../assets/img/logo.png" alt="TeamTalks Logo" class="logo">
            <!-- Aquí puedes colocar la imagen que desees -->
            <img src="../assets/img/2.png" alt="" class="img1">
        </div>
        <div class="login">
            <?php if ($success): ?>
                <h2>¡Contraseña actualizada!</h2>
                <p class="success-message">Tu contraseña ha sido actualizada exitosamente.</p>
                <div class="buttons" style="justify-content: center; margin-top: 20px;">
                    <a href="../login/login.php"><button type="button" class="primary-btn">Iniciar sesión</button></a>
                </div>
            <?php else: ?>
                <h2>Ingresa una nueva contraseña</h2>
                
                <?php if (!empty($error)): ?>
                    <p class="error-message"><?php echo $error; ?></p>
                <?php endif; ?>
                
                <form action="" method="POST" autocomplete="off">
                    <label for="password1">Contraseña</label>
                    <div style="position: relative;">
                        <input type="password" id="password1" name="password1" placeholder="Ingresa la nueva contraseña" required>
                        <i class='bx bx-show' id="showpass1" onclick="showpass1()"></i>
                    </div>

                    <label for="password2">Confirmar contraseña</label>
                    <div style="position: relative;">
                        <input type="password" id="password2" name="password2" placeholder="Vuelve a ingresar la nueva contraseña" required>
                        <i class='bx bx-show' id="showpass2" onclick="showpass2()"></i>
                    </div>
                    <p class="coincide" id="coincide">¡Las contraseñas no coinciden!</p>
                
                    <div class="buttons">
                        <a href="verify_otp.php"><button type="button" class="secondary-btn">Regresar</button></a>
                        <button name="submit" type="submit" class="primary-btn">Confirmar</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function showpass1() {
            const passw = document.getElementById("password1");
            const iconshow = document.getElementById("showpass1");
            
            if (passw.type === "password") {
                passw.type = "text";
                iconshow.classList.replace("bx-show", "bx-hide");
            } else {
                passw.type = "password";
                iconshow.classList.replace("bx-hide", "bx-show");
            }
        }

        function showpass2() {
            const passw = document.getElementById("password2");
            const iconshow = document.getElementById("showpass2");
            
            if (passw.type === "password") {
                passw.type = "text";
                iconshow.classList.replace("bx-show", "bx-hide");
            } else {
                passw.type = "password";
                iconshow.classList.replace("bx-hide", "bx-show");
            }
        }

        // Verificar si las contraseñas coinciden
        document.getElementById('password2').addEventListener('input', function() {
            const pass1 = document.getElementById('password1').value;
            const pass2 = document.getElementById('password2').value;
            const coincide = document.getElementById('coincide');
            
            if (pass1 !== pass2) {
                coincide.style.display = 'block';
            } else {
                coincide.style.display = 'none';
            }
        });
    </script>
</body>
</html>
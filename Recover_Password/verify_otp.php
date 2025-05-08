<?php
session_start();
require_once('../conexion/conexion.php');

// Verificar si hay una sesión de recuperación activa
if (!isset($_SESSION['recovery_email']) || !isset($_SESSION['recovery_user_id'])) {
    header("Location: recovery_form.php");
    exit;
}


$email = $_SESSION['recovery_email'];
$userId = $_SESSION['recovery_user_id'];

$conexion = new database();
$conex = $conexion->connect();

$error = "";
$success = false;

if (isset($_POST['verify'])) {
    $otp = trim($_POST['otp']);
    
    if (empty($otp)) {
        $error = "Por favor ingresa el código de verificación.";
    } else {
        // Verificar el código OTP
        $query = $conex->prepare("
            SELECT id_recuperacion 
            FROM recuperacion 
            WHERE id_usuario = ? AND token = ? AND fecha_expiracion > NOW()
        ");
        $query->execute([$userId, $otp]);
        $result = $query->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            // OTP válido, redirigir a la página de nueva contraseña
            $_SESSION['otp_verified'] = true;
            header("Location: new_password.php");
            exit;
        } else {
            $error = "Código de verificación inválido o expirado.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar código</title>
    <link rel="stylesheet" href="../styles/recovery.css">
    <link rel="icon" href="../assets/img/logo.png">
</head>
<body>
    <div class="container">
        <div class="welcome">
            <img src="../assets/img/logo.png" alt="TeamTalks Logo" class="logo">
            <img src="../assets/img/1.png" alt="" class="img1">
        </div>
        <div class="login">
            <h2>Verificación de código</h2>
            <p>Hemos enviado un código de verificación a:<br>
            <strong><?php echo htmlspecialchars($email); ?></strong></p>
            
            <?php if (!empty($error)): ?>
                <p style="color: red;"><?php echo $error; ?></p>
            <?php endif; ?>
            
            <form action="" method="POST" autocomplete="off">
                <label for="otp">Código de verificación</label>
                <input type="text" id="otp" name="otp" placeholder="Ingresa el código de 6 dígitos" maxlength="6" required>
                
                <div class="buttons">
                    <a href="recovery_form.php"><button type="button" class="secondary-btn">Regresar</button></a>
                    <button type="submit" name="verify" class="primary-btn">Verificar</button>
                </div>
            </form>
            
            <p style="margin-top: 20px; font-size: 14px;">
                ¿No recibiste el código? <a href="send_otp.php" style="color: #007bff; text-decoration: none;">Enviar de nuevo</a>
            </p>
        </div>
    </div>
</body>
</html>
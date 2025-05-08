<?php
session_start();
require '../src/PHPMailer.php';
require '../src/SMTP.php';
require '../src/Exception.php';
require '../conexion/conexion.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = trim($_POST["correo"]);
    $documento = trim($_POST["docu"]);

    if (empty($correo) || empty($documento)) {
        echo '<script>alert("Ningún dato puede estar vacío");</script>';
        echo '<script>window.location = "recovery_form.php";</script>';
        exit;
    }

    // Conectar a la base de datos
    $db = new Database();
    $conn = $db->connect();

    // Verificar si el usuario existe
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE correo = ? AND id = ?");
    $stmt->execute([$correo, $documento]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo '<script>alert("Correo o documento incorrectos");</script>';
        echo '<script>window.location = "recovery_form.php";</script>';
        exit;
    }

    $userId = $user['id'];

    // Generar un código OTP de 6 dígitos
    $otp = mt_rand(100000, 999999);
    $expira = date("Y-m-d H:i:s", strtotime("+15 minutes")); // Expira en 15 minutos

    // Primero, eliminar cualquier token existente para este usuario
    $stmt = $conn->prepare("DELETE FROM recuperacion WHERE id_usuario = ?");
    $stmt->execute([$userId]);

    // Guardar el OTP en la tabla de recuperación
    $stmt = $conn->prepare("INSERT INTO recuperacion (id_usuario, token, fecha_expiracion) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $otp, $expira]);

    // Guardar información en la sesión para la verificación
    $_SESSION['recovery_email'] = $correo;
    $_SESSION['recovery_user_id'] = $userId;

    // Configurar PHPMailer
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'teamtalks39@gmail.com';
        $mail->Password = 'vjpz udnq kacd gwyl';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('teamtalks39@gmail.com', 'Soporte TeamTalks');
        $mail->addAddress($correo);
        $mail->Subject = 'Código de verificación - TeamTalks';

        // Contenido del correo con el código OTP
        $mail->isHTML(true);
        $mail->Body = "<h2>Recuperación de contraseña</h2>
                    <p>Hola, has solicitado recuperar tu contraseña.</p>
                    <p>Tu código de verificación es:</p>
                    <h1 style='font-size: 32px; letter-spacing: 5px; text-align: center; padding: 10px; background-color: #f0f0f0; border-radius: 5px;'>$otp</h1>
                    <p>Este código expirará en 15 minutos.</p>
                    <p>Si no solicitaste este cambio, ignora este mensaje.</p>";

        // Enviar correo
        $mail->send();
        
        // Redirigir a la página de verificación
        header("Location: verify_otp.php");
        exit;
    } catch (Exception $e) {
        echo '<script>alert("Error al enviar el correo: ' . $mail->ErrorInfo . '");</script>';
        echo '<script>window.location = "recovery_form.php";</script>';
    }
}
else {
    // Si alguien intenta acceder directamente a esta página
    header("Location: recovery_form.php");
    exit;
}
?>
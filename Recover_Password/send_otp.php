<?php
// send_otp.php

// 1) Mostrar errores (solo en dev)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../conexion/conexion.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// 2) Sólo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: recovery_form.php');
    exit;
}

// 3) Validar input
$correo    = trim($_POST['correo'] ?? '');
$documento = trim($_POST['docu']   ?? '');
if ($correo === '' || $documento === '') {
    echo '<script>alert("Ningún dato puede estar vacío");window.location="recovery_form.php";</script>';
    exit;
}

// 4) Conectar BDD
$db   = new Database();
$conn = $db->connect();

// 5) Verificar usuario
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE correo = ? AND id = ?");
$stmt->execute([$correo, $documento]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    echo '<script>alert("Correo o documento incorrectos");window.location="recovery_form.php";</script>';
    exit;
}
$userId = $user['id'];

// 6) Generar OTP y expira
$otp    = mt_rand(100000, 999999);
$expira = date("Y-m-d H:i:s", strtotime("+15 minutes"));

// 7) Guardar token
$conn->prepare("DELETE FROM recuperacion WHERE id_usuario = ?")->execute([$userId]);
$conn->prepare("INSERT INTO recuperacion (id_usuario, token, fecha_expiracion) VALUES (?, ?, ?)")
     ->execute([$userId, $otp, $expira]);

$_SESSION['recovery_email']   = $correo;
$_SESSION['recovery_user_id'] = $userId;

// 8) Envío con PHPMailer
$mail = new PHPMailer(true);
try {
    // Configurar el servidor SMTP de Gmail
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'teamtalks39@gmail.com';
    $mail->Password   = 'vjpz udnq kacd gwyl';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('teamtalks39@gmail.com', 'Recuperación TeamTalks');
    $mail->addAddress($correo);

    $mail->isHTML(true);
    $mail->Subject = 'Código de verificación - TeamTalks';
    $mail->Body    = "
    <html>
    <head>
      <style>
        body { font-family:'Segoe UI',sans-serif; background:#f9f9f9; color:#333; }
        .box { background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1); max-width:600px; margin:auto; }
        h2 { color:#0E4A86; }
        .otp { font-size:32px; letter-spacing:4px; text-align:center; padding:10px; background:#e9f0fb; border-radius:5px; }
      </style>
    </head>
    <body>
      <div class='box'>
        <h2>Recuperación de contraseña</h2>
        <p>Tu código de verificación es:</p>
        <div class='otp'>{$otp}</div>
        <p>Expira en 15 minutos. Si no lo solicitaste, ignora este correo.</p>
      </div>
    </body>
    </html>";

    $mail->send();

    header('Location: verify_otp.php');
    exit;
} catch (Exception $e) {
    echo '<script>
            alert("Error al enviar el correo: ' . addslashes($mail->ErrorInfo) . '");
            window.location="recovery_form.php";
          </script>';
    exit;
}
<?php
// reportes.php

// 1) Carga de dependencias de PHPMailer
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 2) Procesar envío de formulario
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Capturar datos del formulario
    $nombre      = $_POST['nombreReporte']    ?? '';
    $correo      = $_POST['correoSoporte']    ?? '';
    $tipoReporte = $_POST['tipoReporte']      ?? '';
    $descripcion = $_POST['descripcionReporte'] ?? '';

    $mail = new PHPMailer(true);

    try {
        // 3) Configuración SMTP de Hostinger/Titan
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com';                // Host SMTP
        $mail->SMTPAuth   = true;
        $mail->Username   = 'soporte@teamtalks.com.co';          // Tu correo Titan
        $mail->Password   = '1104940105Edier.';           // Contraseña de buzón o app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;         // SSL
        $mail->Port       = 465;                                 // Puerto SSL

        // Alternativa STARTTLS (PORT 587)
        // $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        // $mail->Port       = 587;

        // 4) Remitente y destinatarios
        $mail->setFrom('soporte@teamtalks.com.co', 'Soporte TeamTalks');
        $mail->addAddress('soporte@teamtalks.com.co', 'Equipo de Reportes');

        // 5) Contenido HTML
        $mail->isHTML(true);
        $mail->Subject = 'Nuevo Reporte de Usuario';
        $mail->Body    = "
        <html>
        <head>
           <style>
                body { font-family: 'Segoe UI', sans-serif; background: #f4f4f4; color: #333; }
                .container { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { background: #0E4A86; color: #fff; padding: 15px; border-radius:8px 8px 0 0; text-align:center; }
                .content { padding:20px; }
                .info-box { background:#e9f0fb; border:1px solid #d0e1f9; padding:15px; border-radius:8px; }
                .footer { text-align:center; color:#999; font-size:14px; margin-top:20px; }
           </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'><h2>Nuevo Reporte</h2></div>
                <div class='content'>
                    <p><strong>Nombre:</strong> {$nombre}</p>
                    <p><strong>Correo:</strong> {$correo}</p>
                    <p><strong>Tipo de Reporte:</strong> {$tipoReporte}</p>
                    <div class='info-box'>
                        <h3>Descripción:</h3>
                        <p>{$descripcion}</p>
                    </div>
                </div>
                <div class='footer'>
                    <p>Este mensaje fue generado automáticamente. Si no lo solicitaste, ignóralo.</p>
                </div>
            </div>
        </body>
        </html>";

        // 6) Envío
        if ($mail->send()) {
            $successMessage = "Tu reporte ha sido enviado exitosamente.";
            echo '<script>var showModal = true;</script>';
        } else {
            $successMessage = "No se pudo enviar el reporte.";
            echo '<script>var showModal = false;</script>';
        }

    } catch (Exception $e) {
        $successMessage = "Error al enviar: {$mail->ErrorInfo}";
        echo '<script>var showModal = false;</script>';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Realizar Reporte</title>
  <link rel="icon" href="assets/img/icon2.png">
  <!-- Bootstrap & Boxicons & FontAwesome -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    :root {
      --azul-oscuro: #061D35;
      --azul-intermedio: #0E4A86;
      --azul-claro: #348FEA;
      --blanco: #FFFFFF;
    }
    body { background: var(--blanco); font-family: 'Segoe UI', sans-serif; }
    .card-custom { border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
    .btn-azul { background: var(--azul-claro); color: var(--blanco); border-radius: 10px; }
    .btn-azul:hover { background: var(--azul-intermedio); }
    .header-text { text-align: center; margin-bottom: 2rem; }
    .form-container { max-width: 800px; margin: 0 auto; }
  </style>
</head>
<body>
  <header>
    <?php include 'includes/design/header.php'; ?>
  </header>

  <section class="py-5">
    <div class="container">
      <div class="header-text">
        <h1><i class="bi bi-exclamation-triangle-fill"></i> Realizar Reporte</h1>
        <p class="lead">Completa el formulario y nuestro equipo se pondrá en contacto contigo</p>
      </div>
      <div class="form-container">
        <div class="card card-custom p-4">
          <form action="reportes.php" method="POST" onsubmit="return validateForm()">
            <div class="mb-3">
              <label for="nombreReporte" class="form-label">Nombre completo (opcional)</label>
              <input type="text" class="form-control" id="nombreReporte" name="nombreReporte" placeholder="Puedes dejarlo en blanco">
            </div>
            <div class="mb-3">
              <label for="correoSoporte" class="form-label">Correo electrónico</label>
              <input type="email" class="form-control" id="correoSoporte" name="correoSoporte" placeholder="ejemplo@email.com" required>
            </div>
            <div class="mb-3">
              <label for="tipoReporte" class="form-label">Tipo de reporte</label>
              <select class="form-select" id="tipoReporte" name="tipoReporte" required>
                <option selected disabled>Selecciona una opción</option>
                <option>Problemas de acceso o inicio de sesión</option>
                <option>Error en el sistema o caída del servicio</option>
                <option>Fallas en la carga de la página</option>
                <option>Problemas con la funcionalidad de formularios</option>
                <option>Fallas en la visualización de contenidos o imágenes</option>
                <option>Errores al enviar o recibir datos</option>
                <option>Problemas con la cuenta de usuario</option>
                <option>Desempeño lento o problemas de velocidad</option>
                <option>Otros problemas técnicos</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="descripcionReporte" class="form-label">Descripción</label>
              <textarea class="form-control" id="descripcionReporte" name="descripcionReporte" rows="4" required placeholder="Describe la situación..."></textarea>
            </div>
            <button type="submit" class="btn btn-azul w-100">Enviar reporte</button>
          </form>
        </div>
      </div>
    </div>
  </section>

  <?php include 'includes/design/footer.php'; ?>

  <!-- Modal de Éxito/Error -->
  <div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Estado del Envío</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <p><?php echo $successMessage ?? ''; ?></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS y validación -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Mostrar modal según resultado
    if (typeof showModal !== 'undefined' && showModal) {
      new bootstrap.Modal(document.getElementById('successModal'), { keyboard: false }).show();
    }
    // Validación simple en cliente
    function validateForm() {
      const correo = document.getElementById("correoSoporte").value.trim();
      const tipo   = document.getElementById("tipoReporte").value;
      const desc   = document.getElementById("descripcionReporte").value.trim();
      if (!correo || !tipo || !desc) {
        alert("Todos los campos obligatorios deben completarse.");
        return false;
      }
      return true;
    }
  </script>
</body>
</html>

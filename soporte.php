<?php
// Incluir el autoload de Composer para cargar automáticamente PHPMailer y otras dependencias
require 'vendor/autoload.php';  // Asegúrate de que esta ruta sea correcta

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recibir los datos del formulario
    $nombre = isset($_POST['nombreSoporte']) ? $_POST['nombreSoporte'] : '';  // Campo opcional
    $correo = $_POST['correoSoporte'];
    $numero = isset($_POST['numeroSoporte']) ? $_POST['numeroSoporte'] : '';  // Evitar el warning
    $problema = $_POST['problema'];

    // Crear una instancia de PHPMailer
    $mail = new PHPMailer(true);

   try {
        // 3) Configuración SMTP de Hostinger/Titan
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com';                // Host SMTP
        $mail->SMTPAuth   = true;
        $mail->Username   = 'soporte@teamtalks.com.co';          // Tu correo Titan
        $mail->Password   = 'TeamTalks_2901879.';           // Contraseña de buzón o app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;         // SSL
        $mail->Port       = 465;                                 // Puerto SSL

        // Alternativa STARTTLS (PORT 587)
        // $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        // $mail->Port       = 587;

        // 4) Remitente y destinatarios
        $mail->setFrom('soporte@teamtalks.com.co', 'Soporte TeamTalks');
        $mail->addAddress('soporte@teamtalks.com.co', 'Equipo de Reportes');

        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject = 'Solicitud de Soporte Tecnico';
        $mail->Body    = "
        <html>
        <head>
            <style>
                body {
                    font-family: 'Segoe UI', sans-serif;
                    background-color: #f4f4f4;
                    color: #333;
                }
                .container {
                    width: 100%;
                    padding: 20px;
                    box-sizing: border-box;
                    background-color: #fff;
                    border-radius: 8px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                }
                .header {
                    background-color: #0E4A86;
                    color: white;
                    padding: 15px;
                    text-align: center;
                    font-size: 18px;
                    border-radius: 8px 8px 0 0;
                }
                .content {
                    padding: 20px;
                    font-size: 16px;
                }
                .content p {
                    margin: 10px 0;
                }
                .footer {
                    text-align: center;
                    font-size: 14px;
                    color: #999;
                    margin-top: 20px;
                } 
                .info-box {
                    background-color: #e9f0fb;
                    border: 1px solid #d0e1f9;
                    padding: 15px;
                    margin-top: 20px;
                    border-radius: 8px;
                }
                .info-box h3 {
                    color: #0E4A86;
                    margin-bottom: 10px;
                }
                .info-box p {
                    color: #555;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Solicitud de Soporte Tecnico</h2>
                </div>
                <div class='content'>
                    <p><strong>Nombre:</strong> $nombre</p>
                    <p><strong>Correo:</strong> $correo</p>
                    <p><strong>Numero:</strong> $numero</p>
                    <p><strong>Fecha:</strong> " . date('Y-m-d H:i:s') . "</p>
                    <div class='info-box'>
                        <h3>Descripción del Problema:</h3>
                        <p>$problema</p>
                    </div>
                </div>
                <div class='footer'>
                    <p>Este mensaje fue generado automáticamente. Si no has solicitado soporte, por favor ignora este correo.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        // Enviar el correo
        if ($mail->send()) {
            // Establecer una variable para mostrar el modal en el frontend
            $successMessage = "Tu correo ha sido enviado exitosamente.";
            echo '<script>var showModal = true;</script>';
        } else {
            $successMessage = "No se pudo enviar el mensaje.";
            echo '<script>var showModal = false;</script>';
        }
    } catch (Exception $e) {
        $successMessage = "Error al enviar el mensaje: {$mail->ErrorInfo}";
        echo '<script>var showModal = false;</script>';
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Soporte Tecnico</title>
  <link rel="icon" href="assets/img/icon2.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <style>
    :root {
      --azul-oscuro: #061D35;
      --azul-intermedio: #0E4A86;
      --azul-claro: #348FEA;
      --blanco: #FFFFFF;
    }

    body {
      background-color: var(--blanco);
      font-family: 'Segoe UI', sans-serif;
    }

    .card-custom {
      background-color: var(--blanco);
      border-radius: 15px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .btn-azul {
      background-color: var(--azul-claro);
      color: var(--blanco);
      border-radius: 10px;
    }

    .btn-azul:hover {
      background-color: var(--azul-intermedio);
    }
  </style>
</head>

<body>
  <header>
    <?php include 'includes/design/header.php'; ?>
  </header>

  <section class="py-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8 text-center">
          <h1><i class="bi bi-tools me-2"></i>Soporte Tecnico</h1>
          <p class="lead">Estamos aquí para ayudarte<br>Completa el formulario y nuestro equipo de soporte técnico se pondrá en contacto contigo</p>
        </div>
        <div class="col-lg-8">
          <div class="card card-custom p-4">
            <form action="soporte.php" method="POST" onsubmit="return validateForm()">
              <div class="mb-3">
                <label for="nombreSoporte" class="form-label">Nombre completo</label>
                <input type="text" class="form-control" id="nombreSoporte" name="nombreSoporte" placeholder="Tu nombre" required>
              </div>
              <div class="mb-3">
                <label for="correoSoporte" class="form-label">Correo electrónico</label>
                <input type="email" class="form-control" id="correoSoporte" name="correoSoporte" placeholder="ejemplo@email.com" required>
              </div>
              <div class="mb-3">
                <label for="numeroSoporte" class="form-label">Número de teléfono</label>
                <input type="text" class="form-control" id="numeroSoporte" name="numeroSoporte" placeholder="+573333333333" required>
              </div>
              <div class="mb-3">
                <label for="problema" class="form-label">Describe tu problema</label>
                <textarea class="form-control" id="problema" name="problema" rows="4" placeholder="¿Qué está ocurriendo?" required></textarea>
              </div>
              <button type="submit" class="btn btn-azul w-100">Enviar solicitud</button>
            </form>

            <!-- Modal de Éxito -->
            <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="successModalLabel">¡Correo Enviado Exitosamente!</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <p><?php echo isset($successMessage) ? $successMessage : ''; ?></p>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cerrar</button>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </section>

  <?php include 'includes/design/footer.php'; ?>

  <script>
    // Mostrar el modal si el correo fue enviado exitosamente
    if (typeof showModal !== 'undefined' && showModal) {
      var myModal = new bootstrap.Modal(document.getElementById('successModal'), {
        keyboard: false
      });
      myModal.show();
    }

    // Validaciones del formulario
    function validateForm() {
      var correo = document.getElementById("correoSoporte").value;
      var numero = document.getElementById("numeroSoporte").value;
      var problema = document.getElementById("problema").value;

      // Validar correo
      var emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
      if (!emailPattern.test(correo)) {
        alert("Por favor ingresa un correo válido.");
        return false;
      }

      // Validar número de teléfono
      if (isNaN(numero) || numero.length < 10) {
        alert("Por favor ingresa un número de teléfono válido.");
        return false;
      }

      // Validar descripción
      if (problema.trim() === "") {
        alert("Por favor describe el problema.");
        return false;
      }

      return true;
    }
  </script>

</body>

</html>
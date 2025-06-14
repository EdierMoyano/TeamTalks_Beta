<?php
// Incluir el autoload de Composer para cargar automáticamente PHPMailer y otras dependencias
require 'vendor/autoload.php';  // Asegúrate de que esta ruta sea correcta

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Recibir los datos del formulario
  $nombre = isset($_POST['nombreReporte']) ? $_POST['nombreReporte'] : '';  // Campo opcional
  $correo = $_POST['correoSoporte'];
  $tipoReporte = $_POST['tipoReporte'];
  $descripcion = $_POST['descripcionReporte'];

  // Crear una instancia de PHPMailer
  $mail = new PHPMailer(true);

  try {
    // Configurar el servidor SMTP
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'teamtalks39@gmail.com'; // Cambia esto por tu correo
    $mail->Password = 'vjpz udnq kacd gwyl'; // Cambia esto por tu contraseña o una contraseña de aplicación
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Remitente y destinatario
    $mail->setFrom('teamtalks39@gmail.com', 'Soporte Tecnico');
    $mail->addAddress('teamtalks39@gmail.com', 'Equipo de Reportes'); // Cambia esto por el correo de la empresa

    // Contenido del correo
    $mail->isHTML(true);
    $mail->Subject = 'Nuevo Reporte de Usuario';
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
                    <h2>Nuevo Reporte</h2>
                </div>
                <div class='content'>
                    <p><strong>Nombre:</strong> $nombre</p>
                    <p><strong>Correo:</strong> $correo</p>
                    <p><strong>Tipo de Reporte:</strong> $tipoReporte</p>
                    <div class='info-box'>
                        <h3>Descripción del Reporte:</h3>
                        <p>$descripcion</p>
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
      $successMessage = "Tu reporte ha sido enviado exitosamente.";
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
  <title>Realizar Reporte</title>
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

    .header-text {
      text-align: center;
      margin-bottom: 2rem;
    }

    .form-container {
      max-width: 800px;
      margin: 0 auto;
    }
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
        <p class="lead">Estamos aquí para ayudarte<br>Completa el formulario y nuestro equipo de reportes se pondrá en contacto contigo</p>
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

              <div class="mb-3">
                <label for="descripcionReporte" class="form-label">Descripción</label>
                <textarea class="form-control" id="descripcionReporte" name="descripcionReporte" rows="4" placeholder="Describe la situación..." required></textarea>
              </div>
              <button type="submit" class="btn btn-azul w-100">Enviar reporte</button>
          </form>
        </div>
      </div>
    </div>
  </section>

  <?php include 'includes/design/footer.php'; ?>

  <!-- Modal de Éxito -->
  <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="successModalLabel">¡Reporte Enviado Exitosamente!</h5>
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

  <script>
    // Mostrar el modal si el correo fue enviado exitosamente
    if (typeof showModal !== 'undefined' && showModal) {
      var myModal = new bootstrap.Modal(document.getElementById('successModal'), {
        keyboard: false
      });
      myModal.show();
    }

    // Validaciones de formulario
    function validateForm() {
      var correo = document.getElementById("correoSoporte").value;
      var tipoReporte = document.getElementById("tipoReporte").value;
      var descripcion = document.getElementById("descripcionReporte").value;

      if (correo == "" || tipoReporte == "" || descripcion == "") {
        alert("Todos los campos son obligatorios.");
        return false;
      }
      return true;
    }
  </script>

</body>

</html>
<?php
// Incluir el autoload de Composer para cargar automáticamente PHPMailer y otras dependencias
require 'vendor/autoload.php';  // Asegúrate de que esta ruta sea correcta

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recibir los datos del formulario
    $correo = $_POST['correo'];
    $mensaje = $_POST['mensaje'];

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
        $mail->addAddress('teamtalks39@gmail.com', 'Equipo de Contacto'); // Cambia esto por el correo de la empresa

        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject = 'Nuevo Mensaje de Contacto';
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
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Nuevo Mensaje de Contacto</h2>
                </div>
                <div class='content'>
                    <p><strong>Correo:</strong> $correo</p>
                    <p><strong>Mensaje:</strong></p>
                    <div class='info-box'>
                        <p>$mensaje</p>
                    </div>
                </div>
                <div class='footer'>
                    <p>Este mensaje fue generado automáticamente. Si no has solicitado este mensaje, por favor ignora este correo.</p>
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
  <title>Contactanos</title>
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

    /* Estilos para el formulario con borde y margen */
    form {
      border: 2px solid #0E4A86;  /* Borde azul */
      padding: 20px;
      border-radius: 10px;
      margin-top: 20px;
    }

    form .form-label {
      font-weight: bold;
    }
  </style>
</head>
<body>

    <?php include 'includes/design/header.php'; ?>

    <div class="container-fluid py-4 py-lg-5">
        <!-- Sección de texto contacto -->
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h2 class="display-5">Contacto</h2>
                <p class="lead">Siempre nos alegra escuchar y hablar sobre nuestros productos y servicios.<br>¡Comunícanos qué piensas!</p>
            </div>
        </div>

        <!-- Formulario e imagen -->
        <div class="row mb-5 align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <form action="contact_us.php" method="POST" onsubmit="return validateForm()">
                    <h2 class="mb-4">Contacto</h2>

                    <div class="mb-3">
                        <label for="correo" class="form-label">Correo Electrónico:</label>
                        <input type="email" class="form-control" id="correo" name="correo" placeholder="Ingresa tu Correo Electrónico" required>
                    </div>

                    <div class="mb-3">
                        <label for="mensaje" class="form-label">Mensaje:</label>
                        <textarea class="form-control" id="mensaje" name="mensaje" rows="5" placeholder="Escribe tu mensaje aquí" required></textarea>
                    </div>

                    <button type="submit" class="btn btn-azul w-100">Enviar</button>
                </form>
            </div>

            <div class="col-lg-6">
                <img src="assets/img/img3.jpg" alt="Grupo de personas" class="img-fluid rounded shadow">
            </div>
        </div>

        <!-- Sección de ayuda -->
        <div class="row mb-5">
            <div class="col-12 text-center mb-4">
                <h2 class="display-5">¿Cómo podemos ayudarte?</h2>
            </div>

            <div class="row justify-content-center">
                <div class="col-12 col-md-6 col-lg-4 mb-4">
                    <a href="soporte.php" class="text-decoration-none">
                        <div class="card text-center p-4 shadow-sm h-100">
                            <img src="assets/img/soporte.webp" class="card-img-top mx-auto" style="max-width: 100px;" alt="Soporte Técnico">
                            <div class="card-body">
                                <h3 class="card-title">Soporte Técnico</h3>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-12 col-md-6 col-lg-4 mb-4">
                    <a href="reportes.php" class="text-decoration-none">
                        <div class="card text-center p-4 shadow-sm h-100">
                            <img src="assets/img/seg.webp" class="card-img-top mx-auto" style="max-width: 100px;" alt="Realiza Reportes">
                            <div class="card-body">
                                <h3 class="card-title">Realiza Reportes</h3>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

        </div>
    </div>

    <!-- Sección de ubicación -->
    <section class="ubicacion py-5 bg-light">
        <div class="container">
            <div class="row d-flex justify-content-center ">
                <div class="col-12 text-center mb-4">
                    <h2 class="display-5">Nuestra ubicación</h2>
                </div>

                <div class="col-12" style="width: 800px;">
                    <div class="ratio ratio-16x9">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3978.048260414805!2d-75.15232398960484!3d4.402072895553596!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8e38daac36ef33ef%3A0xc4167c4b60b14a15!2sSENA%20Centro%20de%20Industria%20y%20de%20la%20Construcci%C3%B3n!5e0!3m2!1ses-419!2sco!4v1733783533842!5m2!1ses-419!2sco"
                            allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
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
        var correo = document.getElementById("correo").value;
        var mensaje = document.getElementById("mensaje").value;

        // Validar correo
        var emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
        if (!emailPattern.test(correo)) {
          alert("Por favor ingresa un correo válido.");
          return false;
        }

        // Validar mensaje
        if (mensaje.trim() === "") {
          alert("Por favor escribe un mensaje.");
          return false;
        }

        return true;
      }
    </script>

</body>
</html>

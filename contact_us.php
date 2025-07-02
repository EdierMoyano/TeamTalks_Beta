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
                    font-family: 'Inter', sans-serif;
                    background-color: #f8fafc;
                    color: #1e293b;
                    margin: 0;
                    padding: 0;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    background-color: #ffffff;
                    border-radius: 16px;
                    overflow: hidden;
                    box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
                }
                .header {
                    background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
                    color: white;
                    padding: 2rem;
                    text-align: center;
                }
                .header h2 {
                    margin: 0;
                    font-size: 1.5rem;
                    font-weight: 600;
                }
                .content {
                    padding: 2rem;
                }
                .info-item {
                    background: #f8fafc;
                    border-radius: 12px;
                    padding: 1.5rem;
                    margin-bottom: 1rem;
                    border-left: 4px solid #2563eb;
                }
                .info-label {
                    font-weight: 600;
                    color: #2563eb;
                    margin-bottom: 0.5rem;
                }
                .info-value {
                    color: #64748b;
                    line-height: 1.6;
                }
                .footer {
                    background: #f8fafc;
                    padding: 1.5rem;
                    text-align: center;
                    font-size: 0.9rem;
                    color: #64748b;
                    border-top: 1px solid #e2e8f0;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Nuevo Mensaje de Contacto</h2>
                </div>
                <div class='content'>
                    <div class='info-item'>
                        <div class='info-label'>Correo Electrónico:</div>
                        <div class='info-value'>$correo</div>
                    </div>
                    <div class='info-item'>
                        <div class='info-label'>Mensaje:</div>
                        <div class='info-value'>$mensaje</div>
                    </div>
                </div>
                <div class='footer'>
                    <p>Este mensaje fue generado automáticamente desde TeamTalks.</p>
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contáctanos - TeamTalks</title>
    <link rel="icon" href="assets/img/icon2.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/index.css">
        <link rel="stylesheet" href="styles/header.css">

</head>

<body>
    <?php include 'includes/design/header.php'; ?>

    <!-- Hero Section -->
    <section class="contact-hero-section">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-8">
                    <div class="contact-hero-content">
                        <div class="contact-hero-badge mb-4">
                            <i class='bx bx-message-dots me-2'></i>
                            <span>Estamos aquí para ayudarte</span>
                        </div>

                        <h1 class="contact-hero-title mb-4">
                            Ponte en <span class="text-gradient">contacto</span> con nosotros
                        </h1>

                        <p class="contact-hero-description">
                            Siempre nos alegra escuchar y hablar sobre nuestros productos y servicios.
                            ¡Comunícanos qué piensas y cómo podemos ayudarte!
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Form Section -->
    <section class="contact-form-section">
        <div class="container">
            <div class="row align-items-center g-5">
                <!-- Form -->
                <div class="col-lg-6">
                    <div class="contact-form-container">
                        <div class="contact-form-header mb-4">
                            <h2 class="contact-form-title">Envíanos un mensaje</h2>
                            <p class="contact-form-subtitle">
                                Completa el formulario y nos pondremos en contacto contigo lo antes posible.
                            </p>
                        </div>

                        <form action="contact_us.php" method="POST" class="contact-form" onsubmit="return validateForm()">
                            <div class="form-group mb-4">
                                <label for="correo" class="form-label">
                                    <i class='bx bx-envelope me-2'></i>
                                    Correo Electrónico
                                </label>
                                <input
                                    type="email"
                                    class="form-control contact-input"
                                    id="correo"
                                    name="correo"
                                    placeholder="tu@email.com"
                                    required>
                            </div>

                            <div class="form-group mb-4">
                                <label for="mensaje" class="form-label">
                                    <i class='bx bx-message me-2'></i>
                                    Mensaje
                                </label>
                                <textarea
                                    class="form-control contact-textarea"
                                    id="mensaje"
                                    name="mensaje"
                                    rows="6"
                                    placeholder="Escribe tu mensaje aquí..."
                                    required></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary contact-submit-btn w-100">
                                <i class='bx bx-send me-2'></i>
                                Enviar mensaje
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Image -->
                <div class="col-lg-6">
                    <div class="contact-image-container">
                        <img src="assets/img/img3.jpg" alt="Equipo de soporte" class="img-fluid contact-image">
                        <div class="contact-image-overlay">
                            <div class="contact-overlay-content">
                                <i class='bx bx-support'></i>
                                <span>Soporte 24/7</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Help Section -->
    <section class="contact-help-section">
        <div class="container">
            <div class="row justify-content-center text-center mb-5">
                <div class="col-lg-8">
                    <h2 class="section-title mb-3">¿Cómo podemos ayudarte?</h2>
                    <p class="section-subtitle">
                        Explora nuestras opciones de soporte y encuentra la ayuda que necesitas
                    </p>
                </div>
            </div>

            <div class="row g-4 justify-content-center">
                <!-- Soporte Técnico -->
                <div class="col-lg-4 col-md-6">
                    <a href="soporte.php" class="contact-help-card-link">
                        <div class="contact-help-card">
                            <div class="contact-help-icon">
                                <img src="assets/img/soporte.webp" alt="Soporte Técnico" class="contact-help-image">

                            </div>
                            <div class="contact-help-content">
                                <h3 class="contact-help-title">Soporte Técnico</h3>
                                <p class="contact-help-text">
                                    Obtén ayuda técnica especializada para resolver cualquier problema
                                </p>
                                <div class="contact-help-arrow">
                                    <i class='bx bx-right-arrow-alt'></i>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Reportes -->
                <div class="col-lg-4 col-md-6">
                    <a href="reportes.php" class="contact-help-card-link">
                        <div class="contact-help-card">
                            <div class="contact-help-icon">
                                <img src="assets/img/seg.webp" alt="Realiza Reportes" class="contact-help-image">

                            </div>
                            <div class="contact-help-content">
                                <h3 class="contact-help-title">Realiza Reportes</h3>
                                <p class="contact-help-text">
                                    Reporta problemas de seguridad o comportamiento inapropiado
                                </p>
                                <div class="contact-help-arrow">
                                    <i class='bx bx-right-arrow-alt'></i>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Info Section -->
    <section class="contact-info-section">
        <div class="container">
            <div class="row justify-content-center text-center mb-5">
                <div class="col-lg-8">
                    <h2 class="section-title mb-3">Información de contacto</h2>
                    <p class="section-subtitle">
                        Múltiples formas de ponerte en contacto con nuestro equipo
                    </p>
                </div>
            </div>

            <div class="row g-4">
                <!-- Email -->
                <div class="col-lg-4 col-md-6">
                    <div class="contact-info-card">
                        <div class="contact-info-icon">
                            <i class='bx bx-envelope'></i>
                        </div>
                        <h3 class="contact-info-title">Email</h3>
                        <p class="contact-info-text">
                            <a href="mailto:teamtalks39@gmail.com" class="contact-info-link">
                                teamtalks39@gmail.com
                            </a>
                        </p>
                        <span class="contact-info-label">Respuesta en 24 horas</span>
                    </div>
                </div>

                <!-- Teléfono -->
                <div class="col-lg-4 col-md-6">
                    <div class="contact-info-card">
                        <div class="contact-info-icon">
                            <i class='bx bx-phone'></i>
                        </div>
                        <h3 class="contact-info-title">Teléfono</h3>
                        <p class="contact-info-text">
                            <a href="tel:+573197666683" class="contact-info-link">
                                +57 319 766 6683
                            </a>
                        </p>
                        <span class="contact-info-label">Lunes a Viernes 8AM - 6PM</span>
                    </div>
                </div>

                <!-- Ubicación -->
                <div class="col-lg-4 col-md-6">
                    <div class="contact-info-card">
                        <div class="contact-info-icon">
                            <i class='bx bx-map'></i>
                        </div>
                        <h3 class="contact-info-title">Ubicación</h3>
                        <p class="contact-info-text">
                            SENA Centro de Industria<br>
                            Ibagué, Tolima
                        </p>
                        <span class="contact-info-label">Visítanos</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="contact-map-section">
        <div class="container">
            <div class="row justify-content-center text-center mb-5">
                <div class="col-lg-8">
                    <h2 class="section-title mb-3">Nuestra ubicación</h2>
                    <p class="section-subtitle">
                        Encuéntranos en el SENA Centro de Industria y de la Construcción
                    </p>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="contact-map-container">
                        <iframe
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3978.048260414805!2d-75.15232398960484!3d4.402072895553596!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8e38daac36ef33ef%3A0xc4167c4b60b14a15!2sSENA%20Centro%20de%20Industria%20y%20de%20la%20Construcci%C3%B3n!5e0!3m2!1ses-419!2sco!4v1733783533842!5m2!1ses-419!2sco"
                            class="contact-map"
                            allowfullscreen
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/design/footer.php'; ?>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content contact-modal">
                <div class="modal-header contact-modal-header">
                    <div class="contact-modal-icon">
                        <i class='bx bx-check-circle'></i>
                    </div>
                    <h5 class="modal-title contact-modal-title" id="successModalLabel">
                        ¡Mensaje enviado exitosamente!
                    </h5>
                </div>
                <div class="modal-body contact-modal-body">
                    <p><?php echo isset($successMessage) ? $successMessage : ''; ?></p>
                    <p class="contact-modal-subtitle">
                        Nos pondremos en contacto contigo lo antes posible.
                    </p>
                </div>
                <div class="modal-footer contact-modal-footer">
                    <button type="button" class="btn btn-primary contact-modal-btn" data-bs-dismiss="modal">
                        Entendido
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/contact-animations.js"></script>

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
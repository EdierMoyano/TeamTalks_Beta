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
        $mail->addAddress('teamtalks39@gmail.com', 'Equipo de Soporte'); // Cambia esto por el correo de la empresa

        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject = 'Solicitud de Soporte Tecnico';
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
                .problem-box {
                    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
                    border-radius: 12px;
                    padding: 1.5rem;
                    margin-top: 1rem;
                    border-left: 4px solid #f59e0b;
                }
                .problem-box h3 {
                    color: #92400e;
                    margin: 0 0 0.5rem 0;
                    font-size: 1.1rem;
                }
                .problem-box p {
                    color: #78350f;
                    margin: 0;
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
                    <h2>🛠️ Solicitud de Soporte Técnico</h2>
                </div>
                <div class='content'>
                    <div class='info-item'>
                        <div class='info-label'>Nombre Completo:</div>
                        <div class='info-value'>$nombre</div>
                    </div>
                    <div class='info-item'>
                        <div class='info-label'>Correo Electrónico:</div>
                        <div class='info-value'>$correo</div>
                    </div>
                    <div class='info-item'>
                        <div class='info-label'>Número de Teléfono:</div>
                        <div class='info-value'>$numero</div>
                    </div>
                    <div class='info-item'>
                        <div class='info-label'>Fecha de Solicitud:</div>
                        <div class='info-value'>" . date('d/m/Y H:i:s') . "</div>
                    </div>
                    <div class='problem-box'>
                        <h3>Descripción del Problema:</h3>
                        <p>$problema</p>
                    </div>
                </div>
                <div class='footer'>
                    <p>Este mensaje fue generado automáticamente desde TeamTalks. Nuestro equipo se pondrá en contacto contigo pronto.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        // Enviar el correo
        if ($mail->send()) {
            // Establecer una variable para mostrar el modal en el frontend
            $successMessage = "Tu solicitud de soporte ha sido enviada exitosamente.";
            echo '<script>var showModal = true;</script>';
        } else {
            $successMessage = "No se pudo enviar la solicitud.";
            echo '<script>var showModal = false;</script>';
        }

    } catch (Exception $e) {
        $successMessage = "Error al enviar la solicitud: {$mail->ErrorInfo}";
        echo '<script>var showModal = false;</script>';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soporte Técnico - TeamTalks</title>
    <link rel="icon" href="assets/img/icon2.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/index.css">
</head>
<body>
    <?php include 'includes/design/header.php'; ?>

    <!-- Hero Section -->
    <section class="support-hero-section">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-8">
                    <div class="support-hero-content">
                        <div class="support-hero-badge mb-4">
                            <i class='bx bx-support me-2'></i>
                            <span>Estamos aquí para ayudarte</span>
                        </div>
                        
                        <h1 class="support-hero-title mb-4">
                            <span class="text-gradient">Soporte Técnico</span> Especializado
                        </h1>
                        
                        <p class="support-hero-description">
                            Nuestro equipo de expertos está listo para resolver cualquier problema técnico. 
                            Completa el formulario y nos pondremos en contacto contigo lo antes posible.
                        </p>

                        <!-- Support Stats -->
                        <div class="support-stats-container">
                            <div class="support-stat-item">
                                <div class="support-stat-number">24/7</div>
                                <div class="support-stat-label">Disponibilidad</div>
                            </div>
                            <div class="support-stat-item">
                                <div class="support-stat-number">&lt;2h</div>
                                <div class="support-stat-label">Tiempo de respuesta</div>
                            </div>
                            <div class="support-stat-item">
                                <div class="support-stat-number">98%</div>
                                <div class="support-stat-label">Satisfacción</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Support Form Section -->
    <section class="support-form-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="support-form-container">
                        <div class="support-form-header mb-4">
                            <h2 class="support-form-title">Solicitar Soporte Técnico</h2>
                            <p class="support-form-subtitle">
                                Proporciona la información necesaria para que podamos ayudarte de la mejor manera
                            </p>
                        </div>

                        <form action="soporte.php" method="POST" class="support-form" onsubmit="return validateForm()">
                            <div class="row g-4">
                                <!-- Nombre -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="nombreSoporte" class="form-label">
                                            <i class='bx bx-user me-2'></i>
                                            Nombre Completo
                                        </label>
                                        <input 
                                            type="text" 
                                            class="form-control support-input" 
                                            id="nombreSoporte" 
                                            name="nombreSoporte" 
                                            placeholder="Tu nombre completo" 
                                            required
                                        >
                                    </div>
                                </div>

                                <!-- Correo -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="correoSoporte" class="form-label">
                                            <i class='bx bx-envelope me-2'></i>
                                            Correo Electrónico
                                        </label>
                                        <input 
                                            type="email" 
                                            class="form-control support-input" 
                                            id="correoSoporte" 
                                            name="correoSoporte" 
                                            placeholder="tu@email.com" 
                                            required
                                        >
                                    </div>
                                </div>

                                <!-- Teléfono -->
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="numeroSoporte" class="form-label">
                                            <i class='bx bx-phone me-2'></i>
                                            Número de Teléfono
                                        </label>
                                        <input 
                                            type="tel" 
                                            class="form-control support-input" 
                                            id="numeroSoporte" 
                                            name="numeroSoporte" 
                                            placeholder="+57 300 123 4567" 
                                            required
                                        >
                                    </div>
                                </div>

                                <!-- Problema -->
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="problema" class="form-label">
                                            <i class='bx bx-message-detail me-2'></i>
                                            Describe tu Problema
                                        </label>
                                        <textarea 
                                            class="form-control support-textarea" 
                                            id="problema" 
                                            name="problema" 
                                            rows="6" 
                                            placeholder="Describe detalladamente el problema que estás experimentando. Incluye pasos para reproducir el error, mensajes de error específicos, y cualquier información adicional que consideres relevante..."
                                            required
                                        ></textarea>
                                        <div class="form-text">
                                            <i class='bx bx-info-circle me-1'></i>
                                            Mientras más detalles proporciones, mejor podremos ayudarte
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="support-form-actions mt-4">
                                <button type="submit" class="btn btn-primary support-submit-btn">
                                    <i class='bx bx-send me-2'></i>
                                    Enviar Solicitud de Soporte
                                </button>
                                <p class="support-form-note">
                                    <i class='bx bx-shield-check me-1'></i>
                                    Tu información está protegida y será utilizada únicamente para brindarte soporte
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Support Info Section -->
    <section class="support-info-section">
        <div class="container">
            <div class="row justify-content-center text-center mb-5">
                <div class="col-lg-8">
                    <h2 class="section-title mb-3">¿Qué puedes esperar?</h2>
                    <p class="section-subtitle">
                        Nuestro proceso de soporte está diseñado para resolver tu problema de manera rápida y eficiente
                    </p>
                </div>
            </div>

            <div class="row g-4">
                <!-- Paso 1 -->
                <div class="col-lg-4 col-md-6">
                    <div class="support-info-card">
                        <div class="support-info-step">1</div>
                        <div class="support-info-icon">
                            <i class='bx bx-receipt'></i>
                        </div>
                        <h3 class="support-info-title">Recibimos tu Solicitud</h3>
                        <p class="support-info-text">
                            Tu solicitud es registrada inmediatamente en nuestro sistema y 
                            asignada a un especialista según el tipo de problema.
                        </p>
                    </div>
                </div>

                <!-- Paso 2 -->
                <div class="col-lg-4 col-md-6">
                    <div class="support-info-card">
                        <div class="support-info-step">2</div>
                        <div class="support-info-icon">
                            <i class='bx bx-search-alt'></i>
                        </div>
                        <h3 class="support-info-title">Análisis del Problema</h3>
                        <p class="support-info-text">
                            Nuestro equipo técnico analiza tu problema y prepara una 
                            solución personalizada basada en tu descripción.
                        </p>
                    </div>
                </div>

                <!-- Paso 3 -->
                <div class="col-lg-4 col-md-6">
                    <div class="support-info-card">
                        <div class="support-info-step">3</div>
                        <div class="support-info-icon">
                            <i class='bx bx-check-circle'></i>
                        </div>
                        <h3 class="support-info-title">Solución y Seguimiento</h3>
                        <p class="support-info-text">
                            Te contactamos con la solución y hacemos seguimiento para 
                            asegurar que tu problema haya sido resuelto completamente.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="support-faq-section">
        <div class="container">
            <div class="row justify-content-center text-center mb-5">
                <div class="col-lg-8">
                    <h2 class="section-title mb-3">Preguntas Frecuentes</h2>
                    <p class="section-subtitle">
                        Encuentra respuestas rápidas a las consultas más comunes
                    </p>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="accordion support-accordion" id="supportAccordion">
                        <!-- FAQ 1 -->
                        <div class="accordion-item support-accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button support-accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    <i class='bx bx-time-five me-2'></i>
                                    ¿Cuánto tiempo tarda la respuesta?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#supportAccordion">
                                <div class="accordion-body support-accordion-body">
                                    Nuestro tiempo promedio de respuesta es de menos de 2 horas durante horario laboral. 
                                    Para problemas críticos, respondemos inmediatamente.
                                </div>
                            </div>
                        </div>

                        <!-- FAQ 2 -->
                        <div class="accordion-item support-accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button support-accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    <i class='bx bx-calendar me-2'></i>
                                    ¿Qué horarios de atención tienen?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#supportAccordion">
                                <div class="accordion-body support-accordion-body">
                                    Ofrecemos soporte 24/7 para problemas críticos. Para consultas generales, 
                                    nuestro horario es de lunes a viernes de 8:00 AM a 6:00 PM.
                                </div>
                            </div>
                        </div>

                        <!-- FAQ 3 -->
                        <div class="accordion-item support-accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button support-accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    <i class='bx bx-info-circle me-2'></i>
                                    ¿Qué información debo incluir?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#supportAccordion">
                                <div class="accordion-body support-accordion-body">
                                    Incluye una descripción detallada del problema, pasos para reproducirlo, 
                                    mensajes de error específicos, y el navegador/dispositivo que estás usando.
                                </div>
                            </div>
                        </div>

                        <!-- FAQ 4 -->
                        <div class="accordion-item support-accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button support-accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                    <i class='bx bx-shield-check me-2'></i>
                                    ¿Es seguro compartir mi información?
                                </button>
                            </h2>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#supportAccordion">
                                <div class="accordion-body support-accordion-body">
                                    Absolutamente. Toda la información compartida está protegida y se utiliza 
                                    únicamente para brindarte el mejor soporte técnico posible.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/design/footer.php'; ?>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content support-modal">
                <div class="modal-header support-modal-header">
                    <div class="support-modal-icon">
                        <i class='bx bx-check-circle'></i>
                    </div>
                    <h5 class="modal-title support-modal-title" id="successModalLabel">
                        ¡Solicitud Enviada Exitosamente!
                    </h5>
                </div>
                <div class="modal-body support-modal-body">
                    <p><?php echo isset($successMessage) ? $successMessage : ''; ?></p>
                    <p class="support-modal-subtitle">
                        Nuestro equipo técnico revisará tu solicitud y se pondrá en contacto contigo pronto.
                    </p>
                    <div class="support-modal-info">
                        <i class='bx bx-info-circle me-2'></i>
                        <span>Recibirás una confirmación por correo electrónico</span>
                    </div>
                </div>
                <div class="modal-footer support-modal-footer">
                    <button type="button" class="btn btn-primary support-modal-btn" data-bs-dismiss="modal">
                        Entendido
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/support-animations.js"></script>

    <script>
        // Mostrar el modal si el correo fue enviado exitosamente
        if (typeof showModal !== 'undefined' && showModal) {
            var myModal = new bootstrap.Modal(document.getElementById('successModal'), {
                keyboard: false
            });
            myModal.show();
        }

        // Validaciones del formulario mejoradas
        function validateForm() {
            var nombre = document.getElementById("nombreSoporte").value;
            var correo = document.getElementById("correoSoporte").value;
            var numero = document.getElementById("numeroSoporte").value;
            var problema = document.getElementById("problema").value;

            // Validar nombre
            if (nombre.trim().length < 2) {
                alert("Por favor ingresa tu nombre completo.");
                return false;
            }

            // Validar correo
            var emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
            if (!emailPattern.test(correo)) {
                alert("Por favor ingresa un correo válido.");
                return false;
            }

            // Validar número de teléfono (más flexible)
            var phonePattern = /^[\+]?[0-9\s\-$$$$]{10,}$/;
            if (!phonePattern.test(numero)) {
                alert("Por favor ingresa un número de teléfono válido.");
                return false;
            }

            // Validar descripción del problema
            if (problema.trim().length < 10) {
                alert("Por favor describe el problema con más detalle (mínimo 10 caracteres).");
                return false;
            }

            return true;
        }
    </script>
</body>
</html>

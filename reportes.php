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
    $mail->setFrom('teamtalks39@gmail.com', 'Sistema de Reportes');
    $mail->addAddress('teamtalks39@gmail.com', 'Equipo de Reportes'); // Cambia esto por el correo de la empresa

    // Contenido del correo
    $mail->isHTML(true);
    $mail->Subject = 'Nuevo Reporte de Usuario - ' . $tipoReporte;
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
                    background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
                    color: white;
                    padding: 2rem;
                    text-align: center;
                }
                .header h2 {
                    margin: 0;
                    font-size: 1.5rem;
                    font-weight: 600;
                }
                .priority-badge {
                    background: rgba(255, 255, 255, 0.2);
                    padding: 0.5rem 1rem;
                    border-radius: 20px;
                    font-size: 0.9rem;
                    margin-top: 0.5rem;
                    display: inline-block;
                }
                .content {
                    padding: 2rem;
                }
                .info-item {
                    background: #f8fafc;
                    border-radius: 12px;
                    padding: 1.5rem;
                    margin-bottom: 1rem;
                    border-left: 4px solid #dc2626;
                }
                .info-label {
                    font-weight: 600;
                    color: #dc2626;
                    margin-bottom: 0.5rem;
                }
                .info-value {
                    color: #64748b;
                    line-height: 1.6;
                }
                .report-type {
                    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
                    border-radius: 12px;
                    padding: 1.5rem;
                    margin: 1rem 0;
                    border-left: 4px solid #f59e0b;
                }
                .report-type h3 {
                    color: #92400e;
                    margin: 0 0 0.5rem 0;
                    font-size: 1.1rem;
                }
                .report-type p {
                    color: #78350f;
                    margin: 0;
                    font-weight: 600;
                }
                .description-box {
                    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
                    border-radius: 12px;
                    padding: 1.5rem;
                    margin-top: 1rem;
                    border-left: 4px solid #dc2626;
                }
                .description-box h3 {
                    color: #991b1b;
                    margin: 0 0 0.5rem 0;
                    font-size: 1.1rem;
                }
                .description-box p {
                    color: #7f1d1d;
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
                .urgent-notice {
                    background: #dc2626;
                    color: white;
                    padding: 1rem;
                    text-align: center;
                    font-weight: 600;
                    font-size: 0.9rem;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='urgent-notice'>
                    🚨 REPORTE URGENTE - REQUIERE ATENCIÓN INMEDIATA
                </div>
                <div class='header'>
                    <h2>🛡️ Nuevo Reporte de Usuario</h2>
                    <div class='priority-badge'>Prioridad: Alta</div>
                </div>
                <div class='content'>
                    <div class='info-item'>
                        <div class='info-label'>Nombre del Reportante:</div>
                        <div class='info-value'>" . ($nombre ?: 'Anónimo') . "</div>
                    </div>
                    <div class='info-item'>
                        <div class='info-label'>Correo Electrónico:</div>
                        <div class='info-value'>$correo</div>
                    </div>
                    <div class='info-item'>
                        <div class='info-label'>Fecha del Reporte:</div>
                        <div class='info-value'>" . date('d/m/Y H:i:s') . "</div>
                    </div>
                    <div class='report-type'>
                        <h3>Tipo de Reporte:</h3>
                        <p>$tipoReporte</p>
                    </div>
                    <div class='description-box'>
                        <h3>Descripción Detallada:</h3>
                        <p>$descripcion</p>
                    </div>
                </div>
                <div class='footer'>
                    <p><strong>Acción requerida:</strong> Este reporte requiere revisión y respuesta inmediata del equipo técnico.</p>
                    <p>Generado automáticamente desde TeamTalks - Sistema de Reportes</p>
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
      $successMessage = "No se pudo enviar el reporte.";
      echo '<script>var showModal = false;</script>';
    }
  } catch (Exception $e) {
    $successMessage = "Error al enviar el reporte: {$mail->ErrorInfo}";
    echo '<script>var showModal = false;</script>';
  }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Realizar Reporte - TeamTalks</title>
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
  <section class="reports-hero-section">
    <div class="container">
      <div class="row justify-content-center text-center">
        <div class="col-lg-8">
          <div class="reports-hero-content">
            <div class="reports-hero-badge mb-4">
              <i class='bx bx-shield-check me-2'></i>
              <span>Tu seguridad es nuestra prioridad</span>
            </div>

            <h1 class="reports-hero-title mb-4">
              <span class="text-gradient">Reportar</span> un Problema
            </h1>

            <p class="reports-hero-description">
              Ayúdanos a mantener TeamTalks seguro y funcional para todos.
              Tu reporte es confidencial y será atendido con la máxima prioridad.
            </p>

            <!-- Security Features -->
            <div class="reports-security-features">
              <div class="security-feature">
                <i class='bx bx-lock-alt'></i>
                <span>100% Confidencial</span>
              </div>
              <div class="security-feature">
                <i class='bx bx-time-five'></i>
                <span>Respuesta en 24h</span>
              </div>
              <div class="security-feature">
                <i class='bx bx-shield'></i>
                <span>Datos Protegidos</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Report Form Section -->
  <section class="reports-form-section">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="reports-form-container">
            <div class="reports-form-header mb-4">
              <h2 class="reports-form-title">Formulario de Reporte</h2>
              <p class="reports-form-subtitle">
                Completa la información necesaria para que podamos investigar y resolver el problema
              </p>
            </div>

            <form action="reportes.php" method="POST" class="reports-form" onsubmit="return validateForm()">
              <div class="row g-4">
                <!-- Nombre (Opcional) -->
                <div class="col-12">
                  <div class="form-group">
                    <label for="nombreReporte" class="form-label">
                      <i class='bx bx-user me-2'></i>
                      Nombre Completo
                      <span class="optional-badge">Opcional</span>
                    </label>
                    <input
                      type="text"
                      class="form-control reports-input"
                      id="nombreReporte"
                      name="nombreReporte"
                      placeholder="Puedes mantener tu anonimato dejando este campo vacío">
                    <div class="form-text">
                      <i class='bx bx-info-circle me-1'></i>
                      Tu identidad será protegida independientemente de si proporcionas tu nombre
                    </div>
                  </div>
                </div>

                <!-- Correo -->
                <div class="col-12">
                  <div class="form-group">
                    <label for="correoSoporte" class="form-label">
                      <i class='bx bx-envelope me-2'></i>
                      Correo Electrónico
                      <span class="required-badge">Requerido</span>
                    </label>
                    <input
                      type="email"
                      class="form-control reports-input"
                      id="correoSoporte"
                      name="correoSoporte"
                      placeholder="tu@email.com"
                      required>
                    <div class="form-text">
                      <i class='bx bx-lock-alt me-1'></i>
                      Necesario para enviarte actualizaciones sobre tu reporte
                    </div>
                  </div>
                </div>

                <!-- Tipo de Reporte -->
                <div class="col-12">
                  <div class="form-group">
                    <label for="tipoReporte" class="form-label">
                      <i class='bx bx-category me-2'></i>
                      Tipo de Problema
                      <span class="required-badge">Requerido</span>
                    </label>
                    <select class="form-select reports-select" id="tipoReporte" name="tipoReporte" required>
                      <option value="" disabled selected>Selecciona el tipo de problema</option>
                      <optgroup label="🔐 Problemas de Acceso">
                        <option value="Problemas de acceso o inicio de sesión">Problemas de acceso o inicio de sesión</option>
                        <option value="Problemas con la cuenta de usuario">Problemas con la cuenta de usuario</option>
                      </optgroup>
                      <optgroup label="⚠️ Errores del Sistema">
                        <option value="Error en el sistema o caída del servicio">Error en el sistema o caída del servicio</option>
                        <option value="Fallas en la carga de la página">Fallas en la carga de la página</option>
                        <option value="Problemas con la funcionalidad de formularios">Problemas con la funcionalidad de formularios</option>
                        <option value="Errores al enviar o recibir datos">Errores al enviar o recibir datos</option>
                      </optgroup>
                      <optgroup label="🎨 Problemas Visuales">
                        <option value="Fallas en la visualización de contenidos o imágenes">Fallas en la visualización de contenidos o imágenes</option>
                      </optgroup>
                      <optgroup label="🐌 Rendimiento">
                        <option value="Desempeño lento o problemas de velocidad">Desempeño lento o problemas de velocidad</option>
                      </optgroup>
                      <optgroup label="🔧 Otros">
                        <option value="Otros problemas técnicos">Otros problemas técnicos</option>
                      </optgroup>
                    </select>
                  </div>
                </div>

                <!-- Descripción -->
                <div class="col-12">
                  <div class="form-group">
                    <label for="descripcionReporte" class="form-label">
                      <i class='bx bx-message-detail me-2'></i>
                      Descripción Detallada
                      <span class="required-badge">Requerido</span>
                    </label>
                    <textarea
                      class="form-control reports-textarea"
                      id="descripcionReporte"
                      name="descripcionReporte"
                      rows="6"
                      placeholder="Describe detalladamente el problema:&#10;&#10;• ¿Qué estaba haciendo cuando ocurrió?&#10;• ¿Qué esperaba que pasara?&#10;• ¿Qué pasó en su lugar?&#10;• ¿Cuándo comenzó el problema?&#10;• ¿Has intentado alguna solución?"
                      required></textarea>
                    <div class="form-text">
                      <i class='bx bx-bulb me-1'></i>
                      Mientras más detalles proporciones, más rápido podremos resolver el problema
                    </div>
                  </div>
                </div>
              </div>

              <!-- Privacy Notice -->
              <div class="reports-privacy-notice">
                <div class="privacy-icon">
                  <i class='bx bx-shield-check'></i>
                </div>
                <div class="privacy-content">
                  <h4>Compromiso de Privacidad</h4>
                  <p>
                    Tu reporte será tratado con total confidencialidad. Solo el equipo técnico
                    autorizado tendrá acceso a esta información para resolver el problema reportado.
                  </p>
                </div>
              </div>

              <div class="reports-form-actions mt-4">
                <button type="submit" class="btn btn-primary reports-submit-btn">
                  <i class='bx bx-send me-2'></i>
                  Enviar Reporte
                </button>
                <p class="reports-form-note">
                  <i class='bx bx-time me-1'></i>
                  Recibirás una confirmación por correo y actualizaciones sobre el progreso
                </p>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Report Types Info Section -->
  <section class="reports-info-section">
    <div class="container">
      <div class="row justify-content-center text-center mb-5">
        <div class="col-lg-8">
          <h2 class="section-title mb-3">¿Qué tipos de problemas puedes reportar?</h2>
          <p class="section-subtitle">
            Estamos aquí para ayudarte con cualquier problema técnico que encuentres
          </p>
        </div>
      </div>

      <div class="row g-4">
        <!-- Problemas Críticos -->
        <div class="col-lg-4 col-md-6">
          <div class="reports-info-card critical">
            <div class="reports-info-icon critical">
              <i class='bx bx-error-circle'></i>
            </div>
            <h3 class="reports-info-title">Problemas Críticos</h3>
            <ul class="reports-info-list">
              <li>Sistema no funciona</li>
              <li>Pérdida de datos</li>
              <li>Errores de seguridad</li>
              <li>Caídas del servicio</li>
            </ul>
            <div class="reports-info-badge critical">
              Respuesta inmediata
            </div>
          </div>
        </div>

        <!-- Problemas Moderados -->
        <div class="col-lg-4 col-md-6">
          <div class="reports-info-card moderate">
            <div class="reports-info-icon moderate">
              <i class='bx bx-error'></i>
            </div>
            <h3 class="reports-info-title">Problemas Moderados</h3>
            <ul class="reports-info-list">
              <li>Funciones no responden</li>
              <li>Errores en formularios</li>
              <li>Problemas de visualización</li>
              <li>Lentitud del sistema</li>
            </ul>
            <div class="reports-info-badge moderate">
              Respuesta en 24h
            </div>
          </div>
        </div>

        <!-- Problemas Menores -->
        <div class="col-lg-4 col-md-6">
          <div class="reports-info-card minor">
            <div class="reports-info-icon minor">
              <i class='bx bx-info-circle'></i>
            </div>
            <h3 class="reports-info-title">Problemas Menores</h3>
            <ul class="reports-info-list">
              <li>Mejoras de interfaz</li>
              <li>Sugerencias de funciones</li>
              <li>Problemas cosméticos</li>
              <li>Optimizaciones</li>
            </ul>
            <div class="reports-info-badge minor">
              Respuesta en 48h
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
      <div class="modal-content reports-modal">
        <div class="modal-header reports-modal-header">
          <div class="reports-modal-icon">
            <i class='bx bx-check-shield'></i>
          </div>
          <h5 class="modal-title reports-modal-title" id="successModalLabel">
            ¡Reporte Enviado Exitosamente!
          </h5>
        </div>
        <div class="modal-body reports-modal-body">
          <p><?php echo isset($successMessage) ? $successMessage : ''; ?></p>
          <div class="reports-modal-info">
            <div class="modal-info-item">
              <i class='bx bx-envelope me-2'></i>
              <span>Recibirás una confirmación por correo</span>
            </div>
            <div class="modal-info-item">
              <i class='bx bx-time me-2'></i>
              <span>Nuestro equipo revisará tu reporte pronto</span>
            </div>
            <div class="modal-info-item">
              <i class='bx bx-shield-check me-2'></i>
              <span>Tu información está completamente protegida</span>
            </div>
          </div>
        </div>
        <div class="modal-footer reports-modal-footer">
          <button type="button" class="btn btn-primary reports-modal-btn" data-bs-dismiss="modal">
            Entendido
          </button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/reports-animations.js"></script>

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
      var correo = document.getElementById("correoSoporte").value;
      var tipoReporte = document.getElementById("tipoReporte").value;
      var descripcion = document.getElementById("descripcionReporte").value;

      // Validar correo
      var emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
      if (!emailPattern.test(correo)) {
        alert("Por favor ingresa un correo válido.");
        return false;
      }

      // Validar tipo de reporte
      if (!tipoReporte) {
        alert("Por favor selecciona el tipo de problema.");
        return false;
      }

      // Validar descripción
      if (descripcion.trim().length < 20) {
        alert("Por favor describe el problema con más detalle (mínimo 20 caracteres).");
        return false;
      }

      return true;
    }
  </script>
</body>

</html>
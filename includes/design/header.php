<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';

$usuario_logueado = isset($_SESSION['documento']);
$id_usuario = $_SESSION['documento'] ?? null;
$username = $_SESSION['nombres'] ?? 'Nombres';
$lastname = $_SESSION['apellidos'] ?? 'Apellidos';
$rol = $_SESSION['rol'] ?? null;
$uri = $_SERVER['REQUEST_URI'];

if (strpos($uri, '/instructor/') !== false) {
    $carpeta_inicio = BASE_URL . '/instructor/index.php';
} elseif (strpos($uri, '/transversal/') !== false) {
    $carpeta_inicio = BASE_URL . '/transversal/index.php';
} elseif (strpos($uri, '/aprendiz/') !== false) {
    $carpeta_inicio = BASE_URL . '/aprendiz/tarjeta_formacion/index.php';
} elseif (strpos($uri, '/mod/') !== false) {

    // Para rutas compartidas, usamos el rol para definir a qué dashboard enviar
    if ($rol == 3) { // Instructor gerente
        $carpeta_inicio = BASE_URL . '/instructor/index.php';
    } elseif ($rol == 5) { // Instructor transversal
        $carpeta_inicio = BASE_URL . '/transversal/index.php';
    } else {
        $carpeta_inicio = BASE_URL . '/index.php';
    }
} else {
    $carpeta_inicio = BASE_URL . '/index.php';
}

$logo_href = !$usuario_logueado ? BASE_URL . '/index.php' : $carpeta_inicio;

// Cargar datos del usuario si hay sesión
if ($id_usuario) {
    $datos = $conex->prepare("SELECT * FROM usuarios INNER JOIN roles ON usuarios.id_rol = roles.id_rol WHERE id = ? ");
    $datos->execute([$id_usuario]);
    $user = $datos->fetch(PDO::FETCH_ASSOC);
} else {
    $user = null;
}

$notificaciones = [];
if ($id_usuario) {

    $stmt = $conex->prepare("

        SELECT
        n.id_notificacion,
        n.mensaje,
        n.url_destino,
        n.leido,
        n.fecha,
        u.nombres AS autor_nombres,
        ro.rol,
        u.apellidos AS autor_apellidos,
        r.descripcion AS contenido
    FROM notificaciones n
    JOIN usuarios u ON n.id_emisor = u.id
    JOIN roles ro ON u.id_rol = ro.id_rol 
    LEFT JOIN respuesta_foro r ON n.id_respuesta_foro = r.id_respuesta_foro
    WHERE n.id_usuario = ?
    ORDER BY n.fecha DESC
    LIMIT 10
    ");

    $stmt->execute([$id_usuario]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {

        $notificaciones[] = [
            'id_notificacion' => $row['id_notificacion'],
            'mensaje' => $row['mensaje'],
            'url_destino' => $row['url_destino'],
            'leido' => $row['leido'],
            'fecha' => $row['fecha'],
            'autor' => trim(($row['autor_nombres'] ?? 'Usuario') . ' ' . ($row['autor_apellidos'] ?? 'Anónimo')),
            'contenido' => $row['contenido'] ? mb_strimwidth($row['contenido'], 0, 100, '...') : 'Sin contenido disponible'

        ];
    }
}

// Contar notificaciones no leídas

$notificaciones_no_leidas = 0;

if ($id_usuario) {
    $stmt_count = $conex->prepare("SELECT COUNT(*) FROM notificaciones WHERE id_usuario = ? AND leido = 0");
    $stmt_count->execute([$id_usuario]);
    $notificaciones_no_leidas = $stmt_count->fetchColumn();
}

?>

<style>
    .btn-primary-modern {
        background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
        border: none;
        border-radius: 12px;
        padding: 1rem 2rem;
        font-weight: 600;
        font-size: 0.875rem;
        transition: all 0.3s ease;
        color: white;
        min-width: 140px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .btn-primary-modern:hover {
        transform: translateY(-2px);
        color: white;
        box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
        background: linear-gradient(135deg, #1e40af 0%, #1d4ed8 100%);
    }
</style>

<header class="modern-header">
    <!-- Notification Panel -->
    <div class="offcanvas offcanvas-start notification-panel" data-bs-scroll="true" tabindex="-1" id="offcanvasWithBothOptions" aria-labelledby="offcanvasWithBothOptionsLabel">
        <div class="notification-header">
            <div class="header-content">
                <div class="header-title">
                    <div class="notification-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h5 class="offcanvas-title" id="offcanvasWithBothOptionsLabel">Notificaciones</h5>
                </div>
                <button type="button" class="btn-close-modern" data-bs-dismiss="offcanvas" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <?php if (!empty($notificaciones)): ?>
                <div class="header-actions" id="notification-actions">
                    <button type="button" id="marcar-todas-leidas" class="btn-mark-all">
                        <i class="fas fa-check-double"></i>
                        Marcar todas como leídas
                    </button>
                    <button type="button" id="eliminar-todas" class="btn-delete-all">
                        <i class="fas fa-trash-alt"></i>
                        Eliminar todas
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <div class="offcanvas-body notification-body">
            <?php if (empty($notificaciones)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-bell-slash"></i>
                    </div>
                    <h6 class="empty-title">Sin notificaciones</h6>
                    <p class="empty-text">No tienes notificaciones recientes</p>
                </div>
            <?php else: ?>
                <div class="notifications-list">
                    <?php foreach ($notificaciones as $index => $noti): ?>
                        <div class="notification-item <?= $noti['leido'] ? 'read' : 'unread' ?>"
                            data-notification-id="<?= $noti['id_notificacion'] ?>"
                            style="animation-delay: <?= $index * 0.1 ?>s;">
                            <div class="notification-content">
                                <div class="notification-avatar">
                                    <i class="fas fa-comment-dots"></i>
                                </div>
                                <a href="<?= htmlspecialchars($noti['url_destino']) . '&id_notificacion=' . $noti['id_notificacion'] ?>"
                                    class="notification-body" style="text-decoration: none; color: inherit;">
                                    <div class="notification-message">
                                        <strong><?= htmlspecialchars($noti['autor']) ?></strong>
                                        <span class="action-text">respondió a tu comentario</span>
                                    </div>
                                    <?php if (!empty($noti['contenido']) && $noti['contenido'] !== 'Sin contenido disponible'): ?>
                                        <div class="notification-preview">
                                            <i class="fas fa-quote-left quote-icon"></i>
                                            <span class="preview-text"><?= htmlspecialchars($noti['contenido']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="notification-meta">
                                        <span class="notification-time">
                                            <i class="fas fa-clock"></i>
                                            <?= $noti['fecha'] ?>
                                        </span>
                                        <?php if (!$noti['leido']): ?>
                                            <span class="notification-badge">Nuevo</span>
                                        <?php endif; ?>
                                    </div>
                                </a>
                                <div class="notification-actions">
                                    <button type="button"
                                        class="btn-delete-individual"
                                        data-notification-id="<?= $noti['id_notificacion'] ?>"
                                        title="Eliminar notificación">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <?php if (!$noti['leido']): ?>
                                        <div class="unread-indicator"></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modern Navbar -->
    <nav class="modern-navbar">
        <!-- Logo Section -->
        <a href="<?= $logo_href ?>" class="logo-section">
            <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Logo" class="logo-image">
            <h1 class="logo-text">TeamTalks</h1>
        </a>

        <?php if (!$usuario_logueado): ?>
            <!-- Navigation Links (Desktop) - Para usuarios no logueados -->
            <ul class="nav-links">
                <li><a href="index.php" class="header-nav-link">Inicio</a></li>
                <li><a href="about_we.php" class="header-nav-link">Sobre nosotros</a></li>
                <li><a href="contact_us.php" class="header-nav-link">Contáctanos</a></li>
            </ul>

            <!-- User Section - Para usuarios no logueados -->
            <div class="user-section">
                <!-- Login Button -->
                <a href="login/login.php" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i>
                    Iniciar Sesión
                </a>
                <!-- Mobile Menu Toggle -->
                <button class="mobile-toggle" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars mobile-toggle-icon"></i>
                </button>
            </div>
        <?php else: ?>
            <!-- User Section - Para usuarios logueados -->
            <div class="user-section-logged">
                <!-- Home Link -->
                <a href="<?= $carpeta_inicio ?>" class="header-home-link">
                    <i class="fas fa-home"></i>
                    <span class="header-home-text">Inicio</span>
                </a>
                <!-- Notifications -->
                <button class="notification-btn" data-bs-toggle="offcanvas" data-bs-target="#offcanvasWithBothOptions" aria-controls="offcanvasWithBothOptions">
                    <i class="fas fa-bell notification-icon" style="position: relative; top: 11px; font-size: 20px;"></i>
                    <?php if ($notificaciones_no_leidas > 0): ?>
                        <span class="notification-badge" id="notification-count">
                            <?= $notificaciones_no_leidas > 99 ? '99+' : $notificaciones_no_leidas ?>
                        </span>
                    <?php endif; ?>
                </button>
                <!-- User Profile Dropdown -->
                <div class="user-profile dropdown">
                    <a class="profile-trigger dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?= BASE_URL ?>/<?= empty($user['avatar']) ? 'uploads/avatar/user.webp' : htmlspecialchars($user['avatar']) ?>"
                            alt="Avatar" class="profile-avatar">
                        <div class="profile-info">
                            <p class="profile-name"><?= htmlspecialchars($username) ?></p>
                            <p class="profile-role"><?= htmlspecialchars($user['rol']) ?></p>
                        </div>
                        <i class="fas fa-chevron-down profile-chevron"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#modalEditarPerfil">
                                <i class="fas fa-user-edit"></i>
                                Editar perfil
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?= BASE_URL ?>/includes/exit.php">
                                <i class="fas fa-sign-out-alt"></i>
                                Cerrar sesión
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Mobile Menu Toggle - SOLO PARA USUARIOS NO LOGUEADOS -->
                <!-- Este botón ya no aparecerá para usuarios logueados gracias al CSS -->
                <button class="mobile-toggle" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars mobile-toggle-icon"></i>
                </button>
            </div>
        <?php endif; ?>
    </nav>

    <!-- Mobile Navigation Overlay - SOLO PARA USUARIOS NO LOGUEADOS -->
    <div class="mobile-nav-overlay" onclick="toggleMobileMenu()">
        <div class="mobile-nav-menu" onclick="event.stopPropagation()">
            <?php if (!$usuario_logueado): ?>
                <ul class="mobile-nav-links">
                    <li><a href="index.php" class="header-mobile-nav-link">Inicio</a></li>
                    <li><a href="about_we.php" class="header-mobile-nav-link">Sobre nosotros</a></li>
                    <li><a href="contact_us.php" class="header-mobile-nav-link">Contáctanos</a></li>
                </ul>
            <?php else: ?>
                <!-- Para usuarios logueados, el menú móvil estará oculto por CSS -->
                <ul class="mobile-nav-links">
                    <li><a href="<?= $carpeta_inicio ?>" class="header-mobile-nav-link">Inicio</a></li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php if ($usuario_logueado): ?>
    <script>
        (function() {
            const timeoutInSeconds = <?= $timeout ?? 500000000000 ?>; // Tiempo de inactividad en segundos
            const timeoutMillis = timeoutInSeconds * 1000; // Tiempo en milisegundos
            let timeoutId;
            function cerrarSesion() {
                fetch('<?= BASE_URL ?>/includes/exit.php')
                    .then(() => {
                        alert('Tu sesión ha expirado por inactividad.');
                        window.location.href = '<?= BASE_URL ?>/login/login.php';
                    })
                    .catch(() => {
                        // Si no logra salir, al menos redirige
                        window.location.href = '<?= BASE_URL ?>/login/login.php';
                    });
            }
            function resetTimer() {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(cerrarSesion, timeoutMillis);
            }
            // Escuchar cualquier actividad del usuario
            ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'].forEach(evt => {
                window.addEventListener(evt, resetTimer, true);
            });
            // Iniciar el temporizador
            resetTimer();
        })();
    </script>
<?php endif; ?>
<!-- Modal Editar Perfil -->
<div class="modal fade modal-editar-perfil" id="modalEditarPerfil" tabindex="-1" aria-labelledby="modalEditarPerfilLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <form action="<?= BASE_URL ?>/actions/editar_perfil.php" method="POST" enctype="multipart/form-data" class="modal-content" onsubmit="return validarFormularioEditarPerfil();">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarPerfilLabel">Editar Perfil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <!-- Avatar Section -->
                <div class="avatar-section">
                    <img id="imgPreview" src="<?= BASE_URL ?>/<?= $user['avatar'] ?? 'uploads/avatar/user.webp' ?>" alt="Avatar actual" class="avatar-preview">
                    <p class="avatar-label">Foto de perfil actual</p>
                </div>
                <!-- Email -->
                <div class="form-group">
                    <label for="email" class="form-label-modern">Correo electrónico</label>
                    <input type="email" class="form-control form-control-modern" name="email" id="email" placeholder="ejemplo@correo.com" value="<?= htmlspecialchars($user['correo'] ?? '') ?>" style="min-height: 44px;">
                </div>
                <!-- Teléfono -->
                <div class="form-group">
                    <label for="telefono" class="form-label-modern">Número de teléfono</label>
                    <input type="text" class="form-control form-control-modern" name="telefono" id="telefono" placeholder="+57 300 123 4567" value="<?= htmlspecialchars($user['telefono'] ?? '') ?>" style="min-height: 44px;">
                </div>
                <!-- Contraseña -->
                <div class="form-group">
                    <label for="password" class="form-label-modern">Nueva contraseña</label>
                    <div class="password-group">
                        <input type="password" class="form-control form-control-modern" name="password" id="password" placeholder="Mínimo 8 caracteres" style="min-height: 44px;">
                    </div>
                </div>
                <!-- Confirmar Contraseña -->
                <div class="form-group">
                    <label for="confirmar_password" class="form-label-modern">Confirmar nueva contraseña</label>
                    <div class="password-group">
                        <input type="password" class="form-control form-control-modern" name="confirmar_password" id="confirmar_password" placeholder="Repite la contraseña" style="min-height: 44px;">
                    </div>
                </div>
                <!-- Avatar Upload -->
                <div class="form-group">
                    <label for="avatar" class="form-label-modern">Cambiar foto de perfil</label>
                    <div class="file-input-wrapper">
                        <input type="file" class="file-input-modern" name="avatar" id="avatar" accept="image/*">
                        <label for="avatar" class="file-input-label">
                            <i class="bi bi-cloud-upload me-2"></i>
                            Seleccionar imagen (máx. 800x800 px)
                        </label>
                    </div>
                    <div id="vistaPreviaAvatar" class="text-center"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary-modern" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary-modern">
                    <i class="bi bi-check-circle me-2"></i>
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>
<!-- Modal Configuración -->
<div class="modal fade" id="modalConfiguracion" tabindex="-1" aria-labelledby="modalConfiguracionLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= BASE_URL ?>/actions/config_guardar.php" method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalConfiguracionLabel">Configuración</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <!-- Puedes agregar aquí más opciones configurables -->
                <div class="mb-3">
                    <label for="tema" class="form-label">Tema de interfaz</label>
                    <select name="tema" id="tema" class="form-select">
                        <option value="claro">Claro</option>
                        <option value="oscuro">Oscuro</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="notificaciones" class="form-label">Notificaciones</label>
                    <select name="notificaciones" id="notificaciones" class="form-select">
                        <option value="activadas">Activadas</option>
                        <option value="desactivadas">Desactivadas</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Guardar configuración</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Mobile menu toggle - MODIFICADO PARA USUARIOS LOGUEADOS
    function toggleMobileMenu() {
        // Solo funciona si NO hay usuario logueado
        const userSectionLogged = document.querySelector('.user-section-logged');
        if (!userSectionLogged) {
            document.body.classList.toggle('mobile-nav-active');
        }
    }
    // Close mobile menu when clicking on links
    document.querySelectorAll('.header-mobile-nav-link').forEach(link => {
        link.addEventListener('click', () => {
            document.body.classList.remove('mobile-nav-active');
        });
    });
    // Close mobile menu on window resize
    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) {
            document.body.classList.remove('mobile-nav-active');
        }
    });
    function mostrarError(elemento, mensaje) {
        eliminarError(elemento);
        const error = document.createElement("div");
        error.className = "text-danger small mt-1";
        error.innerText = mensaje;
        const contenedor = elemento.closest(".mb-3") || elemento.parentElement;
        contenedor.appendChild(error);
        elemento.classList.add("is-invalid");
    }
    function eliminarError(elemento) {
        elemento.classList.remove("is-invalid");
        const contenedor = elemento.closest(".mb-3") || elemento.parentElement;
        const errores = contenedor.querySelectorAll(".text-danger");
        errores.forEach(e => e.remove());
    }
    function validarFormularioEditarPerfil() {
        return validarTodo();
    }
    document.addEventListener('DOMContentLoaded', function() {
        const email = document.getElementById("email");
        const telefono = document.getElementById("telefono");
        const password = document.getElementById("password");
        const confirmar = document.getElementById("confirmar_password");
        const btnGuardar = document.querySelector('#modalEditarPerfil .btn.btn-primary-modern');
        function validarCorreo() {
            const correo = email.value.trim();
            eliminarError(email);
            if (correo !== "") {
                const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!regex.test(correo)) {
                    mostrarError(email, "Correo electrónico inválido.");
                    return false;
                }
            }
            return true;
        }

        // NUEVA FUNCIÓN: Validación de teléfono - solo números y caracteres permitidos

        function validarTelefono() {

            const valor = telefono.value.trim();

            eliminarError(telefono);

            if (valor !== "") {

                // Solo permitir números, espacios, guiones, paréntesis y signo +

                const regex = /^[\d\s\-()+ ]*$/;

                if (!regex.test(valor)) {

                    mostrarError(telefono, "Solo se permiten números y caracteres: + - ( ) espacios");

                    return false;

                }

                // Verificar que tenga al menos 7 dígitos si se ingresa algo

                const soloNumeros = valor.replace(/[\s\-()+ ]/g, '');

                if (soloNumeros.length > 0 && soloNumeros.length < 7) {

                    mostrarError(telefono, "El teléfono debe tener al menos 7 dígitos");

                    return false;

                }

                // Verificar que no sea solo caracteres especiales

                if (soloNumeros.length === 0 && valor.length > 0) {

                    mostrarError(telefono, "Debe contener al menos un número");

                    return false;

                }

            }

            return true;

        }

        function validarPassword() {

            const pass = password.value;

            eliminarError(password);

            if (pass !== "") {

                const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;

                if (!regex.test(pass)) {

                    mostrarError(password, "Mínimo 8 caracteres, una mayúscula, una minúscula y un número.");

                    return false;

                }

            }

            return true;

        }

        function validarConfirmacion() {

            eliminarError(confirmar);

            if (password.value !== "" && password.value !== confirmar.value) {

                mostrarError(confirmar, "Las contraseñas no coinciden.");

                return false;

            }

            return true;

        }

        // FUNCIÓN ACTUALIZADA: Incluye validación de teléfono

        window.validarTodo = function() {

            const esValido = validarCorreo() && validarTelefono() && validarPassword() && validarConfirmacion() && validarAvatarActual();

            btnGuardar.disabled = !esValido;

            return esValido;

        };

        // Event listeners para validación en tiempo real

        email.addEventListener("input", validarTodo);

        telefono.addEventListener("input", validarTodo);

        password.addEventListener("input", validarTodo);

        confirmar.addEventListener("input", validarTodo);

        // NUEVO: Filtro de entrada para teléfono - previene escritura de caracteres no válidos

        telefono.addEventListener("keypress", function(e) {

            // Permitir: números (0-9), espacio, guión, paréntesis, signo +, backspace, delete, tab, escape, enter

            const allowedKeys = [8, 9, 27, 13, 46]; // backspace, tab, escape, enter, delete

            const allowedChars = /[\d\s\-()+ ]/;

            // Si es una tecla especial, permitir

            if (allowedKeys.indexOf(e.keyCode) !== -1 ||

                // Permitir Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X

                (e.keyCode === 65 && e.ctrlKey === true) ||

                (e.keyCode === 67 && e.ctrlKey === true) ||

                (e.keyCode === 86 && e.ctrlKey === true) ||

                (e.keyCode === 88 && e.ctrlKey === true)) {

                return;

            }

            // Si el carácter no está permitido, prevenir la entrada

            if (!allowedChars.test(e.key)) {

                e.preventDefault();

            }

        });

        // NUEVO: Validación adicional en paste para teléfono

        telefono.addEventListener("paste", function(e) {

            // Permitir el paste pero validar después

            setTimeout(() => {

                const valor = telefono.value;

                const regex = /^[\d\s\-()+ ]*$/;

                if (!regex.test(valor)) {

                    // Limpiar caracteres no válidos

                    telefono.value = valor.replace(/[^\d\s\-()+ ]/g, '');

                    validarTodo();

                }

            }, 10);

        });

    });

    document.addEventListener("DOMContentLoaded", function() {

        const avatarInput = document.getElementById("avatar");

        const vistaPrevia = document.getElementById("vistaPreviaAvatar");

        const btnGuardar = document.querySelector('#modalEditarPerfil .btn.btn-primary-modern');

        window.validarAvatarActual = function() {

            const file = avatarInput.files[0];

            if (!file) {

                eliminarError(avatarInput);

                vistaPrevia.innerHTML = "";

                return true;

            }

            if (!file.type.startsWith('image/')) {

                mostrarError(avatarInput, "Solo se permiten archivos de imagen.");

                vistaPrevia.innerHTML = "";

                return false;

            }

            if (file.size > 1024 * 1024) {

                mostrarError(avatarInput, "El archivo no debe superar 1MB.");

                vistaPrevia.innerHTML = "";

                return false;

            }

            return true;

        };

        avatarInput.addEventListener("change", function() {

            const file = avatarInput.files[0];

            if (!file) return;

            const reader = new FileReader();

            reader.onload = function(e) {

                const img = new Image();

                img.onload = function() {

                    if (img.width > 800 || img.height > 800) {

                        mostrarError(avatarInput, "El avatar no debe superar 800x800 píxeles.");

                        vistaPrevia.innerHTML = "";

                        btnGuardar.disabled = true;

                    } else {

                        eliminarError(avatarInput);

                        vistaPrevia.innerHTML = `<img src="${e.target.result}" alt="Vista previa" style="max-width:100px; max-height:100px; border-radius:50%;">`;

                        validarTodo();

                    }

                };

                img.src = e.target.result;

            };

            reader.readAsDataURL(file);

        });

    });

    // Función para actualizar el contador del badge

    function actualizarBadgeNotificaciones() {

        const badgeElement = document.getElementById('notification-count');

        const unreadItems = document.querySelectorAll('.notification-panel .notification-item.unread');

        const count = unreadItems.length;

        if (badgeElement) {

            if (count === 0) {

                badgeElement.classList.add('hidden');

            } else {

                badgeElement.textContent = count > 99 ? '99+' : count;

                badgeElement.classList.remove('hidden');

            }

        }

    }

    // Función para verificar si deben mostrarse los botones de acción
    function verificarBotonesAccion() {
        const notificationActions = document.getElementById('notification-actions');
        const remainingNotifications = document.querySelectorAll('.notification-item');

        if (notificationActions) {
            if (remainingNotifications.length === 0) {
                notificationActions.style.display = 'none';
            } else {
                notificationActions.style.display = 'flex';
            }
        }
    }

    // JavaScript para las notificaciones

    document.addEventListener('DOMContentLoaded', function() {

        // Marcar notificación individual como leída

        document.querySelectorAll('.notification-panel .notification-item').forEach(item => {

            item.addEventListener('click', function(e) {

                const notificationId = this.dataset.notificationId;

                // Marcar visualmente como leída

                this.classList.remove('unread');

                this.classList.add('read');

                // Remover indicador de no leída

                const indicator = this.querySelector('.unread-indicator');

                if (indicator) {

                    indicator.style.animation = 'fadeOut 0.3s ease forwards';

                    setTimeout(() => indicator.remove(), 300);

                }

                // Remover badge "Nuevo"

                const badge = this.querySelector('.notification-badge');

                if (badge) {

                    badge.style.animation = 'fadeOut 0.3s ease forwards';

                    setTimeout(() => badge.remove(), 300);

                }

                // Actualizar badge del icono

                setTimeout(() => {

                    actualizarBadgeNotificaciones();

                }, 100);

            });

        });

        // AJAX para marcar todas las notificaciones como leídas

        const markAllBtn = document.getElementById('marcar-todas-leidas');

        if (markAllBtn) {

            markAllBtn.addEventListener('click', function(e) {

                e.preventDefault();

                const originalContent = this.innerHTML;

                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Marcando...';

                this.disabled = true;

                fetch('<?= BASE_URL ?>/ajax/marcar_todas_leidas.php', {

                        method: 'POST',

                        headers: {

                            'X-Requested-With': 'XMLHttpRequest',

                            'Content-Type': 'application/x-www-form-urlencoded'

                        }

                    })

                    .then(response => response.json())

                    .then(data => {

                        if (data.success) {

                            this.innerHTML = '<i class="fas fa-check-double"></i> ¡Marcadas!';

                            // Actualizar notificaciones en el panel

                            document.querySelectorAll('.notification-panel .notification-item.unread').forEach(item => {

                                item.classList.remove('unread');

                                item.classList.add('read');

                                const badge = item.querySelector('.notification-badge');

                                const indicator = item.querySelector('.unread-indicator');

                                if (badge) badge.remove();

                                if (indicator) indicator.remove();

                            });

                            // Actualizar badge del icono

                            actualizarBadgeNotificaciones();

                            setTimeout(() => {

                                this.innerHTML = originalContent;

                                this.disabled = false;

                                // NO ocultar los botones aquí, solo verificar si hay notificaciones
                                verificarBotonesAccion();

                            }, 2000);

                        } else {

                            this.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';

                            setTimeout(() => {

                                this.innerHTML = originalContent;

                                this.disabled = false;

                            }, 2000);

                        }

                    })

                    .catch(error => {

                        console.error('Error:', error);

                        this.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';

                        setTimeout(() => {

                            this.innerHTML = originalContent;

                            this.disabled = false;

                        }, 2000);

                        alert('Error de conexión. Inténtalo de nuevo.');

                    });

            });

        }

        // AJAX para eliminar TODAS las notificaciones
        const deleteAllBtn = document.getElementById('eliminar-todas');
        if (deleteAllBtn) {
            deleteAllBtn.addEventListener('click', function(e) {
                e.preventDefault();

                if (!confirm('¿Estás seguro de que quieres eliminar todas las notificaciones? Esta acción no se puede deshacer.')) {
                    return;
                }

                const originalContent = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Eliminando...';
                this.disabled = true;

                fetch('<?= BASE_URL ?>/ajax/eliminar_todas_notificaciones.php', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Content-Type': 'application/x-www-form-urlencoded'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.innerHTML = '<i class="fas fa-check"></i> ¡Eliminadas!';

                            // Eliminar todas las notificaciones del DOM
                            const notificationsList = document.querySelector('.notifications-list');
                            if (notificationsList) {
                                notificationsList.innerHTML = `
                                <div class="empty-state">
                                    <div class="empty-icon">
                                        <i class="fas fa-bell-slash"></i>
                                    </div>
                                    <h6 class="empty-title">Sin notificaciones</h6>
                                    <p class="empty-text">No tienes notificaciones recientes</p>
                                </div>
                            `;
                            }

                            // Actualizar badge del icono
                            actualizarBadgeNotificaciones();

                            // Ocultar botones de acción
                            verificarBotonesAccion();

                            setTimeout(() => {
                                this.innerHTML = originalContent;
                                this.disabled = false;
                            }, 2000);

                        } else {
                            this.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
                            setTimeout(() => {
                                this.innerHTML = originalContent;
                                this.disabled = false;
                            }, 2000);
                            alert(data.message || 'Error al eliminar las notificaciones');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        this.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
                        setTimeout(() => {
                            this.innerHTML = originalContent;
                            this.disabled = false;
                        }, 2000);
                        alert('Error de conexión. Inténtalo de nuevo.');
                    });
            });
        }

        // JavaScript para eliminar notificaciones individuales

        document.addEventListener('click', function(e) {

            if (e.target.closest('.btn-delete-individual')) {

                e.preventDefault();

                e.stopPropagation();

                const btn = e.target.closest('.btn-delete-individual');

                const notificationId = btn.dataset.notificationId;

                const notificationItem = btn.closest('.notification-item');

                if (!confirm('¿Eliminar esta notificación?')) {

                    return;

                }

                const originalContent = btn.innerHTML;

                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                btn.disabled = true;

                fetch('<?= BASE_URL ?>/ajax/eliminar_notificacion_individual.php', {

                        method: 'POST',

                        headers: {

                            'X-Requested-With': 'XMLHttpRequest',

                            'Content-Type': 'application/x-www-form-urlencoded'

                        },

                        body: `id_notificacion=${notificationId}`

                    })

                    .then(response => response.json())

                    .then(data => {

                        if (data.success) {

                            notificationItem.style.animation = 'slideOutNotification 0.3s ease forwards';

                            setTimeout(() => {

                                notificationItem.remove();

                                actualizarBadgeNotificaciones();

                                // Verificar si quedan notificaciones

                                const remainingNotifications = document.querySelectorAll('.notification-item');

                                if (remainingNotifications.length === 0) {

                                    const notificationsList = document.querySelector('.notifications-list');

                                    if (notificationsList) {

                                        notificationsList.innerHTML = `

                                    <div class="empty-state">

                                        <div class="empty-icon">

                                            <i class="fas fa-bell-slash"></i>

                                        </div>

                                        <h6 class="empty-title">Sin notificaciones</h6>

                                        <p class="empty-text">No tienes notificaciones recientes</p>

                                    </div>

                                `;

                                    }

                                }

                                // Verificar botones de acción
                                verificarBotonesAccion();

                            }, 300);

                        } else {

                            btn.innerHTML = originalContent;

                            btn.disabled = false;

                            alert(data.message || 'Error al eliminar la notificación');

                        }

                    })

                    .catch(error => {

                        console.error('Error:', error);

                        btn.innerHTML = originalContent;

                        btn.disabled = false;

                        alert('Error de conexión. Inténtalo de nuevo.');

                    });

            }

        });

    });
</script>
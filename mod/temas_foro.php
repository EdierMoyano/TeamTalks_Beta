<?php
if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['DOCUMENT_ROOT'], 'htdocs') !== false) {
  require_once $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php';
} else {
  require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';
}include 'session.php';

if ($_SESSION['rol'] !== 3 && $_SESSION['rol'] !== 5) {
    header('Location:' . BASE_URL . '/includes/exit.php?motivo=acceso-denegado');
    exit;
}

$id_instructor = $_SESSION['documento'];
$rol = $_SESSION['rol'] ?? '';
$redirecciones = [
    3 => '/instructor/foros.php',
    5 => '/transversal/foros.php'
];
$destino = BASE_URL . ($redirecciones[$rol] ?? '/index.php');
$id_foro = isset($_GET['id_foro']) ? (int) $_GET['id_foro'] : 0;
$id_user = $_SESSION['documento'];

// Verificar foro
$stmt = $conex->prepare("
    SELECT f.id_foro, f.fecha_foro, mf.id_ficha, m.materia, fo.nombre AS nombre_formacion
    FROM foros f
    JOIN materia_ficha mf ON f.id_materia_ficha = mf.id_materia_ficha
    JOIN materias m ON mf.id_materia = m.id_materia
    JOIN fichas fi ON mf.id_ficha = fi.id_ficha
    JOIN formacion fo ON fi.id_formacion = fo.id_formacion
    WHERE f.id_foro = ?
");
$stmt->execute([$id_foro]);
$foro = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$foro) {
    echo "<div class='tt-alert tt-alert--error'>ID de foro inválido.</div>";
    exit;
}

// Obtener temas
$stmt = $conex->prepare("
    SELECT tf.*, u.nombres, u.apellidos
    FROM temas_foro tf
    JOIN usuarios u ON tf.id_user = u.id
    WHERE tf.id_foro = ?
    ORDER BY tf.fecha_creacion DESC
");
$stmt->execute([$id_foro]);
$temas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Temas del Foro - TeamTalks</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/styles/style_side.css" />
    <link rel="stylesheet" href="<?= BASE_URL ?>/styles/header.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/styles/temas_foro.css">
    <link rel="icon" href="<?= BASE_URL ?>/assets/img/icon2.png" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" />

</head>

<body class="sidebar-collapsed tt-forum-container" style="padding-top: 180px;">
    <?php include 'design/header.php'; ?>
    <?php include 'design/sidebar.php'; ?>

    <main class="tt-main">
        <!-- Header Section -->
        <div class="tt-header">
            <div class="tt-header-content">
                <div class="tt-header-info">
                    <a href="<?= $destino ?>" class="tt-back-btn">
                        <i class="bi bi-arrow-left"></i>
                        Volver
                    </a>

                    <h1 class="tt-title">
                        <div class="tt-title-icon">
                            <i class="bi bi-chat-dots-fill"></i>
                        </div>
                        Temas del foro
                    </h1>

                    <p class="tt-subtitle">
                        Explora y participa en las discusiones de este foro educativo
                    </p>

                    <div class="tt-meta-grid">
                        <div class="tt-meta-item">
                            <div class="tt-meta-icon">
                                <i class="bi bi-book"></i>
                            </div>
                            <div class="tt-meta-content">
                                <div class="tt-meta-label">Materia</div>
                                <div class="tt-meta-value"><?= htmlspecialchars($foro['materia']) ?></div>
                            </div>
                        </div>
                        <div class="tt-meta-item">
                            <div class="tt-meta-icon">
                                <i class="bi bi-folder"></i>
                            </div>
                            <div class="tt-meta-content">
                                <div class="tt-meta-label">Ficha</div>
                                <div class="tt-meta-value"><?= $foro['id_ficha'] ?></div>
                            </div>
                        </div>
                        <div class="tt-meta-item">
                            <div class="tt-meta-icon">
                                <i class="bi bi-mortarboard"></i>
                            </div>
                            <div class="tt-meta-content">
                                <div class="tt-meta-label">Formación</div>
                                <div class="tt-meta-value"><?= htmlspecialchars($foro['nombre_formacion']) ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <button class="tt-btn tt-btn--primary" data-bs-toggle="modal" data-bs-target="#modalCrearTema">
                    <i class="bi bi-plus-circle"></i>
                    Nuevo tema
                </button>
            </div>
        </div>

        <!-- Topics Section -->
        <?php if (count($temas) > 0): ?>
            <!-- View Controls (only show if more than 6 topics) -->
            <?php if (count($temas) > 6): ?>
                <div class="tt-view-controls">
                    <div class="tt-view-toggle">
                        <button class="tt-view-btn active" data-view="list">
                            <i class="bi bi-list-ul"></i>
                            Lista
                        </button>
                        <button class="tt-view-btn" data-view="grid">
                            <i class="bi bi-grid-3x3-gap"></i>
                            Cuadrícula
                        </button>
                    </div>
                    <div class="tt-topics-count">
                        <?= count($temas) ?> tema<?= count($temas) !== 1 ? 's' : '' ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="tt-topics <?= count($temas) > 6 ? 'tt-topics--list' : '' ?>" id="topicsContainer">
                <?php foreach ($temas as $tema): ?>
                    <a href="ver_respuestas.php?id_tema=<?= $tema['id_tema_foro'] ?>" class="tt-topic">
                        <div class="tt-topic-header">
                            <div class="tt-topic-icon">
                                <i class="bi bi-chat-text"></i>
                            </div>
                            <div class="tt-topic-content">
                                <h3 class="tt-topic-title"><?= htmlspecialchars($tema['titulo']) ?></h3>
                                <p class="tt-topic-description">
                                    <?= nl2br(htmlspecialchars($tema['descripcion'])) ?>
                                </p>
                            </div>
                        </div>
                        <div class="tt-topic-footer">
                            <div class="tt-topic-author">
                                <i class="bi bi-person-circle"></i>
                                <?= htmlspecialchars($tema['nombres'] . ' ' . $tema['apellidos']) ?>
                            </div>
                            <div class="tt-topic-date">
                                <?= date("d/m/Y H:i", strtotime($tema['fecha_creacion'])) ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="tt-empty">
                <div class="tt-empty-icon">
                    <i class="bi bi-chat-dots"></i>
                </div>
                <h2 class="tt-empty-title">No hay temas aún</h2>
                <p class="tt-empty-description">
                    Este foro está esperando su primer tema. ¡Sé el primero en iniciar una conversación!
                </p>
                <button class="tt-btn tt-btn--primary" data-bs-toggle="modal" data-bs-target="#modalCrearTema">
                    <i class="bi bi-plus-circle"></i>
                    Crear primer tema
                </button>
                <img src="<?= BASE_URL ?>/assets/img/n-foro.webp" alt="Sin temas" class="tt-empty-image">
            </div>
        <?php endif; ?>
    </main>

    <!-- Modal Crear Tema -->
    <div class="modal fade tt-modal" id="modalCrearTema" tabindex="-1" aria-labelledby="crearTemaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form action="crear_tema_foro.php?id_ficha=<?= $foro['id_ficha'] ?>" method="POST" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="crearTemaLabel">
                        <i class="bi bi-plus-circle me-2"></i>
                        Crear nuevo tema
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="id_foro" value="<?= $foro['id_foro'] ?>">

                    <div class="tt-form-group">
                        <label for="titulo" class="tt-form-label">
                            <i class="bi bi-type"></i>
                            Título del tema
                        </label>
                        <input
                            type="text"
                            class="tt-form-control"
                            name="titulo"
                            id="titulo"
                            placeholder="Ej. Dudas sobre proyecto final"
                            required>
                    </div>

                    <div class="tt-form-group">
                        <label for="descripcion" class="tt-form-label">
                            <i class="bi bi-text-paragraph"></i>
                            Descripción
                        </label>
                        <textarea
                            class="tt-form-control"
                            name="descripcion"
                            id="descripcion"
                            rows="6"
                            placeholder="Describe tu tema de manera clara y detallada..."
                            required
                            style="resize: vertical; min-height: 120px;"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="tt-btn tt-btn--secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="tt-btn tt-btn--primary">
                        <i class="bi bi-check-circle"></i>
                        Crear tema
                    </button>
                </div>
            </form>
        </div>
        </main>

        <script>
            // Enhanced interactions and animations
            document.addEventListener('DOMContentLoaded', function() {
                // Smooth scroll behavior
                document.documentElement.style.scrollBehavior = 'smooth';

                // View toggle functionality
                const viewButtons = document.querySelectorAll('.tt-view-btn');
                const topicsContainer = document.getElementById('topicsContainer');

                viewButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const view = this.dataset.view;

                        // Update active button
                        viewButtons.forEach(btn => btn.classList.remove('active'));
                        this.classList.add('active');

                        // Update container class
                        topicsContainer.className = view === 'grid' ? 'tt-topics tt-topics--grid' : 'tt-topics';

                        // Store preference
                        localStorage.setItem('tt-topics-view', view);
                    });
                });

                // Restore saved view preference
                const savedView = localStorage.getItem('tt-topics-view');
                if (savedView && topicsContainer) {
                    const targetButton = document.querySelector(`[data-view="${savedView}"]`);
                    if (targetButton) {
                        targetButton.click();
                    }
                }

                // Add loading state to form submission
                const form = document.querySelector('#modalCrearTema form');
                if (form) {
                    form.addEventListener('submit', function() {
                        const submitBtn = form.querySelector('button[type="submit"]');
                        const originalText = submitBtn.innerHTML;
                        submitBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Creando...';
                        submitBtn.disabled = true;

                        setTimeout(() => {
                            submitBtn.innerHTML = originalText;
                            submitBtn.disabled = false;
                        }, 3000);
                    });
                }

                // Enhanced topic hover effects (only for non-touch devices)
                if (window.matchMedia('(hover: hover)').matches) {
                    const topics = document.querySelectorAll('.tt-topic');
                    topics.forEach(topic => {
                        topic.addEventListener('mouseenter', function() {
                            this.style.transform = 'translateY(-6px) scale(1.01)';
                        });

                        topic.addEventListener('mouseleave', function() {
                            this.style.transform = 'translateY(0) scale(1)';
                        });
                    });
                }

                // Add ripple effect to buttons
                const buttons = document.querySelectorAll('.tt-btn');
                buttons.forEach(button => {
                    button.addEventListener('click', function(e) {
                        const ripple = document.createElement('span');
                        const rect = this.getBoundingClientRect();
                        const size = Math.max(rect.width, rect.height);
                        const x = e.clientX - rect.left - size / 2;
                        const y = e.clientY - rect.top - size / 2;

                        ripple.style.cssText = `
                position: absolute;
                width: ${size}px;
                height: ${size}px;
                left: ${x}px;
                top: ${y}px;
                background: rgba(255, 255, 255, 0.3);
                border-radius: 50%;
                transform: scale(0);
                animation: ripple 0.6s linear;
                pointer-events: none;
            `;

                        this.appendChild(ripple);

                        setTimeout(() => {
                            ripple.remove();
                        }, 600);
                    });
                });

                // Add CSS for ripple animation
                const style = document.createElement('style');
                style.textContent = `
        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    `;
                document.head.appendChild(style);

                // Intersection Observer for lazy loading animations
                const observerOptions = {
                    threshold: 0.1,
                    rootMargin: '0px 0px -50px 0px'
                };

                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.style.animationPlayState = 'running';
                            observer.unobserve(entry.target);
                        }
                    });
                }, observerOptions);

                // Observe all topics for animation
                document.querySelectorAll('.tt-topic').forEach(topic => {
                    topic.style.animationPlayState = 'paused';
                    observer.observe(topic);
                });
            });
        </script>
</body>

</html>
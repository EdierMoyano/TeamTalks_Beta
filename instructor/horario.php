<?php
if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['DOCUMENT_ROOT'], 'htdocs') !== false) {
  require_once $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php';
} else {
  require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';
}include 'session.php';
if ($_SESSION['rol'] !== 3) {
    header('Location:' . BASE_URL . '/includes/exit.php?motivo=acceso-denegado');
    exit;
}

$id_instructor = $_SESSION['documento'];

$sql = "
    SELECT 
        h.id_horario,
        h.nombre_horario,
        h.descripcion,
        h.dia_semana,
        h.hora_inicio,
        h.hora_fin,
        h.id_jornada,
        h.id_ficha,
        h.id_trimestre,
        m.materia,
        f.id_ficha,
        mf.id_materia_ficha
    FROM horario h
    INNER JOIN materia_ficha mf ON h.id_materia_ficha = mf.id_materia_ficha
    INNER JOIN materias m ON mf.id_materia = m.id_materia
    INNER JOIN fichas f ON h.id_ficha = f.id_ficha
    WHERE mf.id_instructor = :id_instructor
    ORDER BY m.materia, FIELD(h.dia_semana, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'), h.hora_inicio
";

$stmt = $conex->prepare($sql);
$stmt->execute(['id_instructor' => $id_instructor]);
$horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar horarios por materia
$horarios_agrupados = [];
foreach ($horarios as $horario) {
    $key = $horario['materia'] . '_' . $horario['id_ficha'];
    if (!isset($horarios_agrupados[$key])) {
        $horarios_agrupados[$key] = [
            'materia' => $horario['materia'],
            'ficha' => $horario['id_ficha'],
            'trimestre' => $horario['id_trimestre'],
            'descripcion' => $horario['descripcion'],
            'horarios' => []
        ];
    }
    $horarios_agrupados[$key]['horarios'][] = $horario;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Horarios</title>
    <link rel="icon" href="<?= BASE_URL ?>/assets/img/icon2.png" />
    <link rel="stylesheet" href="<?= BASE_URL ?>/styles/header.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/styles/horario.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" />


</head>

<body style="padding-top:180px;" class="sidebar-collapsed">
    <?php include 'design/header.php'; ?>
    <?php include 'design/sidebar.php'; ?>

    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header animate-in">
            <h1><i class="fas fa-calendar-alt me-3"></i>Mis Horarios</h1>
            <p>Gestiona y visualiza tus horarios de clases asignados</p>
        </div>

        <?php if (empty($horarios_agrupados)): ?>
            <!-- Empty State -->
            <div class="empty-state animate-in">
                <div class="empty-icon">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <h3 class="empty-title">No tienes horarios asignados</h3>
                <p class="empty-description">
                    Actualmente no tienes ningún horario de clases asignado.
                    Contacta con el coordinador académico para más información.
                </p>
            </div>
        <?php else: ?>
            <!-- Schedules Container -->
            <div class="schedules-container">
                <?php $index = 0; foreach ($horarios_agrupados as $grupo): ?>
                    <div class="subject-card" data-aos="fade-up">
                        <!-- Card Header -->
                        <div class="card-header-custom" onclick="toggleSchedule(<?= $index ?>)">
                            <h3 class="subject-title">
                                <div class="subject-icon">
                                    <i class="fas fa-book"></i>
                                </div>
                                <?= htmlspecialchars($grupo['materia']) ?>
                            </h3>
                            <div class="subject-meta">
                                <div class="meta-badges">
                                    <span class="meta-badge">
                                        <i class="fas fa-users"></i>
                                        Ficha <?= htmlspecialchars($grupo['ficha']) ?>
                                    </span>
                                    <span class="meta-badge">
                                        <i class="fas fa-calendar"></i>
                                        Trimestre <?= htmlspecialchars($grupo['trimestre']) ?>
                                    </span>
                                </div>
                                <button class="toggle-button" id="toggle-<?= $index ?>">
                                    <span class="schedule-count"><?= count($grupo['horarios']) ?></span>
                                    <span>Ver horarios</span>
                                    <i class="fas fa-chevron-down toggle-icon"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Schedule List -->
                        <ul class="schedule-list" id="schedule-<?= $index ?>">
                            <?php foreach ($grupo['horarios'] as $horario): ?>
                                <li class="schedule-item day-<?= strtolower($horario['dia_semana']) ?>">
                                    <div class="schedule-day">
                                        <span class="day-indicator"></span>
                                        <?= htmlspecialchars($horario['dia_semana']) ?>
                                    </div>
                                    <div class="schedule-time">
                                        <i class="fas fa-clock"></i>
                                        <span class="time-badge"><?= htmlspecialchars($horario['hora_inicio']) ?></span>
                                        <span>-</span>
                                        <span class="time-badge"><?= htmlspecialchars($horario['hora_fin']) ?></span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <!-- Description -->
                        <?php if (!empty($grupo['descripcion'])): ?>
                            <div class="card-footer-custom">
                                <i class="fas fa-info-circle me-1"></i>
                                <?= htmlspecialchars($grupo['descripcion']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php $index++; endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Función para toggle de horarios
        function toggleSchedule(index) {
            const scheduleList = document.getElementById(`schedule-${index}`);
            const toggleButton = document.getElementById(`toggle-${index}`);
            const toggleIcon = toggleButton.querySelector('.toggle-icon');
            const toggleText = toggleButton.querySelector('span:nth-child(2)');

            if (scheduleList.classList.contains('expanded')) {
                // Colapsar
                scheduleList.classList.remove('expanded');
                toggleButton.classList.remove('expanded');
                toggleText.textContent = 'Ver horarios';
                
                // Animar items hacia arriba
                const items = scheduleList.querySelectorAll('.schedule-item');
                items.forEach((item, i) => {
                    setTimeout(() => {
                        item.style.opacity = '0';
                        item.style.transform = 'translateY(-10px)';
                    }, i * 50);
                });
            } else {
                // Expandir
                scheduleList.classList.add('expanded');
                toggleButton.classList.add('expanded');
                toggleText.textContent = 'Ocultar horarios';
                
                // Animar items hacia abajo
                setTimeout(() => {
                    const items = scheduleList.querySelectorAll('.schedule-item');
                    items.forEach((item, i) => {
                        setTimeout(() => {
                            item.style.opacity = '1';
                            item.style.transform = 'translateY(0)';
                        }, i * 100);
                    });
                }, 100);
            }
        }

        // Animación de entrada para las tarjetas
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.subject-card');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry, index) => {
                    if (entry.isIntersecting) {
                        setTimeout(() => {
                            entry.target.classList.add('visible');
                        }, index * 100);
                    }
                });
            }, {
                threshold: 0.1
            });

            cards.forEach(card => {
                observer.observe(card);
            });

            // Agregar efecto de hover mejorado
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-4px)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(-2px)';
                });
            });
        });

        // Función para expandir/colapsar todos
        function toggleAllSchedules(expand = true) {
            const schedules = document.querySelectorAll('.schedule-list');
            schedules.forEach((schedule, index) => {
                if (expand && !schedule.classList.contains('expanded')) {
                    toggleSchedule(index);
                } else if (!expand && schedule.classList.contains('expanded')) {
                    toggleSchedule(index);
                }
            });
        }

        // Atajos de teclado
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch (e.key) {
                    case 'e':
                        e.preventDefault();
                        toggleAllSchedules(true);
                        break;
                    case 'c':
                        e.preventDefault();
                        toggleAllSchedules(false);
                        break;
                }
            }
        });

        // Manejar cambios de orientación
        window.addEventListener('orientationchange', () => {
            setTimeout(() => {
                window.scrollTo(0, 0);
            }, 100);
        });
    </script>
</body>
</html>

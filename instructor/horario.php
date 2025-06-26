<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php';
include 'session.php';
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
    <link rel="stylesheet" href="<?= BASE_URL ?>/styles/style_side.css" />
    <link rel="icon" href="<?= BASE_URL ?>/assets/img/icon2.png" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
        crossorigin="anonymous"
        referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" />

    <style>
        :root {
            --primary-color: #0E4A86;
            --primary-hover: #0d4077;
            --primary-light: #e8f1ff;
            --secondary-color: #6c757d;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --background-color: #f8fafc;
            --surface-color: #ffffff;
            --border-color: #e2e8f0;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: var(--background-color);
            color: var(--text-primary);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .main-content {
            margin-left: 280px;
            transition: margin-left 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 2rem;
            min-height: 100vh;
        }

        body.sidebar-collapsed .main-content {
            margin-left: 160px;
        }

        /* Header */
        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            color: white;
            padding: 2rem;
            border-radius: var(--radius-xl);
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(50%, -50%);
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
            position: relative;
            z-index: 1;
        }

        .page-header p {
            font-size: 1.125rem;
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        /* Cards Container */
        .schedules-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        /* Subject Card */
        .subject-card {
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            position: relative;
        }

        .subject-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-color);
        }

        .subject-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--success-color));
        }

        /* Card Header */
        .card-header-custom {
            padding: 1.5rem;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: all 0.2s ease;
            user-select: none;
        }

        .card-header-custom:hover {
            background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
        }

        .subject-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0 0 0.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .subject-icon {
            width: 2.5rem;
            height: 2.5rem;
            background: var(--primary-color);
            color: white;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.125rem;
        }

        .subject-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .meta-badges {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .meta-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            background: var(--primary-light);
            color: var(--primary-color);
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Toggle Button */
        .toggle-button {
            background: none;
            border: none;
            color: var(--primary-color);
            font-size: 0.875rem;
            font-weight: 500;
            padding: 0.375rem 0.75rem;
            border-radius: var(--radius-sm);
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        .toggle-button:hover {
            background: var(--primary-light);
            color: var(--primary-hover);
        }

        .toggle-icon {
            transition: transform 0.3s ease;
            font-size: 0.75rem;
        }

        .toggle-button.expanded .toggle-icon {
            transform: rotate(180deg);
        }

        .schedule-count {
            background: var(--primary-color);
            color: white;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.125rem 0.5rem;
            border-radius: 50px;
            min-width: 1.5rem;
            text-align: center;
        }

        /* Schedule List */
        .schedule-list {
            padding: 0;
            margin: 0;
            list-style: none;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .schedule-list.expanded {
            max-height: 500px;
        }

        .schedule-item {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            transition: all 0.2s ease;
            position: relative;
            opacity: 0;
            transform: translateY(-10px);
            animation: fadeInSchedule 0.3s ease forwards;
        }

        .schedule-list.expanded .schedule-item {
            opacity: 1;
            transform: translateY(0);
        }

        .schedule-item:nth-child(1) {
            animation-delay: 0.1s;
        }

        .schedule-item:nth-child(2) {
            animation-delay: 0.15s;
        }

        .schedule-item:nth-child(3) {
            animation-delay: 0.2s;
        }

        .schedule-item:nth-child(4) {
            animation-delay: 0.25s;
        }

        .schedule-item:nth-child(5) {
            animation-delay: 0.3s;
        }

        .schedule-item:last-child {
            border-bottom: none;
        }

        .schedule-item:hover {
            background: var(--primary-light);
        }

        .schedule-day {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .day-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--primary-color);
        }

        .schedule-time {
            color: var(--text-secondary);
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }

        .time-badge {
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            padding: 0.125rem 0.5rem;
            border-radius: var(--radius-sm);
            font-weight: 500;
            font-size: 0.75rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--surface-color);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
        }

        .empty-icon {
            width: 4rem;
            height: 4rem;
            background: var(--primary-light);
            color: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }

        .empty-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .empty-description {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInSchedule {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-in {
            animation: fadeInUp 0.6s ease-out;
        }

        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--background-color);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--text-muted);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .page-header {
                padding: 1.5rem;
                text-align: center;
            }

            .page-header h1 {
                font-size: 2rem;
            }

            .schedules-container {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .subject-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }

            .meta-badges {
                flex-wrap: wrap;
            }

            .schedule-item {
                padding: 0.75rem 1rem;
            }
        }

        /* Day Colors */
        .day-lunes .day-indicator {
            background: #ef4444;
        }

        .day-martes .day-indicator {
            background: #f97316;
        }

        .day-miercoles .day-indicator {
            background: #eab308;
        }

        .day-jueves .day-indicator {
            background: #22c55e;
        }

        .day-viernes .day-indicator {
            background: #3b82f6;
        }

        .day-sabado .day-indicator {
            background: #8b5cf6;
        }

        .day-domingo .day-indicator {
            background: #ec4899;
        }

        /* Scroll Animation */
        .subject-card {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.6s ease;
        }

        .subject-card.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Card Footer */
        .card-footer-custom {
            padding: 1rem 1.5rem;
            background: #f8f9fa;
            border-top: 1px solid var(--border-color);
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
    </style>
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
                <?php $index = 0;
                foreach ($horarios_agrupados as $grupo): ?>
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
                <?php $index++;
                endforeach; ?>
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
    </script>
</body>

</html>
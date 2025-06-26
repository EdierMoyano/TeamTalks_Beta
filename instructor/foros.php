<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php';
include 'session.php';
$id_instructor = $_SESSION['documento'];


$sql = "
    SELECT 
        f.id_foro,
        f.fecha_foro,
        mf.id_ficha,
        fi.id_ficha,
        fo.nombre AS nombre_formacion,
        m.materia,
        COUNT(tf.id_tema_foro) AS cantidad_temas
    FROM foros f
    INNER JOIN materia_ficha mf ON f.id_materia_ficha = mf.id_materia_ficha
    INNER JOIN fichas fi ON mf.id_ficha = fi.id_ficha
    INNER JOIN formacion fo ON fi.id_formacion = fo.id_formacion
    INNER JOIN materias m ON mf.id_materia = m.id_materia
    LEFT JOIN temas_foro tf ON tf.id_foro = f.id_foro
    WHERE mf.id_instructor = ?
    GROUP BY f.id_foro
    ORDER BY f.fecha_foro DESC
";

$stmt = $conex->prepare($sql);
$stmt->execute([$id_instructor]);
$foros = $stmt->fetchAll(PDO::FETCH_ASSOC);


?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foros</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/styles/style_side.css" />
    <link rel="icon" href="<?= BASE_URL ?>/assets/img/icon2.png" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
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
        .main-content {
            margin-left: 280px;
            transition: margin-left 0.4s ease;
        }

        body.sidebar-collapsed .main-content {
            margin-left: 200px;
        }

        .card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
        }

        .foro {
            border-width: 2px;
            color: white;
            background-color: #0E4A86;

        }

        .foro:hover {
            color: white;
            background-color:rgb(12, 51, 90);
        }

    </style>
</head>

<body style="padding-top:180px;" class="sidebar-collapsed">
    <?php include 'design/header.php'; ?>
    <?php include 'design/sidebar.php'; ?>
    <main class="main-content px-4">
        <div class="container">
            <h3 class="mb-4 fw-bold text-dark">
                <i class="bi bi-chat-dots me-2" style="color: #0E4A86;"></i>Foros asignados a tus fichas
            </h3>

            <?php if (count($foros) > 0): ?>
                <div class="row g-4">
                    <?php foreach ($foros as $foro): ?>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card border-0 shadow-lg rounded-4 h-100">
                                <div class="card-body d-flex flex-column justify-content-between">
                                    <div>
                                        <h5 class="card-title fw-semibold mb-2" style="color: #0E4A86;">
                                            <i class="bi bi-bookmark-star-fill me-2" style="color: #0E4A86;"></i><?= htmlspecialchars($foro['materia']) ?>
                                        </h5>
                                        <p class="mb-1 text-secondary small">
                                            <i class="bi bi-journal-code me-1"></i><strong>Ficha:</strong> <?= $foro['id_ficha'] ?>
                                        </p>
                                        <p class="mb-1 text-secondary small">
                                            <i class="bi bi-mortarboard-fill me-1"></i><strong>Formación:</strong> <?= htmlspecialchars($foro['nombre_formacion']) ?>
                                        </p>
                                        <p class="mb-1 text-secondary small">
                                            <i class="bi bi-calendar-event me-1"></i><strong>Fecha del foro:</strong> <?= date("d/m/Y", strtotime($foro['fecha_foro'])) ?>
                                        </p>
                                        <p class="text-muted small mb-3">
                                            <i class="bi bi-chat-left-text me-1"></i><?= $foro['cantidad_temas'] ?> tema<?= $foro['cantidad_temas'] == 1 ? '' : 's' ?>
                                        </p>
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        <a href="../mod/temas_foro.php?id_foro=<?= $foro['id_foro'] ?>" class="foro btn btn rounded-pill btn-sm shadow-sm">
                                            <i class="bi bi-eye-fill me-1"></i>Ver foro
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center mt-5">
                    <i class="bi bi-info-circle-fill me-2"></i>No tienes foros asignados actualmente.
                </div>
            <?php endif; ?>
        </div>
    </main>



</body>

</html>
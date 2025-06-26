<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php';
include 'session.php';

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
    echo "<div class='alert alert-danger text-center mt-4'>ID de foro inválido.</div>";
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

        h3.section-title {
            color: #0E4A86;
        }

        .card-custom {
            border-left: 5px solid #0E4A86;
            transition: all 0.3s ease-in-out;
        }

        .card-custom:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }

        .btn-main,
        .crear {
            background-color: #0E4A86;
            border-color: #0E4A86;
            color: #fff;
        }

        .btn-main:hover,
        .crear:hover {
            background-color: #0c3d71;
            color: white;
            border-color: #0c3d71;
        }

        .form-label {
            color: #0E4A86;
            font-weight: 500;
        }

        .cancelar {
            color: #0E4A86;
            background-color: white;
            border-color: #0E4A86;
        }

        .cancelar:hover {
            color: #0E4A86;
            background-color: rgb(224, 224, 224);
            border-color: #0E4A86;
        }

        .tema-card-link {
            text-decoration: none;
            display: block;
            transition: all 0.2s ease-in-out;
        }

        .tema-card {
            background-color: #fff;
            border-left: 5px solid #0E4A86;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }

        .tema-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
            background-color: #f9fbfd;
        }

        .icon-container {
            width: 48px;
            height: 48px;
            background-color: #0E4A86;
        }

        .but {
            background-color: #0E4A86;
            border-color: rgb(14, 74, 134);
            color: white;
            cursor: default;
        }

        .but:hover {
            background-color: rgb(9, 50, 91);
            border-color: rgb(23, 101, 180);
            color: white;
            cursor: default;
        }
    </style>


</head>

<body class="sidebar-collapsed" style="padding-top: 180px;">
    <?php include 'design/header.php'; ?>
    <?php include 'design/sidebar.php'; ?>

    <!-- Sustituye el contenido del <main> y el modal con este nuevo diseño -->

    <main class="main-content px-4">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="section-title mb-1">
                        <a href="../instructor/foros.php"><button class="but btn" type="button" style="margin-right: 10px; cursor: pointer;">
                                <i class="bi bi-arrow-90deg-left"></i>
                            </button>
                        </a>
                        <i class="bi bi-chat-dots-fill me-2"></i>Temas del foro

                    </h3>

                    <p class="text-muted mb-0">
                        <strong>Materia:</strong> <?= htmlspecialchars($foro['materia']) ?> |
                        <strong>Ficha:</strong> <?= $foro['id_ficha'] ?> |
                        <strong>Formación:</strong> <?= $foro['nombre_formacion'] ?>
                    </p>
                </div>
                <button class="btn btn-main btn-sm rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCrearTema">
                    <i class="bi bi-plus-circle me-1"></i>Nuevo tema
                </button>
            </div>

            <?php if (count($temas) > 0): ?>
                <div class="row g-3">
                    <?php foreach ($temas as $tema): ?>
                        <div class="col-12">
                            <a href="ver_respuestas.php?id_tema=<?= $tema['id_tema_foro'] ?>" class="tema-card-link">
                                <div class="tema-card d-flex flex-column flex-md-row align-items-md-start gap-3 p-3 shadow-sm rounded-4">
                                    <div class="icon-container d-flex align-items-center justify-content-center rounded-circle flex-shrink-0">
                                        <i class="bi bi-chat-text fs-4 text-white"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start flex-wrap">
                                            <h5 class="mb-1 text-dark fw-semibold"><?= htmlspecialchars($tema['titulo']) ?></h5>
                                            <small class="text-muted"><?= date("d/m/Y H:i", strtotime($tema['fecha_creacion'])) ?></small>
                                        </div>
                                        <p class="text-secondary small mb-1">
                                            <?= nl2br(htmlspecialchars(mb_strimwidth($tema['descripcion'], 0, 140, "..."))) ?>
                                        </p>
                                        <div class="text-end">
                                            <small class="text-muted fst-italic">
                                                <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($tema['nombres'] . ' ' . $tema['apellidos']) ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>



            <?php else: ?>
                <div class="alert alert-info text-center mt-4 shadow-sm">
                    <i class="bi bi-info-circle me-1"></i>Este foro aún no tiene temas creados.
                </div>
            <?php endif; ?>
        </div>
    </main>

    <div class="modal fade" id="modalCrearTema" tabindex="-1" aria-labelledby="crearTemaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form action="crear_tema_foro.php?id_ficha=<?= $foro['id_ficha'] ?>" method="POST" class="modal-content border-0 shadow-lg rounded-4">

                <!-- Encabezado del modal -->
                <div class="modal-header px-4 py-3" style="background-color: #0E4A86; border-top-left-radius: 1rem; border-top-right-radius: 1rem;">
                    <h5 class="modal-title text-white fw-semibold" id="crearTemaLabel">
                        <i class="bi bi-plus-circle me-2"></i>Crear nuevo tema
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <!-- Cuerpo del modal -->
                <div class="modal-body px-4 py-4 bg-white">
                    <input type="hidden" name="id_foro" value="<?= $foro['id_foro'] ?>">

                    <div class="mb-4">
                        <label for="titulo" class="form-label fw-semibold" style="color: #0E4A86;">Título del tema</label>
                        <input
                            type="text"
                            class="form-control border rounded-3 px-3 py-2 shadow-sm"
                            name="titulo"
                            id="titulo"
                            placeholder="Ej. Dudas sobre proyecto final"
                            required>
                    </div>

                    <div class="mb-3">
                        <label for="descripcion" class="form-label fw-semibold" style="color: #0E4A86;">Descripción</label>
                        <textarea
                            class="form-control border rounded-3 px-3 py-2 shadow-sm"
                            name="descripcion"
                            id="descripcion"
                            rows="5"
                            placeholder="Escribe una descripción clara del tema..."
                            required
                            style="max-height: 180px;"></textarea>
                    </div>
                </div>

                <!-- Pie del modal -->
                <div class="modal-footer px-4 py-3 bg-light rounded-bottom-4">
                    <button type="button" class="btn cancelar rounded-pill px-4" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn crear px-4 rounded-pill">
                        Crear
                    </button>
                </div>

            </form>
        </div>
    </div>





</body>

</html>
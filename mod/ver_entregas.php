<?php
$esLocal = strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['DOCUMENT_ROOT'], 'htdocs') !== false;

// Ruta dinámica hacia init.php
$rutaInit = $esLocal
    ? $_SERVER['DOCUMENT_ROOT'] . '/teamtalks/conexion/init.php'
    : $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';

require_once $rutaInit;include 'session.php';

$id_instructor = $_SESSION['documento'];

$id_actividad = $_GET['id_actividad'] ?? null;

$aprendices = [];
if ($id_actividad) {
    $stmt = $conex->prepare("
        SELECT DISTINCT us.id, us.nombres, us.apellidos, us.avatar,
            f.id_ficha,
            fo.nombre AS nombre_formacion,
            m.materia AS nombre_materia
        FROM actividades a
        INNER JOIN materia_ficha mf ON a.id_materia_ficha = mf.id_materia_ficha
        INNER JOIN materias m ON mf.id_materia = m.id_materia
        INNER JOIN fichas f ON mf.id_ficha = f.id_ficha
        INNER JOIN formacion fo ON f.id_formacion = fo.id_formacion
        INNER JOIN user_ficha uf ON uf.id_ficha = f.id_ficha
        INNER JOIN usuarios us ON us.id = uf.id_user
        INNER JOIN actividades_user au ON au.id_user = us.id AND au.id_actividad = a.id_actividad
        WHERE a.id_actividad = ?
    ");
    $stmt->execute([$id_actividad]);
    $aprendices = $stmt->fetchAll(PDO::FETCH_ASSOC);
}


$numero_ficha = !empty($aprendices) ? $aprendices[0]['id_ficha'] : null;



?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Actividad</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/styles/style_side.css" />
    <link rel="icon" href="<?= BASE_URL ?>/assets/img/icon2.png" />
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
        .main-content {
            margin-left: 260px;
            transition: margin-left 0.4s ease;
        }

        body.sidebar-collapsed .main-content {
            margin-left: 160px;
        }

        .fichas-scroll {
            max-height: calc(100vh - 200px);
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #888 transparent;
        }

        /* Estilo para navegadores WebKit (Chrome, Edge, Safari) */
        .fichas-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .fichas-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .fichas-scroll::-webkit-scrollbar-thumb {
            background-color: #888;
            border-radius: 10px;
            border: 2px solid transparent;
            background-clip: content-box;
        }

        .fichas-scroll::-webkit-scrollbar-thumb:hover {
            background-color: #555;
        }

        .aprendiz-item {
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            transition: background 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
            min-height: 60px;
            margin-bottom: 10px;
        }

        .aprendiz-item:hover {
            background-color: #f0f4ff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .aprendiz-item.selected {
            background-color: #e7f0ff;
            box-shadow: 0 0 0 2px #0d6efd;
        }
    </style>
</head>

<body style="padding-top:180px;" class="sidebar-collapsed">

    <?php
    include 'design/header.php';
    include 'design/sidebar.php';
    ?>

    <div class="main-content">
        <div class="row gx-3" style="margin-right: 0px;">
            <div class="col-12 col-md-6 border-md-end p-3 fichas-scroll" style="max-height: 60vh; overflow-y: auto;">
                <h5 class="mb-3">Aprendices<?= $numero_ficha ? " de la ficha $numero_ficha" : "" ?></h5>
                <div id="contenedor-aprendices">
                    <?php if (empty($aprendices)): ?>
                        <div class="text-center text-muted">No hay aprendices asignados.</div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($aprendices as $aprendiz): ?>
                                <div class="col-12 mb-2">
                                    <div
                                        class="card shadow-sm aprendiz-item d-flex align-items-start"
                                        onclick="seleccionarAprendiz(this); cargarEntrega(<?= $aprendiz['id'] ?>);"
                                        style="cursor: pointer; border-left: 5px solid #0E4A86; transition: transform 0.2s ease;"
                                        onmouseover="this.style.transform = 'scale(1.02)'; this.style.boxShadow = '0 8px 20px rgba(74,144,226,0.3)';"
                                        onmouseout="this.style.transform = 'scale(1)'; this.style.boxShadow = '0 1px 6px rgba(0,0,0,0.1)';">
                                        <div class="d-flex align-items-center p-2">
                                            <img src="<?= BASE_URL ?>/uploads/avatars/<?= htmlspecialchars($aprendiz['avatar'] ?? 'default.png') ?>" alt="avatar"
                                                class="rounded-circle me-3" style="width: 48px; height: 48px; object-fit: cover;">
                                            <div>
                                                <h6 class="mb-0" style="color: #0E4A86;"><?= htmlspecialchars($aprendiz['nombres'] . ' ' . $aprendiz['apellidos']) ?></h6>
                                                <p class="mb-0 text-muted small">
                                                    <?= htmlspecialchars($aprendiz['nombre_formacion'] ?? 'Sin formación') ?> -
                                                    <?= htmlspecialchars($aprendiz['nombre_materia'] ?? 'Sin materia') ?>
                                                </p>

                                            </div>
                                        </div>
                                    </div>
                                </div>

                            <?php endforeach; ?>

                        </div>


                    <?php endif; ?>

                </div>

            </div>

            <div class="col-12 col-md-6 d-flex flex-column p-3" style="max-height: 65vh; " id="contenedor-actividades">
                <div class="text-center text-muted fs-5 mt-5">Aquí se mostrará la actividad del aprendiz.</div>
            </div>
        </div>
    </div>


    <script>
        function cargarEntrega(id_user) {
            const id_actividad = <?= json_encode($id_actividad) ?>;
            fetch(`../ajax/cargar_entrega.php?id_actividad=${id_actividad}&id_user=${id_user}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('contenedor-actividades').innerHTML = html;
                })
                .catch(err => {
                    console.error(err);
                    document.getElementById('contenedor-actividades').innerHTML = "<div class='text-danger'>Error al cargar la entrega.</div>";
                });
        }
    </script>

    <script>
        function seleccionarAprendiz(element) {
            document.querySelectorAll('.aprendiz-item').forEach(el => el.classList.remove('selected'));
            element.classList.add('selected');
        }
    </script>



</body>

</html>
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
$id_aprendiz = $_GET['id_aprendiz'] ?? null;
$id_actividad = $_GET['id_actividad'] ?? null;
$rol = $_SESSION['rol'] ?? '';
$redirecciones = [
    3 => '/instructor/actividades.php',
    5 => '/transversal/actividades.php'
];

$destino = BASE_URL . ($redirecciones[$rol] ?? '/index.php');

$aprendices = [];
if ($id_actividad) {
    $stmt = $conex->prepare("
        SELECT DISTINCT us.id, us.nombres, us.apellidos, us.avatar, us.id,
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
    <link rel="stylesheet" href="<?= BASE_URL ?>/styles/header.css">
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

        .main-content {
            margin-left: 300px;
            transition: margin-left 0.4s ease;
            margin-top: -40px;
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

        .spinner-border {
            width: 3rem;
            height: 3rem;
            animation: spinner-grow 0.8s linear infinite;
        }

        @keyframes spinner-grow {
            0% {
                transform: scale(0.4);
                opacity: 0.3;
            }

            50% {
                transform: scale(1);
                opacity: 1;
            }

            100% {
                transform: scale(0.4);
                opacity: 0.3;
            }
        }

        #buscaraprendiz:focus {
            box-shadow: 0 0 0 0.15rem rgba(14, 74, 134, 0.25);
        }

        .input-group-text {
            background-color: transparent;
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


                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <div class="d-flex align-items-center">
                        <a href="<?= $destino ?>">
                            <button class="but btn me-2" type="button" style="margin-right: 10px; cursor: pointer;">
                                <i class="bi bi-arrow-90deg-left"></i>
                            </button>
                        </a>
                        <h5 class="mb-0">
                            Aprendices<?= $numero_ficha ? " de la ficha $numero_ficha" : "" ?>
                        </h5>
                    </div>

                    <div class="input-group shadow-sm rounded-pill" style="max-width: 250px;">
                        <span class="input-group-text bg-white border-0 ps-3" id="search-icon">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input id="buscaraprendiz" type="search"
                            value="<?= $id_aprendiz ?>"
                            class="form-control border-0 rounded-end-pill ps-2"
                            placeholder="N° Doc. o Nombres"
                            aria-label="Buscar"
                            aria-describedby="search-icon">
                    </div>
                </div>



                <div id="contenedor-aprendices">
                    <?php if (empty($aprendices)): ?>
                        <div class="text-center text-muted">No hay aprendices asignados.</div>
                    <?php else: ?>
                        <div class="list-group">

                            <?php foreach ($aprendices as $aprendiz): ?>
                                <div class="col-12 mb-2">
                                    <div
                                        class="card shadow-sm aprendiz-item d-flex align-items-start"
                                        data-nombre="<?= strtolower($aprendiz['nombres'] . ' ' . $aprendiz['apellidos']) ?>"
                                        data-documento="<?= $aprendiz['id'] ?>"
                                        onclick="seleccionarAprendiz(this); cargarEntrega(<?= $aprendiz['id'] ?>);"
                                        style="cursor: pointer; border-left: 5px solid #0E4A86; transition: transform 0.2s ease;"
                                        onmouseover="this.style.transform = 'scale(1.02)'; this.style.boxShadow = '0 8px 20px rgba(74,144,226,0.3)';"
                                        onmouseout="this.style.transform = 'scale(1)'; this.style.boxShadow = '0 1px 6px rgba(0,0,0,0.1)';">
                                        <div class="d-flex align-items-center p-2">
                                            <img src="<?= BASE_URL ?>/<?= empty($aprendiz['avatar']) ? 'uploads/avatar/user.webp' : htmlspecialchars($aprendiz['avatar']) ?>" alt="avatar"
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
                        <div id="mensajeNoCoincidencias" class="text-center text-muted mt-4" style="display: none;">
                            <i class="bi bi-search fs-2"></i><br>
                            <span class="fs-5">No se encontraron coincidencias.</span>
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
            document.getElementById('contenedor-actividades').innerHTML = `
            <div class="d-flex justify-content-center align-items-center flex-column text-secondary" style="height: 100%;">
                <div class="spinner-border text-primary mb-3" role="status"></div>
                <div class="fs-5">Cargando entrega del aprendiz...</div>
            </div>`;

            fetch(`../ajax/cargar_entrega.php?id_actividad=${id_actividad}&id_user=${id_user}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('contenedor-actividades').innerHTML = html;

                    // 🧠 Aquí reactivamos el evento submit
                    const form = document.getElementById('form-nota');
                    if (form) {
                        form.addEventListener('submit', function(e) {
                            e.preventDefault();

                            const nota = parseFloat(document.getElementById('nota').value);
                            const comentario = document.getElementById('comentario').value;
                            const idActividadUser = form.querySelector('input[name="id_actividad_user"]').value;

                            const formData = new FormData();
                            formData.append('nota', nota);
                            formData.append('comentario_inst', comentario);
                            formData.append('id_actividad_user', idActividadUser);

                            fetch('../ajax/guardar_calificacion.php', {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(r => r.json())
                                .then(data => {
                                    const mensaje = document.getElementById('mensajeCalificacion');
                                    if (data.success) {
                                        mensaje.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';

                                        // 🔁 Actualizar visualmente el estado si existe
                                        const estadoElemento = document.querySelector("small.text-muted strong");
                                        if (estadoElemento && data.nuevo_estado) {
                                            estadoElemento.textContent = data.nuevo_estado;

                                            // (Opcional) Cambiar el color del estado
                                            estadoElemento.classList.remove("text-success", "text-danger", "text-warning");
                                            if (data.nuevo_estado === "Aprobado") {
                                                estadoElemento.classList.add("text-success");
                                            } else if (data.nuevo_estado === "Desaprobado") {
                                                estadoElemento.classList.add("text-danger");
                                            } else {
                                                estadoElemento.classList.add("text-warning");
                                            }
                                        }
                                    } else {
                                        mensaje.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
                                    }
                                })

                                .catch(() => {
                                    document.getElementById('mensajeCalificacion').innerHTML =
                                        '<div class="alert alert-danger">Error de conexión.</div>';
                                });
                        });
                    }

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

    <script>
        function normalize(str) {
            return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();
        }

        document.getElementById('buscaraprendiz').addEventListener('input', function() {
            const query = normalize(this.value);
            const items = document.querySelectorAll('.aprendiz-item');
            let hayCoincidencias = false;

            items.forEach(item => {
                const nombre = normalize(item.getAttribute('data-nombre'));
                const documento = normalize(item.getAttribute('data-documento'));

                if (nombre.includes(query) || documento.includes(query)) {
                    item.parentElement.style.display = '';
                    hayCoincidencias = true;
                } else {
                    item.parentElement.style.display = 'none';
                }
            });

            // Mostrar mensaje si no hay coincidencias
            const mensaje = document.getElementById('mensajeNoCoincidencias');
            mensaje.style.display = hayCoincidencias ? 'none' : 'block';
        });
    </script>

    <script>
        window.addEventListener('DOMContentLoaded', () => {
            const input = document.getElementById('buscaraprendiz');
            if (input.value) {
                input.dispatchEvent(new Event('input')); // Fuerza el filtro al cargar
            }

            // Si solo hay un aprendiz visible tras el filtro, lo seleccionamos automáticamente
            setTimeout(() => {
                const visibles = [...document.querySelectorAll('.aprendiz-item')].filter(item => {
                    return item.parentElement.style.display !== 'none';
                });

                if (visibles.length === 1) {
                    visibles[0].click(); // Simula click en el aprendiz
                }
            }, 100); // Espera breve para asegurar que el filtro ya se aplicó
        });
    </script>







</body>

</html>